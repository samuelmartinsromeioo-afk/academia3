<?php

namespace App\Models;

use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Database\Eloquent\Model;

class Mensagem extends Model
{
    protected $table = 'mensagens';

    protected $fillable = [
        'personal_id', 'cliente_id', 'remetente', 'texto', 'lida',
    ];

    protected $casts = [
        'lida' => 'boolean',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
