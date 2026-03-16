<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\cadastro\Personal;
use App\Models\Agenda;
use App\Models\cadastro\Cliente;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; 
use Carbon\Carbon;

class PersonalController extends Controller
{
    public function create()
    {
        return view('cadastro.personal');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'          => 'required|string|max:255',
            'cep'           => 'required|string|max:9',
            'rua'           => 'required|string|max:300',
            'bairro'        => 'required|string|max:200',
            'cidade'        => 'required|string|max:200',
            'estado'        => 'required|string|max:200',
            'complemento'   => 'required|string|min:1',
            'cpf'           => 'required|unique:personals,cpf',
            'email'         => 'required|email|unique:personals,email',
            'certificado'   => 'required|file|mimes:pdf,jpg,png|max:2048',
            'foto'          => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'valor_secao'   => 'required|numeric',
            'senha'         => 'required|string|min:8|confirmed',
            'idade'         => 'required|date',
            'resultados'    => 'nullable|string',
            'avaliacao'     => 'nullable|string',
        ]);

        // pra guardar na pasta a foto do personal
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('personals', 'public');
            $dados['foto'] = $path;
        }

        // pra guardar na pasta a foto do certificado
        if ($request->hasFile('certificado')) {
            $pathCert = $request->file('certificado')->store('certificados', 'public');
            $dados['certificado'] = $pathCert;
        }

        $dados['senha'] = Hash::make($request->senha);
        $dados['avaliacao'] = $dados['avaliacao'] ?? 'Aguardando avaliação inicial';
        $dados['resultados'] = $dados['resultados'] ?? 'Nenhum resultado registrado';
        $dados['agenda'] = 'disponivel';

        Personal::create($dados);

        return redirect()->route('login.index')->with('sucesso', 'Personal cadastrado com sucesso!');
    }

    public function index(Request $request)
    {
        $id = session('personal_id');
        if (!$id) return redirect()->route('login.index');

        $dataRef = $request->query('data') ? \Carbon\Carbon::parse($request->query('data')) : now();
        $inicioSemana = $dataRef->copy()->startOfWeek();
        $fimSemana = $dataRef->copy()->endOfWeek();

        $personal = Personal::with(['agendas' => function($query) use ($inicioSemana, $fimSemana) {
            $query->whereBetween('data', [
                $inicioSemana->format('Y-m-d'),
                $fimSemana->format('Y-m-d')
            ])->orderBy('hora_inicio');
        }])->find($id);

        return view('personal.dashboard', compact('personal', 'inicioSemana', 'dataRef'));
    }

   public function storeHorario(Request $request)
{
    $request->validate([
        'data' => 'required|date',
        'hora_inicio' => 'required',
        'hora_fim' => 'required',
    ]);

    $inicio = $request->hora_inicio;
    $fim = $request->hora_fim;

    // Definição do turno de trabalho (Disponibilidade real)
    $turnoInicio = "06:00";
    $turnoFim = "22:00";

    //  Garante que o registro esteja DENTRO do turno permitido
    if ($inicio < $turnoInicio || $fim > $turnoFim) {
        return back()->withErrors([
            'horario' => "Você só está disponível para agenda entre $turnoInicio e $turnoFim. Ajuste o horário."
        ])->withInput();
    }

    //  Evita que o fim seja antes do início
    if ($inicio >= $fim) {
        return back()->withErrors([
            'horario' => "A hora de início deve ser anterior à hora de término."
        ])->withInput();
    }

    //  Evita choque de horários (Ignora registros marcados como cancelados)
    $conflito = Agenda::where('personal_id', session('personal_id'))
        ->where('data', $request->data)
        ->where('cancelado', false) // ESTA LINHA LIBERA O HORÁRIO CANCELADO
        ->where(function ($query) use ($inicio, $fim) {
            $query->where(function ($q) use ($inicio, $fim) {
                $q->where('hora_inicio', '<', $fim)
                  ->where('hora_fim', '>', $inicio);
            });
        })->exists();

    if ($conflito) {
        return back()->withErrors(['horario' => 'Já existe um compromisso ou aula agendada neste intervalo.']);
    }

    // Se passou nas regras, salva o horário na agenda
    Agenda::create([
        'personal_id' => session('personal_id'),
        'data' => $request->data,
        'hora_inicio' => $inicio,
        'hora_fim' => $fim,
        'descricao' => $request->descricao ?? 'Ocupado',
        'cancelado' => false, // Garante que a nova aula comece como ativa
    ]);

    return back()->with('success', 'Horário registrado com sucesso!');
}
    public function update(Request $request, $id)
    {
        $personal = Personal::findOrFail($id);

        $dados = $request->validate([
            'nome'        => 'required|string|max:255',
            'cep'         => 'required|string|max:9',
            'cidade'      => 'required|string',
            'valor_secao' => 'required|numeric',
            'agenda'      => 'required|in:disponivel,ocupado',
            'avaliacao'   => 'nullable|string',
            'certificado' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'foto'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Atualizar Foto
        if ($request->hasFile('foto')) {
            // Deleta a antiga se existir usando Storage
            if ($personal->foto) {
                Storage::disk('public')->delete($personal->foto);
            }
            $dados['foto'] = $request->file('foto')->store('certificados', 'public');
        }

        // Atualizar Certificado
        if ($request->hasFile('certificado')) {
            if ($personal->certificado) {
                Storage::disk('public')->delete($personal->certificado);
            }
            $dados['certificado'] = $request->file('certificado')->store('certificados', 'public');
        }

        if ($request->filled('senha')) {
            $dados['senha'] = Hash::make($request->senha);
        }

        $personal->update($dados);

        return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');
    }

    public function cancelarAula(Request $request, $id)
    {
        // 1. Busca a aula antes de qualquer coisa
        $agenda = Agenda::findOrFail($id);

        // 2. Lógica de 24 horas (Trava de Segurança)
        $dataAula = \Carbon\Carbon::parse($agenda->data . ' ' . $agenda->hora_inicio);
        $agora = \Carbon\Carbon::now();

        // Verificamos se há pelo menos 24h de antecedência
        if ($agora->diffInHours($dataAula, false) < 24) {
            return redirect()->back()->with('error', 'O cancelamento só é permitido com 24h de antecedência.');
        }

        // 3. Validação da justificativa
        // Nota: Como vamos DELETAR o registro, a justificativa serve apenas para 
        // validar a intenção do usuário no formulário, já que ela não será salva no banco.
        $request->validate([
            'justificativa' => 'required|string|min:10',
        ]);

        // 4. Apaga definitivamente do banco de dados
        // Isso libera o horário para novos agendamentos e remove das finanças
        $agenda->delete();

        return redirect()->back()->with('success', 'Aula cancelada e horário liberado com sucesso!');
    }
    //função criada para mostrar o resumo dia 
    public function getAgendaDia($data)
    {
        $agendas = Agenda::with('academia')->where('personal_id', session('personal_id'))
            ->where('data', $data)
            ->where('cancelado', false)
            ->orderBy('hora_inicio', 'asc')
            ->get();
            

        return response()->json($agendas);
    }

    public function listarAlunos()
    {
        // Busca o personal logado
        $personal = Personal::findOrFail(session('personal_id'));

        // Busca os alunos vinculados a ele
        // Se você tiver o relacionamento configurado no Model, use: $personal->alunos
        $alunos = Cliente::where('personal_id', $personal->id)->get();

        return view('personal.alunos', compact('personal', 'alunos'));
    }

   
}