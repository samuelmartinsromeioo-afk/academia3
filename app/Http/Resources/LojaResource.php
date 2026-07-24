<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil da loja para a API. NUNCA incluir aqui: senha, chave_pix,
 * asaas_api_key, asaas_wallet_id, asaas_account_id ou qualquer credencial.
 */
class LojaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => 'loja',
            'nome' => $this->nome,
            'email' => $this->email,
            'cnpj' => $this->cnpj,
            'whatsapp' => $this->whatsapp,
            'descricao' => $this->descricao,
            'logo' => $this->logo,
            'endereco' => $this->endereco,
            'cep' => $this->cep,
            'rua' => $this->rua,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'complemento' => $this->complemento,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
        ];
    }
}
