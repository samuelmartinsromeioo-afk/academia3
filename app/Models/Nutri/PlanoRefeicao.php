<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class PlanoRefeicao extends Model
{
    protected $table = 'nutri_plano_refeicoes';

    protected $fillable = ['plano_id', 'nome', 'horario', 'ordem', 'observacoes'];

    public function plano()
    {
        return $this->belongsTo(PlanoAlimentar::class, 'plano_id');
    }

    public function itens()
    {
        return $this->hasMany(PlanoItem::class, 'refeicao_id')->orderBy('ordem');
    }

    public function totais(): array
    {
        $t = ['kcal' => 0, 'carbo_g' => 0, 'proteina_g' => 0, 'gordura_g' => 0];
        foreach ($this->itens as $item) {
            $t['kcal'] += $item->kcal;
            $t['carbo_g'] += $item->carbo_g;
            $t['proteina_g'] += $item->proteina_g;
            $t['gordura_g'] += $item->gordura_g;
        }

        return array_map(fn ($v) => round($v, 1), $t);
    }
}
