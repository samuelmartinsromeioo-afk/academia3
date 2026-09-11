<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

/** Marcação de uma meta num dia — um registro por meta por dia. */
class MetaRegistro extends Model
{
    protected $table = 'nutri_meta_registros';

    protected $fillable = ['meta_id', 'data', 'concluida', 'valor'];

    protected $casts = [
        'data' => 'date',
        'concluida' => 'boolean',
        'valor' => 'float',
    ];

    public function meta()
    {
        return $this->belongsTo(Meta::class, 'meta_id');
    }
}
