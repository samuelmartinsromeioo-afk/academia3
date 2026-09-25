<?php

namespace App\Models\Concerns;

/**
 * Dá a um cadastro (personal/nutri, academia, studio, loja) o selo de
 * "pioneiro": estar entre os primeiros a se cadastrar no seu estado.
 *
 * A classe que usa este trait precisa ter a coluna `estado` e a coluna
 * `pioneiro_posicao` (unsignedSmallInteger nullable).
 *
 * O limite por estado vem de config('pioneiro.limite_por_estado'); uma classe
 * pode sobrescrevê-lo declarando a constante LIMITE_PIONEIROS_POR_ESTADO.
 */
trait TemSeloPioneiro
{
    /** Quantos cadastros por estado recebem o selo. */
    public static function limitePioneirosPorEstado(): int
    {
        return defined(static::class.'::LIMITE_PIONEIROS_POR_ESTADO')
            ? static::LIMITE_PIONEIROS_POR_ESTADO
            : (int) config('pioneiro.limite_por_estado', 100);
    }

    /** Está entre os primeiros do seu estado. */
    public function getEhPioneiroAttribute(): bool
    {
        return ! is_null($this->pioneiro_posicao);
    }

    /**
     * Define a posição de pioneiro deste cadastro: se ele está entre os
     * primeiros a se cadastrar no seu estado, grava a posição (1..limite);
     * caso contrário mantém NULL. Deve ser chamado logo após criar o registro.
     */
    public function definirPosicaoPioneiro(): void
    {
        if (empty($this->estado)) {
            return;
        }

        // Próxima posição livre do estado = maior posição já atribuída + 1.
        // Usar a "marca d'água" (e não uma contagem de linhas) mantém as
        // posições únicas e monotônicas mesmo que um cadastro anterior seja
        // excluído: a vaga liberada vira um buraco permanente em vez de ser
        // reaproveitada e gerar números repetidos (ex.: dois "#100").
        $posicao = (int) static::where('estado', $this->estado)->max('pioneiro_posicao') + 1;

        if ($posicao <= static::limitePioneirosPorEstado()) {
            $this->pioneiro_posicao = $posicao;
            $this->save();
        }
    }
}
