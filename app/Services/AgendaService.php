<?php

namespace App\Services;

use App\Models\Agenda;
use Carbon\Carbon;

/**
 * Cálculos de horário da agenda, no fuso certo.
 *
 * `agendas.hora_inicio` é uma coluna TIME: hora de parede, sem fuso. O personal
 * digita "13:00" olhando o relógio dele, no Brasil. Mas `app.timezone` é UTC,
 * então `Carbon::now()` vem 3h adiantado em relação a essa hora. Comparar os
 * dois direto — que era o que o código fazia — desloca toda regra de horário.
 *
 * Aqui os dois lados da conta passam por `config('app.timezone_negocio')`.
 */
class AgendaService
{
    /** Antecedência mínima para o personal cancelar uma aula. */
    public const HORAS_ANTECEDENCIA_CANCELAMENTO = 24;

    /**
     * Janela de trabalho considerada para ofertar horário, e o passo da grade.
     *
     * Os mesmos 06:00–22:00 que `PersonalController::storeHorario` já exigia ao
     * bloquear horário na mão — aqui viram a fonte única, em vez de ficarem
     * repetidos em cada lugar que monta lista de horário.
     */
    public const TURNO_INICIO = '06:00';
    public const TURNO_FIM = '22:00';
    public const PASSO_MINUTOS = 60;

    /**
     * Horários livres do personal num dia, já descontando a agenda dele.
     *
     * Substitui duas cópias idênticas desta conta — `ClienteController::
     * buscarHorariosDisponiveis()` e `Api\ExplorarController` (cujo comentário
     * dizia "igual ao web", assumindo a duplicação). Duas melhorias em relação
     * a elas:
     *
     *  1. UMA query, não uma por slot. As cópias faziam um `exists()` por
     *     horário — 16 queries para montar um único dia.
     *  2. Horário que já passou não é ofertado. Elas listavam 06:00 de hoje às
     *     20h, e a validação do aceite só checa a DATA
     *     (`after_or_equal:today`), então o horário vencido passava.
     *
     * `$duracaoMin` é a duração real da aula: o início continua alinhado à hora
     * cheia (é o que o personal espera ver), mas o conflito é checado na janela
     * inteira, para uma aula de 90min não invadir o compromisso seguinte.
     *
     * @param  \Illuminate\Support\Collection|null  $agendasDoDia  Aulas já carregadas, para evitar query por dia em laço.
     * @return array<int, array{inicio: string, fim: string, label: string}>
     */
    public function horariosLivres(int $personalId, string $dia, int $duracaoMin = 60, $agendasDoDia = null): array
    {
        $tz = $this->fuso();
        $duracaoMin = max(15, $duracaoMin);

        $ocupadas = $agendasDoDia ?? Agenda::where('personal_id', $personalId)
            ->whereDate('data', $dia)
            ->where('cancelado', false)
            ->get(['hora_inicio', 'hora_fim']);

        $agora = $this->agora();
        $cursor = Carbon::parse($dia.' '.self::TURNO_INICIO, $tz);
        $limite = Carbon::parse($dia.' '.self::TURNO_FIM, $tz);
        $livres = [];

        while ($cursor < $limite) {
            $fim = $cursor->copy()->addMinutes($duracaoMin);

            // A aula tem de caber dentro do turno: um slot que termina depois
            // das 22:00 não é horário de trabalho.
            if ($fim > $limite) {
                break;
            }

            $ini = $cursor->format('H:i');
            $fimStr = $fim->format('H:i');

            if ($cursor->gt($agora) && ! $this->temConflito($ocupadas, $ini, $fimStr)) {
                $livres[] = ['inicio' => $ini, 'fim' => $fimStr, 'label' => $ini.' - '.$fimStr];
            }

            $cursor->addMinutes(self::PASSO_MINUTOS);
        }

        return $livres;
    }

    /**
     * Dias com pelo menos um horário livre, a partir de hoje.
     *
     * Existe para o personal não precisar abrir a agenda e caçar dia por dia na
     * hora de marcar uma reposição. Uma query só para toda a janela.
     *
     * @return array<string, array{rotulo: string, horarios: array}>  Indexado por Y-m-d.
     */
    public function diasComHorarioLivre(int $personalId, int $diasAFrente = 21, int $duracaoMin = 60): array
    {
        $tz = $this->fuso();
        $hoje = $this->agora()->startOfDay();
        $fim = $hoje->copy()->addDays($diasAFrente);

        $porDia = Agenda::where('personal_id', $personalId)
            ->where('cancelado', false)
            ->whereBetween('data', [$hoje->format('Y-m-d'), $fim->format('Y-m-d')])
            ->get(['data', 'hora_inicio', 'hora_fim'])
            ->groupBy(fn ($a) => $a->data instanceof Carbon ? $a->data->format('Y-m-d') : (string) $a->data);

        $dias = [];

        for ($d = $hoje->copy(); $d <= $fim; $d->addDay()) {
            $chave = $d->format('Y-m-d');
            $horarios = $this->horariosLivres($personalId, $chave, $duracaoMin, $porDia->get($chave) ?? collect());

            if ($horarios === []) {
                continue;
            }

            $dias[$chave] = [
                'rotulo' => $this->rotuloDoDia($d),
                'horarios' => $horarios,
            ];
        }

        return $dias;
    }

    /**
     * O personal pode marcar aula neste intervalo?
     *
     * Três invariantes, na mesma fonte que monta a lista ofertada na tela:
     * começa no futuro, cabe no turno de trabalho e não colide com a agenda.
     *
     * Existe porque `aceitar`/`remarcar` validavam só a DATA
     * (`after_or_equal:today`) e conferiam conflito: um POST montado à mão
     * marcava aula às 03:00, ou num horário de hoje que já passou. Com a tela
     * ofertando apenas horário livre, deixar o servidor mais frouxo que a
     * interface é convite a inconsistência.
     *
     * Não exige alinhamento com a grade de 60min: aula legítima fora da grade
     * (07:30, por exemplo) continua válida — o que importa é não colidir.
     *
     * @return string|null  Motivo da recusa, ou null quando está livre.
     */
    public function motivoParaNaoMarcar(int $personalId, string $dia, string $inicio, string $fim): ?string
    {
        $tz = $this->fuso();
        $comeca = Carbon::parse($dia.' '.$inicio, $tz);
        $termina = Carbon::parse($dia.' '.$fim, $tz);

        if ($termina <= $comeca) {
            return 'O fim da aula tem de ser depois do início.';
        }

        if ($comeca <= $this->agora()) {
            return 'Esse horário já passou. Escolha um horário futuro.';
        }

        $turnoIni = Carbon::parse($dia.' '.self::TURNO_INICIO, $tz);
        $turnoFim = Carbon::parse($dia.' '.self::TURNO_FIM, $tz);

        if ($comeca < $turnoIni || $termina > $turnoFim) {
            return 'Fora do horário de atendimento ('.self::TURNO_INICIO.' às '.self::TURNO_FIM.').';
        }

        $ocupadas = Agenda::where('personal_id', $personalId)
            ->whereDate('data', $dia)
            ->where('cancelado', false)
            ->get(['hora_inicio', 'hora_fim']);

        if ($this->temConflito($ocupadas, $inicio, $fim)) {
            return 'Você já tem compromisso nesse horário. Escolha outro.';
        }

        return null;
    }

    /** "Hoje · 06/10" / "Seg · 07/10" — para o personal reconhecer o dia de relance. */
    private function rotuloDoDia(Carbon $dia): string
    {
        $hoje = $this->agora()->startOfDay();
        $nomes = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        $prefixo = match ($dia->diffInDays($hoje)) {
            0 => 'Hoje',
            1 => 'Amanhã',
            default => $nomes[$dia->dayOfWeek],
        };

        return $prefixo.' · '.$dia->format('d/m');
    }

    /** O intervalo colide com alguma aula já marcada? */
    private function temConflito($ocupadas, string $inicio, string $fim): bool
    {
        foreach ($ocupadas as $a) {
            $ini = substr((string) $a->hora_inicio, 0, 5);
            $f = substr((string) $a->hora_fim, 0, 5);

            // Mesma condição do SQL usado nos aceites: sobreposição estrita, de
            // modo que uma aula terminando 08:00 não bloqueie outra às 08:00.
            if ($ini < $fim && $f > $inicio) {
                return true;
            }
        }

        return false;
    }

    public function fuso(): string
    {
        return config('app.timezone_negocio', 'America/Sao_Paulo');
    }

    /** "Agora" no mesmo fuso das aulas, para a comparação ser válida. */
    public function agora(): Carbon
    {
        return Carbon::now($this->fuso());
    }

    /** Momento em que a aula começa. Sem hora definida, vale o início do dia. */
    public function inicioDaAula(Agenda $aula): Carbon
    {
        $tz = $this->fuso();
        $dia = $aula->data instanceof Carbon ? $aula->data->format('Y-m-d') : (string) $aula->data;

        return $aula->hora_inicio
            ? Carbon::parse($dia . ' ' . $aula->hora_inicio, $tz)
            : Carbon::parse($dia, $tz)->startOfDay();
    }

    /**
     * Horas entre agora e o início da aula, COM sinal: negativo = a aula já
     * começou. O `diffInHours()` do Carbon é absoluto por padrão, então sem o
     * `false` uma aula de ontem devolve um número alto e positivo — era assim
     * que a regra das 24h deixava cancelar aula que já tinha acontecido.
     */
    public function horasAteAula(Agenda $aula): int
    {
        return (int) $this->agora()->diffInHours($this->inicioDaAula($aula), false);
    }

    /** True quando o personal ainda está dentro da janela para cancelar. */
    public function podeCancelar(Agenda $aula): bool
    {
        return $this->horasAteAula($aula) >= self::HORAS_ANTECEDENCIA_CANCELAMENTO;
    }

    /** Motivo do bloqueio, ou null se o cancelamento está liberado. */
    public function motivoParaNaoCancelar(Agenda $aula): ?string
    {
        if ($this->podeCancelar($aula)) {
            return null;
        }

        $inicio = $this->inicioDaAula($aula);

        if ($inicio->lte($this->agora())) {
            return 'Essa aula já começou em ' . $inicio->format('d/m/Y \à\s H:i') . ' — não dá mais para cancelar.';
        }

        $faltam = $this->horasAteAula($aula);

        return 'O cancelamento só é permitido com ' . self::HORAS_ANTECEDENCIA_CANCELAMENTO
            . 'h de antecedência. Faltam ' . $faltam . 'h para essa aula.';
    }

    // ─────────────────────────────────────────────────────────────
    // LADO DO ALUNO
    //
    // Mesma janela de 24h do personal, mas o que acontece ao fim dela é
    // diferente por tipo de aula:
    //   • avulsa  — dentro do prazo cancela e o dinheiro volta; fora do prazo
    //               não cancela e não há devolução.
    //   • pacote  — não há dinheiro a devolver (o pacote foi pago inteiro);
    //               dentro do prazo o aluno pode pedir para repor a aula em
    //               outro horário, fora do prazo a aula é perdida.
    // ─────────────────────────────────────────────────────────────

    public function ehPacote(Agenda $aula): bool
    {
        return $aula->tipo_aula === 'pacote';
    }

    /**
     * O aluno ainda está no prazo de agir sobre essa aula? Vale tanto para
     * cancelar avulsa quanto para pedir reposição de pacote.
     */
    public function alunoEstaNoPrazo(Agenda $aula): bool
    {
        return $this->podeCancelar($aula);
    }

    /** Motivo do bloqueio para o aluno, já com a consequência explícita. */
    public function motivoParaAlunoNaoAgir(Agenda $aula): ?string
    {
        if ($this->alunoEstaNoPrazo($aula)) {
            return null;
        }

        $inicio = $this->inicioDaAula($aula);
        $passou = $inicio->lte($this->agora());
        $horas = self::HORAS_ANTECEDENCIA_CANCELAMENTO;

        if ($this->ehPacote($aula)) {
            return $passou
                ? 'Essa aula já começou em ' . $inicio->format('d/m/Y \à\s H:i') . ' — não dá mais para pedir reposição.'
                : 'A reposição precisa ser pedida com ' . $horas . 'h de antecedência. Faltam apenas '
                    . $this->horasAteAula($aula) . 'h para essa aula.';
        }

        return $passou
            ? 'Essa aula já começou em ' . $inicio->format('d/m/Y \à\s H:i') . ' — não é mais possível cancelar nem pedir devolução.'
            : 'O cancelamento com devolução só vale até ' . $horas . 'h antes da aula. Faltam apenas '
                . $this->horasAteAula($aula) . 'h, então essa aula não pode mais ser cancelada.';
    }
}
