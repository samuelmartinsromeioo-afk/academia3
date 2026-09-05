<?php

namespace App\Http\Controllers\Nutri\Concerns;

use App\Models\Cadastro\Personal;
use App\Models\Nutri\Paciente;
use App\Models\Nutri\PlanoAlimentar;

/**
 * Resolve o nutricionista logado e garante que ele só acesse os próprios
 * pacientes/planos. Usado por todos os controllers do módulo de nutrição.
 */
trait ResolveNutri
{
    protected function nutri(): Personal
    {
        // Preenchido pelo middleware check.nutri; fallback à sessão por segurança.
        return request()->attributes->get('nutri')
            ?? Personal::findOrFail(session('personal_id'));
    }

    protected function pacienteDoNutri(int $id): Paciente
    {
        return Paciente::where('id', $id)
            ->where('personal_id', $this->nutri()->id)
            ->firstOrFail();
    }

    protected function planoDoNutri(int $id): PlanoAlimentar
    {
        return PlanoAlimentar::where('id', $id)
            ->where('personal_id', $this->nutri()->id)
            ->firstOrFail();
    }
}
