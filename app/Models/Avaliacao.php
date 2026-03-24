<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\cadastro\Personal; 
use App\Models\cadastro\cliente; // Ou Cliente, dependendo de quem faz o login

class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';
    protected $fillable = ['cliente_id', 'personal_id', 'academia_id', 'nota', 'comentario'];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\cadastro\Cliente::class, 'cliente_id');
    }
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }
}