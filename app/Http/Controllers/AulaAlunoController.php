<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AulaReposicao;
use App\Models\Estorno;
use App\Models\Payment;
use App\Services\AgendaService;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * O que o ALUNO pode fazer com uma aula dele.
 *
 * A janela é a mesma nos dois casos (24h, `AgendaService`), o que muda é a
 * consequência:
 *   • avulsa — cancela e abre pedido de devolução do que ele pagou;
 *   • pacote — não há o que devolver (o pacote foi pago inteiro), então ele
 *     avisa a falta e pede para repor a aula em outro horário.
 *
 * Fora da janela não dá nem um nem outro: a aula é perdida. É o que segura o
 * personal de ficar com o horário bloqueado e sem receber.
 */
class AulaAlunoController extends Controller
{
    /** Aula do aluno logado, ou null se não é dele. */
    private function aulaDoAluno($id): ?Agenda
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return null;
        }

        return Agenda::where('id', $id)
            ->where('cliente_id', $clienteId)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->first();
    }

    /**
     * Aula AVULSA: cancela e registra o estorno.
     *
     * A aula é marcada como cancelada, não apagada: quando existe dinheiro no
     * meio, o registro precisa sobreviver para o admin conferir o que devolveu.
     */
    public function cancelar(Request $request, $id, AgendaService $agendas)
    {
        $aula = $this->aulaDoAluno($id);
        if (! $aula) {
            return redirect()->route('cliente.index')->with('error', 'Aula não encontrada.');
        }

        $dados = $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        $r = $this->cancelarInterno($aula, $dados['motivo'] ?? null, $agendas);

        if (! $r['ok']) {
            return redirect()->back()->with('error', $r['erro']);
        }

        return redirect()->back()->with('success', $r['mensagem']);
    }

    /**
     * O cancelamento da avulsa em si — a PORTA COMUM do web e do app.
     *
     * Existe separada porque as duas superfícies só diferem na resposta
     * (redirect x JSON); a regra, o estorno e o aviso ao personal têm de ser
     * os mesmos. Mesmo padrão de `agendarAulaAvulsaInterno`, e pelo mesmo
     * motivo: quando a regra foi copiada entre web e app, ela divergiu.
     *
     * Não decide de quem é a aula: quem chama já resolveu isso (sessão no web,
     * token no app).
     *
     * @return array{ok: bool, erro: ?string, mensagem: ?string, estorno: bool}
     */
    public function cancelarInterno(Agenda $aula, ?string $motivo, AgendaService $agendas): array
    {
        $falha = fn (string $erro) => ['ok' => false, 'erro' => $erro, 'mensagem' => null, 'estorno' => false];

        if ($aula->cancelado) {
            return $falha('Essa aula já está cancelada.');
        }

        if ($agendas->ehPacote($aula)) {
            return $falha('Aula de pacote não é cancelada: peça a reposição em outro horário.');
        }

        if ($bloqueio = $agendas->motivoParaAlunoNaoAgir($aula)) {
            return $falha($bloqueio);
        }

        $inicio = $agendas->inicioDaAula($aula);

        DB::transaction(function () use ($aula, $motivo, &$estorno) {
            $aula->update([
                'cancelado' => true,
                'cancelado_em' => now(),
                'justificativa_cancelamento' => trim('Cancelada pelo aluno. ' . ($motivo ?? '')),
            ]);

            $payment = $aula->payment_id ? Payment::find($aula->payment_id) : null;
            $valor = (float) ($payment->amount_total ?? $aula->valor_aula ?? 0);

            // Sem valor e sem pagamento não há o que devolver (aula lançada à
            // mão pelo personal, por exemplo) — cancela e pronto.
            if ($payment || $valor > 0) {
                $estorno = Estorno::create([
                    'payment_id' => $payment?->id,
                    'agenda_id' => $aula->id,
                    'cliente_id' => $aula->cliente_id,
                    'personal_id' => $aula->personal_id,
                    'valor' => $valor,
                    'motivo' => $motivo,
                    'status' => Estorno::STATUS_PENDENTE,
                ]);
            }
        });

        $this->avisarPersonal(
            $aula,
            'Aula cancelada pelo aluno',
            'O aluno cancelou a aula de ' . $inicio->format('d/m/Y \à\s H:i') . ' dentro do prazo de '
                . AgendaService::HORAS_ANTECEDENCIA_CANCELAMENTO . 'h.'
                . ($motivo ? ' Motivo: ' . $motivo : '')
        );

        return [
            'ok' => true,
            'erro' => null,
            'mensagem' => isset($estorno)
                ? 'Aula cancelada. A devolução foi solicitada e você recebe o valor de volta em breve.'
                : 'Aula cancelada.',
            'estorno' => isset($estorno),
        ];
    }

    /**
     * Aula de PACOTE: avisa a falta e pede reposição.
     *
     * A aula original é liberada na hora do pedido — é o que faz o aviso valer
     * alguma coisa para o personal. Se ele recusar a reposição, a aula é
     * perdida (já estava paga dentro do pacote).
     */
    public function pedirReposicao(Request $request, $id, AgendaService $agendas)
    {
        $aula = $this->aulaDoAluno($id);
        if (! $aula) {
            return redirect()->route('cliente.index')->with('error', 'Aula não encontrada.');
        }

        // O aluno só avisa a falta e diz o porquê. Quem marca dia e hora da
        // reposição é o personal — é a agenda dele que manda.
        $dados = $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        $r = $this->pedirReposicaoInterno($aula, $dados['motivo'] ?? null, $agendas);

        if (! $r['ok']) {
            return redirect()->back()->with('error', $r['erro']);
        }

        return redirect()->back()->with('success', $r['mensagem']);
    }

    /**
     * O pedido de reposição em si — a PORTA COMUM do web e do app (ver o
     * comentário de `cancelarInterno`).
     *
     * @return array{ok: bool, erro: ?string, mensagem: ?string}
     */
    public function pedirReposicaoInterno(Agenda $aula, ?string $motivo, AgendaService $agendas): array
    {
        $falha = fn (string $erro) => ['ok' => false, 'erro' => $erro, 'mensagem' => null];

        if ($aula->cancelado) {
            return $falha('Essa aula já foi cancelada.');
        }

        if (! $agendas->ehPacote($aula)) {
            return $falha('Essa aula é avulsa: use o cancelamento, que devolve o valor pago.');
        }

        if ($bloqueio = $agendas->motivoParaAlunoNaoAgir($aula)) {
            return $falha($bloqueio);
        }

        $dados = ['motivo' => $motivo];
        $inicio = $agendas->inicioDaAula($aula);

        DB::transaction(function () use ($aula, $dados) {
            $aula->update([
                'cancelado' => true,
                'cancelado_em' => now(),
                'justificativa_cancelamento' => trim('Falta avisada pelo aluno. ' . ($dados['motivo'] ?? '')),
            ]);

            AulaReposicao::updateOrCreate(
                ['agenda_id' => $aula->id],
                [
                    'cliente_id' => $aula->cliente_id,
                    'personal_id' => $aula->personal_id,
                    'motivo' => $dados['motivo'] ?? null,
                    'status' => AulaReposicao::STATUS_PENDENTE,
                    'resposta' => null,
                    'respondido_em' => null,
                    'agenda_reposta_id' => null,
                ]
            );
        });

        $this->avisarPersonal(
            $aula,
            'Aluno pediu reposição de aula',
            'O aluno avisou que não vai à aula de ' . $inicio->format('d/m/Y \à\s H:i')
                . '. Defina o dia e a hora da reposição no seu painel.'
                . ($dados['motivo'] ?? null ? ' Motivo: ' . $dados['motivo'] : '')
        );

        return [
            'ok' => true,
            'erro' => null,
            'mensagem' => 'Falta avisada! Seu personal vai definir o dia e a hora da reposição.',
        ];
    }

    private function avisarPersonal(Agenda $aula, string $assunto, string $texto): void
    {
        try {
            if ($aula->personal) {
                NotificacaoService::personal($aula->personal, $assunto, $texto);
            }
        } catch (\Throwable $e) {
            // Aviso é melhor esforço: não desfaz um cancelamento já gravado.
        }
    }
}
