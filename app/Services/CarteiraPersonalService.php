<?php

namespace App\Services;

use App\Models\Cadastro\Personal;
use App\Models\PersonalSaque;
use App\Models\TrainerPayout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Saldo e saque Pix da subconta do personal.
 *
 * Existe porque esta operação passou a ter DUAS portas — o dashboard do site e
 * o app — e ela não pode ter duas implementações. O motivo não é estética:
 *
 * 1. O saque nasce de um `PersonalSaque` criado ANTES da chamada ao Asaas, só
 *    para a transferência carregar `externalReference = personal_saque:{id}`.
 *    `AsaasWebhookController::validarSaque()` é FAIL-CLOSED e recusa qualquer
 *    transferência cuja referência não case com um registro nosso — uma segunda
 *    implementação que esquecesse a referência teria todo saque recusado pelo
 *    próprio webhook, e o dinheiro não sairia.
 * 2. É dinheiro saindo. Duas cópias divergem, e a que divergir vai divergir
 *    numa trava.
 *
 * Os métodos NÃO resolvem quem é o personal e NÃO devolvem HTTP: a sessão
 * (site) e o token Sanctum (app) resolvem o dono cada um à sua maneira, e é
 * justamente por isso que a rota antiga não servia ao app.
 */
class CarteiraPersonalService
{
    private function url(): string
    {
        return config('services.asaas.url');
    }

    /**
     * Saldo da subconta.
     *
     * `sem_conta` não é erro: o personal sem subconta Asaas recebe por repasse
     * manual (ver "Known gap" do CLAUDE.md), então a tela mostra saldo zero com
     * a explicação, em vez de um erro que ele não tem como resolver.
     *
     * @return array{ok: bool, saldo?: float, sem_conta?: bool, tem_pix?: bool, erro?: string}
     */
    public function saldo(Personal $personal): array
    {
        if (! $personal->asaas_api_key) {
            return [
                'ok' => true,
                'saldo' => 0.0,
                'sem_conta' => true,
                'tem_pix' => ! empty($personal->chave_pix),
            ];
        }

        $apiKey = $personal->getAsaasApiKeyDecrypted();

        $res = Http::withHeaders([
            'access_token' => $apiKey,
            'Content-Type' => 'application/json',
        ])->get($this->url().'/finance/balance');

        unset($apiKey);

        if ($res->failed()) {
            Log::error('Asaas saldo: falha', ['personal_id' => $personal->id, 'body' => $res->json()]);

            return ['ok' => false, 'erro' => 'Não foi possível consultar o saldo.'];
        }

        return [
            'ok' => true,
            'saldo' => (float) ($res->json()['balance'] ?? 0),
            'sem_conta' => false,
            'tem_pix' => ! empty($personal->chave_pix),
        ];
    }

    /**
     * Transferência Pix do saldo da subconta para a chave cadastrada no perfil.
     *
     * A chave NÃO vem da requisição de propósito: o destino do dinheiro sai de
     * `personals.chave_pix`, então nem o site nem o app transmitem para onde
     * pagar — o mesmo princípio do saque de indicação. Quem muda a chave passa
     * pela edição de perfil, que é um fluxo autenticado e auditável à parte.
     *
     * @return array{ok: bool, transfer_id?: ?string, valor?: float, erro?: string, status?: int}
     */
    public function sacar(Personal $personal, float $valor): array
    {
        if (! $personal->asaas_api_key) {
            return ['ok' => false, 'status' => 422, 'erro' => 'Sua conta Asaas ainda não foi configurada.'];
        }

        if (! $personal->chave_pix) {
            return ['ok' => false, 'status' => 422, 'erro' => 'Cadastre sua chave PIX no perfil antes de sacar.'];
        }

        $apiKey = $personal->getAsaasApiKeyDecrypted();

        // Registra ANTES de chamar o Asaas, para a transferência nascer com um
        // externalReference verificável — ver o cabeçalho desta classe.
        $saque = PersonalSaque::create([
            'personal_id' => $personal->id,
            'value' => $valor,
            'status' => 'CREATING',
        ]);
        $saque->update(['external_reference' => 'personal_saque:'.$saque->id]);

        $res = Http::withHeaders([
            'access_token' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->url().'/transfers', [
            'operationType' => 'PIX',
            'value' => $valor,
            'pixAddressKey' => $personal->chave_pix,
            'pixAddressKeyType' => AsaasService::tipoChavePix($personal->chave_pix),
            'description' => 'Saque SnrFit',
            'externalReference' => $saque->external_reference,
        ]);

        unset($apiKey);

        $data = $res->json();

        if (! empty($data['errors'])) {
            $erro = AsaasService::mensagemDeErro($data, 'Falha ao processar saque.');
            Log::error('Asaas saque: falha', ['personal_id' => $personal->id, 'body' => $data]);
            $saque->update(['status' => 'FAILED']);

            return ['ok' => false, 'status' => 422, 'erro' => $erro];
        }

        if ($res->failed()) {
            Log::error('Asaas saque: http error', ['personal_id' => $personal->id, 'body' => $data]);
            $saque->update(['status' => 'FAILED']);

            return ['ok' => false, 'status' => 500, 'erro' => 'Falha ao processar saque. Tente novamente.'];
        }

        $saque->update([
            'asaas_transfer_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'PENDING',
            'transaction_receipt_url' => $data['transactionReceiptUrl'] ?? null,
        ]);

        TrainerPayout::where('trainer_id', $personal->id)
            ->where('status', 'in_wallet')
            ->update(['status' => 'paid', 'stripe_payout_id' => $data['id'] ?? null]);

        Log::info('Asaas saque realizado', [
            'personal_id' => $personal->id,
            'valor' => $valor,
            'transfer_id' => $data['id'] ?? null,
        ]);

        return [
            'ok' => true,
            'transfer_id' => $data['id'] ?? null,
            'valor' => $valor,
        ];
    }
}
