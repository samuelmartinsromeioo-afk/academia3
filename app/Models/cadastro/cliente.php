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
        'senha',
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
        'condicao_clinica'
        ];

        public function personal() {
            return $this->belongsTo(\App\Models\cadastro\Personal::class, 'personal_id');
        }
        /*public function academia()
        {
            return $this->belongsTo(\App\Models\cadastro\Academia::class, 'academia_id');
        }*/
    use HasFactory;
}
