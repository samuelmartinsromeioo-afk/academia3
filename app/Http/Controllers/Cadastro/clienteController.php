<?php

namespace App\Http\Controllers\Cadastro;


use App\Http\Controllers\Controller;
use App\Models\cadastro\Cliente;
use App\Models\cadastro\Personal;
use App\Models\cadastro\academia;
use App\Models\Agenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index()
{
    $id = session('cliente_id');
    if (!$id) return redirect()->route('login.index');

    $cliente = \App\Models\cadastro\Cliente::find($id);
    $personals = \App\Models\cadastro\Personal::all();
    $academias = \App\Models\cadastro\academia::all();

   $meusAgendamentos = \App\Models\Agenda::where('cliente_id', $id)
                    ->with(['personal', 'academia'])
                    ->orderBy('data', 'asc')
                    ->get();


    $horariosDisponiveis = collect();
    $diasParaFrente = 7; // Vamos mostrar os próximos 7 dias para não carregar demais

    // DEFINA AQUI O HORÁRIO PADRÃO DE TRABALHO (Já que não tem no banco)
    $inicioPadrao = '08:00'; 
    $fimPadrao = '18:00';

    foreach ($personals as $personal) {
        // Pegamos tudo que já está ocupado na agenda desse personal
        $ocupados = \App\Models\Agenda::where('personal_id', $personal->id)
            ->where('data', '>=', now()->format('Y-m-d'))
            ->where('cancelado', false)
            ->get()
            ->map(function($ag) {
                // Usando 'horario_inicio' como você especificou
                return $ag->data . ' ' . date('H:i', strtotime($ag->horario_inicio));
            })->toArray();

        // Gerar os horários vazios baseados no horário padrão
        for ($i = 0; $i < $diasParaFrente; $i++) {
            $dataLoop = now()->addDays($i)->format('Y-m-d');
            
            $horaAtual = strtotime($inicioPadrao);
            $horaLimite = strtotime($fimPadrao);

            while ($horaAtual < $horaLimite) {
                $formatada = date('H:i', $horaAtual);
                $chave = $dataLoop . ' ' . $formatada;

                // Se NÃO estiver ocupado, adicionamos na lista de "Disponíveis"
                if (!in_array($chave, $ocupados)) {
                    $horariosDisponiveis->push((object)[
                        'personal_id' => $personal->id,
                        'data' => $dataLoop,
                        'horario_inicio' => $formatada, // Nome igual ao da sua tabela
                        'horario_fim' => date('H:i', strtotime('+1 hour', $horaAtual))
                    ]);
                }
                
                $horaAtual = strtotime('+1 hour', $horaAtual);
            }
        }
    }

    return view('cliente.index', compact('cliente', 'personals', 'meusAgendamentos', 'horariosDisponiveis', 
    'academias'));
}

    public function update(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return redirect()->back()->with('error', 'Cliente não encontrado.');
        }

        // Validação básica (opcional, mas recomendada)
        $data = $request->all();

        // Se o usuário preencheu a senha, temos que criptografar
        if ($request->filled('senha')) {
            $data['senha'] = Hash::make($request->senha);
        } else {
            // Se não preencheu, removemos do array para não salvar a senha em branco
            unset($data['senha']);
        }

        // Atualiza todos os campos de uma vez (nome, email, cep, etc)
        $cliente->update($data);

        return redirect()->route('cliente.index')->with('success', 'Perfil atualizado com sucesso!');
    }
  public function create()
    {

        return view('cadastro.cliente'); // ajuste para o nome da sua view
     
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clientes,email',
            'senha' => 'required|string|min:6|max:255',
            'cep'=>'required|string|max:9',
            'rua'=>'required|string|max:300',
            'bairro'=>'required|string|max:200',
            'cidade'=>'required|string|max:200',
            'estado'=>'required|string|max:200',
            'complemento'=>'required|string|min:1',
            'altura' => 'required|numeric|min:0',
            'peso' => 'required|numeric|min:0',
            'idade' => 'required|date',
            'sexo' => 'required|in:Masculino,Feminino,Outro',
            'frequencia_semanal' => 'required|integer|min:0|max:7',
            'resumo_objetivo' => 'required|string|max:255',
            'condicao_clinica' => 'nullable|string|max:255'
        ]);
        
        // Criptografar senha
        $validated['senha'] = Hash::make($validated['senha']);
        
        Cliente::create($validated);

        return redirect()->route('login.index')
       ->with('success', 'Cliente cadastrado com sucesso!');
    }
    
    public function reservarHorario(Request $request)
{
    $clienteId = session('cliente_id');

    if (!$clienteId) {
        return redirect()->route('login.index')->with('erro', 'Sessão expirada.');
    }

    $request->validate([
        'personal_id' => 'required|exists:personals,id',
        'academia_id' => 'required|exists:academias,id',
        'data' => 'required|date',
        'horario_inicio' => 'required',
        'horario_fim' => 'required'
    ]);

    \App\Models\Agenda::create([
        'cliente_id'  => $clienteId,
        'personal_id' => $request->personal_id,
        'academia_id' => $request->academia_id,
        'data'        => $request->data,
        'hora_inicio' => $request->horario_inicio,
        'hora_fim'    => $request->horario_fim,
        'cancelado'   => false
    ]);

    return redirect()->back()->with('sucesso', 'Horário agendado com sucesso!');
}
public function escolherAcademia(Request $request)
{
    $request->validate([
        'academia_id' => 'required|exists:academias,id'
    ]);

    session(['academia_id' => $request->academia_id]);

    return redirect()->route('cliente.index');
}

}
