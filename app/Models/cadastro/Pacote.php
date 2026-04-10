<?php

namespace App\Models\cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pacote extends Model
{
    protected $fillable = ['personal_id', 'frequencia', 'valor_mensal'];
    use HasFactory;
    
}
