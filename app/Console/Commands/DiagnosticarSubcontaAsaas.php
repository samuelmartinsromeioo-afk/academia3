<?php

namespace App\Console\Commands;

use App\Services\AsaasService;
use Illuminate\Console\Command;

/**
 * Por que a subconta de um recebedor está bloqueada.
 *
 * Nasceu de um caso concreto: a cobrança de um pacote falhava com
 * "Não é possível fazer split para wallet [...], pois a mesma está bloqueada",
 * e não havia como descobrir o motivo sem abrir o painel do Asaas conta por
 * conta. O erro diz QUE está bloqueada e nunca POR QUE.
 *
 * O comando faz as duas pontas que faltavam:
 *   1. traduz o walletId (única coisa que a mensagem de erro traz) no dono —
 *      personal, academia, studio ou loja;
 *   2. consulta a situação cadastral COM A CHAVE DA PRÓPRIA SUBCONTA, que é a
 *      única que enxerga `/myAccount/*`. A chave da plataforma não serve: ela
 *      vê a conta-mãe, não a do recebedor.
 *
 * Uso:
 *   php artisan asaas:diagnosticar-subconta --wallet=82947cc6-...
 *   php artisan asaas:diagnosticar-subconta --personal=42
 *   php artisan asaas:diagnosticar-subconta --todos     (varre quem tem wallet)
 */
class DiagnosticarSubcontaAsaas extends Command
{
    protected $signature = 'asaas:diagnosticar-subconta
        {--wallet= : walletId que apareceu na mensagem de erro}
        {--personal= : id do personal}
        {--academia= : id da academia}
        {--studio= : id do studio}
        {--loja= : id da loja}
        {--todos : varre todos os recebedores que têm walletId}';

    protected $description = 'Mostra a situação cadastral da subconta Asaas de um recebedor (motivo do bloqueio e link de onboarding).';

    /** Papéis que têm subconta, na ordem em que são procurados. */
    private const PAPEIS = [
        'personal' => \App\Models\Cadastro\Personal::class,
        'academia' => \App\Models\Cadastro\Academia::class,
        'studio' => \App\Models\Cadastro\Studio::class,
        'loja' => \App\Models\Cadastro\Loja::class,
    ];

    public function handle(AsaasService $asaas): int
    {
        $alvos = $this->resolverAlvos();

        if ($alvos === []) {
            $this->error('Nada encontrado. Informe --wallet, --personal/--academia/--studio/--loja, ou --todos.');

            return self::FAILURE;
        }

        foreach ($alvos as [$papel, $conta]) {
            $this->diagnosticar($asaas, $papel, $conta);
        }

        return self::SUCCESS;
    }

    /** @return array<int, array{0:string, 1:\Illuminate\Database\Eloquent\Model}> */
    private function resolverAlvos(): array
    {
        if ($wallet = $this->option('wallet')) {
            foreach (self::PAPEIS as $papel => $classe) {
                if ($conta = $classe::where('asaas_wallet_id', $wallet)->first()) {
                    return [[$papel, $conta]];
                }
            }

            $this->error("Nenhum recebedor com walletId {$wallet}.");
            $this->line('A wallet pode ser de uma conta já apagada, ou de outro ambiente (sandbox x produção).');

            return [];
        }

        foreach (self::PAPEIS as $papel => $classe) {
            if ($id = $this->option($papel)) {
                $conta = $classe::find($id);

                return $conta ? [[$papel, $conta]] : [];
            }
        }

        if ($this->option('todos')) {
            $alvos = [];
            foreach (self::PAPEIS as $papel => $classe) {
                foreach ($classe::whereNotNull('asaas_wallet_id')->get() as $conta) {
                    $alvos[] = [$papel, $conta];
                }
            }

            return $alvos;
        }

        return [];
    }

    private function diagnosticar(AsaasService $asaas, string $papel, $conta): void
    {
        $this->newLine();
        $this->line(str_repeat('─', 72));
        $this->info(strtoupper($papel)." #{$conta->id} — ".($conta->nome ?: 'sem nome'));
        $this->line('  walletId  : '.($conta->asaas_wallet_id ?: '(nenhum)'));
        $this->line('  accountId : '.($conta->asaas_account_id ?: '(nenhum)'));
        $this->line('  chave     : '.($conta->asaas_api_key ? 'gravada' : 'AUSENTE'));

        if (! $conta->asaas_api_key) {
            $this->warn('  Sem chave da subconta: não dá para consultar /myAccount. Recrie a subconta pelo painel do admin.');

            return;
        }

        $situacao = $asaas->situacaoSubconta($conta);

        if (! $situacao['ok']) {
            $this->error('  Falha ao consultar o Asaas: '.$situacao['erro']);

            /*
             * 401 merece explicação própria: "chave inválida" sozinho manda o
             * leitor adivinhar entre causas com consertos diferentes. E a
             * consequência também é contraintuitiva — ele CONTINUA vendendo.
             */
            if ($situacao['auth_falhou'] ?? false) {
                $chave = $situacao['chave'] ?? [];
                $this->newLine();
                $this->line('  <comment>A chave que guardamos não serve.</comment>');
                $this->line('    forma    : '.($chave['forma'] ?? '?'));
                $this->line('    prefixo  : '.($chave['prefixo'] ?? '?').'…');
                $this->line('    tamanho  : '.($chave['tamanho'] ?? 0).' caracteres');
                $this->newLine();

                $diagnostico = match ($chave['forma'] ?? '') {
                    'cifrada_ilegivel' => 'Cifrada com OUTRO APP_KEY (base copiada entre ambientes, '
                        ."ou APP_KEY rotacionada).\n    O texto cifrado está indo como token. "
                        .'A chave original NÃO é recuperável: recrie a subconta pelo painel do admin.',
                    'texto_puro' => 'Formato de chave do Asaas, legível — então foi REVOGADA no painel '
                        ."do Asaas.\n    Gere uma nova chave e regrave pelo painel do admin.",
                    'ausente' => 'Não há chave gravada.',
                    default => 'Formato inesperado. Pode ser chave de SANDBOX contra produção '
                        .'(ou o inverso): confira ASAAS_URL e a origem da chave.',
                };
                $this->line('    <error>=> '.$diagnostico.'</error>');

                $this->newLine();
                $this->line('  <comment>Consequência (contraintuitiva):</comment>');
                $this->line('    - COBRANÇA e SPLIT seguem funcionando: usam a chave da plataforma');
                $this->line('      e o walletId. Este profissional continua vendendo normalmente.');
                $this->line('    - SALDO e SAQUE estão quebrados para ele: usam a chave da subconta.');
            }

            return;
        }

        $this->newLine();
        $this->line('  <comment>Situação cadastral (/myAccount/status)</comment>');
        foreach ($situacao['status'] as $campo => $valor) {
            $cor = $valor === 'APPROVED' ? 'info' : 'error';
            $this->line(sprintf('    %-18s <%s>%s</%s>', $campo, $cor, $valor ?? 'null', $cor));
        }

        $geral = $situacao['geral'];
        $this->newLine();
        if ($geral === 'APPROVED') {
            $this->info('  => APROVADA. Se o split ainda falha, o bloqueio é por compliance e só o suporte do Asaas resolve.');
        } else {
            $this->error("  => NÃO APROVADA (general = {$geral}). É isto que faz o split ser recusado.");
        }

        if ($situacao['pendencias']) {
            $this->newLine();
            $this->line('  <comment>Documentos pendentes (/myAccount/documents)</comment>');
            foreach ($situacao['pendencias'] as $p) {
                $this->line("    - {$p}");
            }
        }

        if ($situacao['onboarding_url']) {
            $this->newLine();
            $this->line('  <comment>Link para o profissional regularizar:</comment>');
            $this->line('    '.$situacao['onboarding_url']);
        }
    }
}
