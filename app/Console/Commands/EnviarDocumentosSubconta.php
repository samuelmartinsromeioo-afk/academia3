<?php

namespace App\Console\Commands;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\DocumentoSubconta;
use App\Services\DocumentoSubcontaService;
use Illuminate\Console\Command;

/**
 * Envia ao Asaas os documentos que os recebedores subiram pelo site.
 *
 * POR QUE ISTO É UM COMANDO, E NÃO PARTE DO CADASTRO — três razões, cada uma
 * suficiente:
 *
 *  1. A doc do Asaas manda esperar **≥15s** depois de criar a subconta antes de
 *     consultar os documentos: antes disso ele lista documentos não-obrigatórios
 *     por engano. Não há como respeitar isso dentro do request do cadastro.
 *  2. A subconta frequentemente **não existe** no momento do cadastro. Hoje a
 *     criação devolve 403 (`baas_trial_limit_child_account_creation_reached`)
 *     enquanto a homologação BaaS não concluir — o documento fica guardado e
 *     sobe quando a conta passar a existir, sem ninguém reenviar nada.
 *  3. Um documento pode ser **recusado** dias depois. A varredura sincroniza o
 *     veredito e devolve o documento ao estado "precisa de outro arquivo".
 *
 * É uma varredura idempotente, no mesmo princípio de `CupomService::apurar()`:
 * recalcula do estado atual em vez de depender de um gancho que alguém esquece
 * de chamar num dos oito pontos de cadastro.
 *
 * SEGURO NO AGENDADOR, ao contrário de `asaas:criar-subcontas-faltantes`: não
 * cria nada e não move dinheiro. Só sobe arquivo que o próprio dono mandou e lê
 * status.
 */
class EnviarDocumentosSubconta extends Command
{
    protected $signature = 'asaas:enviar-documentos
        {--papel= : limita a um papel (personal|academia|studio|loja)}
        {--id= : limita a um recebedor (exige --papel)}
        {--dry-run : só lista o que seria enviado}';

    protected $description = 'Envia ao Asaas os documentos de identidade que os recebedores subiram pelo site';

    private const PAPEIS = [
        'personal' => Personal::class,
        'academia' => Academia::class,
        'studio' => Studio::class,
        'loja' => Loja::class,
    ];

    public function handle(DocumentoSubcontaService $servico): int
    {
        $papel = $this->option('papel');
        $id = $this->option('id');

        if ($id && ! $papel) {
            $this->error('--id exige --papel.');

            return self::FAILURE;
        }

        if ($papel && ! isset(self::PAPEIS[$papel])) {
            $this->error('Papel inválido. Use: '.implode(', ', array_keys(self::PAPEIS)));

            return self::FAILURE;
        }

        $papeis = $papel ? [$papel => self::PAPEIS[$papel]] : self::PAPEIS;
        $seco = (bool) $this->option('dry-run');

        $total = ['recebedores' => 0, 'enviados' => 0, 'so_link' => 0,
            'aprovados' => 0, 'recusados' => 0, 'falhas' => 0, 'sem_conta' => 0];

        foreach ($papeis as $nome => $classe) {
            /*
             * Só quem TEM documento a tratar. A varredura parte dos documentos,
             * não dos recebedores: recebedor sem documento pendente não gera
             * request nenhum ao Asaas, o que mantém o comando barato o
             * suficiente para rodar no agendador.
             *
             * Inclui `enviado` de propósito — é o estado em que o Asaas ainda
             * está analisando, e é justamente esse que precisa ser relido para
             * descobrir aprovação ou recusa.
             */
            $ids = DocumentoSubconta::query()
                ->where('recebedor_type', (new $classe)->getMorphClass())
                ->whereIn('status', [
                    DocumentoSubconta::STATUS_PENDENTE,
                    DocumentoSubconta::STATUS_RECUSADO,
                    DocumentoSubconta::STATUS_ENVIADO,
                ])
                ->when($id, fn ($q) => $q->where('recebedor_id', $id))
                ->distinct()
                ->pluck('recebedor_id');

            if ($ids->isEmpty()) {
                continue;
            }

            foreach ($classe::whereIn('id', $ids)->get() as $recebedor) {
                $total['recebedores']++;
                $rotulo = "{$nome} #{$recebedor->id} ({$recebedor->nome})";

                if ($seco) {
                    $pendentes = DocumentoSubconta::query()
                        ->where('recebedor_type', $recebedor->getMorphClass())
                        ->where('recebedor_id', $recebedor->id)
                        ->aEnviar()->count();
                    $this->line("  {$rotulo}: {$pendentes} documento(s) a enviar");

                    continue;
                }

                $r = $servico->enviarPendentes($recebedor);

                if (! $r['ok']) {
                    $total['sem_conta']++;
                    $this->warn("  {$rotulo}: {$r['erro']}");

                    continue;
                }

                foreach (['enviados', 'so_link', 'aprovados', 'recusados', 'falhas'] as $k) {
                    $total[$k] += $r[$k];
                }

                $this->line(sprintf(
                    '  %s: %d enviado(s), %d aprovado(s), %d recusado(s), %d só-link, %d falha(s)',
                    $rotulo, $r['enviados'], $r['aprovados'], $r['recusados'], $r['so_link'], $r['falhas']
                ));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%d recebedor(es) · %d enviado(s) · %d aprovado(s) · %d recusado(s) · %d só-link · %d falha(s) · %d sem subconta',
            $total['recebedores'], $total['enviados'], $total['aprovados'],
            $total['recusados'], $total['so_link'], $total['falhas'], $total['sem_conta']
        ));

        if ($total['so_link'] > 0) {
            $this->newLine();
            $this->warn('Os "só-link" NÃO são falha nossa: o Asaas recusa envio por API');
            $this->warn('quando o grupo traz `onboardingUrl`. O painel do profissional');
            $this->warn('mostra o link, e só por ele o documento é aceito.');
        }

        if ($total['sem_conta'] > 0) {
            $this->newLine();
            $this->warn('Quem está "sem subconta" tem o documento guardado e enviará sozinho');
            $this->warn('quando a subconta existir. Confira com: php artisan asaas:conferir-repasses');
        }

        return self::SUCCESS;
    }
}
