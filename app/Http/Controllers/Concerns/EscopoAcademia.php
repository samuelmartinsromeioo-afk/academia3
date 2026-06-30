<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Cadastro\Cliente;
use Illuminate\Database\Eloquent\Builder;

/**
 * Escopo de filial para as áreas da academia.
 *
 * A academia loga sempre com `academia_id` na sessão. Se for uma SUBCONTA de
 * filial, a sessão também tem `filial_id` — e a partir daí ela só enxerga os
 * alunos (e dados derivados) daquela filial. A conta principal (sem filial_id)
 * enxerga todos os alunos da academia.
 */
trait EscopoAcademia
{
    protected function academiaId(): ?int
    {
        return session('academia_id') ? (int) session('academia_id') : null;
    }

    protected function filialId(): ?int
    {
        return session('filial_id') ? (int) session('filial_id') : null;
    }

    /** A sessão atual é a subconta de uma filial (acesso restrito)? */
    protected function ehSubcontaFilial(): bool
    {
        return $this->filialId() !== null;
    }

    /** A sessão atual é a conta principal da academia (acesso total)? */
    protected function ehAcademiaPrincipal(): bool
    {
        return $this->academiaId() !== null && $this->filialId() === null;
    }

    /**
     * Query base dos alunos visíveis para a sessão atual: todos da academia
     * (principal) ou só os da filial logada (subconta).
     */
    protected function clientesVisiveis(): Builder
    {
        $query = Cliente::where('academia_id', $this->academiaId());

        if ($this->ehSubcontaFilial()) {
            $query->where('filial_id', $this->filialId());
        }

        return $query;
    }

    /** Retorna o aluno se a sessão atual puder acessá-lo; senão, null. */
    protected function clienteAcessivel($clienteId): ?Cliente
    {
        return $this->clientesVisiveis()->where('id', $clienteId)->first();
    }
}
