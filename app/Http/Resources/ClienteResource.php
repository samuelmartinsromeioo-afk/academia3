<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => 'cliente',
            'nome' => $this->nome,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'foto' => $this->foto,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'idade' => $this->idade,
            'sexo' => $this->sexo,
            'altura' => $this->altura,
            'peso' => $this->peso,
            'resumo_objetivo' => $this->resumo_objetivo,
            // Presencial | Online | null. O app guarda o `user` do /me em
            // contexto e usa isto para abrir a vitrine já no filtro que o aluno
            // escolheu no cadastro, sem uma chamada extra ao /perfil.
            'modalidade_preferida' => $this->modalidade_preferida,
            // Academia contratada: o app usa isso para exibir a aba "Minha Academia".
            'academia_id' => $this->academia_id,
            'plano_ativo' => (bool) $this->plano_ativo,
            'studio_plano_ativo' => (bool) $this->studio_plano_ativo,
        ];
    }
}
