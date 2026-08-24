<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cadastro\Personal;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Presenca;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            'cpf'           => ['required', 'unique:personals,cpf', function ($attribute, $value, $fail) {
                if (!$this->validarCPF($value)) {
                    $fail('O CPF informado é inválido.');
                }
            }],
            'email'         => 'required|email|unique:personals,email',
            'cref'          => 'required|string|max:30',
            'foto'          => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'valor_secao'   => 'required|numeric',
            'senha'         => 'required|string|min:8|confirmed',
            'idade'         => 'required|date',
            'resultados'    => 'nullable|string',
            'avaliacao'     => 'nullable|string',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'academias' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('personals', 'public');
            $dados['foto'] = $path;
        }

        $dados['senha']     = Hash::make($request->senha);
        $dados['avaliacao'] = $dados['avaliacao'] ?? 'Aguardando avaliação inicial';
        $dados['resultados'] = $dados['resultados'] ?? 'Nenhum resultado registrado';
        $dados['status']    = 'pendente';

        if ($request->filled('whatsapp')) {
            $dados['whatsapp'] = $request->whatsapp;
        }

        $personal = Personal::create($dados);

        // Marca o personal como pioneiro se estiver entre os 100 primeiros do estado.
        $personal->definirPosicaoPioneiro();

        $this->criarSubcontaAsaas($personal);

        return redirect()->route('login.index')
            ->with('sucesso', 'Personal cadastrado com sucesso! Aguarde a aprovação do administrador.')
            ->with('fb_event', ['event' => 'CompleteRegistration', 'params' => ['content_name' => 'Personal', 'status' => 'pendente']]);
    }

    public function index(Request $request)
    {
        $id = session('personal_id');
        if (!$id) return redirect()->route('login.index');

        $personal = Personal::find($id);

        if (!$personal || $personal->status !== 'aprovado') {
            session()->forget('personal_id');
            return redirect()->route('login.index')->with('error', '⏳ Seu cadastro não foi aprovado ainda pelo administrador.');
        }

        $dataRef = $request->query('data') ? Carbon::parse($request->query('data')) : now();
        $inicioSemana = $dataRef->copy()->startOfWeek(Carbon::SUNDAY);
        $fimSemana = $dataRef->copy()->endOfWeek(Carbon::SUNDAY);

        $personal = Personal::with(['fotos', 'agendas' => function ($query) use ($inicioSemana, $fimSemana) {
            $query->whereRaw("DATE_FORMAT(data, '%Y-%m-%d') BETWEEN ? AND ?", [
                $inicioSemana->format('Y-m-d'),
                $fimSemana->format('Y-m-d')
            ])->where('cancelado', false)->orderBy('hora_inicio');
        }])->find($id);

        $resultado = $this->calcularFinanceiroMes($id);

        return view('personal.dashboard', compact('personal', 'inicioSemana', 'dataRef', 'resultado'));
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

        $turnoInicio = "06:00";
        $turnoFim = "22:00";

        if ($inicio < $turnoInicio || $fim > $turnoFim) {
            return back()->withErrors([
                'horario' => "Você só está disponível para agenda entre $turnoInicio e $turnoFim. Ajuste o horário."
            ])->withInput();
        }

        if ($inicio >= $fim) {
            return back()->withErrors([
                'horario' => "A hora de início deve ser anterior à hora de término."
            ])->withInput();
        }

        $conflito = Agenda::where('personal_id', session('personal_id'))
            ->where('data', $request->data)
            ->where('cancelado', false)
            ->where(function ($query) use ($inicio, $fim) {
                $query->where(function ($q) use ($inicio, $fim) {
                    $q->where('hora_inicio', '<', $fim)
                        ->where('hora_fim', '>', $inicio);
                });
            })->exists();

        if ($conflito) {
            return back()->withErrors(['horario' => 'Já existe um compromisso ou aula agendada neste intervalo.']);
        }

        Agenda::create([
            'personal_id' => session('personal_id'),
            'data' => $request->data,
            'hora_inicio' => $inicio,
            'hora_fim' => $fim,
            'descricao' => $request->descricao ?? 'Ocupado',
            'cancelado' => false,
            'tipo_aula' => 'bloqueio',
        ]);

        return back()->with('success', 'Horário registrado com sucesso!');
    }

    public function update(Request $request, $id)
    {
        if ((int)$id !== (int)session('personal_id')) {
            abort(403);
        }

        $personal = Personal::findOrFail($id);

        $dados = $request->validate([
            'nome'        => 'required|string|max:255',
            'cep'         => 'required|string|max:9',
            'cidade'      => 'required|string',
            'aceita_termos_update' => 'required|accepted',
            'valor_secao'  => 'required|numeric',
            'valor_ficha'  => 'nullable|numeric|min:0',
            'avaliacao'    => 'nullable|string',
            'foto'         => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'senha'        => 'nullable|string|min:8|confirmed',
            'chave_pix'    => 'nullable|string|max:255',
            'academias'    => 'nullable|string|max:2000',
        ], [
            'aceita_termos_update.required' => 'Você deve concordar com os Termos de Uso',
            'aceita_termos_update.accepted' => 'Você deve concordar com os Termos de Uso',
        ]);

        if ($request->hasFile('foto')) {
            if ($personal->foto) {
                Storage::disk('public')->delete($personal->foto);
            }
            $dados['foto'] = $request->file('foto')->store('personals', 'public');
        }

        if ($request->filled('senha')) {
            $dados['senha'] = Hash::make($request->senha);
        }

        $dados['data_aceicao_termos_atualizacao'] = Carbon::now();
        $dados['ip_aceicao_termos_atualizacao'] = $request->ip();

        unset($dados['aceita_termos_update']);

        $personal->update($dados);

        return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');
    }

    public function cancelarAula(Request $request, $id)
    {
        try {
            $agenda = Agenda::findOrFail($id);

            if ($agenda->personal_id !== session('personal_id')) {
                abort(403);
            }

            $dataStr = $agenda->data instanceof \Carbon\Carbon ?
                $agenda->data->format('Y-m-d') :
                $agenda->data;

            $dataAula = Carbon::parse($dataStr . ' ' . $agenda->hora_inicio);
            $agora = Carbon::now();
            $diffHoras = $agora->diffInHours($dataAula);

            Log::info("Cancelamento - ID: $id | Aula: $dataAula | Agora: $agora | Diff: $diffHoras horas");

            if ($diffHoras < 24) {
                return redirect()->back()->with('error', "O cancelamento só é permitido com 24h de antecedência. Faltam " . $diffHoras . " horas.");
            }

            $request->validate([
                'justificativa' => 'required|string|min:10',
            ]);

            $personal = Personal::find(session('personal_id'));
            $cliente = $agenda->cliente_id ? Cliente::find($agenda->cliente_id) : null;

            $dadosEmail = [
                'personal_nome' => $personal ? $personal->nome : 'Personal',
                'cliente_nome'  => $cliente ? $cliente->nome : null,
                'data'          => $dataStr,
                'hora_inicio'   => $agenda->hora_inicio,
                'hora_fim'      => $agenda->hora_fim,
                'justificativa' => $request->justificativa,
            ];

            $agenda->delete();

            if ($cliente && $cliente->email) {
                try {
                    Mail::send('emails.aula-cancelada', $dadosEmail, function ($message) use ($cliente) {
                        $message->to($cliente->email)
                            ->subject('❌ Sua aula foi cancelada - SnrFit');
                    });
                } catch (\Exception $e) {
                    // Não bloqueia o fluxo
                }
            }

            Log::info("Aula cancelada com sucesso - ID: $id");
            return redirect()->back()->with('success', 'Aula cancelada com sucesso!');
        } catch (\Exception $e) {
            Log::error("Erro ao cancelar aula: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao cancelar aula: ' . $e->getMessage());
        }
    }

    public function getAgendaDia($data)
    {
        $agendas = Agenda::with(['academia', 'cliente'])
            ->where('personal_id', session('personal_id'))
            ->where('data', $data)
            ->where('cancelado', false)
            ->orderBy('hora_inicio', 'asc')
            ->get();

        // ✅ FORMATA A DATA CORRETAMENTE
        $agendas = $agendas->map(function ($agenda) {
            return [
                'id'           => $agenda->id,
                'data'         => $agenda->data->format('Y-m-d'),
                'hora_inicio'  => $agenda->hora_inicio,
                'hora_fim'     => $agenda->hora_fim,
                'descricao'    => $agenda->descricao,
                'tipo_aula'    => $agenda->tipo_aula,
                'cliente'      => $agenda->cliente,
                'academia'     => $agenda->academia,
                'academia_nome' => $agenda->academia_nome,
            ];
        });

        return response()->json($agendas);
    }

    public function listarAlunos()
    {
        $personal = Personal::findOrFail(session('personal_id'));
        $alunos = Cliente::where('personal_id', $personal->id)->get();

        return view('personal.clientes', compact('personal', 'alunos'));
    }

    public function meusAlunos()
    {
        $personalId = session('personal_id');

        $meusAlunos = Agenda::with('cliente')
            ->where('personal_id', $personalId)
            ->where('cancelado', false)
            ->get()
            ->unique('cliente_id');

        return view('personal.meus-alunos', compact('meusAlunos'));
    }

    // ==========================================
    // FREQUÊNCIA DOS ALUNOS
    // ==========================================

    /** Alunos do personal (clientes com agenda não cancelada ou vinculados). */
    private function alunosDoPersonal(int $personalId)
    {
        $ids = Agenda::where('personal_id', $personalId)
            ->where('cancelado', false)
            ->whereNotNull('cliente_id')
            ->pluck('cliente_id')
            ->merge(Cliente::where('personal_id', $personalId)->pluck('id'))
            ->unique();

        return Cliente::whereIn('id', $ids)->orderBy('nome')->get();
    }

    /** Classificação de frequência a partir da taxa de presença. */
    private function classificarFrequencia(int $total, int $presentes): array
    {
        if ($total === 0) {
            return ['key' => 'sem_registro', 'label' => 'Sem registros', 'cor' => '#a0a0a0', 'taxa' => null];
        }
        $taxa = $presentes / $total;
        if ($taxa >= 0.8) return ['key' => 'frequente', 'label' => 'Frequente',      'cor' => '#00ff88', 'taxa' => $taxa];
        if ($taxa >= 0.5) return ['key' => 'medio',     'label' => 'Mais ou menos',  'cor' => '#ffaa00', 'taxa' => $taxa];
        return            ['key' => 'ausente',   'label' => 'Pouco frequente', 'cor' => '#ff4444', 'taxa' => $taxa];
    }

    public function frequencia()
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $personal = Personal::findOrFail($personalId);
        $alunos   = $this->alunosDoPersonal($personalId);

        $presencas = Presenca::where('personal_id', $personalId)->get()->groupBy('cliente_id');

        $stats = [];
        foreach ($alunos as $aluno) {
            $regs      = $presencas->get($aluno->id, collect());
            $total     = $regs->count();
            $presentes = $regs->where('presente', true)->count();
            $stats[$aluno->id] = [
                'total'         => $total,
                'presentes'     => $presentes,
                'faltas'        => $total - $presentes,
                'classificacao' => $this->classificarFrequencia($total, $presentes),
            ];
        }

        return view('personal.frequencia', compact('personal', 'alunos', 'stats'));
    }

    public function frequenciaAluno(Request $request, $clienteId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $personal = Personal::findOrFail($personalId);

        $alunoIds = $this->alunosDoPersonal($personalId)->pluck('id');
        if (!$alunoIds->contains((int) $clienteId)) {
            abort(403);
        }

        $cliente = Cliente::findOrFail($clienteId);

        $mes = $request->query('mes', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) $mes = now()->format('Y-m');

        $todas     = Presenca::where('personal_id', $personalId)->where('cliente_id', $clienteId)
            ->orderByDesc('data')->get();
        $total     = $todas->count();
        $presentes = $todas->where('presente', true)->count();
        $faltas    = $total - $presentes;
        $classificacao = $this->classificarFrequencia($total, $presentes);

        $diasSemana = [0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'];

        // Faltas agrupadas por dia da semana
        $faltasPorDia = $todas->where('presente', false)
            ->groupBy(fn ($p) => $p->data->dayOfWeek)
            ->map->count();

        $diaMaisFalta = null;
        if ($faltasPorDia->isNotEmpty()) {
            $maxDow = $faltasPorDia->sortDesc()->keys()->first();
            $diaMaisFalta = ['nome' => $diasSemana[$maxDow], 'qtd' => $faltasPorDia[$maxDow]];
        }

        $faltasMes = $todas->where('presente', false)
            ->filter(fn ($p) => $p->data->format('Y-m') === now()->format('Y-m'))
            ->count();

        // Dias de aula agendados no mês escolhido (para marcação rápida)
        $diasAgenda = Agenda::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('cancelado', false)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->whereYear('data', substr($mes, 0, 4))
            ->whereMonth('data', substr($mes, 5, 2))
            ->get()
            ->map(fn ($a) => $a->data->format('Y-m-d'))
            ->unique()->sort()->values();

        $presencasPorData = $todas->keyBy(fn ($p) => $p->data->format('Y-m-d'));

        $mesesDisponiveis = Presenca::where('personal_id', $personalId)->where('cliente_id', $clienteId)
            ->selectRaw("DATE_FORMAT(data, '%Y-%m') as mes")
            ->distinct()->orderByDesc('mes')->pluck('mes');

        return view('personal.frequencia_aluno', compact(
            'personal', 'cliente', 'todas', 'total', 'presentes', 'faltas', 'classificacao',
            'diaMaisFalta', 'faltasMes', 'diasAgenda', 'presencasPorData', 'mes', 'mesesDisponiveis',
            'faltasPorDia', 'diasSemana'
        ));
    }

    public function marcarPresenca(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $dados = $request->validate([
            'cliente_id' => 'required|integer',
            'data'       => 'required|date',
            'presente'   => 'required|in:0,1',
        ]);

        $alunoIds = $this->alunosDoPersonal($personalId)->pluck('id');
        if (!$alunoIds->contains((int) $dados['cliente_id'])) {
            abort(403);
        }

        Presenca::updateOrCreate(
            ['personal_id' => $personalId, 'cliente_id' => $dados['cliente_id'], 'data' => $dados['data']],
            ['presente' => (bool) $dados['presente']]
        );

        return redirect()->back()->with('success', 'Frequência registrada!');
    }

    public function removerPresenca($id)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        Presenca::where('personal_id', $personalId)->where('id', $id)->firstOrFail()->delete();

        return redirect()->back()->with('success', 'Registro removido.');
    }

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
            $dataStr = $ag->data instanceof \Carbon\Carbon ?
                $ag->data->format('Y-m-d') :
                $ag->data;

            $dataAula = Carbon::parse($dataStr . ' ' . $ag->hora_inicio);
            if (Carbon::now()->diffInHours($dataAula) >= 24) {
                $ag->delete();
                $cancelados++;
            }
        }

        if ($cancelados === 0) {
            return redirect()->back()->with('error', 'Nenhum horário pode ser cancelado. Verifique a regra de 24h de antecedência.');
        }

        return redirect()->back()->with('success', "Dia cancelado! $cancelados horário(s) liberado(s).");
    }

    public function bloquearHorarioFixo(Request $request)
    {
        $request->validate([
            'hora_inicio' => 'required',
            'hora_fim'    => 'required',
            'dias'        => 'required|integer|min:1|max:90',
            'descricao'   => 'nullable|string|max:255',
        ]);

        $inicio = $request->hora_inicio;
        $fim = $request->hora_fim;

        if ($inicio >= $fim) {
            return back()->withErrors(['horario' => 'A hora de início deve ser anterior à hora de término.']);
        }

        $criados = 0;
        $conflitos = 0;

        for ($i = 0; $i < $request->dias; $i++) {
            $data = Carbon::now()->addDays($i)->format('Y-m-d');

            $conflito = Agenda::where('personal_id', session('personal_id'))
                ->where('data', $data)
                ->where('cancelado', false)
                ->where(function ($q) use ($inicio, $fim) {
                    $q->where('hora_inicio', '<', $fim)
                        ->where('hora_fim', '>', $inicio);
                })->exists();

            if (!$conflito) {
                // ✅ IMPORTANTE: Salvar tipo_aula = 'bloqueio'
                Agenda::create([
                    'personal_id' => session('personal_id'),
                    'data'        => $data,
                    'hora_inicio' => $inicio,
                    'hora_fim'    => $fim,
                    'descricao'   => $request->descricao ?? 'Horário Fixo Bloqueado',
                    'cancelado'   => false,
                    'tipo_aula'   => 'bloqueio', // ✅ NOVO
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

    public function configurarPrecos($id = null)
    {
        $personalId = $id ?? session('personal_id');

        if (!$personalId) {
            return redirect()->route('login')->with('error', 'Sessão expirada ou acesso inválido.');
        }

        $personal = Personal::with('pacotes')->findOrFail($personalId);
        $precosSalvos = $personal->pacotes->pluck('valor_mensal', 'frequencia')->toArray();

        if (session()->has('aluno_id') || (auth()->check() && auth()->user()->tipo === 'aluno')) {
            return view('personal.exibir-precos', compact('precosSalvos', 'personal'));
        }

        return view('personal.configurar-precos', compact('precosSalvos', 'personal'));
    }

    public function storePrecos(Request $request)
    {
        $request->validate([
            'precos' => 'required|array',
            'precos.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($request->precos as $frequencia => $valor) {
            if ($valor) {
                \App\Models\Cadastro\Pacote::updateOrCreate(
                    ['personal_id' => session('personal_id'), 'frequencia' => $frequencia],
                    ['valor_mensal' => $valor]
                );
            }
        }

        return redirect()->back()->with('success', 'Tabela de preços atualizada com sucesso!');
    }

    // ✅ NOVO: Busca detalhes do aluno com pacote
    public function detalhesAluno($clienteId)
    {
        $personalId = session('personal_id');
        if (!$personalId) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        $temRelacao = Agenda::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('cancelado', false)
            ->exists();

        if (!$temRelacao) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        try {
            $cliente = Cliente::findOrFail($clienteId);

            $pacote = null;
            $aulas = Agenda::where('personal_id', $personalId)
                ->where('cliente_id', $clienteId)
                ->where('cancelado', false)
                ->where('frequencia_pacote', '!=', null)
                ->first();

            if ($aulas && $aulas->frequencia_pacote) {
                $pacote = \App\Models\Cadastro\Pacote::where('personal_id', session('personal_id'))
                    ->where('frequencia', $aulas->frequencia_pacote)
                    ->first();
            }

            return response()->json([
                'nome' => $cliente->nome,
                'idade' => $cliente->idade,
                'condicao_clinica' => $cliente->condicao_clinica ?? 'Nenhuma condição registrada',
                'pacote' => $pacote ? [
                    'frequencia' => $pacote->frequencia,
                    'valor_mensal' => number_format($pacote->valor_mensal, 2, ',', '.')
                ] : null
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calcularFinanceiroMes($personalId, $mes = null, $ano = null)
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        $faturamentoPacotes = 0;
        $pacotesProcessados = [];

        $aulasPackote = Agenda::where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('tipo_aula', 'pacote')
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        foreach ($aulasPackote as $agenda) {
            if ($agenda->frequencia_pacote && !isset($pacotesProcessados[$agenda->frequencia_pacote])) {
                $pacote = \App\Models\Cadastro\Pacote::where('personal_id', $personalId)
                    ->where('frequencia', $agenda->frequencia_pacote)
                    ->first();

                if ($pacote) {
                    $pacotesProcessados[$agenda->frequencia_pacote] = true;
                    $faturamentoPacotes += $pacote->valor_mensal;
                }
            }
        }

        $faturamentoAvulsas = 0;
        $aulasAvulsas = Agenda::where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('tipo_aula', 'avulsa')
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        $personal = Personal::find($personalId);

        foreach ($aulasAvulsas as $agenda) {
            $duracao = Carbon::parse($agenda->hora_inicio)
                ->diffInMinutes(Carbon::parse($agenda->hora_fim)) / 60;
            $faturamentoAvulsas += ($duracao * ($personal->valor_secao ?? 0));
        }

        return [
            'pacotes' => $faturamentoPacotes,
            'avulsas' => $faturamentoAvulsas,
            'total' => $faturamentoPacotes + $faturamentoAvulsas,
            'detalhes' => [
                'quantidade_aulas_pacote' => $aulasPackote->count(),
                'quantidade_aulas_avulsa' => $aulasAvulsas->count(),
                'valor_secao' => $personal->valor_secao ?? 0,
            ]
        ];
    }

    public function finalizarAula(Request $request, $id)
    {
        try {
            $aula = Agenda::findOrFail($id);

            if ($aula->personal_id !== session('personal_id')) {
                return redirect()->back()->with('error', 'Você não tem permissão para esta ação.');
            }

            $personal = Personal::find(session('personal_id'));
            $cliente = $aula->cliente_id ? Cliente::find($aula->cliente_id) : null;

            if (!$cliente) {
                return redirect()->back()->with('error', 'Cliente não encontrado.');
            }

            $aula->update(['concluida' => true]);

            $this->notificarClienteWhatsApp(
                $cliente,
                $aula->tipo_aula ?? 'aula',
                $aula,
                $personal->nome
            );

            return redirect()->back()->with('success', '✅ Aula finalizada! Cliente foi notificado.');
        } catch (\Exception $e) {
            Log::error('Erro ao finalizar aula: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao finalizar aula: ' . $e->getMessage());
        }
    }

    private function validarCPF(string $cpf): bool
    {
        return \App\Support\CadastroHelper::validarCPF($cpf);
    }

    private function criarSubcontaAsaas(Personal $personal): void
    {
        \App\Support\CadastroHelper::criarSubcontaAsaasPersonal($personal);
    }

    private function notificarClienteWhatsApp($cliente, $tipoAula, $aula, $nomePersonal)
    {
        try {
            if (!$cliente) {
                return;
            }

            $data = $aula->data instanceof \Carbon\Carbon ?
                $aula->data->format('d/m/Y') :
                \Carbon\Carbon::parse($aula->data)->format('d/m/Y');

            $mensagem = "👋 Olá {$cliente->nome}!\n\n";
            $mensagem .= "✅ Sua aula foi concluída com sucesso!\n\n";
            $mensagem .= "👨‍🏫 Personal: {$nomePersonal}\n";
            $mensagem .= "📅 Data: {$data}\n";
            $mensagem .= "⏰ Horário: {$aula->hora_inicio} - {$aula->hora_fim}\n";

            if ($tipoAula === 'pacote') {
                $mensagem .= "📦 Tipo: Aula de Pacote\n";
            } elseif ($tipoAula === 'avulsa') {
                $mensagem .= "🎯 Tipo: Aula Avulsa\n";
            }

            $mensagem .= "\nObrigado por treinar conosco! 💪";

            \App\Services\NotificacaoService::cliente(
                $cliente,
                'Aula concluída — SnrFit',
                $mensagem,
                'aula_finalizada_aluno',
                [$cliente->nome, $nomePersonal, $data, "{$aula->hora_inicio} - {$aula->hora_fim}"]
            );
        } catch (\Exception $e) {
            Log::error('Erro ao notificar cliente: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────
    // SOLICITAÇÕES DE FICHA
    // ─────────────────────────────────────────────
    public function listarSolicitacoesFicha()
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $personal = Personal::findOrFail($personalId);
        $solicitacoes = \App\Models\SolicitacaoFicha::where('personal_id', $personalId)
            ->with('cliente')
            ->orderByRaw("FIELD(status, 'pendente', 'concluida')")
            ->latest()
            ->get();

        return view('personal.solicitacoes_ficha', compact('personal', 'solicitacoes'));
    }

    public function concluirSolicitacaoFicha(Request $request, $id)
    {
        $personalId = session('personal_id');
        $solicitacao = \App\Models\SolicitacaoFicha::where('id', $id)
            ->where('personal_id', $personalId)
            ->firstOrFail();

        $solicitacao->update(['status' => 'concluida']);

        $personal = Personal::find($personalId);
        $cliente  = $solicitacao->cliente;

        if ($cliente) {
            $mensagem = "🏋️ *Sua Ficha Está Pronta!*\n\n" .
                "Olá, *{$cliente->nome}*!\n" .
                "Seu personal *{$personal->nome}* acabou de montar sua ficha de treino personalizada.\n" .
                "Acesse o aplicativo para visualizá-la. 💪";

            \App\Services\NotificacaoService::cliente(
                $cliente,
                'Sua ficha de treino está pronta — SnrFit',
                $mensagem,
                'ficha_pronta',
                [$cliente->nome, $personal->nome]
            );
        }

        return back()->with('success', 'Ficha marcada como concluída! O aluno foi notificado.');
    }

    public function atualizarValorFicha(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $request->validate(['valor_ficha' => 'required|numeric|min:0']);

        Personal::where('id', $personalId)->update(['valor_ficha' => $request->valor_ficha]);

        return back()->with('success', 'Valor da ficha atualizado!');
    }
}