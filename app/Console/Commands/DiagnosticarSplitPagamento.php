<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\AsaasService;
use Illuminate\Console\Command;

/**
 * Por que o repasse de uma cobrança não chegou ao profissional.
 *
 * Nasceu de um caso concreto: uma cobrança paga pelo app com o valor INTEIRO
 * na conta da plataforma e nada na do profissional. Havia duas causas
 * possíveis, que pedem ações opostas, e nenhuma forma de distinguir sem abrir
 * o painel do Asaas:
 *
 *  1. o split nunca foi ENVIADO — o código anterior a 20/10/2026 devolvia
 *     `null` em montarSplit() quando o recebedor não tinha walletId, e null
 *     significa "siga sem split". Ação: deploy + criar as subcontas.
 *  2. o split foi enviado e RECUSADO depois — a validação de saque do Asaas
 *     responde REFUSED quando ASAAS_WEBHOOK_TOKEN não bate, e o token é
 *     conferido antes do tipo da operação, então ela recusa até PAYMENT_SPLIT.
 *     Ação: corrigir o token.
 *
 * A diferença está no Asaas, não no nosso banco: `payments` não registra o
 * split enviado (ver o docblock de ConferirRepasses). Este comando pergunta
 * direto à API e traduz a resposta na ação.
 *
 * Só LÊ (dois GET). Não cria, não altera e não transfere nada.
 *
 * Uso:
 *   php artisan asaas:diagnosticar-split pay_1234567890
 *   php artisan asaas:diagnosticar-split --payment=57     (id local)
 *   php artisan asaas:diagnosticar-split --ultimos=5
 */
class DiagnosticarSplitPagamento extends Command
{
    protected $signature = 'asaas:diagnosticar-split
        {asaas_id? : id da cobrança no Asaas (pay_...)}
        {--payment= : id local em payments, se preferir}
        {--ultimos= : diagnostica as N cobranças mais recentes}';

    protected $description = 'Diz se o split de uma cobrança foi enviado, aprovado ou recusado — e o que fazer.';

    public function handle(AsaasService $asaas): int
    {
        $alvos = $this->resolverAlvos();

        if ($alvos === []) {
            $this->error('Informe o id do Asaas, --payment=<id local> ou --ultimos=<n>.');

            return self::FAILURE;
        }

        $algumProblema = false;

        foreach ($alvos as $asaasId) {
            $algumProblema = $this->diagnosticar($asaas, $asaasId) || $algumProblema;
            $this->newLine();
        }

        return $algumProblema ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function resolverAlvos(): array
    {
        if ($id = $this->argument('asaas_id')) {
            return [(string) $id];
        }

        if ($local = $this->option('payment')) {
            // A coluna do id Asaas é legada: `stripe_payment_intent_id`.
            $ref = Payment::find((int) $local)?->stripe_payment_intent_id;

            if (! $ref) {
                $this->error("payments #{$local} não existe ou não tem id do Asaas gravado.");

                return [];
            }

            return [(string) $ref];
        }

        if ($n = (int) $this->option('ultimos')) {
            return Payment::whereNotNull('stripe_payment_intent_id')
                ->latest('id')
                ->limit(max($n, 1))
                ->pluck('stripe_payment_intent_id')
                ->map(fn ($r): string => (string) $r)
                ->all();
        }

        return [];
    }

    /** @return bool houve problema nesta cobrança? */
    private function diagnosticar(AsaasService $asaas, string $asaasId): bool
    {
        $this->line("<fg=cyan>=== {$asaasId} ===</>");

        $pagamento = $asaas->consultarPagamento($asaasId);

        if ($pagamento === null) {
            // Null é "não sei", nunca "não existe": pode ser rede, chave errada
            // ou ambiente trocado (sandbox x produção).
            $this->error('  Não foi possível consultar a cobrança. Verifique ASAAS_API_KEY e ASAAS_URL.');

            return true;
        }

        $valor = (float) ($pagamento['value'] ?? 0);

        $this->components->twoColumnDetail('situação', (string) ($pagamento['status'] ?? '?'));
        $this->components->twoColumnDetail('valor cobrado do cliente', 'R$ '.number_format($valor, 2, ',', '.'));

        $local = Payment::where('stripe_payment_intent_id', $asaasId)->first();

        if ($local) {
            $this->components->twoColumnDetail(
                'nosso registro diz',
                'profissional R$ '.number_format((float) $local->trainer_amount, 2, ',', '.')
                .' · plataforma R$ '.number_format((float) $local->company_fee, 2, ',', '.')
            );
        }

        $splits = $asaas->consultarSplitsDoPagamento($asaasId);

        if ($splits === null) {
            $this->error('  Não foi possível consultar os splits.');

            return true;
        }

        if ($splits === []) {
            $this->newLine();
            $this->error('  SPLIT NUNCA FOI ENVIADO.');
            $this->line('  O valor inteiro ficou na conta da plataforma e o repasse é dívida manual.');
            $this->line('  <fg=yellow>Ação:</> o código que cria a cobrança seguiu sem split — típico de');
            $this->line('  recebedor sem walletId antes do fix de 20/10/2026 (commit 640a08d9).');
            $this->line('  Confira o commit em produção, rode `asaas:conferir-repasses` e');
            $this->line('  `asaas:criar-subcontas-faltantes` antes de subir a trava.');

            if ($local) {
                $this->line('  Dívida com o profissional nesta cobrança: <fg=yellow>R$ '
                    .number_format((float) $local->trainer_amount, 2, ',', '.').'</>');
            }

            return true;
        }

        $this->newLine();
        $this->line('  Splits registrados no Asaas:');

        $houveRecusa = false;

        foreach ($splits as $split) {
            $status = (string) ($split['status'] ?? '?');
            $destino = (string) ($split['walletId'] ?? '?');

            $quanto = isset($split['fixedValue'])
                ? 'R$ '.number_format((float) $split['fixedValue'], 2, ',', '.')
                : (isset($split['percentualValue']) ? $split['percentualValue'].'%' : '?');

            // No Asaas o split percorre PENDING → AWAITING_CREDIT → DONE.
            // CANCELLED/REFUSED é o que interessa aqui.
            $recusado = in_array(mb_strtoupper($status), ['CANCELLED', 'REFUSED', 'CANCELED'], true);
            $houveRecusa = $houveRecusa || $recusado;

            $cor = $recusado ? 'red' : 'green';

            $this->line("    <fg={$cor}>{$status}</>  {$quanto}  wallet ".mb_substr($destino, 0, 12).'…');
        }

        if ($houveRecusa) {
            $this->newLine();
            $this->error('  SPLIT ENVIADO, MAS RECUSADO.');
            $this->line('  O repasse foi criado certo e barrado depois — o dinheiro ficou na plataforma.');
            $this->line('  <fg=yellow>Ação:</> a validação de saque respondeu REFUSED. A causa mais comum é');
            $this->line('  ASAAS_WEBHOOK_TOKEN diferente do token configurado no painel do Asaas:');
            $this->line('  o token é conferido ANTES do tipo da operação, então um token errado');
            $this->line('  recusa TODOS os repasses e todos os saques. Veja o motivo exato em');
            $this->line('  storage/logs/security-*.log: "validação de saque NEGADA".');

            return true;
        }

        $this->newLine();
        $this->info('  Split enviado e sem recusa. O repasse está no caminho normal.');
        $this->line('  Se o profissional ainda não viu o dinheiro, é liquidação: o Asaas credita a');
        $this->line('  subconta quando a cobrança liquida, não no instante do pagamento.');

        return false;
    }
}
