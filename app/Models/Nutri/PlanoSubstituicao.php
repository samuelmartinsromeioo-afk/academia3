<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

/**
 * Opção de substituição de um item do plano (tabela nutri_plano_substituicoes).
 * Equivalente que o paciente pode usar no lugar do item original.
 */
class PlanoSubstituicao extends Model
{
    protected $table = 'nutri_plano_substituicoes';

    protected $fillable = [
        'plano_item_id', 'alimento_id', 'descricao', 'quantidade_g', 'medida',
        'kcal', 'carbo_g', 'proteina_g', 'gordura_g', 'ordem',
    ];

    protected $casts = [
        'quantidade_g' => 'float',
        'kcal' => 'float',
        'carbo_g' => 'float',
        'proteina_g' => 'float',
        'gordura_g' => 'float',
    ];

    public function item()
    {
        return $this->belongsTo(PlanoItem::class, 'plano_item_id');
    }

    public function alimento()
    {
        return $this->belongsTo(Alimento::class, 'alimento_id');
    }
}
