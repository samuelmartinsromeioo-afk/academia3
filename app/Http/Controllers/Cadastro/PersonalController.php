<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\cadastro\Personal;
use App\Models\Agenda;
use App\Models\cadastro\Cliente;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
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
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
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
            $dados['foto'] = $request->file('foto')->store('personals', 'public');
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
        $request->validate([
            'justificativa' => 'required|string|min:10',
        ]);
 
        // 4. Guarda dados antes de deletar para usar no e-mail
        $personal = \App\Models\cadastro\Personal::find(session('personal_id'));
        $cliente  = $agenda->cliente_id ? \App\Models\cadastro\Cliente::find($agenda->cliente_id) : null;
        $dadosEmail = [
            'personal_nome' => $personal ? $personal->nome : 'Personal',
            'cliente_nome'  => $cliente ? $cliente->nome : null,
            'data'          => $agenda->data,
            'hora_inicio'   => $agenda->hora_inicio,
            'hora_fim'      => $agenda->hora_fim,
            'justificativa' => $request->justificativa,
        ];
 
        // 5. Apaga definitivamente do banco de dados
        $agenda->delete();
 
        // 6. Notifica o cliente por e-mail (se existir)
        if ($cliente && $cliente->email) {
            try {
                Mail::send('emails.aula-cancelada', $dadosEmail, function ($message) use ($cliente) {
                    $message->to($cliente->email)
                            ->subject('❌ Sua aula foi cancelada - FitSys');
                });
            } catch (\Exception $e) {
                // Não bloqueia o fluxo se o e-mail falhar
            }
        }
 
        return redirect()->back()->with('success', 'Aula cancelada e horário liberado com sucesso!');
    }
    //função criada para mostrar o resumo dia 
    public function getAgendaDia($data)
    {
        // Adicionamos as relações. 
        // Certifique-se de que no Model Agenda existe public function cliente()
        $agendas = Agenda::with(['academia', 'cliente']) 
            ->where('personal_id', session('personal_id'))
            ->where('data', $data)
            ->where('cancelado', false)
            ->orderBy('hora_inicio', 'asc')
            ->get();
 
        // Debug opcional: se quiser testar se o nome está vindo, descomente a linha abaixo temporariamente:
        // dd($agendas->toArray()); 
 
        return response()->json($agendas);
    }
    public function listarAlunos()
    {
        // Busca o personal logado
        $personal = Personal::findOrFail(session('personal_id'));
 
        // Busca os alunos vinculados a ele
        // Se você tiver o relacionamento configurado no Model, use: $personal->alunos
        $alunos = Cliente::where('personal_id', $personal->id)->get();
 
        return view('personal.clientes', compact('personal', 'alunos'));
    }
 
    public function meusAlunos()
{
    // Pega o ID do personal logado na sessão
    $personalId = session('personal_id'); 
 
    // Busca os agendamentos e os dados dos clientes vinculados
    $meusAlunos = Agenda::with('cliente')
        ->where('personal_id', $personalId)
        ->get()
        ->unique('cliente_id'); 
 
    return view('personal.meus-alunos', compact('meusAlunos'));
}
 
    // Cancela todos os agendamentos de um dia inteiro
    public function cancelarDia(Request $request)
    {
        $request->validate([
            'data' => 'required|date',
        ]);
 
        $agendamentos = Agenda::where('personal_id', session('personal_id'))
            ->where('data', $request->data)
            ->where('cancelado', false)
            ->get();
 
        if ($agendamentos->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum compromisso encontrado nesta data.');
        }
 
        $cancelados = 0;
        foreach ($agendamentos as $ag) {
            $dataAula = \Carbon\Carbon::parse($ag->data . ' ' . $ag->hora_inicio);
            if (\Carbon\Carbon::now()->diffInHours($dataAula, false) >= 24) {
                $ag->delete();
                $cancelados++;
            }
        }
 
        if ($cancelados === 0) {
            return redirect()->back()->with('error', 'Nenhum horário pode ser cancelado. Verifique a regra de 24h de antecedência.');
        }
 
        return redirect()->back()->with('success', "Dia cancelado! $cancelados horário(s) liberado(s).");
    }
 
    // Bloqueia um horário fixo em todos os dias pelos próximos X dias
    public function bloquearHorarioFixo(Request $request)
    {
        $request->validate([
            'hora_inicio' => 'required',
            'hora_fim'    => 'required',
            'dias'        => 'required|integer|min:1|max:90',
            'descricao'   => 'nullable|string|max:255',
        ]);
 
        $inicio = $request->hora_inicio;
        $fim    = $request->hora_fim;
 
        if ($inicio >= $fim) {
            return back()->withErrors(['horario' => 'A hora de início deve ser anterior à hora de término.']);
        }
 
        $criados   = 0;
        $conflitos = 0;
 
        for ($i = 0; $i < $request->dias; $i++) {
            $data = \Carbon\Carbon::now()->addDays($i)->format('Y-m-d');
 
            $conflito = Agenda::where('personal_id', session('personal_id'))
                ->where('data', $data)
                ->where('cancelado', false)
                ->where(function ($q) use ($inicio, $fim) {
                    $q->where('hora_inicio', '<', $fim)
                      ->where('hora_fim', '>', $inicio);
                })->exists();
 
            if (!$conflito) {
                Agenda::create([
                    'personal_id' => session('personal_id'),
                    'data'        => $data,
                    'hora_inicio' => $inicio,
                    'hora_fim'    => $fim,
                    'descricao'   => $request->descricao ?? 'Horário Fixo Bloqueado',
                    'cancelado'   => false,
                ]);
                $criados++;
            } else {
                $conflitos++;
            }
        }
 
        $msg = "Bloqueio fixo aplicado em $criados dia(s).";
        if ($conflitos > 0) {
            $msg .= " $conflitos dia(s) ignorado(s) por conflito de horário.";
        }
 
        return redirect()->back()->with('success', $msg);
    }
}