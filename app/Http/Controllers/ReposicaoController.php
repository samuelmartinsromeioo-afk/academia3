<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AulaReposicao;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lado do PERSONAL nos pedidos de reposição.
 *
 * Quem decide o horário é ele — é a agenda dele, e "horário vago" não é a mesma
 * coisa que "horário que ele quer trabalhar". Por isso o pedido do aluno é uma
 * sugestão, não um agendamento.
 */
class ReposicaoController extends Controller
{
    private function personalLogado(): ?int
    {
        return session('personal_id') ?: null;
    }

    /** Pedidos do personal, pendentes primeiro. */
    public function index()
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $personal = \App\Models\Cadastro\Personal::findOrFail($personalId);

        $pedidos = AulaReposicao::with(['cliente', 'agenda', 'agendaReposta'])
            ->where('personal_id', $personalId)
            ->orderByRaw("FIELD(status, 'pendente', 'aceita', 'recusada', 'cancelada')")
            ->latest()
            ->get();

        return view('personal.reposicoes', compact('personal', 'pedidos'));
    }

    /**
     * Aceita e cria a aula reposta.
     *
     * O horário final é o que o PERSONAL confirmar: ele pode acatar a sugestão
     * do aluno ou mandar outra. Conflito na agenda barra — senão a reposição
     * criaria duas aulas no mesmo horário.
     */
    public function aceitar(Request $request, $id)
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $pedido = AulaReposicao::where('id', $id)->where('personal_id', $personalId)->firstOrFail();

        if (! $pedido->estaPendente()) {
            return redirect()->back()->with('error', 'Esse pedido já foi respondido.');
        }

        $dados = $request->validate([
            'data' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'resposta' => 'nullable|string|max:500',
        ]);

        $conflito = Agenda::where('personal_id', $personalId)
            ->where('data', $dados['data'])
            ->where('cancelado', false)
            ->whereRaw('hora_inicio < ? AND hora_fim > ?', [$dados['hora_fim'], $dados['hora_inicio']])
            ->exists();

        if ($conflito) {
            return redirect()->back()->with('error', 'Você já tem compromisso nesse horário. Escolha outro para a reposição.');
        }

        $original = $pedido->agenda;

        DB::transaction(function () use ($pedido, $dados, $original, $personalId) {
            $nova = Agenda::create([
                'cliente_id' => $pedido->cliente_id,
                'personal_id' => $personalId,
                'academia_id' => $original->academia_id ?? null,
                'academia_nome' => $original->academia_nome ?? null,
                'data' => $dados['data'],
                'hora_inicio' => $dados['hora_inicio'],
                'hora_fim' => $dados['hora_fim'],
                'cancelado' => false,
                'tipo_aula' => 'pacote',
                'frequencia_pacote' => $original->frequencia_pacote ?? null,
                'valor_aula' => $original->valor_aula ?? null,
                'descricao' => 'Reposição de aula',
            ]);

            $pedido->update([
                'status' => AulaReposicao::STATUS_ACEITA,
                'resposta' => $dados['resposta'] ?? null,
                'respondido_em' => now(),
                'agenda_reposta_id' => $nova->id,
            ]);
        });

        $this->avisarAluno(
            $pedido,
            'Reposição confirmada',
            'Sua aula foi reposta para ' . \Carbon\Carbon::parse($dados['data'])->format('d/m/Y')
                . ' às ' . $dados['hora_inicio'] . '.'
                . ($dados['resposta'] ?? null ? ' ' . $dados['resposta'] : '')
        );

        return redirect()->back()->with('success', 'Reposição confirmada e lançada na sua agenda.');
    }

    /** Recusa — normalmente com uma contraproposta de horário no texto. */
    public function recusar(Request $request, $id)
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $pedido = AulaReposicao::where('id', $id)->where('personal_id', $personalId)->firstOrFail();

        if (! $pedido->estaPendente()) {
            return redirect()->back()->with('error', 'Esse pedido já foi respondido.');
        }

        $dados = $request->validate([
            'resposta' => 'required|string|min:5|max:500',
        ]);

        $pedido->update([
            'status' => AulaReposicao::STATUS_RECUSADA,
            'resposta' => $dados['resposta'],
            'respondido_em' => now(),
        ]);

        $this->avisarAluno($pedido, 'Sobre sua reposição', $dados['resposta']);

        return redirect()->back()->with('success', 'Resposta enviada ao aluno.');
    }

    private function avisarAluno(AulaReposicao $pedido, string $assunto, string $texto): void
    {
        try {
            if ($pedido->cliente) {
                NotificacaoService::cliente($pedido->cliente, $assunto, $texto);
            }
        } catch (\Throwable $e) {
            // Aviso é melhor esforço: não desfaz a resposta já gravada.
        }
    }
}
