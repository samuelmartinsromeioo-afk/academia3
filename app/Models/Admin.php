<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Admin extends Model
{
    protected $table = 'admins';
    public $timestamps = true;

    protected $fillable = [
        'nome',
        'email',
        'senha',
    ];

    protected $hidden = [
        'senha',
    ];

    // ✅ Mutator para hash automático de senha
    public function setSenhaAttribute($value)
    {
        $this->attributes['senha'] = Hash::make($value);
    }

    // ✅ Verificar senha
    public function verificarSenha($senha)
    {
        return Hash::check($senha, $this->senha);
    }
}