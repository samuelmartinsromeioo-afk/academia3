<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
