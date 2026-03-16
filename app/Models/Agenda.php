<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\cadastro\Personal; 

class Agenda extends Model
{
    use HasFactory;

    // Define o nome da tabela (opcional se for o padrão 'agendas')
    protected $table = 'agendas';

    // Campos que podem ser preenchidos em massa (essenciais para o storeHorario)
    protected $fillable = [
        'personal_id',
        'data',
        'hora_inicio',
        'hora_fim',
        'descricao',
        'cancelado',
        'justificativa_cancelamento',
        'cancelado_em'
    ];

    /**
     * Relacionamento: Um horário pertence a um Personal.
     */
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }
    public function academia()
    {
        return $this->belongsTo(\App\Models\cadastro\Academia::class, 'academia_id');
    }
}