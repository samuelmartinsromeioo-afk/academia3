<?php

namespace App\Console\Commands;

use App\Services\IndicacaoSaqueService;
use Illuminate\Console\Command;

/**
 * Conciliação dos saques de indicação presos em `processando`.
 *
 * O desfecho normal chega pelo webhook de transferência (TRANSFER_DONE/FAILED).
 * Este comando é a rede de segurança para quando ele não chega: webhook perdido,
 * fora do ar na hora, ou criação com timeout (em que a transferência pode existir
 * no Asaas sem termos gravado o id). Consulta o Asaas e aplica o resultado.
 *
 * Sem ele, uma transferência que falhou deixaria o bônus do indicador preso num
 * saque que nunca vai pagar. Agende junto com os outros:
 *
 *     php artisan indicacoes:conciliar-saques
 */
class ConciliarSaquesIndicacao extends Command
{
    protected $signature = 'indicacoes:conciliar-saques {--limite=50 : Quantos saques checar nesta passada}';

    protected $description = 'Consulta no Asaas os saques de indicação em processamento e aplica o desfecho';

    public function handle(IndicacaoSaqueService $saques): int
    {
        $r = $saques->conciliarPendentes((int) $this->option('limite'));

        $this->info(sprintf(
            '%d saque(s) conciliado(s); %d ainda em processamento.',
            $r['conciliados'],
            $r['pendentes']
        ));

        return self::SUCCESS;
    }
}
