<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvaliacaoFisica extends Model
{
    protected $table = 'avaliacoes_fisicas';

    public const TIPOS = ['antes_depois', 'dinamometro', 'oximetro', 'pressao_arterial', 'bioimpedancia'];

    protected $fillable = [
        'personal_id',
        'cliente_id',
        'tipo',
        'data_avaliacao',
        'foto',
        'arquivo',
        'peso',
        'medidas',
        'forca',
        'spo2',
        'bpm',
        'pressao_sistolica',
        'pressao_diastolica',
        'observacoes',
    ];

    protected $casts = [
        'data_avaliacao' => 'date',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'cliente_id');
    }
}
