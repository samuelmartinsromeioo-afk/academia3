<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Uma linha do livro-caixa da indicação: `percentual` da COMISSÃO que a
 * plataforma ganhou em UMA receita do indicado (um pagamento, uma consulta) que
 * caiu dentro da janela.
 *
 * `base_valor` é a comissão da plataforma naquela receita — é sobre ela que o
 * percentual incide. `bruto_valor` é o que o indicado faturou, guardado apenas
 * para o extrato mostrar a conta inteira (faturou → comissão → sua parte); não
 * entra em cálculo nenhum e é nulo nos créditos anteriores à coluna.
 *
 * Append-only de propósito. `origem` ("payment:123") é unique no banco e serve
 * de chave de idempotência: reapurar a mesma indicação, ou o Asaas reentregar o
 * mesmo webhook, não gera crédito em dobro. Nada aqui é editado depois de
 * escrito — corrigir um valor significa um crédito novo, não um UPDATE, para a
 * trilha de auditoria do dinheiro continuar íntegra.
 */
class IndicacaoCredito extends Model
{
    protected $table = 'indicacao_creditos';

    protected $fillable = [
        'cupom_uso_id',
        'origem',
        'base_valor',
        'bruto_valor',
        'percentual',
        'valor',
        'ocorreu_em',
    ];

    protected $casts = [
        'base_valor'  => 'decimal:2',
        'bruto_valor' => 'decimal:2',
        'percentual'  => 'decimal:4',
        'valor'       => 'decimal:2',
        'ocorreu_em'  => 'datetime',
    ];

    public function uso()
    {
        return $this->belongsTo(CupomUso::class, 'cupom_uso_id');
    }

    /** Rótulo legível da origem, para o extrato do painel. */
    public function origemLabel(): string
    {
        return match (true) {
            str_starts_with($this->origem, 'payment:')        => 'Pagamento na plataforma',
            str_starts_with($this->origem, 'nutri_cobranca:') => 'Consulta nutricional',
            default                                           => 'Receita do indicado',
        };
    }
}
