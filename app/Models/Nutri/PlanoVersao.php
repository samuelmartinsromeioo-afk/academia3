<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class PlanoVersao extends Model
{
    protected $table = 'nutri_plano_versoes';

    public $timestamps = false;

    protected $fillable = ['plano_id', 'versao', 'snapshot', 'origem', 'criado_em'];

    protected $casts = [
        'snapshot' => 'array',
        'criado_em' => 'datetime',
    ];

    public function plano()
    {
        return $this->belongsTo(PlanoAlimentar::class, 'plano_id');
    }
}
