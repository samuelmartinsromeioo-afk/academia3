<?php

namespace App\Console\Commands;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Support\CadastroHelper;
use Illuminate\Console\Command;

/**
 * Cria a subconta Asaas de quem já está cadastrado e não tem wallet.
 *
 * A partir de 20/10/2026 recebedor sem `asaas_wallet_id` NÃO VENDE
 * (`AsaasService::montarSplit()` aborta, para o dinheiro dele não cair na conta
 * da plataforma). Os cadastros novos já criam a subconta; este comando é para o
 * passivo — e sem ele a trava simplesmente desliga a venda de quem está lá.
 *
 * O passivo existe porque a criação só era automática no cadastro de PERSONAL:
 * academia e loja dependiam de uma rota self-service que o dono precisava achar
 * no painel, e studio não tinha criação em lugar nenhum.
 *
 * NÃO roda no agendador de propósito. Cada chamada cria conta de verdade no
 * Asaas, com os dados cadastrais de alguém; é operação para rodar olhando, com
 * `--dry-run` antes, e não algo que deva acontecer sozinho às 4 da manhã.
 */
class CriarSubcontasFaltantes extends Command
{
    protected $signature = 'asaas:criar-subcontas-faltantes
        {--dry-run : só lista quem seria criado, sem chamar o Asaas}
        {--papel= : limita a um papel (personal|academia|studio|loja)}
        {--limite=0 : no máximo N criações nesta execução (0 = sem limite)}';

    protected $description = 'Cria subconta Asaas dos recebedores aprovados que estão sem walletId';

    private const PAPEIS = [
        'personal' => Personal::class,
        'academia' => Academia::class,
        'studio' => Studio::class,
        'loja' => Loja::class,
    ];

    public function handle(): int
    {
        $papelFiltro = $this->option('papel');
        $limite = (int) $this->option('limite');
        $seco = (bool) $this->option('dry-run');

        if ($papelFiltro && ! isset(self::PAPEIS[$papelFiltro])) {
            $this->error('Papel inválido. Use: '.implode(', ', array_keys(self::PAPEIS)));

            return self::FAILURE;
        }

        $papeis = $papelFiltro ? [$papelFiltro => self::PAPEIS[$papelFiltro]] : self::PAPEIS;

        if ($seco) {
            $this->warn('DRY-RUN: nada será criado no Asaas.');
        }

        $criados = 0;
        $falhas = 0;

        foreach ($papeis as $papel => $classe) {
            $pendentes = $classe::whereNull('asaas_wallet_id')
                ->where('status', 'aprovado')
                ->orderBy('id')
                ->get();

            if ($pendentes->isEmpty()) {
                $this->line("{$papel}: nenhum pendente.");

                continue;
            }

            $this->info("{$papel}: {$pendentes->count()} sem wallet");

            foreach ($pendentes as $r) {
                if ($limite > 0 && $criados >= $limite) {
                    $this->warn("Limite de {$limite} criações atingido — pare aqui e rode de novo.");

                    return self::SUCCESS;
                }

                $rotulo = "  #{$r->id} ".mb_strimwidth((string) $r->nome, 0, 30, '…');

                if ($seco) {
                    $this->line($rotulo.'  → seria criada');

                    continue;
                }

                // Personal é pessoa física (CPF); os outros três são PJ (CNPJ).
                // Os dois caminhos divergem só no payload, e o helper de cada um
                // já trata isso — nenhum deles lança.
                if ($papel === 'personal') {
                    CadastroHelper::criarSubcontaAsaasPersonal($r);
                } else {
                    CadastroHelper::criarSubcontaAsaasPJ($r);
                }

                // O helper nunca lança: o resultado se confere na coluna.
                if ($r->fresh()->asaas_wallet_id) {
                    $this->line($rotulo.'  <fg=green>✓ criada</>');
                    $criados++;
                } else {
                    $this->line($rotulo.'  <fg=red>✗ falhou (ver laravel.log)</>');
                    $falhas++;
                }
            }
        }

        if ($seco) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Criadas: {$criados}");

        if ($falhas > 0) {
            // Falha aqui costuma ser dado cadastral que o Asaas recusa (CNPJ
            // inválido, CEP inexistente). Esses recebedores continuam sem
            // vender, e é informação que alguém precisa ver.
            $this->warn("Falhas: {$falhas} — estes continuam sem poder vender.");
        }

        return self::SUCCESS;
    }
}
