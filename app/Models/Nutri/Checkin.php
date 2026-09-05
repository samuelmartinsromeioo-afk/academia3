<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class Checkin extends Model
{
    protected $table = 'nutri_checkins';

    protected $fillable = ['paciente_id', 'data', 'peso', 'adesao', 'humor', 'comentario'];

    protected $casts = [
        'data' => 'date',
        'peso' => 'float',
        'adesao' => 'integer',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
