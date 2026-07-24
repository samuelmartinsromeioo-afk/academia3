<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClienteResource;
use App\Http\Resources\PersonalResource;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use Illuminate\Http\Request;

/**
 * Dashboard do personal trainer no app mobile: perfil e clientes vinculados.
 */
class PersonalController extends Controller
{
    use ResolvesApiUser;

    // GET /api/v1/personal/profile
    public function profile(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        return response()->json([
            'personal' => new PersonalResource($personal),
            'financeiro_mes' => $personal->calcularFinanceiroMes(),
        ]);
    }

    // GET /api/v1/personal/clientes — clientes com aulas ativas na agenda do personal.
    public function clientes(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $clienteIds = Agenda::where('personal_id', $personal->id)
            ->where('cancelado', false)
            ->whereNotNull('cliente_id')
            ->distinct()
            ->pluck('cliente_id');

        $clientes = Cliente::whereIn('id', $clienteIds)
            ->orderBy('nome')
            ->paginate(20);

        return ClienteResource::collection($clientes);
    }
}
