<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil público/próprio do personal. NUNCA incluir aqui: senha, chave_pix,
 * asaas_api_key, asaas_wallet_id, asaas_account_id ou qualquer credencial.
 */
class PersonalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => 'personal',
            'nome' => $this->nome,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'foto' => $this->foto,
            'cref' => $this->cref,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'status' => $this->status,
            'valor_secao' => $this->valor_secao !== null ? (float) $this->valor_secao : null,
            'valor_ficha' => $this->valor_ficha !== null ? (float) $this->valor_ficha : null,
            'valor_avaliacao' => $this->valor_avaliacao !== null ? (float) $this->valor_avaliacao : null,
            'precos_avaliacao' => $this->precos_avaliacao ?? [],
            'media_avaliacao' => $this->media_avaliacao,
            'pioneiro' => $this->eh_pioneiro,
        ];
    }
}
