<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioPlano extends Model
{
    use HasFactory;

    protected $table = 'studio_planos';

    protected $fillable = [
        'studio_id',
        'nome',
        'valor',
        'duracao_meses',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'studio_plano_id');
    }
}
