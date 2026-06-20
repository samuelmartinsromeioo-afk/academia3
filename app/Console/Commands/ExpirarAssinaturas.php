<?php

namespace App\Console\Commands;

use App\Http\Controllers\PaymentController;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpirarAssinaturas extends Command
{
    protected $signature   = 'assinaturas:expirar';
    protected $description = 'Suspende o acesso de assinaturas canceladas/em atraso cujo período pago (30 dias) já terminou';

    public function handle(): int
    {
        // Só precisamos reavaliar quem tem assinatura cancelada ou em atraso —
        // assinaturas ativas continuam renovando e mantêm o acesso.
        $clienteIds = Subscription::whereIn('status', ['canceled', 'overdue'])
            ->distinct()
            ->pluck('user_id');

        if ($clienteIds->isEmpty()) {
            $this->info('Nenhuma assinatura para reavaliar.');
            return self::SUCCESS;
        }

        $controller = app(PaymentController::class);
        $processados = 0;

        foreach ($clienteIds as $clienteId) {
            $controller->recalcularAcessoCliente((int) $clienteId);
            $processados++;
        }

        $this->info("Acesso reavaliado para {$processados} aluno(s).");
        return self::SUCCESS;
    }
}
