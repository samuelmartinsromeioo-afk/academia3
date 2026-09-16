<?php

namespace App\Models\Concerns;

use App\Models\Cupom;
use App\Models\CupomUso;

/**
 * Dá a um cadastro (personal, cliente, academia, studio, loja) um código de
 * indicação próprio e acesso às indicações que ele fez/recebeu.
 */
trait TemCupomIndicacao
{
    /** O código pessoal deste usuário (pode não existir ainda — use codigoIndicacao()). */
    public function cupomIndicacao()
    {
        return $this->morphOne(Cupom::class, 'dono');
    }

    /** O uso do cupom de outra pessoa no cadastro deste usuário, se houve. */
    public function indicacaoRecebida()
    {
        return $this->morphOne(CupomUso::class, 'usuario');
    }

    /** Query das indicações feitas por este usuário. */
    public function indicacoesFeitas()
    {
        return CupomUso::query()->whereIn(
            'cupom_id',
            Cupom::query()
                ->where('dono_type', $this->getMorphClass())
                ->where('dono_id', $this->getKey())
                ->select('id')
        );
    }

    /** Código de indicação, criado sob demanda no primeiro acesso. */
    public function codigoIndicacao(): string
    {
        return app(\App\Services\CupomService::class)->cupomDe($this)->codigo;
    }

    /** Soma do bônus das indicações confirmadas. */
    public function bonusIndicacao(): float
    {
        return (float) $this->indicacoesFeitas()->confirmados()->sum('bonus_valor');
    }

    /** Quantas indicações confirmadas este usuário já trouxe. */
    public function totalIndicacoes(): int
    {
        return $this->indicacoesFeitas()->confirmados()->count();
    }
}
