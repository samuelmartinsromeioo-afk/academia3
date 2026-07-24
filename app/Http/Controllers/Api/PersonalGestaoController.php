<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Anamnese;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use App\Models\Meta;
use App\Models\Presenca;
use App\Services\EstatisticasTreino;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Gestão do PERSONAL pela API: agenda, frequência dos alunos, acompanhamento
 * (anamnese/progresso/metas/aderência) e preços — espelha os fluxos web de
 * Cadastro\PersonalController, FichaTreinoController (aderência) e afins.
 */
class PersonalGestaoController extends Controller
{
    use ResolvesApiUser;

    private const DIAS_SUMIDO = 7;

    // ===================== AGENDA =====================

    // GET /api/v1/personal/agenda/{data}
    public function agendaDia(Request $request, $data)
    {
        $personal = $this->personalAutenticado($request);

        $agendas = Agenda::with(['academia:id,nome', 'cliente:id,nome,whatsapp'])
            ->where('personal_id', $personal->id)
            ->where('data', $data)
            ->where('cancelado', false)
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'data' => $a->data instanceof Carbon ? $a->data->format('Y-m-d') : $a->data,
                'hora_inicio' => $a->hora_inicio,
                'hora_fim' => $a->hora_fim,
                'descricao' => $a->descricao,
                'tipo_aula' => $a->tipo_aula,
                'concluida' => (bool) ($a->concluida ?? false),
                'cliente' => $a->cliente ? ['id' => $a->cliente->id, 'nome' => $a->cliente->nome] : null,
                'academia_nome' => $a->academia?->nome ?? $a->academia_nome,
            ]);

        return response()->json(['agendas' => $agendas]);
    }

    // POST /api/v1/personal/agenda — bloqueia um horário na agenda
    public function storeHorario(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $request->validate([
            'data' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fim' => 'required',
            'descricao' => 'nullable|string|max:255',
        ]);

        $inicio = $request->hora_inicio;
        $fim = $request->hora_fim;

        if ($inicio < '06:00' || $fim > '22:00') {
            return response()->json(['error' => 'Sua agenda funciona entre 06:00 e 22:00. Ajuste o horário.'], 422);
        }
        if ($inicio >= $fim) {
            return response()->json(['error' => 'A hora de início deve ser anterior à hora de término.'], 422);
        }

        $conflito = Agenda::where('personal_id', $personal->id)
            ->where('data', $request->data)
            ->where('cancelado', false)
            ->where(fn ($q) => $q->where('hora_inicio', '<', $fim)->where('hora_fim', '>', $inicio))
            ->exists();

        if ($conflito) {
            return response()->json(['error' => 'Já existe um compromisso ou aula agendada neste intervalo.'], 409);
        }

        Agenda::create([
            'personal_id' => $personal->id,
            'data' => $request->data,
            'hora_inicio' => $inicio,
            'hora_fim' => $fim,
            'descricao' => $request->descricao ?? 'Ocupado',
            'cancelado' => false,
            'tipo_aula' => 'bloqueio',
        ]);

        return response()->json(['success' => true, 'message' => 'Horário registrado com sucesso!'], 201);
    }

    // POST /api/v1/personal/agenda/{id}/cancelar — regra das 24h + justificativa
    public function cancelarAula(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);

        $agenda = Agenda::findOrFail($id);
        if ($agenda->personal_id !== $personal->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $dataStr = $agenda->data instanceof Carbon ? $agenda->data->format('Y-m-d') : $agenda->data;
        $dataAula = Carbon::parse($dataStr . ' ' . $agenda->hora_inicio);
        $diffHoras = Carbon::now()->diffInHours($dataAula);

        if ($diffHoras < 24) {
            return response()->json(['error' => "O cancelamento só é permitido com 24h de antecedência. Faltam {$diffHoras} horas."], 422);
        }

        $request->validate(['justificativa' => 'required|string|min:10']);

        $cliente = $agenda->cliente_id ? Cliente::find($agenda->cliente_id) : null;

        $dadosEmail = [
            'personal_nome' => $personal->nome,
            'cliente_nome' => $cliente?->nome,
            'data' => $dataStr,
            'hora_inicio' => $agenda->hora_inicio,
            'hora_fim' => $agenda->hora_fim,
            'justificativa' => $request->justificativa,
        ];

        $agenda->delete();

        if ($cliente && $cliente->email) {
            try {
                Mail::send('emails.aula-cancelada', $dadosEmail, function ($message) use ($cliente) {
                    $message->to($cliente->email)->subject('❌ Sua aula foi cancelada - SnrFit');
                });
            } catch (\Exception $e) {
                // e-mail é melhor esforço
            }
        }

        return response()->json(['success' => true, 'message' => 'Aula cancelada com sucesso!']);
    }

    // POST /api/v1/personal/agenda/cancelar-dia
    public function cancelarDia(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $request->validate(['data' => 'required|date']);

        $agendamentos = Agenda::where('personal_id', $personal->id)
            ->where('data', $request->data)
            ->where('cancelado', false)
            ->get();

        if ($agendamentos->isEmpty()) {
            return response()->json(['error' => 'Nenhum compromisso encontrado nesta data.'], 404);
        }

        $cancelados = 0;
        foreach ($agendamentos as $ag) {
            $dataStr = $ag->data instanceof Carbon ? $ag->data->format('Y-m-d') : $ag->data;
            if (Carbon::now()->diffInHours(Carbon::parse($dataStr . ' ' . $ag->hora_inicio)) >= 24) {
                $ag->delete();
                $cancelados++;
            }
        }

        if ($cancelados === 0) {
            return response()->json(['error' => 'Nenhum horário pode ser cancelado. Verifique a regra de 24h de antecedência.'], 422);
        }

        return response()->json(['success' => true, 'message' => "Dia cancelado! {$cancelados} horário(s) liberado(s)."]);
    }

    // POST /api/v1/personal/agenda/bloquear-fixo
    public function bloquearFixo(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $request->validate([
            'hora_inicio' => 'required',
            'hora_fim' => 'required',
            'dias' => 'required|integer|min:1|max:90',
            'descricao' => 'nullable|string|max:255',
        ]);

        $inicio = $request->hora_inicio;
        $fim = $request->hora_fim;

        if ($inicio >= $fim) {
            return response()->json(['error' => 'A hora de início deve ser anterior à hora de término.'], 422);
        }

        $criados = 0;
        $conflitos = 0;

        for ($i = 0; $i < (int) $request->dias; $i++) {
            $data = Carbon::now()->addDays($i)->format('Y-m-d');

            $conflito = Agenda::where('personal_id', $personal->id)
                ->where('data', $data)
                ->where('cancelado', false)
                ->where(fn ($q) => $q->where('hora_inicio', '<', $fim)->where('hora_fim', '>', $inicio))
                ->exists();

            if (! $conflito) {
                Agenda::create([
                    'personal_id' => $personal->id,
                    'data' => $data,
                    'hora_inicio' => $inicio,
                    'hora_fim' => $fim,
                    'descricao' => $request->descricao ?? 'Horário Fixo Bloqueado',
                    'cancelado' => false,
                    'tipo_aula' => 'bloqueio',
                ]);
                $criados++;
            } else {
                $conflitos++;
            }
        }

        $msg = "Bloqueio fixo aplicado em {$criados} dia(s).";
        if ($conflitos > 0) {
            $msg .= " {$conflitos} dia(s) ignorado(s) por conflito de horário.";
        }

        return response()->json(['success' => true, 'message' => $msg], 201);
    }

    // POST /api/v1/personal/aulas/{id}/finalizar
    public function finalizarAula(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);

        $aula = Agenda::findOrFail($id);
        if ($aula->personal_id !== $personal->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $cliente = $aula->cliente_id ? Cliente::find($aula->cliente_id) : null;
        if (! $cliente) {
            return response()->json(['error' => 'Cliente não encontrado.'], 404);
        }

        $aula->update(['concluida' => true]);

        try {
            $dataStr = $aula->data instanceof Carbon ? $aula->data->format('d/m/Y') : Carbon::parse($aula->data)->format('d/m/Y');
            NotificacaoService::cliente(
                $cliente,
                'Aula concluída! 💪',
                "✅ *Aula concluída*\n\nSua aula de {$dataStr} ({$aula->hora_inicio} - {$aula->hora_fim}) com *{$personal->nome}* foi finalizada.\n\nBom treino e até a próxima! 💪",
                'aula_concluida_cliente',
                [$dataStr, "{$aula->hora_inicio} - {$aula->hora_fim}", $personal->nome]
            );
        } catch (\Exception $e) {
            // notificação é melhor esforço
        }

        return response()->json(['success' => true, 'message' => '✅ Aula finalizada! Cliente foi notificado.']);
    }

    // ===================== FREQUÊNCIA =====================

    // GET /api/v1/personal/frequencia
    public function frequencia(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $alunos = $this->alunosDoPersonal($personal->id);
        $presencas = Presenca::where('personal_id', $personal->id)->get()->groupBy('cliente_id');

        $lista = $alunos->map(function ($aluno) use ($presencas) {
            $regs = $presencas->get($aluno->id, collect());
            $total = $regs->count();
            $presentes = $regs->where('presente', true)->count();

            return [
                'cliente' => ['id' => $aluno->id, 'nome' => $aluno->nome],
                'total' => $total,
                'presentes' => $presentes,
                'faltas' => $total - $presentes,
                'classificacao' => $this->classificarFrequencia($total, $presentes),
            ];
        });

        return response()->json(['alunos' => $lista]);
    }

    // GET /api/v1/personal/frequencia/{clienteId}?mes=YYYY-MM
    public function frequenciaAluno(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);

        if (! $this->alunosDoPersonal($personal->id)->pluck('id')->contains((int) $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $cliente = Cliente::findOrFail($clienteId);

        $mes = $request->query('mes', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = now()->format('Y-m');
        }

        $todas = Presenca::where('personal_id', $personal->id)
            ->where('cliente_id', $clienteId)
            ->orderByDesc('data')
            ->get();
        $total = $todas->count();
        $presentes = $todas->where('presente', true)->count();

        $diasAgenda = Agenda::where('personal_id', $personal->id)
            ->where('cliente_id', $clienteId)
            ->where('cancelado', false)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->whereYear('data', substr($mes, 0, 4))
            ->whereMonth('data', substr($mes, 5, 2))
            ->get()
            ->map(fn ($a) => $a->data->format('Y-m-d'))
            ->unique()->sort()->values();

        return response()->json([
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome],
            'total' => $total,
            'presentes' => $presentes,
            'faltas' => $total - $presentes,
            'classificacao' => $this->classificarFrequencia($total, $presentes),
            'mes' => $mes,
            'dias_agenda' => $diasAgenda,
            'registros' => $todas->map(fn ($p) => [
                'id' => $p->id,
                'data' => $p->data->format('Y-m-d'),
                'presente' => (bool) $p->presente,
            ]),
        ]);
    }

    // POST /api/v1/personal/frequencia
    public function marcarPresenca(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $dados = $request->validate([
            'cliente_id' => 'required|integer',
            'data' => 'required|date',
            'presente' => 'required|boolean',
        ]);

        if (! $this->alunosDoPersonal($personal->id)->pluck('id')->contains((int) $dados['cliente_id'])) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        Presenca::updateOrCreate(
            ['personal_id' => $personal->id, 'cliente_id' => $dados['cliente_id'], 'data' => $dados['data']],
            ['presente' => (bool) $dados['presente']]
        );

        return response()->json(['success' => true, 'message' => 'Frequência registrada!']);
    }

    // DELETE /api/v1/personal/frequencia/{id}
    public function removerPresenca(Request $request, $id)
    {
        $personal = $this->personalAutenticado($request);

        Presenca::where('personal_id', $personal->id)->where('id', $id)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Registro removido.']);
    }

    // ===================== ALUNOS E ACOMPANHAMENTO =====================

    // GET /api/v1/personal/alunos/{clienteId} — detalhes + pacote
    public function detalhesAluno(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);

        if (! $this->podeVer($personal->id, $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $cliente = Cliente::findOrFail($clienteId);

        $pacote = null;
        $aula = Agenda::where('personal_id', $personal->id)
            ->where('cliente_id', $clienteId)
            ->where('cancelado', false)
            ->whereNotNull('frequencia_pacote')
            ->first();

        if ($aula?->frequencia_pacote) {
            $p = Pacote::where('personal_id', $personal->id)->where('frequencia', $aula->frequencia_pacote)->first();
            if ($p) {
                $pacote = ['frequencia' => (int) $p->frequencia, 'valor_mensal' => (float) $p->valor_mensal];
            }
        }

        return response()->json([
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
                'email' => $cliente->email,
                'whatsapp' => $cliente->whatsapp,
                'idade' => $cliente->idade,
                'condicao_clinica' => $cliente->condicao_clinica ?? null,
            ],
            'pacote' => $pacote,
        ]);
    }

    // GET /api/v1/personal/alunos/{clienteId}/anamnese
    public function anamneseAluno(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);

        if (! $this->podeVer($personal->id, $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        return response()->json([
            'cliente' => Cliente::select('id', 'nome')->findOrFail($clienteId),
            'anamnese' => Anamnese::where('cliente_id', $clienteId)->first(),
        ]);
    }

    // GET /api/v1/personal/alunos/{clienteId}/progresso
    public function progressoAluno(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);

        if (! $this->podeVer($personal->id, $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $medidas = MedidaCorporal::where('cliente_id', $clienteId)->orderBy('data')->get();
        $fotos = FotoProgresso::where('cliente_id', $clienteId)->orderByDesc('data')->get();
        $metas = Meta::where('cliente_id', $clienteId)->orderBy('concluida')->orderByDesc('created_at')->get();

        return response()->json([
            'cliente' => Cliente::select('id', 'nome')->findOrFail($clienteId),
            'medidas' => $medidas->map(function ($m) {
                $json = ['id' => $m->id, 'data' => $m->data?->toDateString(), 'observacoes' => $m->observacoes];
                foreach (array_keys(MedidaCorporal::CAMPOS) as $campo) {
                    $json[$campo] = $m->$campo !== null ? (float) $m->$campo : null;
                }
                return $json;
            }),
            'fotos' => $fotos->map(fn ($f) => [
                'id' => $f->id,
                'data' => $f->data instanceof Carbon ? $f->data->toDateString() : $f->data,
                'url' => $f->caminho ? Storage::disk('public')->url($f->caminho) : null,
                'peso' => $f->peso !== null ? (float) $f->peso : null,
            ]),
            'metas' => $metas->map(fn (Meta $m) => [
                'id' => $m->id,
                'tipo' => $m->tipo,
                'titulo' => $m->titulo,
                'alvo' => $m->alvo !== null ? (float) $m->alvo : null,
                'exercicio' => $m->exercicio,
                'concluida' => (bool) $m->concluida,
                'progresso' => $m->calcularProgresso(),
            ]),
        ]);
    }

    // POST /api/v1/personal/alunos/{clienteId}/metas
    public function criarMetaAluno(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);

        if (! $this->podeVer($personal->id, $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $request->validate([
            'tipo' => 'required|in:treinos_mes,carga,livre',
            'titulo' => 'required|string|max:255',
            'alvo' => 'nullable|numeric|min:0|max:99999',
            'exercicio' => 'nullable|string|max:255',
            'prazo' => 'nullable|date',
        ]);

        Meta::create([
            'cliente_id' => $clienteId,
            'criada_por_personal_id' => $personal->id,
            'tipo' => $request->tipo,
            'titulo' => $request->titulo,
            'alvo' => $request->tipo === 'livre' ? null : $request->alvo,
            'exercicio' => $request->tipo === 'carga' ? $request->exercicio : null,
            'prazo' => $request->prazo,
        ]);

        return response()->json(['success' => true, 'message' => 'Meta definida para o aluno! 🎯'], 201);
    }

    // GET /api/v1/personal/alunos/{clienteId}/evolucao-carga?exercicio=&dias=
    public function evolucaoCargaAluno(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);

        if (! $this->podeVer($personal->id, $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $exercicios = RegistroExercicio::where('cliente_id', $clienteId)
            ->distinct()->orderBy('nome_exercicio')->pluck('nome_exercicio');

        $dias = (int) $request->query('dias', 90);
        if (! in_array($dias, [30, 90, 180], true)) {
            $dias = 90;
        }

        $query = RegistroExercicio::where('cliente_id', $clienteId)
            ->whereNotNull('peso')
            ->where('data_treino', '>=', now()->subDays($dias)->format('Y-m-d'));

        if ($request->query('exercicio')) {
            $query->where('nome_exercicio', $request->query('exercicio'));
        }

        $porData = [];
        foreach ($query->orderBy('data_treino')->get(['data_treino', 'peso', 'repeticoes', 'series']) as $r) {
            $d = $r->data_treino->format('Y-m-d');
            if (! isset($porData[$d]) || (float) $r->peso > $porData[$d]['peso']) {
                $porData[$d] = ['peso' => (float) $r->peso, 'reps' => $r->repeticoes, 'series' => $r->series];
            }
        }
        ksort($porData);

        return response()->json([
            'exercicios' => $exercicios,
            'pontos' => collect($porData)->map(fn ($v, $d) => [
                'data' => $d, 'peso' => $v['peso'], 'repeticoes' => $v['reps'], 'series' => $v['series'],
            ])->values(),
        ]);
    }

    // GET /api/v1/personal/aderencia
    public function aderencia(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $fichas = FichaTreino::where('personal_id', $personal->id)->where('ativo', true)->get();
        $fichasPorCliente = $fichas->groupBy('cliente_id');

        $clientes = Cliente::whereIn('id', $fichasPorCliente->keys())->orderBy('nome')->get();

        $alunos = [];
        $somaAderencia = 0;
        $comPlano = 0;
        $sumidos = 0;

        foreach ($clientes as $cliente) {
            $resumo = $this->aderenciaResumo($cliente->id, $fichasPorCliente->get($cliente->id, collect()));
            $resumo['cliente'] = ['id' => $cliente->id, 'nome' => $cliente->nome];
            $alunos[] = $resumo;

            if ($resumo['aderencia'] !== null) {
                $somaAderencia += $resumo['aderencia'];
                $comPlano++;
            }
            if ($resumo['sumido']) {
                $sumidos++;
            }
        }

        usort($alunos, function ($a, $b) {
            if ($a['sumido'] !== $b['sumido']) {
                return $b['sumido'] <=> $a['sumido'];
            }
            return ($a['aderencia'] ?? 101) <=> ($b['aderencia'] ?? 101);
        });

        return response()->json([
            'alunos' => $alunos,
            'resumo' => [
                'total_alunos' => count($alunos),
                'media_aderencia' => $comPlano > 0 ? (int) round($somaAderencia / $comPlano) : null,
                'sumidos' => $sumidos,
            ],
        ]);
    }

    // POST /api/v1/personal/aderencia/cutucar/{clienteId}
    public function cutucar(Request $request, $clienteId)
    {
        $personal = $this->personalAutenticado($request);

        if (! $this->podeVer($personal->id, $clienteId)) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $cliente = Cliente::find($clienteId);
        if ($cliente) {
            $nome = explode(' ', trim($cliente->nome))[0];

            NotificacaoService::cliente(
                $cliente,
                'Senti sua falta nos treinos! 💪',
                "Olá, {$nome}! 👋\n\n" .
                "Notei que você está um tempinho sem treinar. Bora retomar o ritmo? Cada treino conta — e seu shape agradece! 🔥\n\n" .
                "Conte comigo. — {$personal->nome}",
                'incentivo_aluno',
                [$nome]
            );
        }

        return response()->json(['success' => true, 'message' => 'Incentivo enviado para ' . ($cliente->nome ?? 'o aluno') . '! 💪']);
    }

    // ===================== PREÇOS =====================

    // GET /api/v1/personal/precos
    public function precos(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        return response()->json([
            'pacotes' => Pacote::where('personal_id', $personal->id)->orderBy('frequencia')->get()
                ->map(fn ($p) => ['frequencia' => (int) $p->frequencia, 'valor_mensal' => (float) $p->valor_mensal]),
            'valor_secao' => $personal->valor_secao !== null ? (float) $personal->valor_secao : null,
            'valor_ficha' => $personal->valor_ficha !== null ? (float) $personal->valor_ficha : null,
        ]);
    }

    // POST /api/v1/personal/precos — { precos: {1: 400, 2: 700, ...} }
    public function salvarPrecos(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $request->validate([
            'precos' => 'required|array',
            'precos.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($request->precos as $frequencia => $valor) {
            if ($valor) {
                Pacote::updateOrCreate(
                    ['personal_id' => $personal->id, 'frequencia' => $frequencia],
                    ['valor_mensal' => $valor]
                );
            }
        }

        return response()->json(['success' => true, 'message' => 'Tabela de preços atualizada com sucesso!']);
    }

    // ===================== HELPERS =====================

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

    private function classificarFrequencia(int $total, int $presentes): array
    {
        if ($total === 0) {
            return ['key' => 'sem_registro', 'label' => 'Sem registros', 'taxa' => null];
        }
        $taxa = $presentes / $total;
        if ($taxa >= 0.8) return ['key' => 'frequente', 'label' => 'Frequente', 'taxa' => round($taxa, 2)];
        if ($taxa >= 0.5) return ['key' => 'medio', 'label' => 'Mais ou menos', 'taxa' => round($taxa, 2)];
        return ['key' => 'ausente', 'label' => 'Pouco frequente', 'taxa' => round($taxa, 2)];
    }

    private function podeVer($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $personalId)->exists();
    }

    private function aderenciaResumo($clienteId, $fichas): array
    {
        $inicio = now()->startOfMonth();
        $hoje = now();

        $planejados = 0;
        foreach ($fichas as $f) {
            $d = $inicio->copy()->startOfDay();
            while ($d->lte($hoje)) {
                if ($d->dayOfWeek === (int) $f->dia_semana) {
                    $planejados++;
                }
                $d->addDay();
            }
        }

        $fichaIds = $fichas->pluck('id');

        $realizados = $fichaIds->isEmpty() ? 0 : TreinoConcluido::whereIn('ficha_id', $fichaIds)
            ->where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->whereBetween('data_treino', [$inicio->toDateString(), $hoje->toDateString()])
            ->count();

        $aderencia = $planejados > 0 ? min(100, (int) round($realizados / $planejados * 100)) : null;

        $ultimoTreino = $fichaIds->isEmpty() ? null : TreinoConcluido::whereIn('ficha_id', $fichaIds)
            ->where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->orderByDesc('data_treino')
            ->first();

        $ultimo = $ultimoTreino?->data_treino;
        $diasSemTreino = $ultimo ? (int) Carbon::parse($ultimo)->startOfDay()->diffInDays(now()->startOfDay()) : null;

        return [
            'planejados' => $planejados,
            'realizados' => $realizados,
            'aderencia' => $aderencia,
            'ultimo_treino' => $ultimo ? Carbon::parse($ultimo)->toDateString() : null,
            'dias_sem_treino' => $diasSemTreino,
            'sumido' => $diasSemTreino === null || $diasSemTreino >= self::DIAS_SUMIDO,
            'fichas_ativas' => $fichas->count(),
        ];
    }
}
