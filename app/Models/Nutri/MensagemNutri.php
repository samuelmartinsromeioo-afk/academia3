<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class MensagemNutri extends Model
{
    protected $table = 'nutri_mensagens';

    protected $fillable = ['paciente_id', 'remetente', 'texto', 'lida_em'];

    protected $casts = ['lida_em' => 'datetime'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
