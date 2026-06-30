<?php

namespace App\Models\Cadastro;

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
        'filial_id',
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
        'foto',
        'plano',
        'plano_ativo',
        'studio_id',
        'studio_plano_id',
        'studio_plano_ativo',
    ];
    protected $casts = [
        'aceita_termos' => 'boolean',
        'data_aceitacao_termos' => 'datetime',
    ];

        public function personal() {
            return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
        }

        public function studio() {
            return $this->belongsTo(\App\Models\Cadastro\Studio::class, 'studio_id');
        }

        public function filial() {
            return $this->belongsTo(\App\Models\Cadastro\Filial::class, 'filial_id');
        }

        public function studioPlano() {
            return $this->belongsTo(\App\Models\Cadastro\StudioPlano::class, 'studio_plano_id');
        }

        public function anamnese() {
            return $this->hasOne(\App\Models\Anamnese::class, 'cliente_id');
        }
    use HasFactory;
}
