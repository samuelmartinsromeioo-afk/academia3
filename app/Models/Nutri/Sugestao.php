<?php

namespace App\Models\Nutri;

use App\Models\Cadastro\Personal;
use Illuminate\Database\Eloquent\Model;

class Sugestao extends Model
{
    protected $table = 'nutri_sugestoes';

    protected $fillable = ['personal_id', 'titulo', 'descricao', 'status', 'votos_count'];

    protected $casts = ['votos_count' => 'integer'];

    public const STATUS = [
        'em_analise' => 'Em análise',
        'planejado' => 'Planejado',
        'em_desenvolvimento' => 'Em desenvolvimento',
        'entregue' => 'Entregue',
        'recusado' => 'Recusado',
    ];

    public function autor()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function votou(int $personalId): bool
    {
        return \Illuminate\Support\Facades\DB::table('nutri_sugestao_votos')
            ->where('sugestao_id', $this->id)
            ->where('personal_id', $personalId)
            ->exists();
    }
}
