<?php

namespace App\Models;

use App\Models\Cadastro\Cliente;
use Illuminate\Database\Eloquent\Model;

class FotoProgresso extends Model
{
    protected $table = 'fotos_progresso';

    protected $fillable = [
        'cliente_id', 'data', 'caminho', 'peso', 'observacao',
    ];

    protected $casts = [
        'data' => 'date',
        'peso' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
