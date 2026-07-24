<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil da academia para a API. NUNCA incluir aqui: senha, chave_pix,
 * asaas_api_key, asaas_wallet_id, asaas_account_id ou qualquer credencial.
 */
class AcademiaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => 'academia',
            'nome' => $this->nome,
            'email' => $this->email,
            'cnpj' => $this->cnpj,
            'descricao' => $this->descricao,
            'endereco' => $this->endereco,
            'cep' => $this->cep,
            'rua' => $this->rua,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'complemento' => $this->complemento,
            'quantidade_alunos' => $this->quantidade_alunos,
            'tipos_aulas' => $this->tipos_aulas,
            'infraestrutura' => $this->infraestrutura,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
        ];
    }
}
