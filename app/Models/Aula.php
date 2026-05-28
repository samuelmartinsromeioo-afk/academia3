<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
     protected $fillable = [
        'cliente_nome',
        'tipo_pacote',
        'status',
        'personal_whatsapp'
    ];
 
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
