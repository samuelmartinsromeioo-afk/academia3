<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\StudioAula;
use App\Models\Cadastro\StudioHorario;
use App\Models\Cadastro\StudioPlano;
use App\Models\Cadastro\StudioProfessor;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Painel do STUDIO pela API — espelha Cadastro\StudioController: dashboard,
 * alunos, planos, horários de funcionamento, aulas, professores, bloqueios
 * e agenda do dia.
 */
class StudioController extends Controller
{
    use ResolvesApiUser;

    // GET /api/v1/studio/dashboard
    public function dashboard(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        $totalAlunos = Cliente::where('studio_id', $studio->id)->count();
        $planosAtivos = Cliente::where('studio_id', $studio->id)->where('studio_plano_ativo', true)->count();

        $faturamentoMes = Payment::where('studio_id', $studio->id)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('trainer_amount');

        $aulasHoje = Agenda::where('studio_id', $studio->id)
            ->whereDate('data', today())
            ->where('cancelado', false)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->count();

        // Avaliações recebidas dos alunos (nota + comentário) para o dono ver.
        $avaliacoes = $studio->avaliacoes()->with('cliente:id,nome')->latest()->get();

        return response()->json([
            'studio' => [
                'id' => $studio->id,
                'nome' => $studio->nome,
                'tipo' => $studio->tipo,
                'valor_aula' => $studio->valor_aula !== null ? (float) $studio->valor_aula : null,
                'capacidade_padrao' => $studio->capacidade_padrao,
            ],
            'total_alunos' => $totalAlunos,
            'planos_ativos' => $planosAtivos,
            'faturamento_mes' => (float) $faturamentoMes,
            'aulas_hoje' => $aulasHoje,
            'media_avaliacao' => $avaliacoes->avg('nota') ? round($avaliacoes->avg('nota'), 1) : null,
            'total_avaliacoes' => $avaliacoes->count(),
            'avaliacoes_recentes' => $avaliacoes->take(10)->map(fn ($a) => [
                'nota' => (int) $a->nota,
                'comentario' => $a->comentario,
                'cliente' => $a->cliente?->nome,
                'data' => $a->created_at?->toDateString(),
            ])->values(),
        ]);
    }

    // PUT /api/v1/studio/perfil
    public function atualizarPerfil(Request $request)
    {
        $studio = $this->studioAutenticado($request);

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

        return response()->json(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
    }

    // ===================== ALUNOS =====================

    // GET /api/v1/studio/alunos
    public function alunos(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        $alunos = Cliente::where('studio_id', $studio->id)->orderBy('nome')->get()->map(function ($aluno) use ($studio) {
            $base = Agenda::where('studio_id', $studio->id)
                ->where('cliente_id', $aluno->id)
                ->where('cancelado', false)
                ->where('tipo_aula', '!=', 'bloqueio');

            return [
                'id' => $aluno->id,
                'nome' => $aluno->nome,
                'email' => $aluno->email,
                'whatsapp' => $aluno->whatsapp,
                'plano_ativo' => (bool) $aluno->studio_plano_ativo,
                'frequencia_mes' => (clone $base)->whereMonth('data', now()->month)->whereYear('data', now()->year)->count(),
                'frequencia_total' => $base->count(),
            ];
        });

        return response()->json(['alunos' => $alunos]);
    }

    // DELETE /api/v1/studio/alunos/{id}
    public function desvincularAluno(Request $request, $id)
    {
        $studio = $this->studioAutenticado($request);

        $cliente = Cliente::where('id', $id)->where('studio_id', $studio->id)->firstOrFail();
        $cliente->update([
            'studio_id' => null,
            'studio_plano_id' => null,
            'studio_plano_ativo' => false,
        ]);

        return response()->json(['success' => true, 'message' => "Aluno '{$cliente->nome}' desvinculado do studio."]);
    }

    // ===================== PLANOS =====================

    // GET /api/v1/studio/planos
    public function planos(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        return response()->json([
            'planos' => StudioPlano::where('studio_id', $studio->id)->orderBy('valor')->get()->map(fn ($p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'valor' => (float) $p->valor,
                'duracao_meses' => $p->duracao_meses,
                'descricao' => $p->descricao,
                'ativo' => (bool) $p->ativo,
            ]),
        ]);
    }

    // POST /api/v1/studio/planos
    public function criarPlano(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        $request->validate([
            'nome' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'duracao_meses' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:500',
        ]);

        if (StudioPlano::where('studio_id', $studio->id)->count() >= 5) {
            return response()->json(['error' => 'Limite de 5 planos atingido. Exclua um plano antes de adicionar outro.'], 422);
        }

        StudioPlano::create([
            'studio_id' => $studio->id,
            'nome' => $request->nome,
            'valor' => $request->valor,
            'duracao_meses' => $request->duracao_meses,
            'descricao' => $request->descricao,
            'ativo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Plano criado com sucesso!'], 201);
    }

    // DELETE /api/v1/studio/planos/{id}
    public function excluirPlano(Request $request, $id)
    {
        $studio = $this->studioAutenticado($request);

        StudioPlano::where('id', $id)->where('studio_id', $studio->id)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Plano removido com sucesso!']);
    }

    // ===================== HORÁRIOS, AULAS E PROFESSORES =====================

    // GET /api/v1/studio/horarios?data=YYYY-MM-DD
    public function horarios(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        $dataSelecionada = $request->query('data', today()->format('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataSelecionada)) {
            $dataSelecionada = today()->format('Y-m-d');
        }

        return response()->json([
            'horarios' => StudioHorario::where('studio_id', $studio->id)->orderBy('dia_semana')->get()->map(fn ($h) => [
                'id' => $h->id,
                'dia_semana' => $h->dia_semana,
                'hora_abertura' => substr($h->hora_abertura, 0, 5),
                'hora_fechamento' => substr($h->hora_fechamento, 0, 5),
                'capacidade' => $h->capacidade,
                'ativo' => (bool) $h->ativo,
            ]),
            'aulas' => StudioAula::with('professor:id,nome')->where('studio_id', $studio->id)
                ->orderBy('dia_semana')->orderBy('hora_inicio')->get()->map(fn ($a) => [
                    'id' => $a->id,
                    'dia_semana' => $a->dia_semana,
                    'hora_inicio' => substr($a->hora_inicio, 0, 5),
                    'duracao_min' => $a->duracao_min,
                    'profissional' => $a->professor?->nome ?? $a->profissional,
                    'capacidade' => $a->capacidade,
                ]),
            'professores' => StudioProfessor::where('studio_id', $studio->id)->orderBy('nome')->get()->map(fn ($p) => [
                'id' => $p->id, 'nome' => $p->nome, 'resumo' => $p->resumo,
            ]),
            'data' => $dataSelecionada,
            'slots' => $studio->slotsDisponiveis($dataSelecionada),
            'bloqueios' => Agenda::where('studio_id', $studio->id)
                ->whereDate('data', $dataSelecionada)
                ->where('cancelado', false)
                ->where('tipo_aula', 'bloqueio')
                ->orderBy('hora_inicio')
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'hora_inicio' => substr($b->hora_inicio, 0, 5),
                    'hora_fim' => substr($b->hora_fim, 0, 5),
                ]),
        ]);
    }

    // POST /api/v1/studio/horarios — funcionamento de um dia da semana
    public function salvarHorario(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        $request->validate([
            'dia_semana' => 'required|integer|min:0|max:6',
            'hora_abertura' => 'required|date_format:H:i',
            'hora_fechamento' => 'required|date_format:H:i|after:hora_abertura',
            'capacidade' => 'nullable|integer|min:1|max:500',
        ]);

        StudioHorario::updateOrCreate(
            ['studio_id' => $studio->id, 'dia_semana' => $request->dia_semana],
            [
                'hora_abertura' => $request->hora_abertura,
                'hora_fechamento' => $request->hora_fechamento,
                'capacidade' => $request->capacidade,
                'ativo' => true,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Horário de funcionamento salvo!'], 201);
    }

    // DELETE /api/v1/studio/horarios/{id}
    public function excluirHorario(Request $request, $id)
    {
        $studio = $this->studioAutenticado($request);

        StudioHorario::where('id', $id)->where('studio_id', $studio->id)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Horário de funcionamento removido!']);
    }

    // POST /api/v1/studio/professores
    public function criarProfessor(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        $request->validate([
            'nome' => 'required|string|max:120',
            'resumo' => 'nullable|string|max:2000',
        ]);

        StudioProfessor::create([
            'studio_id' => $studio->id,
            'nome' => $request->nome,
            'resumo' => $request->resumo,
            'ativo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Profissional cadastrado!'], 201);
    }

    // DELETE /api/v1/studio/professores/{id}
    public function excluirProfessor(Request $request, $id)
    {
        $studio = $this->studioAutenticado($request);

        StudioProfessor::where('studio_id', $studio->id)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Profissional removido!']);
    }

    // POST /api/v1/studio/aulas
    public function criarAula(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        $request->validate([
            'dia_semana' => 'required|integer|min:0|max:6',
            'hora_inicio' => 'required|date_format:H:i',
            'duracao_min' => 'required|integer|min:5|max:600',
            'professor_id' => 'nullable|integer',
            'capacidade' => 'nullable|integer|min:1|max:500',
        ]);

        $professorId = null;
        $profNome = null;
        if ($request->filled('professor_id')) {
            $professor = StudioProfessor::where('studio_id', $studio->id)->find($request->professor_id);
            if ($professor) {
                $professorId = $professor->id;
                $profNome = $professor->nome;
            }
        }

        // A aula precisa caber dentro do horário de funcionamento do dia.
        $func = StudioHorario::where('studio_id', $studio->id)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->first();

        if (! $func) {
            return response()->json(['error' => 'Defina primeiro o horário de funcionamento desse dia antes de cadastrar aulas.'], 422);
        }

        $inicio = Carbon::parse($request->hora_inicio);
        $fim = $inicio->copy()->addMinutes((int) $request->duracao_min);
        $abertura = Carbon::parse(substr($func->hora_abertura, 0, 5));
        $fechamento = Carbon::parse(substr($func->hora_fechamento, 0, 5));

        if ($inicio->lt($abertura) || $fim->gt($fechamento)) {
            return response()->json([
                'error' => 'A aula deve estar dentro do funcionamento do dia (' . substr($func->hora_abertura, 0, 5) . ' às ' . substr($func->hora_fechamento, 0, 5) . ').',
            ], 422);
        }

        StudioAula::create([
            'studio_id' => $studio->id,
            'dia_semana' => $request->dia_semana,
            'hora_inicio' => $request->hora_inicio,
            'duracao_min' => $request->duracao_min,
            'profissional' => $profNome,
            'professor_id' => $professorId,
            'capacidade' => $request->capacidade,
            'ativo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Horário de aula adicionado!'], 201);
    }

    // DELETE /api/v1/studio/aulas/{id}
    public function excluirAula(Request $request, $id)
    {
        $studio = $this->studioAutenticado($request);

        StudioAula::where('id', $id)->where('studio_id', $studio->id)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Horário de aula removido!']);
    }

    // POST /api/v1/studio/bloqueios
    public function bloquearSlot(Request $request)
    {
        $studio = $this->studioAutenticado($request);

        $request->validate([
            'data' => 'required|date_format:Y-m-d',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
        ]);

        Agenda::create([
            'studio_id' => $studio->id,
            'cliente_id' => null,
            'data' => $request->data,
            'hora_inicio' => $request->hora_inicio,
            'hora_fim' => $request->hora_fim,
            'tipo_aula' => 'bloqueio',
            'descricao' => 'Horário bloqueado pelo studio',
            'cancelado' => false,
            'status' => 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Horário bloqueado com sucesso!'], 201);
    }

    // DELETE /api/v1/studio/bloqueios/{id}
    public function desbloquearSlot(Request $request, $id)
    {
        $studio = $this->studioAutenticado($request);

        Agenda::where('id', $id)
            ->where('studio_id', $studio->id)
            ->where('tipo_aula', 'bloqueio')
            ->firstOrFail()
            ->delete();

        return response()->json(['success' => true, 'message' => 'Bloqueio removido!']);
    }

    // GET /api/v1/studio/agenda/{data}
    public function agendaDia(Request $request, $data)
    {
        $studio = $this->studioAutenticado($request);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return response()->json(['error' => 'Data inválida'], 422);
        }

        $agendas = Agenda::where('studio_id', $studio->id)
            ->whereDate('data', $data)
            ->where('cancelado', false)
            ->with('cliente:id,nome')
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'hora_inicio' => substr($a->hora_inicio, 0, 5),
                'hora_fim' => substr($a->hora_fim, 0, 5),
                'tipo_aula' => $a->tipo_aula,
                'cliente' => $a->cliente->nome ?? null,
            ]);

        return response()->json(['agendas' => $agendas]);
    }
}
