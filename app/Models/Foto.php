<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Foto extends Model
{
    use HasFactory;

    protected $table = 'fotos';

    protected $fillable = [
        'fotavel_id',
        'fotavel_type',
        'path',
        'legenda',
    ];

    public function fotavel()
    {
        return $this->morphTo();
    }
}
