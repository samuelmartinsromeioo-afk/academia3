<?php

namespace App\Models\Concerns;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\Payment;
use App\Models\Subscription;

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

    /** Bônus já resgatável (indicados que bateram a meta). */
    public function bonusIndicacao(): float
    {
        return (float) $this->indicacoesFeitas()->liberados()->sum('bonus_valor');
    }

    /** Bônus reservado, esperando o indicado bater a meta. */
    public function bonusPendente(): float
    {
        return (float) $this->indicacoesFeitas()->pendentes()->sum('bonus_valor');
    }

    /** Quantas indicações já liberaram bônus. */
    public function totalIndicacoes(): int
    {
        return $this->indicacoesFeitas()->liberados()->count();
    }

    /** Quantas indicações estão esperando a meta. */
    public function indicacoesPendentes(): int
    {
        return $this->indicacoesFeitas()->pendentes()->count();
    }

    /**
     * Alunos que ESTE perfil conquistou pela plataforma — a métrica que libera
     * o bônus de quem o indicou.
     *
     * Conta cliente distinto com pagamento confirmado na SnrFit para este
     * recebedor. Deliberadamente NÃO conta vínculo criado à mão
     * (`clientes.academia_id`, paciente de nutri, agenda avulsa sem cobrança):
     * esses são gratuitos de criar e transformariam a meta em formalidade —
     * bastaria vincular 6 contas de amigos para destravar R$ 30 de indicação.
     *
     * Aluno (Cliente) não conquista aluno: retorna 0.
     */
    public function alunosPelaPlataforma(): int
    {
        $coluna = match (class_basename($this)) {
            'Personal' => 'trainer_id',
            'Academia' => 'academia_id',
            'Studio'   => 'studio_id',
            'Loja'     => 'loja_id',
            default    => null,
        };

        if ($coluna === null) {
            return 0;
        }

        $clientes = Payment::query()
            ->where($coluna, $this->getKey())
            ->whereIn('status', Cupom::STATUS_PAGAMENTO_VALIDO)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        // Assinaturas ativas cobrem o plano recorrente cujo primeiro pagamento
        // pode ter ficado com outra grafia de status.
        if (in_array($coluna, ['trainer_id', 'academia_id', 'studio_id'], true)) {
            $clientes = $clientes->merge(
                Subscription::query()
                    ->where($coluna, $this->getKey())
                    ->whereIn('status', Cupom::STATUS_PAGAMENTO_VALIDO)
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->pluck('user_id')
            );
        }

        // Nutricionista conquista cliente pela consulta paga, que corre por
        // nutri_cobrancas e não por payments.
        if (method_exists($this, 'isNutricionista') && $this->isNutricionista()) {
            $clientes = $clientes->merge(
                \App\Models\Nutri\Cobranca::query()
                    ->where('personal_id', $this->getKey())
                    ->where('status', 'pago')
                    ->whereNotNull('cliente_id')
                    ->distinct()
                    ->pluck('cliente_id')
            );
        }

        return $clientes->unique()->count();
    }

    /** Este perfil já bateu a meta que libera o bônus de quem o indicou? */
    public function bateuMetaIndicacao(): bool
    {
        return $this->alunosPelaPlataforma() >= (int) config('indicacao.meta_alunos', 6);
    }
}
