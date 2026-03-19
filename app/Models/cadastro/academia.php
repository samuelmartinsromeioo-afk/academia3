<?php

namespace App\Models\cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Academia extends Model
{

    // O nome da tabela deve bater com o banco
    protected $table = 'academias';


    protected $fillable = [
        'nome',
        'cep',
        'rua',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'endereco',
        'valor_mensalidade',
        'descricao',
        'email',
        'senha',
        'cnpj',
        'tipos_aulas',
        'latitude',
        'longitude',    
                            ];

    //garante com que os dados saiam de forma correta do banco de dados 
    protected $casts = [
        'valor' => 'decimal:2',
        'created_at' => 'datetime',
    ];
    public function cliente(): HasMany
    {
        return $this->hasMany(Academia::class);
    }
   
    use HasFactory;
}
