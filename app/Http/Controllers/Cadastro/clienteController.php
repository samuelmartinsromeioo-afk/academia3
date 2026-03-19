<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\cadastro\Cliente;
use App\Models\cadastro\Personal;
use App\Models\cadastro\academia as Academia; // Ajustado para evitar erro de caixa alta
use App\Models\Agenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{
    public function index()
    {
        $id = session('cliente_id');
        if (!$id) return redirect()->route('login.index');

        $cliente = Cliente::find($id);
        $personals = Personal::all();
        $academias = Academia::all();

        $meusAgendamentos = Agenda::where('cliente_id', $id)
            ->with(['personal', 'academia'])
            ->orderBy('data', 'asc')
            ->get();

        $horariosDisponiveis = collect();
        $diasParaFrente = 7; 
        $inicioPadrao = '08:00';
        $fimPadrao = '18:00';

        foreach ($personals as $personal) {
            $ocupados = Agenda::where('personal_id', $personal->id)
                ->where('data', '>=', now()->format('Y-m-d'))
                ->where('cancelado', false)
                ->get()
                ->map(function ($ag) {
                    return $ag->data . ' ' . date('H:i', strtotime($ag->hora_inicio));
                })->toArray();

            for ($i = 0; $i < $diasParaFrente; $i++) {
                $dataLoop = now()->addDays($i)->format('Y-m-d');
                $horaAtual = strtotime($inicioPadrao);
                $horaLimite = strtotime($fimPadrao);

                while ($horaAtual < $horaLimite) {
                    $formatada = date('H:i', $horaAtual);
                    $chave = $dataLoop . ' ' . $formatada;

                    if (!in_array($chave, $ocupados)) {
                        $horariosDisponiveis->push((object)[
                            'personal_id' => $personal->id,
                            'data' => $dataLoop,
                            'horario_inicio' => $formatada,
                            'horario_fim' => date('H:i', strtotime('+1 hour', $horaAtual))
                        ]);
                    }
                    $horaAtual = strtotime('+1 hour', $horaAtual);
                }
            }
        }

        return view('cliente.index', compact('cliente', 'personals', 'meusAgendamentos', 'horariosDisponiveis', 'academias'));
    }

    // MÉTODO UPDATE CORRIGIDO
    public function update(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return redirect()->back()->with('error', 'Cliente não encontrado.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clientes,email,'.$id,
            'sexo' => 'required|in:Masculino,Feminino,Outro,masculino,feminino,outro',
            'cep' => 'nullable|string|max:9',
            'altura' => 'nullable|numeric',
            'peso' => 'nullable|numeric',
            'rua' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'complemento' => 'nullable|string|max:255',
        ]);

        //   dados validados
        $data = $request->all();

        //  Ajusta o sexo para o que está na sua Migration 
        $data['sexo'] = strtolower($request->sexo); 

        //  Tratamento da senha
        if ($request->filled('senha')) {
            $data['senha'] = Hash::make($request->senha);
        } else {
            unset($data['senha']);
        }

        //  Salva no banco
        $cliente->update($data);

        //  Retorna com a mensagem de sucesso (ajustado para 'success' que é o que você usa na view)
        return redirect()->route('cliente.index')->with('success', 'Perfil atualizado com sucesso!');
    }

    public function create()
    {
        return view('cadastro.cliente');
    }
public function store(Request $request)
{
    $validated = $request->validate([
        'nome'        => 'required|string|max:255',
        'email'       => 'required|email|max:255|unique:clientes,email',
        'senha'       => 'required|string|min:6|max:255',
        'sexo'        => 'required|in:Masculino,Feminino,Outro,masculino,feminino,outro',
        'cep'         => 'required|string|max:9',
        'rua'         => 'nullable|string|max:255',
        'bairro'      => 'nullable|string|max:255',
        'cidade'      => 'nullable|string|max:255',
        'estado'      => 'nullable|string|max:255',
        'complemento' => 'nullable|string|max:255',
        'altura'      => 'nullable|numeric',
        'peso'        => 'nullable|numeric',
    ]);

    $validated['sexo'] = strtolower($request->sexo);
    $validated['senha'] = Hash::make($validated['senha']);

    Cliente::create($validated);

    return redirect()->route('login.index')->with('success', 'Cliente cadastrado com sucesso!');
}

    public function reservarHorario(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index')->with('erro', 'Sessão expirada.');

        $request->validate([
            'personal_id' => 'required|exists:personals,id',
            'academia_id' => 'nullable|exists:academias,id',
            'data' => 'required|date',
            'horario_inicio' => 'required',
            'horario_fim' => 'required'
        ]);

        Agenda::create([
            'cliente_id'  => $clienteId,
            'personal_id' => $request->personal_id,
            'academia_id' => $request->academia_id ?? null,
            'data'        => $request->data,
            'hora_inicio' => $request->horario_inicio,
            'hora_fim'    => $request->horario_fim,
            'cancelado'   => false
        ]);

        return redirect()->back()->with('sucesso', 'Horário agendado com sucesso!');
    }

    public function contratarAcademia(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index')->with('erro', 'Você precisa estar logado.');

        $request->validate([
            'academia_id' => 'required|exists:academias,id'
        ]);

        $cliente = Cliente::find($clienteId);
        $cliente->update(['academia_id' => $request->academia_id]);

        return redirect()->back()->with('sucesso', 'Academia contratada com sucesso!');
    }
}