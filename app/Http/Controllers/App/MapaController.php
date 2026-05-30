<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Cadastro\Filial;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Academia as Academia;

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
                'cidade'    => $a->cidade,
                'estado'    => $a->estado,
                'endereco'  => $a->endereco ?? "$a->cidade - $a->estado",
                'info'      => 'Mensalidade: R$ ' . number_format($a->valor_mensalidade, 2, ',', '.'),
                'latitude'  => (float) $a->latitude,
                'longitude' => (float) $a->longitude,
            ]);

        $personals = Personal::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'aprovado')
            ->get(['id', 'nome', 'cidade', 'estado', 'valor_secao', 'foto', 'latitude', 'longitude'])
            ->map(fn($p) => [
                'tipo'      => 'personal',
                'id'        => $p->id,
                'nome'      => $p->nome,
                'cidade'    => $p->cidade,
                'estado'    => $p->estado,
                'endereco'  => "$p->cidade - $p->estado",
                'info'      => 'Valor/sessão: R$ ' . number_format($p->valor_secao ?? 0, 2, ',', '.'),
                'latitude'  => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
            ]);

        $filiais = Filial::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('academia:id,nome')
            ->get()
            ->map(fn($f) => [
                'tipo'          => 'filial',
                'id'            => $f->id,
                'nome'          => $f->nome,
                'academia_nome' => $f->academia->nome ?? '',
                'cidade'        => $f->cidade,
                'estado'        => $f->estado,
                'endereco'      => "{$f->rua}, {$f->bairro} — {$f->cidade}/{$f->estado}",
                'info'          => 'Filial de ' . ($f->academia->nome ?? 'Academia'),
                'latitude'      => (float) $f->latitude,
                'longitude'     => (float) $f->longitude,
            ]);

        return response()->json([
            'academias' => $academias,
            'personals' => $personals,
            'filiais'   => $filiais,
        ]);
    }
}
