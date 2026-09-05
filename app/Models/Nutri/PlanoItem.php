<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class PlanoItem extends Model
{
    protected $table = 'nutri_plano_itens';

    protected $fillable = [
        'refeicao_id', 'alimento_id', 'descricao', 'quantidade_g', 'medida',
        'kcal', 'carbo_g', 'proteina_g', 'gordura_g', 'substituicoes', 'ordem',
    ];

    protected $casts = [
        'quantidade_g' => 'float',
        'kcal' => 'float',
        'carbo_g' => 'float',
        'proteina_g' => 'float',
        'gordura_g' => 'float',
        'substituicoes' => 'array',
    ];

    public function refeicao()
    {
        return $this->belongsTo(PlanoRefeicao::class, 'refeicao_id');
    }

    public function alimento()
    {
        return $this->belongsTo(Alimento::class, 'alimento_id');
    }

    /** Opções de substituição (tabela separada nutri_plano_substituicoes). */
    public function opcoes()
    {
        return $this->hasMany(PlanoSubstituicao::class, 'plano_item_id')->orderBy('ordem');
    }
}
