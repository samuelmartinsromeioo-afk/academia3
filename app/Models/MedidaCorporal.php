<?php

namespace App\Models;

use App\Models\Cadastro\Cliente;
use Illuminate\Database\Eloquent\Model;

class MedidaCorporal extends Model
{
    protected $table = 'medidas_corporais';

    protected $fillable = [
        'cliente_id', 'data', 'peso', 'percentual_gordura',
        'cintura', 'quadril', 'braco', 'peito', 'coxa', 'observacoes',
    ];

    protected $casts = [
        'data' => 'date',
        'peso' => 'decimal:2',
        'percentual_gordura' => 'decimal:2',
        'cintura' => 'decimal:2',
        'quadril' => 'decimal:2',
        'braco' => 'decimal:2',
        'peito' => 'decimal:2',
        'coxa' => 'decimal:2',
    ];

    /** Campos numéricos rotulados, para gráfico e formulário. */
    public const CAMPOS = [
        'peso' => 'Peso (kg)',
        'percentual_gordura' => '% Gordura',
        'cintura' => 'Cintura (cm)',
        'quadril' => 'Quadril (cm)',
        'braco' => 'Braço (cm)',
        'peito' => 'Peito (cm)',
        'coxa' => 'Coxa (cm)',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
