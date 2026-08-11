<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Cliente; // Ou Cliente, dependendo de quem faz o login

class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';
    protected $fillable = ['cliente_id', 'personal_id', 'academia_id', 'studio_id', 'loja_id', 'nota', 'comentario'];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'cliente_id');
    }
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function academia()
    {
        return $this->belongsTo(\App\Models\Cadastro\Academia::class, 'academia_id');
    }

    public function studio()
    {
        return $this->belongsTo(\App\Models\Cadastro\Studio::class, 'studio_id');
    }

    public function loja()
    {
        return $this->belongsTo(\App\Models\Cadastro\Loja::class, 'loja_id');
    }
}
