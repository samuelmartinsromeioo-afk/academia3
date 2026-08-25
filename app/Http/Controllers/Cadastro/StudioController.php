<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Services\MetaConversionsService;
use App\Models\Agenda;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\Cadastro\StudioHorario;
use App\Models\Cadastro\StudioAula;
use App\Models\Cadastro\StudioProfessor;
use App\Models\Cadastro\StudioPlano;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudioController extends Controller
{
    public function create()
    {
        return view('cadastro.studio');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'required|string|max:18|unique:studios,cnpj',
            'email' => 'required|email|max:255|unique:studios,email',
            'senha' => 'required|string|min:8|confirmed',
            'whatsapp' => 'nullable|string|max:20',
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:300',
            'bairro' => 'required|string|max:200',
            'cidade' => 'required|string|max:200',
            'estado' => 'required|string|max:2',
            'complemento' => 'nullable|string|max:255',
            'endereco' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:500',
            'modalidades' => 'nullable|string|max:500',
            'tipo' => 'required|in:yoga_pilates,luta,crossfit,fitness,danca,outros',
            'valor_aula' => 'required|numeric|min:0',
            'capacidade_padrao' => 'required|integer|min:1|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ], [
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'senha.confirmed' => 'A confirmação de senha não confere.',
            'senha.min' => 'A senha deve ter no mínimo 8 caracteres.',
        ]);

        // E-mail/CNPJ não podem colidir com outros papéis (o login tenta na ordem e o papel anterior ganharia)
        if (
            Academia::where('email', $dados['email'])->exists() ||
            Personal::where('email', $dados['email'])->exists() ||
            Cliente::where('email', $dados['email'])->exists()
        ) {
            return back()->withErrors(['email' => 'Este e-mail já está em uso na plataforma.'])->withInput();
        }

        if (Academia::where('cnpj', $dados['cnpj'])->exists()) {
            return back()->withErrors(['cnpj' => 'Este CNPJ já está em uso na plataforma.'])->withInput();
        }

        $dados['senha'] = Hash::make($dados['senha']);
        $dados['status'] = 'pendente';

        $studio = Studio::create($dados);

        $fb = app(MetaConversionsService::class);
        return redirect()->route('login.index')
            ->with('sucesso', 'Cadastro enviado com sucesso! Seu studio será analisado pelo administrador e você poderá acessar após a aprovação.')
            ->with('fb_event', $fb->track(
                'CompleteRegistration',
                ['content_name' => 'Studio', 'status' => 'pendente'],
                $fb->userDataFromModel($studio),
                null,
                null,
                route('login.index')
            ));
    }

    // ==========================================
    // PAINEL DO STUDIO
    // ==========================================

    private function studioLogado()
    {
        $studioId = session('studio_id');
        if (!$studioId) {
            return null;
        }
        return Studio::find($studioId);
    }

    public function dashboard()
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            session()->forget('studio_id');
            return redirect()->route('login.index')->withErrors(['login' => 'Acesso negado. Faça o login.']);
        }

        $totalAlunos = Cliente::where('studio_id', $studio->id)->count();
        $planosAtivos = Cliente::where('studio_id', $studio->id)->where('studio_plano_ativo', true)->count();

        $faturamentoMes = Payment::where('studio_id', $studio->id)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('trainer_amount');

        $alunos = Cliente::where('studio_id', $studio->id)->orderBy('nome')->get()->map(function ($aluno) use ($studio) {
            $aluno->frequencia_mes   = Agenda::where('studio_id', $studio->id)
                ->where('cliente_id', $aluno->id)
                ->where('cancelado', false)
                ->where('tipo_aula', '!=', 'bloqueio')
                ->whereMonth('data', now()->month)
                ->whereYear('data', now()->year)
                ->count();
            $aluno->frequencia_total = Agenda::where('studio_id', $studio->id)
                ->where('cliente_id', $aluno->id)
                ->where('cancelado', false)
                ->where('tipo_aula', '!=', 'bloqueio')
                ->count();
            return $aluno;
        });

        $planos = StudioPlano::where('studio_id', $studio->id)->orderBy('valor')->get();
        $horarios = StudioHorario::where('studio_id', $studio->id)->orderBy('dia_semana')->get();

        $agendaHoje = Agenda::where('studio_id', $studio->id)
            ->whereDate('data', today())
            ->where('cancelado', false)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->with('cliente')
            ->orderBy('hora_inicio')
            ->get();

        return view('studio.dashboard', compact(
            'studio',
            'totalAlunos',
            'planosAtivos',
            'faturamentoMes',
            'alunos',
            'planos',
            'horarios',
            'agendaHoje',
        ));
    }

    public function update(Request $request, $id)
    {
        $studio = $this->studioLogado();
        if (!$studio || (int) $id !== $studio->id) {
            return redirect()->route('login.index');
        }

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'descricao' => 'nullable|string|max:500',
            'modalidades' => 'nullable|string|max:500',
            'tipo' => 'required|in:yoga_pilates,luta,crossfit,fitness,danca,outros',
            'valor_aula' => 'required|numeric|min:0',
            'capacidade_padrao' => 'required|integer|min:1|max:500',
            'chave_pix' => 'nullable|string|max:255',
        ]);

        $studio->update($dados);

        return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');
    }

    public function listarAlunos()
    {
        return $this->dashboard();
    }

    public function desvincularAluno($id)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::where('id', $id)->where('studio_id', $studio->id)->firstOrFail();
        $cliente->update([
            'studio_id' => null,
            'studio_plano_id' => null,
            'studio_plano_ativo' => false,
        ]);

        return redirect()->back()->with('success', "Aluno '{$cliente->nome}' desvinculado do studio.");
    }

    // ==========================================
    // PLANOS
    // ==========================================

    public function listarPlanos()
    {
        return $this->dashboard();
    }

    public function storePlano(Request $request)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome'          => 'required|string|max:255',
            'valor'         => 'required|numeric|min:0',
            'duracao_meses' => 'required|integer|min:1',
            'descricao'     => 'nullable|string|max:500',
        ]);

        if (StudioPlano::where('studio_id', $studio->id)->count() >= 5) {
            return redirect()->back()->with('error', 'Limite de 5 planos atingido. Exclua um plano antes de adicionar outro.');
        }

        StudioPlano::create([
            'studio_id'     => $studio->id,
            'nome'          => $request->nome,
            'valor'         => $request->valor,
            'duracao_meses' => $request->duracao_meses,
            'descricao'     => $request->descricao,
            'ativo'         => true,
        ]);

        return redirect()->back()->with('success', 'Plano criado com sucesso!');
    }

    public function updatePlano(Request $request, $id)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $plano = StudioPlano::where('id', $id)->where('studio_id', $studio->id)->firstOrFail();

        $request->validate([
            'nome'          => 'required|string|max:255',
            'valor'         => 'required|numeric|min:0',
            'duracao_meses' => 'required|integer|min:1',
            'descricao'     => 'nullable|string|max:500',
        ]);

        $plano->update([
            'nome'          => $request->nome,
            'valor'         => $request->valor,
            'duracao_meses' => $request->duracao_meses,
            'descricao'     => $request->descricao,
            'ativo'         => $request->has('ativo'),
        ]);

        return redirect()->back()->with('success', 'Plano atualizado com sucesso!');
    }

    public function destroyPlano($id)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        StudioPlano::where('id', $id)->where('studio_id', $studio->id)->firstOrFail()->delete();

        return redirect()->back()->with('success', 'Plano removido com sucesso!');
    }

    // ==========================================
    // HORÁRIOS DE FUNCIONAMENTO E BLOQUEIOS
    // ==========================================

    public function horarios(Request $request)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $horarios = StudioHorario::where('studio_id', $studio->id)->orderBy('dia_semana')->get();
        $aulas = StudioAula::with('professor')
            ->where('studio_id', $studio->id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
        $professores = StudioProfessor::where('studio_id', $studio->id)->orderBy('nome')->get();

        $dataSelecionada = $request->query('data', today()->format('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataSelecionada)) {
            $dataSelecionada = today()->format('Y-m-d');
        }

        $slots = $studio->slotsDisponiveis($dataSelecionada);

        $bloqueios = Agenda::where('studio_id', $studio->id)
            ->whereDate('data', $dataSelecionada)
            ->where('cancelado', false)
            ->where('tipo_aula', 'bloqueio')
            ->orderBy('hora_inicio')
            ->get();

        return view('studio.horarios', compact('studio', 'horarios', 'aulas', 'professores', 'slots', 'bloqueios', 'dataSelecionada'));
    }

    // ==========================================
    // PROFISSIONAIS DO STUDIO
    // ==========================================

    public function storeProfessor(Request $request)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome'   => 'required|string|max:120',
            'resumo' => 'nullable|string|max:2000',
        ]);

        StudioProfessor::create([
            'studio_id' => $studio->id,
            'nome'      => $request->nome,
            'resumo'    => $request->resumo,
            'ativo'     => true,
        ]);

        return redirect()->back()->with('success', 'Profissional cadastrado!');
    }

    public function updateProfessor(Request $request, $id)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome'   => 'required|string|max:120',
            'resumo' => 'nullable|string|max:2000',
        ]);

        $professor = StudioProfessor::where('studio_id', $studio->id)->findOrFail($id);
        $professor->update([
            'nome'   => $request->nome,
            'resumo' => $request->resumo,
        ]);

        // Mantém o nome desnormalizado nas aulas sincronizado.
        StudioAula::where('studio_id', $studio->id)
            ->where('professor_id', $professor->id)
            ->update(['profissional' => $professor->nome]);

        return redirect()->back()->with('success', 'Profissional atualizado!');
    }

    public function destroyProfessor($id)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        StudioProfessor::where('studio_id', $studio->id)->findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Profissional removido!');
    }

    public function storeAula(Request $request)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'dia_semana'   => 'required|integer|min:0|max:6',
            'hora_inicio'  => 'required|date_format:H:i',
            'duracao_min'  => 'required|integer|min:5|max:600',
            'professor_id' => 'nullable|integer',
            'capacidade'   => 'nullable|integer|min:1|max:500',
        ]);

        // Resolve o profissional escolhido (cadastro reutilizável).
        $professorId = null;
        $profNome    = null;
        if ($request->filled('professor_id')) {
            $professor = StudioProfessor::where('studio_id', $studio->id)->find($request->professor_id);
            if ($professor) {
                $professorId = $professor->id;
                $profNome    = $professor->nome;
            }
        }

        // A aula precisa caber dentro do horário de funcionamento do dia.
        $func = StudioHorario::where('studio_id', $studio->id)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->first();

        if (!$func) {
            return redirect()->back()->with('error', 'Defina primeiro o horário de funcionamento desse dia antes de cadastrar aulas.');
        }

        $inicio = \Carbon\Carbon::parse($request->hora_inicio);
        $fim    = $inicio->copy()->addMinutes((int) $request->duracao_min);
        $abertura   = \Carbon\Carbon::parse(substr($func->hora_abertura, 0, 5));
        $fechamento = \Carbon\Carbon::parse(substr($func->hora_fechamento, 0, 5));

        if ($inicio->lt($abertura) || $fim->gt($fechamento)) {
            return redirect()->back()->with('error', 'A aula deve estar dentro do funcionamento do dia (' . substr($func->hora_abertura, 0, 5) . ' às ' . substr($func->hora_fechamento, 0, 5) . ').');
        }

        StudioAula::create([
            'studio_id'    => $studio->id,
            'dia_semana'   => $request->dia_semana,
            'hora_inicio'  => $request->hora_inicio,
            'duracao_min'  => $request->duracao_min,
            'profissional' => $profNome,
            'professor_id' => $professorId,
            'capacidade'   => $request->capacidade,
            'ativo'        => true,
        ]);

        return redirect()->back()->with('success', 'Horário de aula adicionado!');
    }

    public function destroyAula($id)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        StudioAula::where('id', $id)->where('studio_id', $studio->id)->firstOrFail()->delete();

        return redirect()->back()->with('success', 'Horário de aula removido!');
    }

    public function storeHorario(Request $request)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'dia_semana'      => 'required|integer|min:0|max:6',
            'hora_abertura'   => 'required|date_format:H:i',
            'hora_fechamento' => 'required|date_format:H:i|after:hora_abertura',
            'capacidade'      => 'nullable|integer|min:1|max:500',
        ]);

        StudioHorario::updateOrCreate(
            ['studio_id' => $studio->id, 'dia_semana' => $request->dia_semana],
            [
                'hora_abertura'   => $request->hora_abertura,
                'hora_fechamento' => $request->hora_fechamento,
                'capacidade'      => $request->capacidade,
                'ativo'           => true,
            ]
        );

        return redirect()->back()->with('success', 'Horário de funcionamento salvo!');
    }

    public function destroyHorario($id)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        StudioHorario::where('id', $id)->where('studio_id', $studio->id)->firstOrFail()->delete();

        return redirect()->back()->with('success', 'Horário de funcionamento removido!');
    }

    public function bloquearSlot(Request $request)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'data'        => 'required|date_format:Y-m-d',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim'    => 'required|date_format:H:i|after:hora_inicio',
        ]);

        Agenda::create([
            'studio_id'   => $studio->id,
            'cliente_id'  => null,
            'data'        => $request->data,
            'hora_inicio' => $request->hora_inicio,
            'hora_fim'    => $request->hora_fim,
            'tipo_aula'   => 'bloqueio',
            'descricao'   => 'Horário bloqueado pelo studio',
            'cancelado'   => false,
            'status'      => 0,
        ]);

        return redirect()->back()->with('success', 'Horário bloqueado com sucesso!');
    }

    public function desbloquearSlot($id)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return redirect()->route('login.index');
        }

        Agenda::where('id', $id)
            ->where('studio_id', $studio->id)
            ->where('tipo_aula', 'bloqueio')
            ->firstOrFail()
            ->delete();

        return redirect()->back()->with('success', 'Bloqueio removido!');
    }

    public function getAgendaDia($data)
    {
        $studio = $this->studioLogado();
        if (!$studio) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return response()->json(['error' => 'Data inválida'], 422);
        }

        $agendas = Agenda::where('studio_id', $studio->id)
            ->whereDate('data', $data)
            ->where('cancelado', false)
            ->with('cliente')
            ->orderBy('hora_inicio')
            ->get()
            ->map(function ($a) {
                return [
                    'id'          => $a->id,
                    'hora_inicio' => substr($a->hora_inicio, 0, 5),
                    'hora_fim'    => substr($a->hora_fim, 0, 5),
                    'tipo_aula'   => $a->tipo_aula,
                    'cliente'     => $a->cliente->nome ?? null,
                ];
            });

        return response()->json($agendas);
    }
}
