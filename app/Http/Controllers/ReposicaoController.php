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

    /**
     * Tudo que os alunos desmarcaram, com o que dá para fazer em cada caso.
     *
     * Por que `cancelado = true` basta para dizer "foi o aluno": todo
     * cancelamento feito pelo PERSONAL apaga a linha da agenda
     * (`PersonalController::cancelarAula` e `cancelarDia`, e os equivalentes na
     * API). Só o `AulaAlunoController` marca a aula como cancelada e a mantém.
     * Se algum dia o lado do personal virar soft-delete, este filtro precisa de
     * um `cancelado_por`.
     */
    public function index()
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $personal = \App\Models\Cadastro\Personal::findOrFail($personalId);

        $faltas = Agenda::with('cliente:id,nome')
            ->where('personal_id', $personalId)
            ->where('cancelado', true)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->orderByDesc('data')
            ->orderByDesc('hora_inicio')
            ->limit(80)
            ->get();

        // Pedido de reposição e estorno indexados por aula, para a tela não
        // consultar o banco dentro do laço.
        $pedidos = AulaReposicao::with('agendaReposta')
            ->whereIn('agenda_id', $faltas->pluck('id'))
            ->get()
            ->keyBy('agenda_id');

        $estornos = \App\Models\Estorno::whereIn('agenda_id', $faltas->pluck('id'))
            ->get()
            ->keyBy('agenda_id');

        return view('personal.reposicoes', [
            'personal' => $personal,
            'faltas' => $faltas,
            'pedidos' => $pedidos,
            'estornos' => $estornos,
            'pendentes' => $pedidos->where('status', AulaReposicao::STATUS_PENDENTE)->count(),
        ]);
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

    /**
     * Remarca uma aula AVULSA que o aluno cancelou.
     *
     * Diferente do pacote, aqui existe dinheiro no meio: o cancelamento abriu um
     * pedido de devolução. Se ele ainda está pendente, remarcar o encerra como
     * `remarcado` — o aluno recebe a aula em vez do valor, e não as duas coisas.
     * Se o admin já devolveu, a aula sai de graça; a tela avisa o personal disso
     * antes, e a decisão é dele.
     */
    public function remarcarAvulsa(Request $request, $agendaId)
    {
        $personalId = $this->personalLogado();
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $original = Agenda::where('id', $agendaId)
            ->where('personal_id', $personalId)
            ->where('cancelado', true)
            ->firstOrFail();

        if ($original->tipo_aula === 'pacote') {
            return redirect()->back()->with('error', 'Aula de pacote se remarca pelo pedido de reposição.');
        }

        if (AulaReposicao::where('agenda_id', $original->id)->exists()) {
            return redirect()->back()->with('error', 'Essa aula já foi remarcada.');
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
            return redirect()->back()->with('error', 'Você já tem compromisso nesse horário. Escolha outro para a remarcação.');
        }

        $estorno = \App\Models\Estorno::where('agenda_id', $original->id)->first();

        DB::transaction(function () use ($original, $dados, $personalId, $estorno) {
            $nova = Agenda::create([
                'cliente_id' => $original->cliente_id,
                'personal_id' => $personalId,
                // Segue apontando para o mesmo pagamento: a aula é a que ele já pagou.
                'payment_id' => $original->payment_id,
                'academia_id' => $original->academia_id ?? null,
                'academia_nome' => $original->academia_nome ?? null,
                'data' => $dados['data'],
                'hora_inicio' => $dados['hora_inicio'],
                'hora_fim' => $dados['hora_fim'],
                'cancelado' => false,
                'tipo_aula' => 'avulsa',
                'valor_aula' => $original->valor_aula ?? null,
                'descricao' => 'Reposição de aula',
            ]);

            // Registro no mesmo formato do pacote, para o histórico da tela ser
            // um só independente do tipo da aula.
            AulaReposicao::create([
                'agenda_id' => $original->id,
                'cliente_id' => $original->cliente_id,
                'personal_id' => $personalId,
                'agenda_reposta_id' => $nova->id,
                'resposta' => $dados['resposta'] ?? null,
                'status' => AulaReposicao::STATUS_ACEITA,
                'respondido_em' => now(),
            ]);

            // Aula remarcada não é devolvida: o aluno não fica com as duas coisas.
            if ($estorno && $estorno->status === \App\Models\Estorno::STATUS_PENDENTE) {
                $estorno->update([
                    'status' => \App\Models\Estorno::STATUS_REMARCADO,
                    'observacao_admin' => 'Aula remarcada pelo personal — não há valor a devolver.',
                    'resolvido_em' => now(),
                ]);
            }
        });

        $jaDevolvido = $estorno && $estorno->status === \App\Models\Estorno::STATUS_DEVOLVIDO;

        $this->avisarClienteDireto(
            $original->cliente,
            'Sua aula foi remarcada',
            'Sua aula cancelada foi remarcada para ' . \Carbon\Carbon::parse($dados['data'])->format('d/m/Y')
                . ' às ' . $dados['hora_inicio'] . '.'
                . ($jaDevolvido ? '' : ' Como a aula será dada, o valor pago não será devolvido.')
                . ($dados['resposta'] ?? null ? ' ' . $dados['resposta'] : '')
        );

        return redirect()->back()->with('success', $jaDevolvido
            ? 'Aula remarcada. Atenção: o valor já havia sido devolvido ao aluno, então essa aula não será paga.'
            : 'Aula remarcada e devolução cancelada — o aluno recebe a aula no lugar do valor.');
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
        $this->avisarClienteDireto($pedido->cliente, $assunto, $texto);
    }

    private function avisarClienteDireto($cliente, string $assunto, string $texto): void
    {
        try {
            if ($cliente) {
                NotificacaoService::cliente($cliente, $assunto, $texto);
            }
        } catch (\Throwable $e) {
            // Aviso é melhor esforço: não desfaz a resposta já gravada.
        }
    }
}
