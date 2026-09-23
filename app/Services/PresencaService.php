<?php

namespace App\Services;

use App\Models\Agenda;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Regra de quando o personal pode marcar a presença de um aluno.
 *
 * Duas condições, nesta ordem:
 *   1. o aluno precisa ter aula agendada naquele dia (não cancelada, não bloqueio);
 *   2. a aula precisa já ter começado — aula das 13h só é marcável a partir das 13h.
 *
 * Não há limite superior: se o personal esqueceu de marcar no dia, ainda pode
 * marcar depois. A regra é só impedir marcação de aula que ainda não aconteceu.
 *
 * Vive num serviço porque existem DUAS portas para marcar presença — o site
 * (`Cadastro\PersonalController`) e o app (`Api\PersonalGestaoController`). Regra
 * duplicada aqui significa app furando o bloqueio do site.
 */
class PresencaService
{
    /** Aula mais cedo do dia (a presença é uma por dia, não por aula). */
    public function aulaDoDia(int $personalId, int $clienteId, string $data): ?Agenda
    {
        return Agenda::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->whereDate('data', $data)
            ->where('cancelado', false)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->orderByRaw('hora_inicio IS NULL, hora_inicio')
            ->first();
    }

    /**
     * Momento em que a aula começa, no fuso do negócio.
     *
     * `hora_inicio` é hora de parede (coluna TIME, sem fuso), então precisa ser
     * interpretada no fuso do negócio — e não no `app.timezone`, que é UTC.
     * Aula sem horário definido libera desde o início do dia.
     */
    public function inicioDaAula(Agenda $aula): Carbon
    {
        $tz = $this->fuso();
        $dia = $aula->data instanceof Carbon ? $aula->data->format('Y-m-d') : (string) $aula->data;

        return $aula->hora_inicio
            ? Carbon::parse($dia . ' ' . $aula->hora_inicio, $tz)
            : Carbon::parse($dia, $tz)->startOfDay();
    }

    /** "Agora" no mesmo fuso das aulas, para a comparação ser válida. */
    public function agora(): Carbon
    {
        return Carbon::now($this->fuso());
    }

    /**
     * Motivo pelo qual a marcação está bloqueada, ou null se está liberada.
     * É o único ponto que os controllers precisam chamar antes de gravar.
     */
    public function motivoDoBloqueio(int $personalId, int $clienteId, string $data): ?string
    {
        $aula = $this->aulaDoDia($personalId, $clienteId, $data);

        if (! $aula) {
            return 'Esse aluno não tem aula agendada em '
                . Carbon::parse($data)->format('d/m/Y')
                . '. A presença só pode ser marcada em dia de aula.';
        }

        $inicio = $this->inicioDaAula($aula);
        if ($this->agora()->lt($inicio)) {
            return 'A aula de ' . $inicio->format('d/m/Y') . ' começa às ' . $inicio->format('H:i')
                . '. A presença pode ser marcada a partir desse horário.';
        }

        return null;
    }

    /**
     * Dias de aula do mês com o estado de cada um, para a tela montar os botões
     * já sabendo o que está liberado. Mês no formato YYYY-MM.
     *
     * @return Collection<int, array{data:string, hora:?string, liberado:bool}>
     */
    public function diasDoMes(int $personalId, int $clienteId, string $mes): Collection
    {
        $agora = $this->agora();

        return Agenda::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('cancelado', false)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->whereYear('data', substr($mes, 0, 4))
            ->whereMonth('data', substr($mes, 5, 2))
            ->orderBy('data')
            ->orderByRaw('hora_inicio IS NULL, hora_inicio')
            ->get()
            // Uma entrada por dia: fica a aula mais cedo, que é a que libera a marcação.
            ->unique(fn (Agenda $a) => $a->data->format('Y-m-d'))
            ->map(function (Agenda $aula) use ($agora) {
                $inicio = $this->inicioDaAula($aula);

                return [
                    'data'     => $aula->data->format('Y-m-d'),
                    'hora'     => $aula->hora_inicio ? substr($aula->hora_inicio, 0, 5) : null,
                    'liberado' => $agora->gte($inicio),
                ];
            })
            ->sortBy('data')
            ->values();
    }

    private function fuso(): string
    {
        return config('app.timezone_negocio', 'America/Sao_Paulo');
    }
}
