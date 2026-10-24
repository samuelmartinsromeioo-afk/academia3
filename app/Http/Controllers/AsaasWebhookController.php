<?php

namespace App\Http\Controllers;

use App\Models\IndicacaoSaque;
use App\Models\Payment;
use App\Services\IndicacaoSaqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event');

        // Autenticidade do webhook: comparação timing-safe do token configurado
        // com o header enviado pelo Asaas. Calculado ANTES de qualquer ação.
        $expectedToken = config('services.asaas.webhook_token');
        $tokenValido = $expectedToken
            && hash_equals($expectedToken, (string) $request->header('asaas-access-token'));

        // ── Validação de saque (mecanismo de aprovação do Asaas) ──────────────
        //
        // Toda operação de SAÍDA de dinheiro fica ~5 s pendente e o Asaas pergunta
        // aqui se pode liberar. É a nossa SEGUNDA autorização, independente de quem
        // criou a operação: mesmo que uma transferência seja criada com sucesso na
        // conta, ela só sai se esta resposta for APPROVED.
        //
        // Identificado pelo campo `type` (TRANSFER, BILL, PIX_QR_CODE, PIX_REFUND,
        // MOBILE_PHONE_RECHARGE, PAYMENT_SPLIT) — diferente dos eventos de status,
        // que vêm em `event` (TRANSFER_DONE etc.) e são tratados mais abaixo.
        if ($request->filled('type') && ! $event) {
            return $this->validarSaque($request, $tokenValido, $expectedToken);
        }

        // ── Eventos de status de transferência ────────────────────────────────
        // Exigem token válido como qualquer outro evento (fail-closed).
        if (is_string($event) && str_starts_with($event, 'TRANSFER_')) {
            if (! $tokenValido) {
                Log::channel('security')->warning('Asaas: evento de transferência REJEITADO — token ausente ou inválido', [
                    'event' => $event,
                    'token_config' => (bool) $expectedToken,
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $this->conciliarTransferencia($request, $event);
        }

        // ── Eventos de situação da subconta ───────────────────────────────────
        //
        // O Asaas avisa aqui quando a análise cadastral de uma subconta muda de
        // estado (ACCOUNT_STATUS_*). É o que destrava — ou trava — a venda do
        // profissional, porque split para wallet não aprovada é recusado.
        //
        // O evento diz QUE mudou, não o estado final de cada parte do cadastro.
        // Então não tentamos deduzir o status do nome do evento: reconsultamos
        // `/myAccount/status` com a chave da subconta e gravamos o que o Asaas
        // responder. Deduzir daria um mapa de nomes de evento para manter em
        // sincronia com uma API de terceiro — a consulta não tem esse problema.
        //
        // Fail-closed como os demais: sem token válido, não processa.
        if (is_string($event) && str_starts_with($event, 'ACCOUNT_STATUS')) {
            if (! $tokenValido) {
                Log::channel('security')->warning('Asaas: evento de conta REJEITADO — token ausente ou inválido', [
                    'event' => $event,
                    'token_config' => (bool) $expectedToken,
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $this->atualizarSituacaoSubconta($request, $event);
        }

        // Eventos de pagamento também exigem token válido (fail-closed): confirmar
        // um pagamento libera acesso/booking sem cobrança real, então NUNCA
        // processamos um evento a partir de uma requisição não autenticada. Sem
        // token configurado no ambiente OU header inválido, recusamos — o mesmo
        // critério da autorização de saque acima. Configure ASAAS_WEBHOOK_TOKEN.
        if (! $tokenValido) {
            Log::channel('security')->warning('Asaas webhook: evento de pagamento REJEITADO — token ausente ou inválido', [
                'event' => $event,
                'token_config' => (bool) $expectedToken,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payment = $request->input('payment', []);
        Log::info('Asaas webhook recebido', [
            'event' => $event,
            'payment_id' => $payment['id'] ?? null,
            'subscription' => $payment['subscription'] ?? null,
        ]);

        $confirmedEvents = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED', 'PAYMENT_RECEIVED_IN_CASH'];
        $overdueEvents = ['PAYMENT_OVERDUE'];

        $asaasPaymentId = $payment['id'] ?? null;
        $subscriptionId = $payment['subscription'] ?? null;

        if (in_array($event, $confirmedEvents)) {
            if (! $asaasPaymentId) {
                return response()->json(['received' => true]);
            }

            $dbPayment = Payment::where('stripe_payment_intent_id', $asaasPaymentId)->first();

            if ($dbPayment) {
                // Cobrança já conhecida (1ª cobrança ou já registrada): fluxo normal.
                if ($dbPayment->status !== 'succeeded') {
                    app(PaymentController::class)->processarPagamentoConfirmado($dbPayment);
                }
            } elseif ($subscriptionId) {
                // Cobrança nova de uma assinatura existente = renovação mensal.
                app(PaymentController::class)->processarRenovacaoAssinatura($subscriptionId, $payment);
            } else {
                Log::warning('Asaas webhook: pagamento não encontrado', ['asaas_payment_id' => $asaasPaymentId]);
            }
        } elseif (in_array($event, $overdueEvents) && $subscriptionId) {
            // Mensalidade vencida sem pagamento: suspende o acesso até regularizar.
            app(PaymentController::class)->processarVencimentoAssinatura($subscriptionId);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Responde ao mecanismo de validação de saque do Asaas.
     *
     * FAIL-CLOSED. A resposta precisa ser `{"status":"APPROVED"}` ou
     * `{"status":"REFUSED","refuseReason":"..."}`; o Asaas cancela a operação se o
     * webhook falhar 3× ou não devolver status válido, então qualquer problema
     * nosso resulta em dinheiro NÃO saindo — que é o lado seguro do erro.
     *
     * Política por tipo de operação:
     *  • TRANSFER      — saída para chave/conta externa. Só aprova o que casa com
     *                    um `indicacao_saques` em `processando`, no valor exato e
     *                    na chave Pix que o dono cadastrou. É o único tipo que
     *                    criamos automaticamente, e é o perigoso.
     *  • PAYMENT_SPLIT — repasse do marketplace para o profissional. APROVADO: o
     *                    split é definido por nós na criação da cobrança e é o
     *                    núcleo do modelo 90/10 — recusar aqui pararia todos os
     *                    repasses da plataforma.
     *  • o resto       — boleto, recarga, devolução de Pix: não criamos nenhum
     *                    desses, logo ninguém legítimo os dispara. RECUSADO.
     */
    private function validarSaque(Request $request, bool $tokenValido, ?string $expectedToken)
    {
        $tipo = (string) $request->input('type');

        // Nunca autorizamos saída de dinheiro a partir de requisição não
        // autenticada. Sem token configurado/válido, nega.
        if (! $tokenValido) {
            Log::channel('security')->warning('Asaas: validação de saque NEGADA — token ausente ou inválido', [
                'type' => $tipo,
                'token_config' => (bool) $expectedToken,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'REFUSED',
                'refuseReason' => 'Origem não autenticada.',
            ], 401);
        }

        if ($tipo === 'PAYMENT_SPLIT') {
            Log::info('Asaas: split aprovado na validação', [
                'split_id' => $request->input('paymentSplit.id'),
            ]);

            return response()->json(['status' => 'APPROVED']);
        }

        if ($tipo !== 'TRANSFER') {
            Log::channel('security')->warning('Asaas: operação de saída RECUSADA — tipo que a plataforma não emite', [
                'type' => $tipo,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'REFUSED',
                'refuseReason' => 'Operação não emitida pela plataforma.',
            ]);
        }

        $transfer = (array) $request->input('transfer', []);
        $ref = (string) ($transfer['externalReference'] ?? '');

        /*
         * Saque da RECEITA DA PLATAFORMA, feito à mão no painel do Asaas.
         *
         * Vem sem `externalReference`, porque não fomos nós que criamos — e
         * por isso caía em "Referência externa não reconhecida." e era
         * recusado. Não existe fluxo no sistema para a plataforma sacar o que
         * é dela (os `sacar*` são dos profissionais), então sem esta saída o
         * dono da conta não tem como tirar o próprio dinheiro a não ser
         * desligando a validação.
         *
         * Conferido ANTES dos outros dois porque é o único caso em que o
         * destino, por si só, já autoriza: dinheiro indo para uma chave que
         * já estava cadastrada em `config/plataforma.php` não é perda. Quem
         * tiver a API key continua sem conseguir desviar para a conta dele.
         *
         * Devolve null quando o destino não é nosso, e aí a decisão segue
         * para as regras normais.
         */
        $decisao = $this->autorizarSaqueDaPlataforma($transfer)
            // Saque da subconta do profissional (sacarPersonal/sacarSubconta). Só
            // chega aqui se o webhook da subconta apontar para nós; aprovamos quando
            // casa com o registro criado antes da chamada, no valor exato.
            ?? (str_starts_with($ref, 'personal_saque:')
                ? $this->autorizarSaquePersonal($ref, $transfer)
                : app(IndicacaoSaqueService::class)->autorizarTransferencia($transfer));

        // A02 — só metadados no log: o payload traz chave Pix e dados bancários
        // do destino. Nunca o corpo inteiro.
        $contexto = [
            'transfer_id' => $transfer['id'] ?? null,
            'external_reference' => $transfer['externalReference'] ?? null,
            'valor' => $transfer['value'] ?? null,
            'motivo' => $decisao['motivo'],
            // Mascarado, não a chave. Serve para você conferir POR QUE um
            // saque seu não casou com a lista (pontuação, chave trocada) sem
            // que o log carregue o dado de destino de ninguém.
            'destino' => self::mascararChavePix(self::chaveDeDestino($transfer)),
            'ip' => $request->ip(),
        ];

        if (! $decisao['aprovar']) {
            Log::channel('security')->warning('Asaas: TRANSFERÊNCIA RECUSADA na validação', $contexto);

            return response()->json([
                'status' => 'REFUSED',
                'refuseReason' => $decisao['motivo'],
            ]);
        }

        Log::channel('security')->info('Asaas: transferência aprovada na validação', $contexto);

        return response()->json(['status' => 'APPROVED']);
    }

    /**
     * Autoriza o saque da receita da plataforma para uma chave Pix NOSSA.
     *
     * É a única autorização que se decide pelo DESTINO e não por um registro
     * prévio, e isso é deliberado: a plataforma não tem fluxo para sacar o
     * que é dela, então o saque é manual no painel do Asaas e chega aqui sem
     * `externalReference` para conferir. O que sustenta a decisão é que
     * dinheiro indo para uma chave já cadastrada em `config/plataforma.php`
     * não é perda — é o dono recebendo.
     *
     * Devolve `null` quando o destino NÃO é nosso, para a decisão seguir
     * para as regras normais (indicação / subconta do profissional). Devolver
     * uma recusa aqui atropelaria aquelas verificações.
     *
     * @return array{aprovar:bool, motivo:string}|null
     */
    private function autorizarSaqueDaPlataforma(array $transfer): ?array
    {
        $chaves = (array) config('plataforma.saque.chaves_pix', []);
        $destino = self::chaveDeDestino($transfer);

        if ($chaves === [] || $destino === '') {
            return null;
        }

        $destinoNormalizado = self::normalizarChavePix($destino);
        $ehNossa = false;

        foreach ($chaves as $chave) {
            // hash_equals pelo mesmo motivo do resto do módulo: comparação de
            // segredo em tempo constante (ver as notas OWASP no CLAUDE.md).
            if (hash_equals(self::normalizarChavePix((string) $chave), $destinoNormalizado)) {
                $ehNossa = true;
                break;
            }
        }

        if (! $ehNossa) {
            return null;
        }

        /*
         * Teto por transferência, mesmo indo para a nossa chave.
         *
         * Serve ao mesmo propósito do `indicacao.saque_auto_teto`: limitar o
         * estrago de qualquer falha única. Se a conta for drenada para uma
         * chave nossa, o dinheiro é recuperável — mas um valor fora do padrão
         * ainda é sinal de que algo está errado, e vale parar para olhar.
         */
        $teto = (float) config('plataforma.saque.teto', 0);
        $valor = isset($transfer['value']) ? (float) $transfer['value'] : null;

        if ($valor === null) {
            return ['aprovar' => false, 'motivo' => 'Valor da transferência não informado.'];
        }

        if ($teto > 0 && $valor > $teto) {
            return [
                'aprovar' => false,
                'motivo' => 'Valor acima do teto de saque da plataforma.',
            ];
        }

        return ['aprovar' => true, 'motivo' => 'Saque da plataforma para chave cadastrada.'];
    }

    /**
     * O destino EFETIVO da transferência.
     *
     * O Asaas aceita dois formatos e usa um ou o outro:
     *
     *   • `pixAddressKey` (+ `pixAddressKeyType`) — transferência por chave
     *     Pix. É o formato que o próprio sistema usa em todos os `sacar*`.
     *   • `bankAccount` — transferência por dados bancários (agência, conta,
     *     CPF/CNPJ do titular). É o que o painel do Asaas monta quando o saque
     *     é feito à mão escolhendo a conta de destino.
     *
     * Olhar só a chave Pix deixaria o saque manual por dados bancários com
     * destino VAZIO — e a lista de chaves nunca casaria, mantendo o bloqueio
     * que ela existe para resolver.
     *
     * A precedência não é cosmética: se o payload trouxer os dois, vale o que
     * o Asaas usa de fato (`pixAddressKey`). Aceitar "qualquer um dos dois que
     * casar" permitiria mandar dinheiro para a chave de um atacante enquanto
     * se põe o nosso CPF no `bankAccount` só para passar na conferência.
     */
    private static function chaveDeDestino(array $transfer): string
    {
        $chavePix = trim((string) ($transfer['pixAddressKey'] ?? ''));

        if ($chavePix !== '') {
            return $chavePix;
        }

        $conta = (array) ($transfer['bankAccount'] ?? []);

        return trim((string) ($conta['cpfCnpj'] ?? ''));
    }

    /**
     * Forma comparável de uma chave Pix.
     *
     * CPF, CNPJ e telefone são cadastrados com e sem pontuação dependendo de
     * onde se olha, e uma comparação literal faria "123.456.789-09" não casar
     * com "12345678909" — o tipo de divergência que manda o saque legítimo
     * para a recusa. E-mail e chave aleatória só perdem caixa e espaços.
     */
    private static function normalizarChavePix(string $chave): string
    {
        $chave = mb_strtolower(trim($chave));

        // Só dígitos e pontuação de documento/telefone: compara pelos dígitos.
        if ($chave !== '' && preg_match('/^[0-9.\-\/+() ]+$/', $chave) === 1) {
            return (string) preg_replace('/\D/', '', $chave);
        }

        return $chave;
    }

    /**
     * Chave mascarada para o log (A02: a chave em si nunca é registrada).
     *
     * Mostra o bastante para você identificar qual destino foi recusado sem
     * que o log carregue o dado de pagamento de alguém.
     */
    private static function mascararChavePix(string $chave): string
    {
        if ($chave === '') {
            return '(sem chave)';
        }

        $tamanho = mb_strlen($chave);

        if ($tamanho <= 4) {
            return str_repeat('*', $tamanho);
        }

        return mb_substr($chave, 0, 2).str_repeat('*', $tamanho - 4).mb_substr($chave, -2);
    }

    /**
     * Autoriza o saque que o profissional faz da PRÓPRIA subconta.
     *
     * Risco menor que o da indicação (o dinheiro é dele e sai do saldo dele, não
     * da conta da plataforma), mas a conferência é a mesma: tem de existir um
     * `personal_saques` recém-criado, no valor exato, ainda sem desfecho.
     *
     * @return array{aprovar:bool, motivo:string}
     */
    private function autorizarSaquePersonal(string $ref, array $transfer): array
    {
        $id = (int) substr($ref, strlen('personal_saque:'));
        $saque = $id > 0 ? \App\Models\PersonalSaque::find($id) : null;

        if (! $saque) {
            return ['aprovar' => false, 'motivo' => 'Saque não encontrado.'];
        }

        // CREATING = criado por nós agora e ainda sem resposta do Asaas; PENDING =
        // o Asaas já devolveu a criação. Qualquer outro estado (FAILED, DONE…)
        // significa que não há transferência legítima em curso.
        if (! in_array($saque->status, ['CREATING', 'PENDING'], true)) {
            return ['aprovar' => false, 'motivo' => 'Saque não está aguardando transferência.'];
        }

        $valor = isset($transfer['value']) ? round((float) $transfer['value'], 2) : null;

        if ($valor === null || abs($valor - (float) $saque->value) > 0.001) {
            return ['aprovar' => false, 'motivo' => 'Valor divergente do saque registrado.'];
        }

        return ['aprovar' => true, 'motivo' => 'Saque de subconta conferido.'];
    }

    /**
     * Aplica o desfecho de uma transferência (TRANSFER_DONE/FAILED/CANCELLED…) ao
     * saque de indicação correspondente. Idempotente pelo serviço.
     */
    /**
     * Reconsulta e grava a situação cadastral da subconta que mudou de estado.
     *
     * O Asaas identifica a conta pelo `id` da subconta (o mesmo que guardamos
     * em `asaas_account_id` na criação). Procuramos nos quatro papéis porque
     * qualquer um deles pode ter subconta.
     *
     * Responde 200 mesmo quando não reconhece a conta: o webhook não é nosso
     * mecanismo de verdade — a varredura `asaas:sincronizar-subcontas` corrige
     * depois — e devolver erro faria o Asaas reentregar indefinidamente um
     * evento de uma conta que não é nossa (sandbox, conta apagada, outro
     * ambiente apontando para a mesma URL).
     */
    private function atualizarSituacaoSubconta(Request $request, string $event)
    {
        $conta = $request->input('account', []);
        $accountId = $conta['id'] ?? $request->input('id');

        Log::info('Asaas: evento de situação de subconta', [
            'event' => $event,
            'account_id' => $accountId,
        ]);

        if (! $accountId) {
            return response()->json(['received' => true]);
        }

        $papeis = [
            'personal' => \App\Models\Cadastro\Personal::class,
            'academia' => \App\Models\Cadastro\Academia::class,
            'studio' => \App\Models\Cadastro\Studio::class,
            'loja' => \App\Models\Cadastro\Loja::class,
        ];

        foreach ($papeis as $papel => $classe) {
            $recebedor = $classe::where('asaas_account_id', $accountId)->first();
            if (! $recebedor) {
                continue;
            }

            $antes = $recebedor->asaas_account_status;
            $situacao = $recebedor->sincronizarSubconta();

            Log::info('Asaas: subconta reconsultada após webhook', [
                'papel' => $papel,
                'id' => $recebedor->id,
                'de' => $antes,
                'para' => $situacao['geral'] ?? null,
                'ok' => $situacao['ok'],
            ]);

            return response()->json(['received' => true]);
        }

        Log::warning('Asaas: evento de conta sem recebedor correspondente', [
            'account_id' => $accountId,
            'event' => $event,
        ]);

        return response()->json(['received' => true]);
    }

    private function conciliarTransferencia(Request $request, string $event)
    {
        $transfer = (array) $request->input('transfer', []);
        $ref = $transfer['externalReference'] ?? null;
        $id = IndicacaoSaque::idDaReferencia($ref);

        // Transferência que não é saque de indicação (ex.: saque de subconta do
        // personal) não tem nada a conciliar aqui.
        if ($id === null) {
            Log::info('Asaas: evento de transferência fora do escopo de indicação', [
                'event' => $event,
                'transfer_id' => $transfer['id'] ?? null,
            ]);

            return response()->json(['received' => true]);
        }

        $saque = IndicacaoSaque::find($id);

        if (! $saque) {
            Log::channel('security')->warning('Asaas: evento de transferência para saque inexistente', [
                'event' => $event,
                'external_reference' => $ref,
            ]);

            return response()->json(['received' => true]);
        }

        // Confere o id antes de aplicar: um evento cujo transfer.id não é o que
        // gravamos não descreve a nossa transferência.
        if (! empty($transfer['id']) && $saque->asaas_transfer_id
            && $transfer['id'] !== $saque->asaas_transfer_id) {
            Log::channel('security')->warning('Asaas: evento de transferência com id divergente', [
                'event' => $event,
                'saque_id' => $saque->id,
                'esperado' => $saque->asaas_transfer_id,
                'recebido' => $transfer['id'],
            ]);

            return response()->json(['received' => true]);
        }

        app(IndicacaoSaqueService::class)->concluirTransferencia(
            $saque,
            (string) ($transfer['status'] ?? ''),
            $transfer['failReason'] ?? null
        );

        return response()->json(['received' => true]);
    }
}
