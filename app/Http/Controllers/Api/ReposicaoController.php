<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReposicaoController as WebReposicaoController;
use App\Models\Agenda;
use App\Models\AulaReposicao;
use App\Models\Estorno;
use App\Services\AgendaService;
use Illuminate\Http\Request;

/**
 * Lado do PERSONAL nas faltas e reposições, no app.
 *
 * Existe porque o app abriu o lado do ALUNO (pedir reposição, cancelar avulsa)
 * e deixou o personal sem como responder: ele recebia a notificação e tinha de
 * abrir o site para marcar o horário. Meia funcionalidade é pior que nenhuma —
 * o aluno fica esperando uma resposta que não tem como vir.
 *
 * As três ações delegam para as portas comuns do controller web
 * (`aceitarInterno`, `recusarInterno`, `remarcarAvulsaInterno`), então a
 * checagem de conflito, o encerramento do estorno como `remarcado` e o aviso ao
 * aluno são os mesmos nas duas superfícies.
 *
 * Quem decide o horário é o personal: o pedido do aluno é sugestão, não
 * agendamento, porque "horário vago" não é o mesmo que "horário que ele quer
 * trabalhar". Por isso a listagem já entrega os dias com horário livre.
 */
class ReposicaoController extends Controller
{
    use ResolvesApiUser;

    public function __construct(private AgendaService $agendas)
    {
    }

    private function web(): WebReposicaoController
    {
        return app(WebReposicaoController::class);
    }

    /**
     * GET /api/v1/personal/reposicoes
     *
     * Tudo que os alunos desmarcaram + o que dá para fazer em cada caso + os
     * horários livres do personal.
     *
     * `cancelado = true` basta para dizer "foi o aluno": todo cancelamento do
     * lado do PERSONAL apaga a linha da agenda; só o AulaAlunoController marca
     * como cancelada e mantém. (Mesma observação do controller web — se algum
     * dia o lado do personal virar soft-delete, isto precisa de `cancelado_por`.)
     */
    public function index(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $faltas = Agenda::with('cliente:id,nome')
            ->where('personal_id', $personal->id)
            ->where('cancelado', true)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->orderByDesc('data')
            ->orderByDesc('hora_inicio')
            ->limit(80)
            ->get();

        // Indexados por aula, para não consultar o banco dentro do laço.
        $pedidos = AulaReposicao::whereIn('agenda_id', $faltas->pluck('id'))->get()->keyBy('agenda_id');
        $estornos = Estorno::whereIn('agenda_id', $faltas->pluck('id'))->get()->keyBy('agenda_id');

        /*
         * Horários livres por DURAÇÃO, não por falta: repor uma aula de 1h em
         * 1h. Montar a grade uma vez por duração e reaproveitar evita uma
         * varredura de 21 dias por pedido (era o que faria a tela ficar lenta
         * com muitas faltas).
         */
        $porDuracao = [];
        foreach ($faltas as $falta) {
            $dur = $this->web()->duracaoEmMinutos($falta);
            if (! isset($porDuracao[$dur])) {
                $porDuracao[$dur] = $this->agendas->diasComHorarioLivre($personal->id, 21, $dur);
            }
        }

        return response()->json([
            'pendentes' => $pedidos->where('status', AulaReposicao::STATUS_PENDENTE)->count(),
            'faltas' => $faltas->map(function ($falta) use ($pedidos, $estornos) {
                $pedido = $pedidos->get($falta->id);
                $estorno = $estornos->get($falta->id);
                $ehPacote = $falta->tipo_aula === 'pacote';
                $duracao = $this->web()->duracaoEmMinutos($falta);

                return [
                    'agenda_id' => $falta->id,
                    'aluno' => $falta->cliente?->nome ?? 'Aluno removido',
                    'data' => $falta->data instanceof \Carbon\Carbon
                        ? $falta->data->format('Y-m-d')
                        : (string) $falta->data,
                    'hora_inicio' => $falta->hora_inicio ? substr((string) $falta->hora_inicio, 0, 5) : null,
                    'hora_fim' => $falta->hora_fim ? substr((string) $falta->hora_fim, 0, 5) : null,
                    'duracao_min' => $duracao,
                    'tipo' => $ehPacote ? 'pacote' : 'avulsa',
                    'justificativa' => $falta->justificativa_cancelamento,

                    'pedido' => $pedido ? [
                        'id' => $pedido->id,
                        'status' => $pedido->status,
                        'motivo' => $pedido->motivo,
                        'resposta' => $pedido->resposta,
                        'agenda_reposta_id' => $pedido->agenda_reposta_id,
                    ] : null,

                    /*
                     * Dinheiro: só a avulsa tem estorno (o pacote foi pago
                     * inteiro). `ja_devolvido` é o aviso de que remarcar vai
                     * dar a aula de graça — a decisão é do personal, mas ele
                     * precisa saber ANTES de confirmar.
                     */
                    'estorno' => $estorno ? [
                        'status' => $estorno->status,
                        'valor' => (float) $estorno->valor,
                        'ja_devolvido' => $estorno->status === Estorno::STATUS_DEVOLVIDO,
                    ] : null,

                    // O que o app deve oferecer nesta linha.
                    'pode_aceitar' => (bool) ($pedido && $pedido->estaPendente()),
                    'pode_recusar' => (bool) ($pedido && $pedido->estaPendente()),
                    'pode_remarcar' => ! $ehPacote && ! $pedido,
                ];
            }),
            // Indexado por duração em minutos; o app usa a da falta que abriu.
            'horarios_livres' => $porDuracao,
        ]);
    }

    /** POST /api/v1/personal/reposicoes/{id}/aceitar */
    public function aceitar(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);
        $pedido = $this->pedidoDoPersonal($personal->id, $id);

        $dados = $request->validate([
            'data' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'resposta' => 'nullable|string|max:500',
        ]);

        $r = $this->web()->aceitarInterno($pedido, $dados, $this->agendas);

        if (! $r['ok']) {
            return response()->json(['error' => $r['erro']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $r['mensagem'],
            'agenda_id' => $r['agenda_id'],
        ]);
    }

    /** POST /api/v1/personal/reposicoes/{id}/recusar */
    public function recusar(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);
        $pedido = $this->pedidoDoPersonal($personal->id, $id);

        // Resposta obrigatória: recusar sem dizer nada deixa o aluno sem saber
        // o que fazer. Mesma regra do web.
        $dados = $request->validate(['resposta' => 'required|string|min:5|max:500']);

        $r = $this->web()->recusarInterno($pedido, $dados['resposta']);

        if (! $r['ok']) {
            return response()->json(['error' => $r['erro']], 422);
        }

        return response()->json(['success' => true, 'message' => $r['mensagem']]);
    }

    /** POST /api/v1/personal/faltas/{agendaId}/remarcar — aula AVULSA. */
    public function remarcar(Request $request, $agendaId)
    {
        $personal = $this->personalAutenticado($request);

        $original = Agenda::where('id', $agendaId)
            ->where('personal_id', $personal->id)
            ->where('cancelado', true)
            ->first();

        if (! $original) {
            abort(response()->json(['error' => 'Aula não encontrada.'], 404));
        }

        $dados = $request->validate([
            'data' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'resposta' => 'nullable|string|max:500',
        ]);

        $r = $this->web()->remarcarAvulsaInterno($original, $dados, $this->agendas);

        if (! $r['ok']) {
            return response()->json(['error' => $r['erro']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $r['mensagem'],
            'ja_devolvido' => $r['ja_devolvido'],
        ]);
    }

    /**
     * O pedido, se for DESTE personal. 404 caso contrário.
     *
     * O dono vem do token, nunca do request (A01): sem o `personal_id` na
     * cláusula, um personal responderia o pedido de outro mandando o id.
     */
    private function pedidoDoPersonal(int $personalId, $id): AulaReposicao
    {
        $pedido = AulaReposicao::where('id', $id)->where('personal_id', $personalId)->first();

        if (! $pedido) {
            abort(response()->json(['error' => 'Pedido não encontrado.'], 404));
        }

        return $pedido;
    }
}
