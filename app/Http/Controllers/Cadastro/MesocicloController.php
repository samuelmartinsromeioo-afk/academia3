<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Mesociclo;
use App\Models\Cadastro\MesocicloExercicio;
use App\Models\Cadastro\MesocicloTreino;
use App\Models\Cadastro\Personal;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FEATURE 4 — Periodização com troca automática de treino.
 *
 * Personal monta treinos A/B/C/D dentro de um mesociclo com validade; o aluno
 * vê o "treino do dia", que rotaciona automaticamente a cada conclusão. Quando
 * o mesociclo vence, o personal é avisado (flag no painel + notificação).
 */
class MesocicloController extends Controller
{
    private const LETRAS = ['A', 'B', 'C', 'D', 'E', 'F'];

    // ===================== PERSONAL =====================

    // Painel geral de periodizações, com vencidos em destaque.
    public function index()
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $mesociclos = Mesociclo::with(['cliente', 'treinos'])
            ->where('personal_id', $personalId)
            ->orderByDesc('ativo')
            ->orderByDesc('created_at')
            ->get();

        $this->avisarVencidos($mesociclos);

        $vencidos = $mesociclos->filter->estaVencido()->count();

        return view('personal.Periodizacao', compact('mesociclos', 'vencidos'));
    }

    // Gerenciar os mesociclos de um aluno.
    public function doAluno($clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }
        if (! $this->podeVer($personalId, $clienteId)) {
            return redirect()->route('periodizacao.index')->with('error', 'Acesso negado!');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $mesociclos = Mesociclo::with(['treinos.exercicios'])
            ->where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->orderByDesc('ativo')
            ->orderByDesc('created_at')
            ->get();

        return view('personal.PeriodizacaoAluno', compact('cliente', 'mesociclos'));
    }

    // Criar mesociclo com N treinos vazios (A..N).
    public function criar(Request $request, $clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }
        if (! $this->podeVer($personalId, $clienteId)) {
            return redirect()->route('periodizacao.index')->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome'            => 'required|string|max:255',
            'data_inicio'     => 'required|date',
            'modo_validade'   => 'required|in:semanas,data',
            'duracao_semanas' => 'required_if:modo_validade,semanas|nullable|integer|min:1|max:52',
            'data_fim'        => 'required_if:modo_validade,data|nullable|date|after:data_inicio',
            'qtd_treinos'     => 'required|integer|min:1|max:6',
        ]);

        $meso = Mesociclo::create([
            'personal_id'     => $personalId,
            'cliente_id'      => $clienteId,
            'nome'            => $request->nome,
            'data_inicio'     => $request->data_inicio,
            'duracao_semanas' => $request->modo_validade === 'semanas' ? $request->duracao_semanas : null,
            'data_fim'        => $request->modo_validade === 'data' ? $request->data_fim : null,
            'ativo'           => true,
        ]);

        // Só um mesociclo ativo por aluno.
        Mesociclo::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('id', '!=', $meso->id)
            ->update(['ativo' => false]);

        for ($i = 0; $i < (int) $request->qtd_treinos; $i++) {
            MesocicloTreino::create([
                'mesociclo_id' => $meso->id,
                'letra'        => self::LETRAS[$i],
                'nome_treino'  => 'Treino ' . self::LETRAS[$i],
                'ordem'        => $i,
            ]);
        }

        return redirect()->route('periodizacao.aluno', $clienteId)
            ->with('success', 'Mesociclo criado! Agora adicione os exercícios de cada treino.');
    }

    public function atualizarTreino(Request $request, $treinoId)
    {
        $treino = MesocicloTreino::with('mesociclo')->findOrFail($treinoId);
        if (! $this->donoDoTreino($treino)) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
        ]);

        $treino->update($request->only('nome_treino', 'observacoes'));

        return redirect()->back()->with('success', 'Treino atualizado!');
    }

    public function adicionarExercicio(Request $request, $treinoId)
    {
        $treino = MesocicloTreino::with('mesociclo')->findOrFail($treinoId);
        if (! $this->donoDoTreino($treino)) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series'         => 'required|integer|min:1',
            'repeticoes'     => 'required|integer|min:1',
            'peso'           => 'nullable|numeric|min:0',
            'observacoes'    => 'nullable|string',
        ]);

        $ordem = (int) MesocicloExercicio::where('mesociclo_treino_id', $treinoId)->max('ordem');

        MesocicloExercicio::create([
            'mesociclo_treino_id' => $treinoId,
            'nome_exercicio'      => $request->nome_exercicio,
            'series'              => $request->series,
            'repeticoes'          => $request->repeticoes,
            'peso'                => $request->peso,
            'observacoes'         => $request->observacoes,
            'ordem'               => $ordem + 1,
        ]);

        return redirect()->back()->with('success', 'Exercício adicionado!');
    }

    public function deletarExercicio($exercicioId)
    {
        $ex = MesocicloExercicio::with('treino.mesociclo')->findOrFail($exercicioId);
        if (! $ex->treino || ! $this->donoDoTreino($ex->treino)) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }
        $ex->delete();

        return redirect()->back()->with('success', 'Exercício removido!');
    }

    public function ativar($mesocicloId)
    {
        $personalId = session('personal_id');
        $meso = Mesociclo::findOrFail($mesocicloId);
        if ($meso->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        Mesociclo::where('personal_id', $personalId)
            ->where('cliente_id', $meso->cliente_id)
            ->update(['ativo' => false]);
        $meso->update(['ativo' => true, 'avisado_em' => null]);

        return redirect()->back()->with('success', 'Mesociclo ativado!');
    }

    public function deletar($mesocicloId)
    {
        $personalId = session('personal_id');
        $meso = Mesociclo::findOrFail($mesocicloId);
        if ($meso->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }
        $clienteId = $meso->cliente_id;
        $meso->delete();

        return redirect()->route('periodizacao.aluno', $clienteId)->with('success', 'Mesociclo excluído!');
    }

    // Renova a validade a partir de hoje (reinicia o ciclo).
    public function renovar(Request $request, $mesocicloId)
    {
        $personalId = session('personal_id');
        $meso = Mesociclo::findOrFail($mesocicloId);
        if ($meso->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'modo_validade'   => 'required|in:semanas,data',
            'duracao_semanas' => 'required_if:modo_validade,semanas|nullable|integer|min:1|max:52',
            'data_fim'        => 'required_if:modo_validade,data|nullable|date|after:today',
        ]);

        $meso->update([
            'data_inicio'     => now()->toDateString(),
            'duracao_semanas' => $request->modo_validade === 'semanas' ? $request->duracao_semanas : null,
            'data_fim'        => $request->modo_validade === 'data' ? $request->data_fim : null,
            'ativo'           => true,
            'avisado_em'      => null,
        ]);

        return redirect()->back()->with('success', 'Mesociclo renovado! A validade recomeçou a partir de hoje.');
    }

    // ===================== ALUNO =====================

    public function treinoDoDia()
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);

        $meso = Mesociclo::with('treinos.exercicios')
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->orderByDesc('created_at')
            ->first();

        $treino  = $meso?->treinoDoDia();
        $proximo = $meso?->proximoTreino();

        return view('cliente.TreinoDoDia', compact('cliente', 'meso', 'treino', 'proximo'));
    }

    public function concluirDia($mesocicloId)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $meso = Mesociclo::findOrFail($mesocicloId);
        if ($meso->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        if ($meso->concluidoHoje()) {
            return redirect()->back()->with('success', 'Você já concluiu o treino de hoje. 💪');
        }

        $meso->update([
            'posicao_atual'    => $meso->posicao_atual + 1,
            'ultima_conclusao' => now()->toDateString(),
        ]);

        return redirect()->back()->with('success', 'Treino concluído! O próximo da sequência já está pronto. 🔁');
    }

    // ===================== HELPERS =====================

    private function podeVer($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Mesociclo::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists();
    }

    private function donoDoTreino(MesocicloTreino $treino): bool
    {
        return $treino->mesociclo && $treino->mesociclo->personal_id == session('personal_id');
    }

    // Marca vencidos como avisados e dispara notificação interna única ao personal.
    private function avisarVencidos($mesociclos): void
    {
        foreach ($mesociclos as $m) {
            if (! $m->estaVencido() || $m->avisado_em !== null) {
                continue;
            }

            try {
                $personal = $m->personal ?? Personal::find($m->personal_id);
                if ($personal) {
                    NotificacaoService::personal(
                        $personal,
                        'Mesociclo vencido — hora de trocar o treino',
                        "⏰ *Mesociclo vencido*\n\n" .
                        "O mesociclo *{$m->nome}* do aluno *" . ($m->cliente->nome ?? 'aluno') . "* chegou ao fim.\n" .
                        "Acesse o painel de Periodização para montar o próximo ciclo. 💪",
                        'mesociclo_vencido_personal',
                        [$m->nome, $m->cliente->nome ?? 'aluno']
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Falha ao avisar mesociclo vencido', ['mesociclo' => $m->id, 'error' => $e->getMessage()]);
            }

            $m->update(['avisado_em' => now()]);
        }
    }
}
