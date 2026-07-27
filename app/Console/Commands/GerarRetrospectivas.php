<?php

namespace App\Console\Commands;

use App\Models\Cadastro\Cliente;
use App\Models\Celebracao;
use App\Services\Celebracoes;
use App\Services\Retrospectivas;
use Illuminate\Console\Command;

/**
 * Gera as celebrações de retrospectiva do aluno:
 *  - bimestral (a cada ~2 meses): evolução do período;
 *  - semestral (a cada ~6 meses): "como começou vs como está agora".
 *
 * Roda diariamente (Kernel) e só enfileira para quem está "vencido" — ou seja,
 * cuja última retrospectiva daquele tipo saiu há >= 2 / >= 6 meses (ou nunca).
 *
 *   php artisan celebracoes:retrospectivas
 *   php artisan celebracoes:retrospectivas --cliente=24 --force   # teste
 */
class GerarRetrospectivas extends Command
{
    protected $signature = 'celebracoes:retrospectivas {--cliente= : Só este cliente} {--force : Ignora a janela de tempo}';

    protected $description = 'Enfileira as retrospectivas (2 e 6 meses) de evolução dos alunos';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $clientes = $this->option('cliente')
            ? Cliente::where('id', $this->option('cliente'))->get()
            : Cliente::whereIn('id', \App\Models\MedidaCorporal::distinct()->pluck('cliente_id'))->get();

        $bi = 0;
        $sem = 0;

        foreach ($clientes as $cliente) {
            if ($force || $this->vencido($cliente->id, 'retrospectiva_bimestral', 2)) {
                $payload = Retrospectivas::bimestral($cliente->id);
                if ($payload) {
                    Celebracoes::push('cliente', (int) $cliente->id, $payload);
                    $bi++;
                }
            }

            if ($force || $this->vencido($cliente->id, 'retrospectiva_semestral', 6)) {
                $payload = Retrospectivas::semestral($cliente->id);
                if ($payload) {
                    Celebracoes::push('cliente', (int) $cliente->id, $payload);
                    $sem++;
                }
            }
        }

        $this->info("Retrospectivas geradas — bimestral: {$bi}, semestral: {$sem}.");

        return self::SUCCESS;
    }

    /** Vencido: nunca teve OU a última saiu há >= $meses meses. */
    private function vencido(int $clienteId, string $tipo, int $meses): bool
    {
        $ultima = Celebracao::where('papel', 'cliente')
            ->where('usuario_id', $clienteId)
            ->where('tipo', $tipo)
            ->latest('id')
            ->first();

        return ! $ultima || $ultima->created_at->lt(now()->subMonths($meses));
    }
}
