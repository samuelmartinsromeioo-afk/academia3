<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AulaAlunoController as WebAulaAlunoController;
use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\AulaReposicao;
use App\Services\AgendaService;
use Illuminate\Http\Request;

/**
 * As aulas do ALUNO no app: listar, cancelar avulsa, pedir reposição de pacote.
 *
 * O app não tinha NADA disso — o aluno agendava e nunca mais via a aula, então
 * também não tinha como desmarcar. Quem faltava simplesmente não aparecia, e o
 * personal ficava com o horário bloqueado sem aviso.
 *
 * As duas ações delegam para `cancelarInterno`/`pedirReposicaoInterno` do
 * controller WEB: mesma regra de 24h, mesmo estorno, mesmo aviso ao personal,
 * mesmo texto de justificativa. É o padrão que o projeto já usa em
 * `ExplorarController` com `agendarAulaAvulsaInterno`, e pelo motivo de sempre
 * — quando a regra foi copiada entre web e app, ela divergiu.
 */
class AulaAlunoController extends Controller
{
    use ResolvesApiUser;

    public function __construct(private AgendaService $agendas)
    {
    }

    /** O controller web, resolvido como em ExplorarController (`app(...)`). */
    private function web(): WebAulaAlunoController
    {
        return app(WebAulaAlunoController::class);
    }

    /**
     * GET /api/v1/minhas-aulas
     *
     * Devolve as aulas futuras e as recentes, cada uma já com o que o app pode
     * OFERECER (`pode_cancelar` / `pode_pedir_reposicao`) e, quando não pode, o
     * MOTIVO em texto. A decisão é do servidor: o app só desenha.
     *
     * Mandar o motivo pronto não é conveniência — é o que faz a tela explicar
     * "faltam 6h, o prazo é 24h" em vez de um botão cinza sem explicação, que é
     * o tipo de interface que gera ticket de suporte.
     */
    public function index(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $desde = $this->agendas->agora()->subDays(30)->format('Y-m-d');

        $aulas = Agenda::where('cliente_id', $cliente->id)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->whereDate('data', '>=', $desde)
            ->with(['personal:id,nome,foto,modalidade'])
            ->orderBy('data')
            ->orderBy('hora_inicio')
            ->limit(200)
            ->get();

        // Uma query para todos os pedidos de reposição, em vez de uma por aula.
        $reposicoes = AulaReposicao::whereIn('agenda_id', $aulas->pluck('id'))
            ->get()
            ->keyBy('agenda_id');

        return response()->json([
            'horas_antecedencia' => AgendaService::HORAS_ANTECEDENCIA_CANCELAMENTO,
            'aulas' => $aulas->map(fn ($aula) => $this->payload($aula, $reposicoes->get($aula->id))),
        ]);
    }

    /** POST /api/v1/aulas/{id}/cancelar — só aula avulsa (devolve o valor). */
    public function cancelar(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);
        $aula = $this->aulaDoAluno($cliente->id, $id);

        $dados = $request->validate(['motivo' => 'nullable|string|max:500']);

        $r = $this->web()->cancelarInterno($aula, $dados['motivo'] ?? null, $this->agendas);

        if (! $r['ok']) {
            // 422: o pedido está bem formado, mas a regra de negócio recusa
            // (fora do prazo, tipo errado, já cancelada).
            return response()->json(['error' => $r['erro']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $r['mensagem'],
            'estorno_solicitado' => $r['estorno'],
        ]);
    }

    /** POST /api/v1/aulas/{id}/reposicao — só aula de pacote (avisa a falta). */
    public function reposicao(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);
        $aula = $this->aulaDoAluno($cliente->id, $id);

        $dados = $request->validate(['motivo' => 'nullable|string|max:500']);

        $r = $this->web()->pedirReposicaoInterno($aula, $dados['motivo'] ?? null, $this->agendas);

        if (! $r['ok']) {
            return response()->json(['error' => $r['erro']], 422);
        }

        return response()->json(['success' => true, 'message' => $r['mensagem']]);
    }

    /**
     * A aula, se for DESTE aluno. 404 caso contrário.
     *
     * O dono vem do token, nunca do request (A01): sem o `cliente_id` na
     * cláusula, qualquer aluno cancelaria a aula de outro mandando o id.
     */
    private function aulaDoAluno(int $clienteId, $id): Agenda
    {
        $aula = Agenda::where('id', $id)
            ->where('cliente_id', $clienteId)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->first();

        if (! $aula) {
            abort(response()->json(['error' => 'Aula não encontrada.'], 404));
        }

        return $aula;
    }

    private function payload(Agenda $aula, ?AulaReposicao $reposicao): array
    {
        $ehPacote = $this->agendas->ehPacote($aula);
        $inicio = $this->agendas->inicioDaAula($aula);
        $bloqueio = $aula->cancelado ? null : $this->agendas->motivoParaAlunoNaoAgir($aula);
        $noPrazo = ! $aula->cancelado && $bloqueio === null;

        return [
            'id' => $aula->id,
            'data' => $inicio->format('Y-m-d'),
            'hora_inicio' => $aula->hora_inicio ? substr((string) $aula->hora_inicio, 0, 5) : null,
            'hora_fim' => $aula->hora_fim ? substr((string) $aula->hora_fim, 0, 5) : null,
            'inicio_em' => $inicio->toIso8601String(),
            'tipo' => $ehPacote ? 'pacote' : 'avulsa',
            // 3º nível da modalidade: como ESTA aula acontece. Null quando o
            // profissional é híbrido e ninguém escolheu — é o que o app mostra
            // como "a combinar" em vez de inventar presencial.
            'modalidade' => $aula->modalidade,
            'valor' => $aula->valor_aula !== null ? (float) $aula->valor_aula : null,
            'cancelado' => (bool) $aula->cancelado,
            'passou' => $inicio->lte($this->agendas->agora()),
            'personal' => $aula->personal ? [
                'id' => $aula->personal->id,
                'nome' => $aula->personal->nome,
                'foto' => $this->urlPublica($aula->personal->foto),
            ] : null,

            /*
             * As duas ações são mutuamente exclusivas POR TIPO, não uma escolha
             * do aluno: avulsa cancela (e o dinheiro volta); pacote foi pago
             * inteiro, então não há o que devolver e o caminho é repor a aula.
             */
            'pode_cancelar' => $noPrazo && ! $ehPacote,
            'pode_pedir_reposicao' => $noPrazo && $ehPacote,
            'motivo_bloqueio' => $bloqueio,

            'reposicao' => $reposicao ? [
                'status' => $reposicao->status,
                'motivo' => $reposicao->motivo,
                'resposta' => $reposicao->resposta,
                // Quem marca dia e hora da reposição é o PERSONAL (é a agenda
                // dele que manda), então o aluno só acompanha.
                'agenda_reposta_id' => $reposicao->agenda_reposta_id,
            ] : null,
        ];
    }

    /** URL pública da foto — mesma conversão de ExplorarController::urlPublica. */
    private function urlPublica(?string $caminho): ?string
    {
        if (! $caminho) {
            return null;
        }

        return str_starts_with($caminho, 'http')
            ? $caminho
            : \Illuminate\Support\Facades\Storage::disk('public')->url($caminho);
    }
}
