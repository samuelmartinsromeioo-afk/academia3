<?php

namespace App\Models\cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'clientes';

    public $incrementing = true;
    protected $keyType = 'int';
    

    protected $fillable = [
        'nome',
        'id',
        'email',
        'academia_id',
        'senha',
        'aceita_termos',
        'data_aceitacao_termos',
        'ip_aceitacao_termos',
        'cep',
        'rua',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'altura',
        'peso',
        'idade',
        'sexo',
        'frequencia_semanal',
        'resumo_objetivo',
        'condicao_clinica',
        'whatsapp',
    ];
    protected $casts = [
        'aceita_termos' => 'boolean',
        'data_aceitacao_termos' => 'datetime',
    ];

        public function personal() {
            return $this->belongsTo(\App\Models\cadastro\Personal::class, 'personal_id');
        }
    use HasFactory;
}
