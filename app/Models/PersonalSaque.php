<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalSaque extends Model
{
    use HasFactory;

    protected $table = 'personal_saques';

    protected $fillable = [
        'personal_id',
        'asaas_transfer_id',
        // Identifica a transferência no Asaas ("personal_saque:12"). O webhook de
        // validação de saque é fail-closed, então toda transferência que criamos
        // precisa ser reconhecível — ver AsaasWebhookController::validarSaque().
        'external_reference',
        'value',
        'status',
        'transaction_receipt_url',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }
}
