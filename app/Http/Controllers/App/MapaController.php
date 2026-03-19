<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\cadastro\Personal;
use App\Models\cadastro\academia as Academia;

class MapaController extends Controller
{
    // Página do mapa
    public function index()
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        return view('cliente.mapa');
    }

    // Endpoint JSON com todos os pins
    public function dados()
    {
        $academias = Academia::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'nome', 'cidade', 'estado', 'endereco', 'valor_mensalidade', 'latitude', 'longitude'])
            ->map(fn($a) => [
                'tipo'      => 'academia',
                'id'        => $a->id,
                'nome'      => $a->nome,
                'endereco'  => $a->endereco ?? "$a->cidade - $a->estado",
                'info'      => 'Mensalidade: R$ ' . number_format($a->valor_mensalidade, 2, ',', '.'),
                'latitude'  => (float) $a->latitude,
                'longitude' => (float) $a->longitude,
            ]);

        $personals = Personal::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'nome', 'cidade', 'estado', 'valor_secao', 'foto', 'latitude', 'longitude'])
            ->map(fn($p) => [
                'tipo'      => 'personal',
                'id'        => $p->id,
                'nome'      => $p->nome,
                'endereco'  => "$p->cidade - $p->estado",
                'info'      => 'Valor/sessão: R$ ' . number_format($p->valor_secao, 2, ',', '.'),
                'latitude'  => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
            ]);

        return response()->json([
            'academias' => $academias,
            'personals' => $personals,
        ]);
    }
}
