<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AulaReposicao;
use App\Services\AgendaService;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lado do PERSONAL nos pedidos de reposição.
 *
 * Quem decide o horário é ele — é a agenda dele, e "horário vago" não é a mesma
 * coisa que "horário que ele quer trabalhar". Por isso o pedido do aluno é uma
 * sugestão, não um agendamento.
 */
class ReposicaoController extends Controller
{
    private function personalLogado(): ?int
    {
        return session('personal_id') ?: null;
    }

    /**
     * Duração da aula perdida, para a reposição ter o mesmo tamanho.
     *
     * Aula antiga pode estar sem hora de fim; nesse caso vale 60min, que é o
     * passo da grade — melhor que gerar slot de duração zero.
     */
    public function duracaoEmMinutos(Agenda $aula): int
    {
        if (! $aula->hora_inicio || ! $aula->hora_fim) {
            return 60;
        }

        $ini = \Carbon\Carbon::parse($aula->hora_inicio);
        $fim = \Carbon\Carbon::parse($aula->hora_fim);
        $min = $ini->diffInMinutes($fim);

        return $min > 0 ? $min : 60;
    }

    /**
     * Tudo que os alunos desmarcaram, com o que dá para fazer em cada caso.
     *
     * Por que `cancelado = true` basta para dizer "foi o aluno": todo
     * cancelamento feito pelo PERSONAL apaga a linha da agenda
     * (`PersonalController::cancelarAula` e `cancelarDia`, e os equivalentes na
     * API). Só o `AulaAlunoController` marca a aula como cancelada e a mantém.
     * Se algum dia o lado do personal virar soft-delete, este filtro precisa de
     * um `cancelado_por`.
     */
    public function index(AgendaService $agendas)
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $personal = \App\Models\Cadastro\Personal::findOrFail($personalId);

        $faltas = Agenda::with('cliente:id,nome')
            ->where('personal_id', $personalId)
            ->where('cancelado', true)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->orderByDesc('data')
            ->orderByDesc('hora_inicio')
            ->limit(80)
            ->get();

        // Pedido de reposição e estorno indexados por aula, para a tela não
        // consultar o banco dentro do laço.
        $pedidos = AulaReposicao::with('agendaReposta')
            ->whereIn('agenda_id', $faltas->pluck('id'))
            ->get()
            ->keyBy('agenda_id');

        $estornos = \App\Models\Estorno::whereIn('agenda_id', $faltas->pluck('id'))
            ->get()
            ->keyBy('agenda_id');

        // Horários que o personal realmente tem livres, por dia, para ele marcar
        // a reposição sem sair daqui para conferir a agenda.
        //
        // A duração sai da aula perdida — repor uma aula de 1h em 1h. Fichas com
        // durações diferentes geram grades diferentes, então a lista é montada
        // por duração e reaproveitada entre as faltas de mesma duração, em vez de
        // uma varredura de 22 dias por pedido.
        $disponibilidade = [];
        foreach ($faltas as $falta) {
            $dur = $this->duracaoEmMinutos($falta);
            if (! isset($disponibilidade[$dur])) {
                $disponibilidade[$dur] = $agendas->diasComHorarioLivre($personalId, 21, $dur);
            }
        }

        return view('personal.reposicoes', [
            'personal' => $personal,
            'faltas' => $faltas,
            'pedidos' => $pedidos,
            'estornos' => $estornos,
            'pendentes' => $pedidos->where('status', AulaReposicao::STATUS_PENDENTE)->count(),
            'disponibilidade' => $disponibilidade,
            'duracaoDaFalta' => $faltas->mapWithKeys(fn ($f) => [$f->id => $this->duracaoEmMinutos($f)])->all(),
        ]);
    }

    /**
     * Aceita e cria a aula reposta.
     *
     * O horário final é o que o PERSONAL confirmar: ele pode acatar a sugestão
     * do aluno ou mandar outra. Conflito na agenda barra — senão a reposição
     * criaria duas aulas no mesmo horário.
     */
    public function aceitar(Request $request, $id, AgendaService $agendas)
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $pedido = AulaReposicao::where('id', $id)->where('personal_id', $personalId)->firstOrFail();

        if (! $pedido->estaPendente()) {
            return redirect()->back()->with('error', 'Esse pedido já foi respondido.');
        }

        $dados = $request->validate([
            'data' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'resposta' => 'nullable|string|max:500',
        ]);

        $r = $this->aceitarInterno($pedido, $dados, $agendas);

        if (! $r['ok']) {
            return redirect()->back()->with('error', $r['erro']);
        }

        return redirect()->back()->with('success', $r['mensagem']);
    }

    /**
     * O aceite em si — PORTA COMUM do web e do app.
     *
     * Separada porque as duas superfícies só diferem na resposta; a criação da
     * aula reposta, a checagem de conflito e o aviso ao aluno têm de ser os
     * mesmos. Mesmo padrão de `AulaAlunoController::cancelarInterno`.
     *
     * Quem chama já garantiu que o pedido é deste personal e está pendente.
     *
     * @param  array{data: string, hora_inicio: string, hora_fim: string, resposta?: ?string}  $dados
     * @return array{ok: bool, erro: ?string, mensagem: ?string, agenda_id: ?int}
     */
    public function aceitarInterno(AulaReposicao $pedido, array $dados, AgendaService $agendas): array
    {
        $personalId = $pedido->personal_id;

        if (! $pedido->estaPendente()) {
            return ['ok' => false, 'erro' => 'Esse pedido já foi respondido.', 'mensagem' => null, 'agenda_id' => null];
        }

        if ($motivo = $agendas->motivoParaNaoMarcar($personalId, $dados['data'], $dados['hora_inicio'], $dados['hora_fim'])) {
            return ['ok' => false, 'erro' => $motivo, 'mensagem' => null, 'agenda_id' => null];
        }

        $original = $pedido->agenda;

        DB::transaction(function () use ($pedido, $dados, $original, $personalId) {
            $nova = Agenda::create([
                'cliente_id' => $pedido->cliente_id,
                'personal_id' => $personalId,
                'academia_id' => $original->academia_id ?? null,
                'academia_nome' => $original->academia_nome ?? null,
                'data' => $dados['data'],
                'hora_inicio' => $dados['hora_inicio'],
                'hora_fim' => $dados['hora_fim'],
                'cancelado' => false,
                'tipo_aula' => 'pacote',
                'frequencia_pacote' => $original->frequencia_pacote ?? null,
                'valor_aula' => $original->valor_aula ?? null,
                'descricao' => 'Reposição de aula',
            ]);

            $pedido->update([
                'status' => AulaReposicao::STATUS_ACEITA,
                'resposta' => $dados['resposta'] ?? null,
                'respondido_em' => now(),
                'agenda_reposta_id' => $nova->id,
            ]);
        });

        $this->avisarAluno(
            $pedido,
            'Reposição confirmada',
            'Sua aula foi reposta para ' . \Carbon\Carbon::parse($dados['data'])->format('d/m/Y')
                . ' às ' . $dados['hora_inicio'] . '.'
                . ($dados['resposta'] ?? null ? ' ' . $dados['resposta'] : '')
        );

        return [
            'ok' => true,
            'erro' => null,
            'mensagem' => 'Reposição confirmada e lançada na sua agenda.',
            'agenda_id' => $pedido->fresh()->agenda_reposta_id,
        ];
    }

    /**
     * Remarca uma aula AVULSA que o aluno cancelou.
     *
     * Diferente do pacote, aqui existe dinheiro no meio: o cancelamento abriu um
     * pedido de devolução. Se ele ainda está pendente, remarcar o encerra como
     * `remarcado` — o aluno recebe a aula em vez do valor, e não as duas coisas.
     * Se o admin já devolveu, a aula sai de graça; a tela avisa o personal disso
     * antes, e a decisão é dele.
     */
    public function remarcarAvulsa(Request $request, $agendaId, AgendaService $agendas)
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $original = Agenda::where('id', $agendaId)
            ->where('personal_id', $personalId)
            ->where('cancelado', true)
            ->firstOrFail();

        $dados = $request->validate([
            'data' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'resposta' => 'nullable|string|max:500',
        ]);

        $r = $this->remarcarAvulsaInterno($original, $dados, $agendas);

        if (! $r['ok']) {
            return redirect()->back()->with('error', $r['erro']);
        }

        return redirect()->back()->with('success', $r['mensagem']);
    }

    /**
     * A remarcação da avulsa em si — PORTA COMUM do web e do app.
     *
     * Aqui existe DINHEIRO no meio, e é o que diferencia do pacote: o
     * cancelamento abriu um pedido de devolução, e remarcar o encerra como
     * `remarcado` — o aluno recebe a aula em vez do valor, nunca as duas
     * coisas. Se o admin já devolveu, a aula sai de graça e a mensagem avisa.
     *
     * @param  array{data: string, hora_inicio: string, hora_fim: string, resposta?: ?string}  $dados
     * @return array{ok: bool, erro: ?string, mensagem: ?string, ja_devolvido: bool}
     */
    public function remarcarAvulsaInterno(Agenda $original, array $dados, AgendaService $agendas): array
    {
        $personalId = $original->personal_id;
        $falha = fn (string $erro) => ['ok' => false, 'erro' => $erro, 'mensagem' => null, 'ja_devolvido' => false];

        if ($original->tipo_aula === 'pacote') {
            return $falha('Aula de pacote se remarca pelo pedido de reposição.');
        }

        if (AulaReposicao::where('agenda_id', $original->id)->exists()) {
            return $falha('Essa aula já foi remarcada.');
        }

        if ($motivo = $agendas->motivoParaNaoMarcar($personalId, $dados['data'], $dados['hora_inicio'], $dados['hora_fim'])) {
            return $falha($motivo);
        }

        $estorno = \App\Models\Estorno::where('agenda_id', $original->id)->first();

        DB::transaction(function () use ($original, $dados, $personalId, $estorno) {
            $nova = Agenda::create([
                'cliente_id' => $original->cliente_id,
                'personal_id' => $personalId,
                // Segue apontando para o mesmo pagamento: a aula é a que ele já pagou.
                'payment_id' => $original->payment_id,
                'academia_id' => $original->academia_id ?? null,
                'academia_nome' => $original->academia_nome ?? null,
                'data' => $dados['data'],
                'hora_inicio' => $dados['hora_inicio'],
                'hora_fim' => $dados['hora_fim'],
                'cancelado' => false,
                'tipo_aula' => 'avulsa',
                'valor_aula' => $original->valor_aula ?? null,
                'descricao' => 'Reposição de aula',
            ]);

            // Registro no mesmo formato do pacote, para o histórico da tela ser
            // um só independente do tipo da aula.
            AulaReposicao::create([
                'agenda_id' => $original->id,
                'cliente_id' => $original->cliente_id,
                'personal_id' => $personalId,
                'agenda_reposta_id' => $nova->id,
                'resposta' => $dados['resposta'] ?? null,
                'status' => AulaReposicao::STATUS_ACEITA,
                'respondido_em' => now(),
            ]);

            // Aula remarcada não é devolvida: o aluno não fica com as duas coisas.
            if ($estorno && $estorno->status === \App\Models\Estorno::STATUS_PENDENTE) {
                $estorno->update([
                    'status' => \App\Models\Estorno::STATUS_REMARCADO,
                    'observacao_admin' => 'Aula remarcada pelo personal — não há valor a devolver.',
                    'resolvido_em' => now(),
                ]);
            }
        });

        $jaDevolvido = $estorno && $estorno->status === \App\Models\Estorno::STATUS_DEVOLVIDO;

        $this->avisarClienteDireto(
            $original->cliente,
            'Sua aula foi remarcada',
            'Sua aula cancelada foi remarcada para ' . \Carbon\Carbon::parse($dados['data'])->format('d/m/Y')
                . ' às ' . $dados['hora_inicio'] . '.'
                . ($jaDevolvido ? '' : ' Como a aula será dada, o valor pago não será devolvido.')
                . ($dados['resposta'] ?? null ? ' ' . $dados['resposta'] : '')
        );

        return [
            'ok' => true,
            'erro' => null,
            'mensagem' => $jaDevolvido
                ? 'Aula remarcada. Atenção: o valor já havia sido devolvido ao aluno, então essa aula não será paga.'
                : 'Aula remarcada e devolução cancelada — o aluno recebe a aula no lugar do valor.',
            'ja_devolvido' => (bool) $jaDevolvido,
        ];
    }

    /** Recusa — normalmente com uma contraproposta de horário no texto. */
    public function recusar(Request $request, $id)
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $pedido = AulaReposicao::where('id', $id)->where('personal_id', $personalId)->firstOrFail();

        $dados = $request->validate([
            'resposta' => 'required|string|min:5|max:500',
        ]);

        $r = $this->recusarInterno($pedido, $dados['resposta']);

        if (! $r['ok']) {
            return redirect()->back()->with('error', $r['erro']);
        }

        return redirect()->back()->with('success', $r['mensagem']);
    }

    /**
     * A recusa em si — PORTA COMUM do web e do app.
     *
     * A resposta é obrigatória (e com tamanho mínimo) de propósito: recusar sem
     * dizer nada deixa o aluno sem saber o que fazer, e na prática a recusa é
     * uma contraproposta de horário.
     *
     * @return array{ok: bool, erro: ?string, mensagem: ?string}
     */
    public function recusarInterno(AulaReposicao $pedido, string $resposta): array
    {
        if (! $pedido->estaPendente()) {
            return ['ok' => false, 'erro' => 'Esse pedido já foi respondido.', 'mensagem' => null];
        }

        $pedido->update([
            'status' => AulaReposicao::STATUS_RECUSADA,
            'resposta' => $resposta,
            'respondido_em' => now(),
        ]);

        $this->avisarAluno($pedido, 'Sobre sua reposição', $resposta);

        return ['ok' => true, 'erro' => null, 'mensagem' => 'Resposta enviada ao aluno.'];
    }

    private function avisarAluno(AulaReposicao $pedido, string $assunto, string $texto): void
    {
        $this->avisarClienteDireto($pedido->cliente, $assunto, $texto);
    }

    private function avisarClienteDireto($cliente, string $assunto, string $texto): void
    {
        try {
            if ($cliente) {
                NotificacaoService::cliente($cliente, $assunto, $texto);
            }
        } catch (\Throwable $e) {
            // Aviso é melhor esforço: não desfaz a resposta já gravada.
        }
    }
}
