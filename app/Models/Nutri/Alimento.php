<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class Alimento extends Model
{
    protected $table = 'nutri_alimentos';

    protected $fillable = [
        'personal_id', 'nome', 'grupo', 'fonte', 'medida_padrao', 'porcao_g',
        'kcal', 'carbo_g', 'proteina_g', 'gordura_g', 'fibra_g', 'sodio_mg', 'verificado', 'preco_kg',
    ];

    protected $casts = [
        'porcao_g' => 'float',
        'kcal' => 'float',
        'carbo_g' => 'float',
        'proteina_g' => 'float',
        'gordura_g' => 'float',
        'fibra_g' => 'float',
        'sodio_mg' => 'float',
        'preco_kg' => 'float',
        'verificado' => 'boolean',
    ];

    /** Preço de referência (R$/kg): próprio do alimento ou fallback do config. */
    public function precoKgRef(): float
    {
        if ($this->preco_kg) {
            return (float) $this->preco_kg;
        }

        return (float) (config('precos.alimento_kg')[$this->nome]
            ?? config('precos.grupo_kg')[$this->grupo]
            ?? 0);
    }

    /** Custo estimado (R$) para uma quantidade em gramas, ajustado pela UF. */
    public function custoPara(float $gramas, float $ufIndice = 1.0): float
    {
        return round(($gramas / 1000) * $this->precoKgRef() * $ufIndice, 2);
    }

    /** Macros para uma quantidade em gramas (valores da tabela são por 100 g). */
    public function macrosPara(float $gramas): array
    {
        $f = $gramas / 100;

        return [
            'kcal' => round($this->kcal * $f, 1),
            'carbo_g' => round($this->carbo_g * $f, 1),
            'proteina_g' => round($this->proteina_g * $f, 1),
            'gordura_g' => round($this->gordura_g * $f, 1),
        ];
    }

    /** Global (base oficial) ou do próprio nutricionista. */
    public function scopeDisponivelPara($query, int $personalId)
    {
        return $query->whereNull('personal_id')->orWhere('personal_id', $personalId);
    }
}
