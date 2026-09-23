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
}
