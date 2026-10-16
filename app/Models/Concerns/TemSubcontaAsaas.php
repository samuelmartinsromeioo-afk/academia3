<?php

namespace App\Models\Concerns;

use App\Services\AsaasService;

/**
 * Recebedor com subconta Asaas: personal, academia, studio e loja.
 *
 * Concentra a pergunta que o sistema não sabia responder — **este profissional
 * pode receber pagamento agora?** — e que por isso só era descoberta no erro,
 * quando o Asaas recusava o split na cara do aluno.
 *
 * Tudo lê de colunas locais, sem rede. A sincronização com o Asaas é do
 * `asaas:sincronizar-subcontas` e do webhook; aqui só se consulta o que já foi
 * gravado. Isso é obrigatório: estes métodos rodam na vitrine, em listagem
 * paginada e no checkout, e uma chamada HTTP em qualquer um desses lugares
 * transformaria uma instabilidade do Asaas em página lenta ou quebrada.
 */
trait TemSubcontaAsaas
{
    /**
     * O recebedor pode receber pagamento agora?
     *
     * Três situações, e a terceira é a que exige cuidado:
     *
     *  1. sem `asaas_wallet_id` → NÃO há split. Isso não impede a cobrança:
     *     `montarSplit()` devolve null, o dinheiro cai na conta da plataforma e
     *     o repasse vira manual (`personal_saques`). É um fluxo que existe e
     *     funciona, então não é motivo para esconder o botão de pagar.
     *
     *  2. com wallet e status APPROVED → pode.
     *
     *  3. com wallet e status NULL → **pode**. Null significa "ainda não
     *     sincronizamos", não "reprovada". Tratar null como impedimento
     *     derrubaria o pagamento de todo mundo que já recebe, no instante em
     *     que a coluna nasceu vazia — um deploy que para a plataforma inteira.
     *     Depois da primeira sincronização o valor deixa de ser null.
     */
    public function podeReceberPagamento(): bool
    {
        if (empty($this->asaas_wallet_id)) {
            // Sem split: cai na conta da plataforma e o repasse é manual.
            return true;
        }

        if ($this->asaas_account_status === null) {
            return true; // nunca sincronizado — ver o item 3 acima
        }

        return $this->asaas_account_status === AsaasService::STATUS_APROVADA;
    }

    /** A subconta existe E foi confirmada como aprovada. */
    public function subcontaAprovada(): bool
    {
        return ! empty($this->asaas_wallet_id)
            && $this->asaas_account_status === AsaasService::STATUS_APROVADA;
    }

    /**
     * Por que não pode receber, em linguagem de gente — ou null se pode.
     *
     * Este texto é mostrado ao ALUNO, então não cita walletId, status cru do
     * Asaas nem nada que só faça sentido para quem mantém o sistema. O aluno
     * não tem o que fazer com "general = PENDING"; ele precisa saber que não é
     * culpa dele e que não adianta tentar de novo agora.
     */
    public function motivoNaoRecebe(): ?string
    {
        if ($this->podeReceberPagamento()) {
            return null;
        }

        return 'Este profissional ainda está finalizando o cadastro de recebimentos '
            .'e não pode receber pagamentos no momento.';
    }

    /**
     * O que o PRÓPRIO recebedor precisa resolver — aqui sim com detalhe.
     *
     * Separado de `motivoNaoRecebe()` de propósito: são públicos diferentes. O
     * aluno precisa de uma frase; o profissional precisa saber qual parte do
     * cadastro está pendente e para onde ir.
     *
     * @return array{status:?string, partes:array, pendencias:array, onboarding_url:?string, verificado_em:?string}
     */
    public function detalheSubconta(): array
    {
        $detalhe = [];
        if (! empty($this->asaas_status_detalhe)) {
            $detalhe = json_decode($this->asaas_status_detalhe, true) ?: [];
        }

        return [
            'status' => $this->asaas_account_status,
            'partes' => $detalhe['status'] ?? [],
            'pendencias' => $detalhe['pendencias'] ?? [],
            'onboarding_url' => $this->asaas_onboarding_url,
            'verificado_em' => $this->asaas_status_em,
        ];
    }

    /**
     * Grava a situação devolvida por `AsaasService::situacaoSubconta()`.
     *
     * Consulta que falhou NÃO apaga o que já se sabia: manter o último status
     * conhecido é melhor do que zerar a coluna por causa de um timeout e, com
     * isso, voltar todo mundo para o estado "nunca sincronizado" — que libera
     * o pagamento de quem está bloqueado.
     */
    public function registrarSituacaoSubconta(array $situacao): void
    {
        if (! ($situacao['ok'] ?? false)) {
            return;
        }

        $this->forceFill([
            'asaas_account_status' => $situacao['geral'],
            'asaas_status_detalhe' => json_encode([
                'status' => $situacao['status'] ?? [],
                'pendencias' => $situacao['pendencias'] ?? [],
            ], JSON_UNESCAPED_UNICODE),
            'asaas_onboarding_url' => $situacao['onboarding_url'] ?? $this->asaas_onboarding_url,
            'asaas_status_em' => now(),
        ])->save();
    }

    /** Consulta o Asaas e grava. Devolve a situação para quem quiser exibir. */
    public function sincronizarSubconta(?AsaasService $asaas = null): array
    {
        $situacao = ($asaas ?: app(AsaasService::class))->situacaoSubconta($this);
        $this->registrarSituacaoSubconta($situacao);

        return $situacao;
    }

    /**
     * Recebedores que podem receber — para listagem e vitrine.
     *
     * Espelha `podeReceberPagamento()` em SQL. Os dois precisam concordar: se
     * um filtrar diferente do outro, o profissional aparece na vitrine e toma
     * erro no checkout, que é exatamente o defeito que isto veio corrigir.
     */
    public function scopePodeReceber($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('asaas_wallet_id')
                ->orWhereNull('asaas_account_status')
                ->orWhere('asaas_account_status', AsaasService::STATUS_APROVADA);
        });
    }
}
