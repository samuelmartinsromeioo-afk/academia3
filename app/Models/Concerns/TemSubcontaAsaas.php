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
     *  1. sem `asaas_wallet_id` → **NÃO pode**. Sem wallet não há destino para
     *     o repasse (ver abaixo; isto mudou em 20/10/2026).
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
        /*
         * SEM walletId NÃO PODE RECEBER. Isto respondia `true` até 20/10/2026,
         * com a justificativa de que "sem split cai na plataforma e o repasse é
         * manual — fluxo que existe e funciona".
         *
         * O fluxo deixou de existir: `AsaasService::montarSplit()` agora aborta
         * em vez de cobrar sem split, porque dinheiro de terceiro entrando na
         * conta da plataforma é problema fiscal e faz o saldo do profissional
         * mentir (o comentário lá explica os três motivos).
         *
         * Manter `true` aqui seria pior que inconsistência: a vitrine diria
         * "pode receber", o aluno escolheria o profissional, montaria o pacote e
         * tomaria 422 no checkout — exatamente o defeito que esta trava nasceu
         * para corrigir. Os dois lados têm de contar a mesma história, e
         * `scopePodeReceber()` espelha isto em SQL.
         */
        if (empty($this->asaas_wallet_id)) {
            return false;
        }

        /*
         * Wallet bloqueada pelo Asaas vem ANTES do status cadastral, porque é
         * o único impedimento que o status não consegue representar: o
         * personal #7 respondia APPROVED nos quatro campos de
         * `/myAccount/status` e mesmo assim tinha o split recusado.
         *
         * Enquanto a trava vale, a cobrança nem sai — ela falharia no Asaas de
         * qualquer jeito, e falhar aqui ao menos permite dizer ao aluno o que
         * houve em vez de despejar o texto cru do gateway na tela dele.
         */
        if ($this->splitBloqueadoPeloAsaas()) {
            return false;
        }

        if ($this->asaas_account_status === null) {
            return true; // nunca sincronizado — ver o item 3 acima
        }

        /*
         * Chave da subconta inválida NÃO impede receber, e isso é o oposto do
         * que a intuição diz.
         *
         * A cobrança é criada com a chave da PLATAFORMA e o split só referencia
         * o `walletId` — nada nesse caminho usa a chave do profissional. Quem
         * usa são saldo e saque. Então uma chave quebrada deixa ele sem ver o
         * dinheiro e sem sacar, mas vendendo normalmente; bloquear a venda aqui
         * puniria duas vezes quem já está prejudicado, e por um motivo que não
         * tem relação com a cobrança.
         *
         * O estado não fica invisível: `subcontaAprovada()` segue falso e o
         * painel dele mostra o aviso próprio.
         */
        if ($this->asaas_account_status === AsaasService::STATUS_CHAVE_INVALIDA) {
            return true;
        }

        return $this->asaas_account_status === AsaasService::STATUS_APROVADA;
    }

    /** A chave guardada não serve: saldo e saque estão quebrados para ele. */
    public function chaveSubcontaInvalida(): bool
    {
        return $this->asaas_account_status === AsaasService::STATUS_CHAVE_INVALIDA;
    }

    /**
     * O Asaas recusou o split desta wallet há pouco tempo.
     *
     * "Há pouco tempo" é a parte importante. O Asaas não avisa quando
     * desbloqueia uma wallet, e `/myAccount/status` responde APPROVED antes e
     * depois — não há de onde saber que acabou. Então a trava expira sozinha e
     * a próxima cobrança testa: se foi liberada, o profissional volta a vender
     * sem ninguém fazer nada; se não, o carimbo se renova e a trava continua.
     *
     * Um booleano aqui prenderia o profissional para sempre num bloqueio que
     * já tinha acabado, esperando por um aviso que nunca chega.
     */
    public function splitBloqueadoPeloAsaas(): bool
    {
        if (empty($this->asaas_split_bloqueado_em)) {
            return false;
        }

        return \Carbon\Carbon::parse($this->asaas_split_bloqueado_em)
            ->addHours(AsaasService::HORAS_RETENTAR_SPLIT)
            ->isFuture();
    }

    /**
     * Registra que o Asaas recusou o split desta wallet.
     *
     * NÃO toca em `asaas_account_status`, e essa é a correção central: escrever
     * 'BLOCKED' ali colocava este método em guerra com
     * `asaas:sincronizar-subcontas`, que lê `/myAccount/status` e grava
     * APPROVED duas vezes ao dia. O estado oscilava em círculo e, no meio do
     * ciclo, o aluno lia que o profissional estava "finalizando o cadastro" —
     * com o cadastro dele aprovado nos quatro campos.
     */
    public function registrarBloqueioDeSplit(?string $motivo = null): void
    {
        $this->forceFill([
            'asaas_split_bloqueado_em' => now(),
            // Texto do Asaas, truncado. Fica só para o painel do admin e para
            // o chamado de suporte — nunca é mostrado a quem está comprando.
            'asaas_split_bloqueio_motivo' => $motivo ? mb_substr($motivo, 0, 500) : null,
        ])->save();
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

        /*
         * Wallet bloqueada tem texto PRÓPRIO porque o genérico seria falso.
         * Dizer "ainda está finalizando o cadastro" de alguém com cadastro
         * aprovado nos quatro campos não é só impreciso: manda o profissional
         * procurar documentação que já passou, e o aluno culpar quem não tem
         * culpa. O bloqueio é decisão do banco, não pendência de ninguém.
         */
        if ($this->splitBloqueadoPeloAsaas()) {
            return 'A conta de recebimentos deste profissional está temporariamente '
                .'bloqueada pelo banco, e por isso ele não consegue receber agora. '
                .'Não é nada com o seu pagamento — tente mais tarde ou escolha outro profissional.';
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
        /*
         * Falha de AUTENTICAÇÃO é exceção à regra de não gravar em erro.
         *
         * A regra geral existe para não apagar o que já se sabia por causa de
         * um timeout. Mas 401 não é instabilidade: é a nossa chave que não
         * serve, e isso não melhora sozinho. Sem gravar, a conta ficaria como
         * "nunca sincronizado" para sempre — indistinguível de uma conta nova
         * e, portanto, invisível. Era exatamente o que acontecia com dois
         * profissionais cujo saldo e saque estavam quebrados sem ninguém saber.
         */
        if (($situacao['auth_falhou'] ?? false) === true) {
            $this->forceFill([
                'asaas_account_status' => AsaasService::STATUS_CHAVE_INVALIDA,
                'asaas_status_detalhe' => json_encode([
                    'erro' => $situacao['erro'] ?? null,
                    // Só a forma e o prefixo da chave — nunca a credencial.
                    'chave' => $situacao['chave'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
                'asaas_status_em' => now(),
            ])->save();

            return;
        }

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
        $limite = now()->subHours(AsaasService::HORAS_RETENTAR_SPLIT);

        return $query
            // Wallet é OBRIGATÓRIA: sem ela o repasse não tem destino e a
            // cobrança é abortada em montarSplit(). Até 20/10/2026 esta linha
            // era `whereNull('asaas_wallet_id')` dentro do OR, tratando a
            // ausência como "pode" — espelhando o `return true` que havia no
            // método. Os dois mudaram juntos, e têm de continuar juntos.
            ->whereNotNull('asaas_wallet_id')
            ->where(function ($q) {
                $q->whereNull('asaas_account_status')
                    ->orWhere('asaas_account_status', AsaasService::STATUS_APROVADA)
                    ->orWhere('asaas_account_status', AsaasService::STATUS_CHAVE_INVALIDA);
            })
            // Espelha `splitBloqueadoPeloAsaas()`: quem apanhou do Asaas há
            // menos de HORAS_RETENTAR_SPLIT sai da vitrine. Sem esta metade o
            // profissional apareceria na lista e tomaria erro no checkout, que
            // é exatamente o defeito que a trava veio corrigir.
            ->where(function ($q) use ($limite) {
                $q->whereNull('asaas_split_bloqueado_em')
                    ->orWhere('asaas_split_bloqueado_em', '<=', $limite);
            });
    }
}
