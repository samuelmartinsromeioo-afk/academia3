<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Services\MetaConversionsService;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Academia as Academia;
use App\Models\Cadastro\Studio;
use App\Models\Cadastro\Loja;
use App\Models\Agenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index()
    {
        $id = session('cliente_id');
        if (!$id) return redirect()->route('login.index');

        $cliente = Cliente::find($id);
        // Só personal trainers no painel (nutricionistas têm fluxo próprio).
        $personals = Personal::where('status', 'aprovado')
            ->personalTrainers()
            ->with(['fotos', 'avaliacoes', 'pacotesAvaliacao' => fn($q) => $q->where('ativo', true)->orderBy('nome')])
            // Pioneiros (100 primeiros do estado) aparecem em destaque, no topo.
            ->orderByRaw('pioneiro_posicao IS NULL')
            ->orderBy('pioneiro_posicao')
            ->orderBy('id')
            ->get();
        $academias = Academia::with(['fotos', 'planos' => fn($q) => $q->where('ativo', true)->orderBy('valor')])->where('status', 'aprovado')->get();

        // Próximas aulas do aluno, já com o estado da janela de 24h resolvido:
        // a tela é montada em JS e não tem como calcular fuso/prazo sozinha.
        $agendas = app(\App\Services\AgendaService::class);
        $meusAgendamentos = Agenda::where('cliente_id', $id)
            ->with(['personal', 'academia'])
            ->where('tipo_aula', '!=', 'bloqueio')
            ->whereDate('data', '>=', $agendas->agora()->format('Y-m-d'))
            ->orderBy('data', 'asc')
            ->orderBy('hora_inicio', 'asc')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'personal' => $a->personal->nome ?? 'Personal',
                'data' => $a->data->format('d/m/Y'),
                'hora' => substr($a->hora_inicio ?? '', 0, 5),
                'tipo' => $a->tipo_aula,
                'eh_pacote' => $agendas->ehPacote($a),
                'cancelado' => (bool) $a->cancelado,
                'pode_agir' => ! $a->cancelado && $agendas->alunoEstaNoPrazo($a),
                'bloqueio' => $a->cancelado ? null : $agendas->motivoParaAlunoNaoAgir($a),
            ])
            ->values();

        $historico = Agenda::where('cliente_id', $id)
            ->with(['personal', 'academia'])
            ->where('data', '<', now()->format('Y-m-d'))
            ->orderBy('data', 'desc')
            ->get();

        $horariosDisponiveis = collect();
        $diasParaFrente = 7;
        $inicioPadrao = '08:00';
        $fimPadrao = '18:00';

        foreach ($personals as $personal) {
            $ocupados = Agenda::where('personal_id', $personal->id)
                ->where('data', '>=', now()->format('Y-m-d'))
                ->where('cancelado', false)
                ->get()
                ->map(function ($ag) {
                    return $ag->data . ' ' . date('H:i', strtotime($ag->hora_inicio));
                })->toArray();

            for ($i = 0; $i < $diasParaFrente; $i++) {
                $dataLoop = now()->addDays($i)->format('Y-m-d');
                $horaAtual = strtotime($inicioPadrao);
                $horaLimite = strtotime($fimPadrao);

                while ($horaAtual < $horaLimite) {
                    $formatada = date('H:i', $horaAtual);
                    $chave = $dataLoop . ' ' . $formatada;

                    if (!in_array($chave, $ocupados)) {
                        $horariosDisponiveis->push((object)[
                            'personal_id' => $personal->id,
                            'data' => $dataLoop,
                            'horario_inicio' => $formatada,
                            'horario_fim' => date('H:i', strtotime('+1 hour', $horaAtual))
                        ]);
                    }
                    $horaAtual = strtotime('+1 hour', $horaAtual);
                }
            }
        }

        // Assinaturas ativas/em atraso do aluno (para cancelamento no perfil)
        $assinaturas = \App\Models\Subscription::where('user_id', $id)
            ->whereIn('status', ['active', 'overdue'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($s) {
                if ($s->tipo === 'pacote') {
                    $p     = \App\Models\Cadastro\Personal::find($s->trainer_id);
                    $label = 'Pacote com ' . ($p->nome ?? 'Personal');
                } elseif ($s->tipo === 'academia') {
                    $a     = \App\Models\Cadastro\Academia::find($s->academia_id);
                    $pl    = \App\Models\Cadastro\Plano::find($s->plano_id);
                    $label = 'Academia ' . ($a->nome ?? '') . ($pl ? ' — ' . $pl->nome : '');
                } elseif ($s->tipo === 'studio_plano') {
                    $st    = \App\Models\Cadastro\Studio::find($s->studio_id);
                    $pl    = \App\Models\Cadastro\StudioPlano::find($s->studio_plano_id);
                    $label = 'Studio ' . ($st->nome ?? '') . ($pl ? ' — ' . $pl->nome : '');
                } else {
                    $label = 'Assinatura';
                }

                return (object) [
                    'id'         => $s->id,
                    'label'      => $label,
                    'valor'      => $s->amount_total,
                    'metodo'     => $s->payment_method,
                    'status'     => $s->status,
                    'acesso_ate' => $s->acesso_ate ? \Carbon\Carbon::parse($s->acesso_ate)->format('d/m/Y') : null,
                ];
            });

        // ── Painel de treino ────────────────────────────────────────────
        // Quem já fechou com alguém vê o treino em primeiro lugar; quem ainda
        // não fechou continua caindo direto na vitrine de personais/academias.
        $hoje = $agendas->agora();
        $treino = $this->painelDeTreino($cliente, $hoje, $agendas);

        return view('cliente.index', compact(
            'cliente', 'personals', 'meusAgendamentos', 'horariosDisponiveis',
            'academias', 'historico', 'assinaturas', 'treino', 'hoje'
        ));
    }

    /**
     * Ficha do dia + dias de aula do mês, para o topo do painel do aluno.
     *
     * "Fechou com alguém" é vínculo que realmente existe no banco: academia,
     * studio, personal gravado no cadastro, ficha ativa ou aula marcada. Sem
     * nada disso o bloco não aparece — não adianta mostrar um calendário vazio
     * para quem ainda está escolhendo com quem treinar.
     */
    private function painelDeTreino(?Cliente $cliente, \Carbon\Carbon $hoje, \App\Services\AgendaService $agendas): array
    {
        if (! $cliente) {
            return ['tem_vinculo' => false];
        }

        $fichas = \App\Models\Cadastro\FichaTreino::where('cliente_id', $cliente->id)
            ->where('ativo', true)
            ->with(['exercicios', 'personal:id,nome'])
            ->get();

        // Aulas do mês corrente (o calendário é do mês que o aluno está vendo).
        $aulasDoMes = Agenda::where('cliente_id', $cliente->id)
            ->where('tipo_aula', '!=', 'bloqueio')
            ->whereYear('data', $hoje->year)
            ->whereMonth('data', $hoje->month)
            ->with('personal:id,nome')
            ->orderBy('data')
            ->orderBy('hora_inicio')
            ->get();

        // "Fechou com um personal" é mais estrito que "tem vínculo": quem já tem
        // profissional não vê mais a vitrine de personais no painel. Academia e
        // studio não contam aqui — quem treina em academia ainda pode querer um
        // personal.
        $temPersonal = (bool) $cliente->personal_id
            || $fichas->contains(fn ($f) => $f->personal_id !== null)
            || Agenda::where('cliente_id', $cliente->id)
                ->whereNotNull('personal_id')
                ->where('cancelado', false)
                ->where('tipo_aula', '!=', 'bloqueio')
                ->exists();

        $temVinculo = $temPersonal
            || $cliente->academia_id
            || $cliente->studio_id
            || $fichas->isNotEmpty()
            || $aulasDoMes->isNotEmpty();

        if (! $temVinculo) {
            return ['tem_vinculo' => false, 'tem_personal' => $temPersonal];
        }

        $fichaHoje = $fichas->firstWhere('dia_semana', $hoje->dayOfWeek);

        $feitoHoje = \App\Models\Cadastro\TreinoConcluido::where('cliente_id', $cliente->id)
            ->whereDate('data_treino', $hoje->format('Y-m-d'))
            ->where('concluido', true)
            ->exists();

        // Dias do mês que têm aula, indexados pelo número do dia — o calendário
        // só precisa perguntar "tem aula no dia X?".
        $porDia = $aulasDoMes->groupBy(fn ($a) => (int) $a->data->day);

        $diasComAula = $porDia->map(fn ($doDia) => [
            'cancelado' => $doDia->every(fn ($a) => (bool) $a->cancelado),
            'horas' => $doDia->map(fn ($a) => substr($a->hora_inicio ?? '', 0, 5))->filter()->values()->all(),
            'personal' => $doDia->first()->personal->nome ?? null,
        ]);

        // Detalhe de TODOS os dias do mês, para o clique no calendário abrir o
        // dia sem ida ao servidor. Dia sem aula também entra: o aluno clica para
        // ver qual é a ficha daquele dia da semana.
        $nomesDow = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $detalheDias = [];
        for ($d = 1; $d <= $hoje->daysInMonth; $d++) {
            $dataDia = $hoje->copy()->startOfMonth()->addDays($d - 1);
            $dow = $dataDia->dayOfWeek;
            $ficha = $fichas->firstWhere('dia_semana', $dow);

            $detalheDias[$d] = [
                'dia' => $d,
                'rotulo' => $nomesDow[$dow] . ', ' . $dataDia->format('d/m'),
                'eh_hoje' => $dataDia->format('Y-m-d') === $hoje->format('Y-m-d'),
                'passou' => $dataDia->format('Y-m-d') < $hoje->format('Y-m-d'),
                'ficha' => $ficha ? [
                    'nome' => $ficha->nome_treino,
                    'exercicios' => $ficha->exercicios->count(),
                    'url' => route('fichas-treino.executar', $ficha->id),
                ] : null,
                // `id`, `eh_pacote`, `pode_agir` e `bloqueio` são o que a tela
                // precisa para montar os botões de cancelar/repor — os mesmos
                // campos da lista de agendamentos, para reaproveitar a função
                // que já desenha esses botões.
                'aulas' => ($porDia[$d] ?? collect())->map(fn ($a) => [
                    'id' => $a->id,
                    'hora' => substr($a->hora_inicio ?? '', 0, 5),
                    'fim' => substr($a->hora_fim ?? '', 0, 5),
                    'personal' => $a->personal->nome ?? null,
                    'cancelado' => (bool) $a->cancelado,
                    'tipo' => $a->tipo_aula === 'pacote' ? 'Pacote' : 'Avulsa',
                    'eh_pacote' => $agendas->ehPacote($a),
                    'pode_agir' => ! $a->cancelado && $agendas->alunoEstaNoPrazo($a),
                    'bloqueio' => $a->cancelado ? null : $agendas->motivoParaAlunoNaoAgir($a),
                ])->values()->all(),
            ];
        }

        // Lista precisa das próximas aulas — o calendário dá a visão do mês, mas
        // é aqui que o aluno lê dia, hora e com quem, sem passar o mouse em nada.
        $proximasAulas = $aulasDoMes
            ->where('cancelado', false)
            ->filter(fn ($a) => $a->data->format('Y-m-d') >= $hoje->format('Y-m-d'))
            ->take(5)
            ->map(fn ($a) => [
                'dia' => $a->data->day,
                'dow' => ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][$a->data->dayOfWeek],
                'data' => $a->data->format('d/m'),
                'hora' => substr($a->hora_inicio ?? '', 0, 5),
                'personal' => $a->personal->nome ?? null,
                'hoje' => $a->data->format('Y-m-d') === $hoje->format('Y-m-d'),
            ])
            ->values();

        return [
            'tem_vinculo' => true,
            'tem_personal' => $temPersonal,
            'ficha_hoje' => $fichaHoje,
            'feito_hoje' => $feitoHoje,
            'fichas_por_dia' => $fichas->keyBy('dia_semana'),
            'dias_com_aula' => $diasComAula,
            'detalhe_dias' => $detalheDias,
            'proximas_aulas' => $proximasAulas,
            'aulas_no_mes' => $aulasDoMes->where('cancelado', false)->count(),
            'treinos_no_mes' => \App\Models\Cadastro\TreinoConcluido::where('cliente_id', $cliente->id)
                ->where('concluido', true)
                ->whereYear('data_treino', $hoje->year)
                ->whereMonth('data_treino', $hoje->month)
                ->count(),
        ];
    }

    public function update(Request $request, $id)
    {
        if ((int)$id !== (int)session('cliente_id')) abort(403);

        $cliente = Cliente::find($id);
        if (!$cliente) return redirect()->back()->with('error', 'Cliente não encontrado.');

        $validated = $request->validate([
            'nome'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:clientes,email,' . $id,
            // Lista única em config('textos.profissional.sexos') — a cópia que
            // vivia aqui é a mesma que divergiu no cadastro.
            'sexo'        => 'required|' . \App\Support\CadastroHelper::regraSexo(),
            'cep'         => 'nullable|string|max:9',
            'altura'      => 'nullable|numeric',
            'peso'        => 'nullable|numeric',
            /*
             * Campos que o APP já editava e o site não: a mesma conta tinha um
             * conjunto editável diferente dependendo de por onde o aluno
             * entrasse. `condicao_clinica` e `frequencia_semanal` são coletados
             * no cadastro e não eram editáveis em nenhum dos dois — uma lesão
             * nova ou uma mudança de rotina não tinha por onde entrar.
             */
            'whatsapp'    => 'nullable|string|max:20',
            'resumo_objetivo' => 'nullable|string|max:500',
            'condicao_clinica' => 'nullable|string',
            'frequencia_semanal' => 'nullable|integer|min:1',
            'rua'         => 'nullable|string|max:255',
            'bairro'      => 'nullable|string|max:255',
            'cidade'      => 'nullable|string|max:255',
            'estado'      => 'nullable|string|max:255',
            'complemento' => 'nullable|string|max:255',
            // Editável depois do cadastro: a preferência muda (mudou de cidade,
            // começou a viajar), e campo que só se escolhe uma vez na vida vira
            // dado errado com o tempo.
            'modalidade_preferida' => ['nullable', Rule::in(config('textos.profissional.modalidades_aluno'))],
            'foto'        => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            // A07 — a senha era gravada direto de $request->senha, sem passar por
            // regra nenhuma: dava para trocar por uma senha de 1 caractere aqui,
            // contornando o mínimo exigido no cadastro.
            'senha'       => 'nullable|string|min:8|max:255',
        ]);

        $data = $validated;
        $data['sexo'] = strtolower($validated['sexo']);
        unset($data['foto']);

        if ($request->hasFile('foto')) {
            if ($cliente->foto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($cliente->foto);
            }
            $data['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        if ($request->filled('senha')) {
            $data['senha'] = Hash::make($request->senha);
        } else {
            unset($data['senha']);
        }

        $cliente->update($data);
        return redirect()->route('cliente.index')->with('success', 'Perfil atualizado com sucesso!');
    }

    public function create()
    {
        return view('cadastro.cliente');
    }

    public function store(Request $request, \App\Services\CupomService $cupons)
    {
        $validated = $request->validate([
            'nome'               => 'required|string|max:255',
            'email'              => 'required|email|max:255|unique:clientes,email',
            'senha'              => 'required|string|min:8|max:255',
            'whatsapp'           => 'nullable|string|max:20',
            'idade'              => 'required|date',
            // Lista única em config('textos.profissional.sexos'): a cópia que
            // vivia aqui divergiu do <select> do formulário e a terceira opção
            // ("Outro") era recusada na validação.
            'sexo'               => 'required|' . \App\Support\CadastroHelper::regraSexo(),
            'cep'                => 'required|string|max:9',
            'rua'                => 'nullable|string|max:255',
            'bairro'             => 'nullable|string|max:255',
            'cidade'             => 'nullable|string|max:255',
            'estado'             => 'nullable|string|max:255',
            'complemento'        => 'nullable|string|max:255',
            'altura'             => 'nullable|numeric',
            'peso'               => 'nullable|numeric',
            'resumo_objetivo'    => 'nullable|string',
            'frequencia_semanal' => 'nullable|integer|min:1',
            // Preferência de atendimento. Vazio = sem preferência (ver
            // Cliente::modalidadesCompativeis), e "Híbrido" NÃO é opção do aluno:
            // é oferta do profissional, não desejo de quem procura.
            'modalidade_preferida' => ['nullable', Rule::in(config('textos.profissional.modalidades_aluno'))],
            'condicao_clinica'   => 'nullable|string',
            // latitude/longitude saíram: `clientes` não tem essas colunas, o
            // formulário nunca as enviou e o mass-assignment as descartava —
            // regra sobre campo inexistente só engana quem lê depois.
            'aceita_termos'      => 'required|accepted',
            'cupom'              => $cupons->regraValidacao(),
        ], [
            'aceita_termos.required' => 'Você deve concordar com os Termos de Uso',
            'aceita_termos.accepted' => 'Você deve concordar com os Termos de Uso',
        ]);

        // Fora do create(): `cupom` não é coluna de clientes.
        $codigoCupom = \Illuminate\Support\Arr::pull($validated, 'cupom');

        $validated['sexo'] = strtolower($request->sexo);
        $validated['senha'] = Hash::make($validated['senha']);
        $validated['aceita_termos'] = true;
        $validated['data_aceitacao_termos'] = now();
        $validated['ip_aceitacao_termos'] = $request->ip();

        $cliente = Cliente::create($validated);
        $cupons->registrarIndicacao($codigoCupom, $cliente, $request->ip());

        // Aceite dos Termos na versao vigente, registrado no ato do cadastro:
        // sem isto a conta nova cairia na tela de reaceite no primeiro acesso.
        $cliente->registrarAceiteTermos($request->ip(), (string) $request->userAgent(), \App\Models\TermoAceite::ORIGEM_CADASTRO);
        $fb = app(MetaConversionsService::class);
        // A tela de login lê `sucesso`, não `success` — com a chave errada o
        // aluno voltava ao login sem nenhuma confirmação na tela.
        return redirect()->route('login.index')
            ->with('sucesso', 'Sua conta está pronta! Faça login e bora começar — você agora é da família SnrFit.')
            ->with('fb_event', $fb->track(
                'CompleteRegistration',
                ['content_name' => 'Cliente', 'status' => 'completo'],
                $fb->userDataFromModel($cliente),
                null,
                null,
                route('login.index')
            ));
    }

    public function reservarHorario(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index')->with('erro', 'Sessão expirada.');

        $request->validate([
            'personal_id'    => 'required|exists:personals,id',
            'academia_id'    => 'nullable|exists:academias,id',
            'academia_nome'  => 'nullable|string|max:255',
            'data'           => 'required|date',
            'horario_inicio' => 'required',
            'horario_fim'    => 'required',
            'modalidade'     => ['nullable', Rule::in(Agenda::MODALIDADES)],
        ]);

        // Não basta estar na lista: tem de ser algo que ESTE profissional atende.
        // Sem isto, bastaria editar o formulário para marcar "Online" numa aula
        // com quem só atende presencial — e o personal descobriria no dia.
        $personalDaAula = Personal::find($request->personal_id);

        if (! Agenda::modalidadeValida($request->modalidade, $personalDaAula?->modalidade)) {
            return redirect()->back()->with('error', 'Este profissional não atende nessa modalidade.');
        }

        $conflito = Agenda::where('personal_id', $request->personal_id)
            ->where('data', $request->data)
            ->where('cancelado', false)
            ->where(function ($q) use ($request) {
                $q->where('hora_inicio', '<', $request->horario_fim)
                  ->where('hora_fim', '>', $request->horario_inicio);
            })
            ->exists();

        if ($conflito) {
            return redirect()->back()->with('error', 'Este horário já foi reservado por outro aluno. Por favor, escolha outro horário.');
        }

        $agenda = Agenda::create([
            'cliente_id'    => $clienteId,
            'personal_id'   => $request->personal_id,
            'academia_id'   => $request->academia_id ?? null,
            'academia_nome' => $request->academia_nome ?? null,
            'data'          => $request->data,
            'hora_inicio'   => $request->horario_inicio,
            'hora_fim'      => $request->horario_fim,
            'cancelado'     => false,
            'tipo_aula'     => 'avulsa',
            // Quem atende de um jeito só não precisa escolher: o valor é dedutível.
            'modalidade'    => Agenda::modalidadeResolvida($request->modalidade, $personalDaAula?->modalidade),
        ]);

        $this->notificarPersonalWhatsApp($clienteId, $request->personal_id, 'avulsa', $agenda);

        $cliente  = Cliente::find($clienteId);
        $personal = Personal::find($request->personal_id);
        $academia = $request->academia_id ? Academia::find($request->academia_id) : null;

        if ($personal && $personal->email) {
            try {
                Mail::send('emails.aula-agendada', [
                    'personal_nome' => $personal->nome,
                    'cliente_nome'  => $cliente->nome,
                    'cliente_email' => $cliente->email,
                    'data'          => $request->data,
                    'hora_inicio'   => $request->horario_inicio,
                    'hora_fim'      => $request->horario_fim,
                    'academia_nome' => $academia ? $academia->nome : null,
                ], function ($message) use ($personal) {
                    $message->to($personal->email)->subject('📅 Nova aula agendada - SnrFit');
                });
            } catch (\Exception $e) {}
        }

        $fb = app(MetaConversionsService::class);
        return redirect()->back()
            ->with('sucesso', 'Horário agendado com sucesso!')
            ->with('fb_event', $fb->track(
                'Lead',
                ['content_name' => 'Agendamento de horário', 'content_category' => 'aula_avulsa'],
                $fb->userDataFromModel(Cliente::find($clienteId)),
                null,
                null,
                url()->previous()
            ));
    }

    public function contratarAcademia(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index')->with('erro', 'Você precisa estar logado.');

        $request->validate(['academia_id' => 'required|exists:academias,id']);

        $cliente  = Cliente::find($clienteId);
        $academia = Academia::find($request->academia_id);
        $cliente->update(['academia_id' => $request->academia_id]);

        if ($academia && $academia->email) {
            try {
                Mail::send('emails.academia-contratada', [
                    'academia_nome'  => $academia->nome,
                    'cliente_nome'   => $cliente->nome,
                    'cliente_email'  => $cliente->email,
                    'cliente_cidade' => $cliente->cidade ?? null,
                    'cliente_idade'  => $cliente->idade ?? null,
                ], function ($message) use ($academia) {
                    $message->to($academia->email)->subject('🎉 Novo aluno contratou sua academia - SnrFit');
                });
            } catch (\Exception $e) {}
        }

        $fb = app(MetaConversionsService::class);
        return redirect()->back()
            ->with('sucesso', 'Academia contratada com sucesso!')
            ->with('fb_event', $fb->track(
                'Lead',
                ['content_name' => $academia->nome ?? 'Academia', 'content_category' => 'academia'],
                $fb->userDataFromModel($cliente),
                null,
                null,
                url()->previous()
            ));
    }

    // Removido: verPrecos(). Renderizava 'cliente.precos', view que não existe, e
    // nunca teve rota. Também buscava o personal em App\Models\User, que não é a
    // tabela de personais (seria sempre o registro errado ou 404). O aluno vê os
    // pacotes na página de detalhe do personal.

    public function contratarPacote(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index')->with('erro', 'Sessão expirada.');

        $request->validate([
            'personal_id'       => 'required|exists:personals,id',
            'frequencia_pacote' => 'required|integer|min:1|max:7',
            'valor_pacote'      => 'required|numeric|min:0',
            'dias_selecionados' => 'required|json',
            'hora_inicio'       => 'required|date_format:H:i',
            'hora_fim'          => 'required|date_format:H:i',
            'academia_nome'     => 'nullable|string|max:255',
            'modalidade'        => ['nullable', Rule::in(Agenda::MODALIDADES)],
        ]);

        $cliente  = Cliente::find($clienteId);
        $personal = Personal::find($request->personal_id);

        if (!$cliente || !$personal) return redirect()->back()->with('error', 'Dados inválidos.');

        if (! Agenda::modalidadeValida($request->modalidade, $personal->modalidade)) {
            return redirect()->back()->with('error', 'Este profissional não atende nessa modalidade.');
        }

        // Vale para todas as aulas do pacote: o formato é do combinado, não de
        // cada sessão.
        $modalidadeDoPacote = Agenda::modalidadeResolvida($request->modalidade, $personal->modalidade);

        $diasSelecionados = json_decode($request->dias_selecionados, true);
        if (empty($diasSelecionados)) return redirect()->back()->with('error', 'Selecione pelo menos um dia.');

        $frequencia = $request->frequencia_pacote;
        if (count($diasSelecionados) > $frequencia) {
            return redirect()->back()->with('error', "Você selecionou " . count($diasSelecionados) . " dia(s), mas o pacote permite apenas {$frequencia}x na semana.");
        }

        $horaInicio          = $request->hora_inicio;
        $horaFim             = $request->hora_fim;
        $agendamentosCriados = 0;

        foreach ($diasSelecionados as $dia) {
            $dataPrimeira = Carbon::create(now()->year, now()->month, (int)$dia);
            if ($dataPrimeira < now()->startOfDay()) $dataPrimeira = $dataPrimeira->addMonth();

            $diaDaSemana      = $dataPrimeira->dayOfWeek;
            $datasComMesmoDia = [];

            $dataAtual       = Carbon::create(now()->year, now()->month, 1);
            $dataFimMesAtual = now()->endOfMonth();
            while ($dataAtual <= $dataFimMesAtual) {
                if ($dataAtual->dayOfWeek === $diaDaSemana && $dataAtual >= now()->startOfDay()) {
                    $datasComMesmoDia[] = $dataAtual->copy();
                }
                $dataAtual->addDay();
            }

            $proxMes        = now()->addMonth();
            $dataAtual      = Carbon::create($proxMes->year, $proxMes->month, 1);
            $dataFimProxMes = $proxMes->endOfMonth();
            while ($dataAtual <= $dataFimProxMes) {
                if ($dataAtual->dayOfWeek === $diaDaSemana) $datasComMesmoDia[] = $dataAtual->copy();
                $dataAtual->addDay();
            }

            foreach ($datasComMesmoDia as $data) {
                $temConflito = Agenda::where('personal_id', $request->personal_id)
                    ->where('data', $data->format('Y-m-d'))
                    ->where('cancelado', false)
                    ->where(function ($q) use ($horaInicio, $horaFim) {
                        $q->whereRaw("hora_inicio < ? AND hora_fim > ?", [$horaFim, $horaInicio]);
                    })->exists();

                if (!$temConflito) {
                    Agenda::create([
                        'cliente_id'         => $clienteId,
                        'personal_id'        => $request->personal_id,
                        'academia_id'        => $cliente->academia_id ?? null,
                        'academia_nome'      => $request->academia_nome ?? null,
                        'data'               => $data->format('Y-m-d'),
                        'hora_inicio'        => $horaInicio,
                        'hora_fim'           => $horaFim,
                        'cancelado'          => false,
                        'descricao'          => "Aula agendada - {$cliente->nome}",
                        'frequencia_pacote'  => $frequencia,
                        'data_inicio_pacote' => now()->startOfMonth(),
                        'data_fim_pacote'    => now()->endOfMonth(),
                        'tipo_aula'          => 'pacote',
                        'modalidade'         => $modalidadeDoPacote,
                    ]);
                    $agendamentosCriados++;
                }
            }
        }

        if ($agendamentosCriados === 0) {
            return redirect()->back()->with('warning', 'Nenhum horário pôde ser agendado. Verifique conflitos de horários.');
        }

        $this->notificarPersonalWhatsApp($clienteId, $request->personal_id, 'pacote', null, $frequencia, $agendamentosCriados);

        if ($personal && $personal->email) {
            try {
                Mail::send('emails.pacote-contratado', [
                    'personal_nome' => $personal->nome,
                    'cliente_nome'  => $cliente->nome,
                    'cliente_email' => $cliente->email,
                    'frequencia'    => $frequencia,
                    'valor_mensal'  => $request->valor_pacote,
                    'dias_total'    => $agendamentosCriados,
                    'hora_inicio'   => $horaInicio,
                    'hora_fim'      => $horaFim,
                    'data_inicio'   => now()->startOfMonth()->format('d/m/Y'),
                    'data_fim'      => now()->endOfMonth()->format('d/m/Y'),
                ], function ($message) use ($personal) {
                    $message->to($personal->email)->subject('🎉 Novo pacote contratado - SnrFit');
                });
            } catch (\Exception $e) {}
        }

        $fb = app(MetaConversionsService::class);
        return redirect()->back()
            ->with('success', "Pacote contratado com sucesso! {$agendamentosCriados} treino(s) agendado(s).")
            ->with('fb_event', $fb->track(
                'Purchase',
                [
                    'value'        => (float) ($request->valor_pacote ?? 0),
                    'currency'     => 'BRL',
                    'content_name' => 'Pacote de treinos',
                    'content_type' => 'pacote',
                ],
                $fb->userDataFromModel($cliente),
                null,
                null,
                url()->previous()
            ));
    }

    public function agendarAulasInterno(array $booking): void
    {
        $clienteId        = $booking['cliente_id'];
        $personalId       = $booking['personal_id'];
        $frequencia       = $booking['frequencia_pacote'];
        $horaInicio       = $booking['hora_inicio'] ?? null;
        $horaFim          = $booking['hora_fim'] ?? null;
        $diasSelecionados = json_decode($booking['dias_selecionados'] ?? '[]', true) ?: [];
        $diasHorarios     = !empty($booking['dias_horarios']) ? json_decode($booking['dias_horarios'], true) : null;
        $academiaNome     = $booking['academia_nome'] ?? null;
        $valorPacote      = $booking['valor_pacote'] ?? 0;

        $cliente  = Cliente::find($clienteId);
        $personal = Personal::find($personalId);

        /*
         * Modalidade combinada, trazida no booking_data desde a criação do
         * pagamento. Este é o caminho PAGO (chamado após a confirmação no Asaas),
         * então a escolha do aluno precisa atravessar o pagamento inteiro — se
         * `modalidade` não estiver no booking_data, a aula nasce sem ela e o
         * personal volta a não saber se é presencial ou online.
         *
         * Revalidado aqui porque o booking_data é persistido: o profissional pode
         * ter mudado de modalidade entre o pagamento e a confirmação.
         */
        $modalidadeDoPacote = Agenda::modalidadeResolvida(
            Agenda::modalidadeValida($booking['modalidade'] ?? null, $personal?->modalidade)
                ? ($booking['modalidade'] ?? null)
                : null,
            $personal?->modalidade
        );

        // Normaliza para itens {dia (do mês), hora_inicio, hora_fim}. Se vier
        // `dias_horarios` (horário por dia — app novo), usa o horário de cada dia;
        // senão cai no horário único (compatível com o fluxo antigo e o web).
        $itens = [];
        if (is_array($diasHorarios) && count($diasHorarios) > 0) {
            foreach ($diasHorarios as $dh) {
                $dia = (int) ($dh['dia'] ?? 0);
                if ($dia < 1 || $dia > 31) continue;
                $itens[] = [
                    'dia'         => $dia,
                    'hora_inicio' => $dh['hora_inicio'] ?? $horaInicio,
                    'hora_fim'    => $dh['hora_fim'] ?? $horaFim,
                ];
            }
        } else {
            foreach ($diasSelecionados as $dia) {
                $itens[] = ['dia' => (int) $dia, 'hora_inicio' => $horaInicio, 'hora_fim' => $horaFim];
            }
        }

        if (!$cliente || !$personal || empty($itens)) {
            Log::error('agendarAulasInterno: dados inválidos', $booking);
            return;
        }

        // Horário de referência p/ o e-mail (um só): o do primeiro dia.
        $horaInicio = $horaInicio ?? $itens[0]['hora_inicio'];
        $horaFim    = $horaFim ?? $itens[0]['hora_fim'];

        $agendamentosCriados = 0;

        foreach ($itens as $item) {
            $dia     = $item['dia'];
            $hInicio = $item['hora_inicio'];
            $hFim    = $item['hora_fim'];
            if (!$hInicio || !$hFim) continue;

            $dataPrimeira = Carbon::create(now()->year, now()->month, $dia);
            if ($dataPrimeira < now()->startOfDay()) $dataPrimeira = $dataPrimeira->addMonth();

            $diaDaSemana      = $dataPrimeira->dayOfWeek;
            $datasComMesmoDia = [];

            $dataAtual       = Carbon::create(now()->year, now()->month, 1);
            $dataFimMesAtual = now()->endOfMonth();
            while ($dataAtual <= $dataFimMesAtual) {
                if ($dataAtual->dayOfWeek === $diaDaSemana && $dataAtual >= now()->startOfDay()) {
                    $datasComMesmoDia[] = $dataAtual->copy();
                }
                $dataAtual->addDay();
            }

            $proxMes        = now()->addMonth();
            $dataAtual      = Carbon::create($proxMes->year, $proxMes->month, 1);
            $dataFimProxMes = $proxMes->endOfMonth();
            while ($dataAtual <= $dataFimProxMes) {
                if ($dataAtual->dayOfWeek === $diaDaSemana) $datasComMesmoDia[] = $dataAtual->copy();
                $dataAtual->addDay();
            }

            foreach ($datasComMesmoDia as $data) {
                $temConflito = Agenda::where('personal_id', $personalId)
                    ->where('data', $data->format('Y-m-d'))
                    ->where('cancelado', false)
                    ->where(function ($q) use ($hInicio, $hFim) {
                        $q->whereRaw("hora_inicio < ? AND hora_fim > ?", [$hFim, $hInicio]);
                    })->exists();

                if (!$temConflito) {
                    Agenda::create([
                        'cliente_id'         => $clienteId,
                        'personal_id'        => $personalId,
                        'academia_id'        => $cliente->academia_id ?? null,
                        'academia_nome'      => $academiaNome,
                        'data'               => $data->format('Y-m-d'),
                        'hora_inicio'        => $hInicio,
                        'hora_fim'           => $hFim,
                        'cancelado'          => false,
                        'descricao'          => "Aula agendada - {$cliente->nome}",
                        'frequencia_pacote'  => $frequencia,
                        'data_inicio_pacote' => now()->startOfMonth(),
                        'data_fim_pacote'    => now()->endOfMonth(),
                        'tipo_aula'          => 'pacote',
                        'modalidade'         => $modalidadeDoPacote,
                    ]);
                    $agendamentosCriados++;
                }
            }
        }

        if ($personal->email) {
            try {
                Mail::send('emails.pacote-contratado', [
                    'personal_nome' => $personal->nome,
                    'cliente_nome'  => $cliente->nome,
                    'cliente_email' => $cliente->email,
                    'frequencia'    => $frequencia,
                    'valor_mensal'  => $valorPacote,
                    'dias_total'    => $agendamentosCriados,
                    'hora_inicio'   => $horaInicio,
                    'hora_fim'      => $horaFim,
                    'data_inicio'   => now()->startOfMonth()->format('d/m/Y'),
                    'data_fim'      => now()->endOfMonth()->format('d/m/Y'),
                ], function ($message) use ($personal) {
                    $message->to($personal->email)->subject('🎉 Novo pacote contratado - SnrFit');
                });
            } catch (\Exception $e) {
                Log::error('agendarAulasInterno: falha ao enviar e-mail ao personal', ['error' => $e->getMessage()]);
            }
        }

        // Notifica o personal por WhatsApp (mesmo canal usado na aula avulsa).
        $this->notificarPersonalWhatsApp($clienteId, $personalId, 'pacote', null, $frequencia, $agendamentosCriados);

        Log::info('agendarAulasInterno: aulas criadas', [
            'cliente_id'  => $clienteId,
            'personal_id' => $personalId,
            'total'       => $agendamentosCriados,
        ]);
    }

    public function agendarAulaAvulsaInterno(array $booking): void
    {
        $clienteId    = $booking['cliente_id'];
        $personalId   = $booking['personal_id'];
        $data         = $booking['data'];
        $horaInicio   = $booking['hora_inicio'];
        $horaFim      = $booking['hora_fim'];
        $academiaNome = $booking['academia_nome'] ?? null;

        $cliente  = Cliente::find($clienteId);
        $personal = Personal::find($personalId);

        if (!$cliente || !$personal || !$data) {
            Log::error('agendarAulaAvulsaInterno: dados inválidos', $booking);
            return;
        }

        /*
         * Modalidade da aula. Este método é a porta comum do caminho PAGO do web
         * (PaymentController, após a confirmação) e do app, por isso a resolução
         * vive aqui e não só em reservarHorario() — senão a aula avulsa paga nasce
         * sem modalidade e o personal volta a não saber como atender.
         *
         * Revalidado contra a oferta atual do profissional: o booking_data é
         * persistido e ele pode ter mudado de modalidade entre o pagamento e a
         * confirmação. Valor incompatível é descartado (vira null) em vez de
         * gravado — melhor "não informado" que uma afirmação errada.
         */
        $modalidadePedida = $booking['modalidade'] ?? null;

        if (! Agenda::modalidadeValida($modalidadePedida, $personal->modalidade)) {
            Log::warning('agendarAulaAvulsaInterno: modalidade incompatível, gravando sem ela', [
                'personal_id' => $personalId,
                'pedida'      => $modalidadePedida,
                'oferta'      => $personal->modalidade,
            ]);
            $modalidadePedida = null;
        }

        $modalidade = Agenda::modalidadeResolvida($modalidadePedida, $personal->modalidade);

        $temConflito = Agenda::where('personal_id', $personalId)
            ->where('data', $data)
            ->where('cancelado', false)
            ->where(function ($q) use ($horaInicio, $horaFim) {
                $q->whereRaw("hora_inicio < ? AND hora_fim > ?", [$horaFim, $horaInicio]);
            })->exists();

        if ($temConflito) {
            Log::warning('agendarAulaAvulsaInterno: conflito de horário', $booking);
            return;
        }

        $agenda = Agenda::create([
            'cliente_id'    => $clienteId,
            // Guarda de qual pagamento essa aula veio: é por aqui que o
            // cancelamento acha o valor a devolver, sem garimpar booking_data.
            'payment_id'    => $booking['payment_id'] ?? null,
            'personal_id'   => $personalId,
            'academia_id'   => $cliente->academia_id ?? null,
            'academia_nome' => $academiaNome,
            'data'          => $data,
            'hora_inicio'   => $horaInicio,
            'hora_fim'      => $horaFim,
            'cancelado'     => false,
            'descricao'     => "Aula avulsa - {$cliente->nome}",
            'tipo_aula'     => 'avulsa',
            'modalidade'    => $modalidade,
        ]);

        $this->notificarPersonalWhatsApp($clienteId, $personalId, 'avulsa', $agenda);

        Log::info('agendarAulaAvulsaInterno: aula criada', [
            'cliente_id'  => $clienteId,
            'personal_id' => $personalId,
            'data'        => $data,
        ]);
    }

    public function buscarHorariosDisponiveis($personalId, $dia, \App\Services\AgendaService $agendas)
    {
        $personal = Personal::find($personalId);
        if (!$personal) return response()->json(['erro' => 'Personal não encontrado'], 404);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) return response()->json(['erro' => 'Formato de data inválido'], 400);

        // A conta mora em AgendaService::horariosLivres(). Estava copiada aqui e
        // no Api\ExplorarController, com uma query por slot e ofertando horário
        // já vencido. O formato da resposta é o mesmo de antes.
        return response()->json($agendas->horariosLivres((int) $personalId, $dia));
    }

    public function buscarHorariosStudio($studioId, $dia)
    {
        $studio = Studio::where('id', $studioId)->where('status', 'aprovado')->first();
        if (!$studio) return response()->json(['erro' => 'Studio não encontrado'], 404);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) return response()->json(['erro' => 'Formato de data inválido'], 400);

        return response()->json($studio->slotsDisponiveis($dia));
    }

    public function listarAcademias()
    {
        $cliente   = Cliente::find(session('cliente_id'));
        $academias = Academia::with(['fotos', 'planos' => fn($q) => $q->orderBy('valor')])
            ->where('status', 'aprovado')
            ->orderBy('nome')
            ->get();
        return view('cliente.academias', compact('academias', 'cliente'));
    }

    public function listarPersonais()
    {
        $cliente = Cliente::find(session('cliente_id'));

        // Personal trainers — pioneiros do estado em destaque no topo.
        $personais = Personal::where('status', 'aprovado')
            ->personalTrainers()
            ->with(['fotos', 'avaliacoes'])
            ->orderByRaw('pioneiro_posicao IS NULL')
            ->orderBy('pioneiro_posicao')
            ->orderBy('nome')
            ->get();

        /*
         * O filtro já abre na preferência que o aluno declarou no cadastro.
         *
         * Isto é o que impede `modalidade_preferida` de virar o que
         * `personals.modalidade` era: campo coletado que ninguém lê. O valor da
         * URL tem precedência, para o aluno conseguir contrariar a própria
         * preferência sem ir ao perfil — e 'todas' é a fuga explícita.
         */
        $modalidadeFiltro = request('modalidade');

        if ($modalidadeFiltro === null && $cliente) {
            $modalidadeFiltro = $cliente->modalidade_preferida;
        }

        if (! in_array($modalidadeFiltro, config('textos.profissional.modalidades_aluno'), true)) {
            $modalidadeFiltro = '';
        }

        [$especialidadesDisponiveis, $especialidadeFiltro] = $this->filtroEspecialidades($personais);

        return view('cliente.personais', compact('personais', 'cliente') + [
            'modalidadeFiltro'          => $modalidadeFiltro,
            'especialidadesDisponiveis' => $especialidadesDisponiveis,
            'especialidadeFiltro'       => $especialidadeFiltro,
        ]);
    }

    /**
     * Catálogo de especialidades para as pílulas da vitrine + o filtro resolvido.
     *
     * O catálogo é derivado dos profissionais REALMENTE listados, não de
     * `config('textos.profissional.especialidades')`: uma pílula que não casa com
     * ninguém é um beco sem saída, e hoje a maioria dos personais ainda não
     * declarou nada (o campo só passou a ser editável junto com esta tela). A
     * ordem vem do config, para o vocabulário ficar estável entre acessos; valores
     * fora dele — que a validação permite, e que podem entrar por API/import —
     * vão para o fim em ordem alfabética em vez de desaparecerem.
     *
     * Diferente de `modalidade`, quem não declarou especialidade **não** aparece
     * quando o aluno filtra (ver `atendeEspecialidade()` na view). Lá a leniência
     * existe porque o filtro é aplicado sozinho, pela preferência do cadastro;
     * aqui o aluno clicou pedindo "Hipertrofia", e devolver quem nunca afirmou
     * isso faria a pílula mentir.
     *
     * @param  \Illuminate\Support\Collection<int,\App\Models\Cadastro\Personal>  $personais
     * @return array{0: array<string,int>, 1: string}  [especialidade => quantos], filtro vigente
     */
    private function filtroEspecialidades($personais): array
    {
        $contagem = [];

        foreach ($personais as $personal) {
            foreach ((array) $personal->especialidades as $esp) {
                $esp = trim((string) $esp);

                if ($esp !== '') {
                    $contagem[$esp] = ($contagem[$esp] ?? 0) + 1;
                }
            }
        }

        $disponiveis = [];

        foreach ((array) config('textos.profissional.especialidades.PERSONAL_TRAINER', []) as $esp) {
            if (isset($contagem[$esp])) {
                $disponiveis[$esp] = $contagem[$esp];
            }
        }

        $extras = array_diff_key($contagem, $disponiveis);
        ksort($extras);
        $disponiveis += $extras;

        // Resolve o ?especialidade= da URL contra o catálogo, sem diferenciar
        // caixa/acento digitado, e devolve o valor canônico. Um valor que não
        // casa com nada cai para "todas" em vez de render uma lista vazia — mesma
        // tolerância do ?modalidade= logo acima.
        $pedido = trim((string) request('especialidade', ''));
        $filtro = '';

        if ($pedido !== '') {
            foreach (array_keys($disponiveis) as $esp) {
                if (mb_strtolower($esp) === mb_strtolower($pedido)) {
                    $filtro = $esp;
                    break;
                }
            }
        }

        return [$disponiveis, $filtro];
    }

    public function detalheAcademia($id)
    {
        $cliente  = Cliente::find(session('cliente_id'));
        $academia = Academia::with([
            'fotos',
            'planos'      => fn ($q) => $q->orderBy('valor'),
            'professores' => fn ($q) => $q->where('ativo', true)->orderBy('nome'),
            'aulas'       => fn ($q) => $q->where('ativo', true)->with('professor')->orderBy('nome'),
            // Personais com vínculo aprovado — exibidos em "Personais Relacionados"
            'personaisAprovados' => fn ($q) => $q->where('personals.status', 'aprovado')->with('fotos')->orderBy('nome'),
        ])->findOrFail($id);

        $fb = app(MetaConversionsService::class);
        $fbEvent = $fb->track('ViewContent', [
            'content_type'     => 'academia',
            'content_ids'      => [(string) $academia->id],
            'content_name'     => $academia->nome,
            'content_category' => 'Academia',
        ], $fb->userDataFromModel($cliente));

        return view('cliente.academia-detalhes', compact('academia', 'cliente', 'fbEvent'));
    }

    public function listarStudios()
    {
        $cliente = Cliente::find(session('cliente_id'));
        $studios = Studio::where('status', 'aprovado')
            ->with(['fotos', 'planos' => fn($q) => $q->where('ativo', true)->orderBy('valor'), 'avaliacoes'])
            ->get();

        return view('cliente.studios', compact('studios', 'cliente'));
    }

    public function detalheStudio($id)
    {
        $cliente = Cliente::find(session('cliente_id'));
        $studio = Studio::where('status', 'aprovado')
            ->with([
                'fotos',
                'planos'    => fn($q) => $q->where('ativo', true)->orderBy('valor'),
                'horarios'  => fn($q) => $q->where('ativo', true)->orderBy('dia_semana'),
                'avaliacoes' => fn($q) => $q->with('cliente:id,nome')->latest()->limit(20),
            ])
            ->findOrFail($id);

        $fb = app(MetaConversionsService::class);
        $fbEvent = $fb->track('ViewContent', [
            'content_type'     => 'studio',
            'content_ids'      => [(string) $studio->id],
            'content_name'     => $studio->nome,
            'content_category' => 'Studio',
        ], $fb->userDataFromModel($cliente));

        return view('cliente.studio-detalhes', compact('studio', 'cliente', 'fbEvent'));
    }

    public function listarLojas()
    {
        $cliente = Cliente::find(session('cliente_id'));
        $lojas = Loja::where('status', 'aprovado')
            ->withCount(['produtos' => fn($q) => $q->where('ativo', true)])
            ->orderBy('nome')
            ->get();

        return view('cliente.lojas', compact('lojas', 'cliente'));
    }

    public function detalheLoja($id)
    {
        $cliente = Cliente::find(session('cliente_id'));
        $loja = Loja::where('status', 'aprovado')
            ->with(['produtos' => fn($q) => $q->where('ativo', true)->orderBy('nome')])
            ->findOrFail($id);

        $fb = app(MetaConversionsService::class);
        $fbEvent = $fb->track('ViewContent', [
            'content_type'     => 'loja',
            'content_ids'      => [(string) $loja->id],
            'content_name'     => $loja->nome,
            'content_category' => 'Loja',
        ], $fb->userDataFromModel($cliente));

        return view('cliente.loja-detalhes', compact('loja', 'cliente', 'fbEvent'));
    }

    private function notificarPersonalWhatsApp($clienteId, $personalId, $tipo, $agenda = null, $frequencia = null, $diasTotal = null)
    {
        try {
            $cliente  = Cliente::find($clienteId);
            $personal = Personal::find($personalId);

            if (!$cliente || !$personal) {
                Log::warning("Não foi possível notificar. Cliente: {$clienteId}, Personal: {$personalId}");
                return;
            }

            if ($tipo === 'avulsa') {
                $data = $agenda->data instanceof Carbon ?
                    $agenda->data->format('d/m/Y') :
                    Carbon::parse($agenda->data)->format('d/m/Y');

                $mensagem  = "📅 *Nova Aula Avulsa Agendada*\n\n";
                $mensagem .= "👤 *Cliente:* {$cliente->nome}\n";
                $mensagem .= "📅 *Data:* {$data}\n";
                $mensagem .= "⏰ *Horário:* {$agenda->hora_inicio} - {$agenda->hora_fim}\n\n";
                $mensagem .= "Acesse seu painel para confirmar se o cliente compareceu.";

                $assunto  = 'Nova aula avulsa agendada — SnrFit';
                $template = 'aula_avulsa_agendada';
                $params   = [$cliente->nome, $data, "{$agenda->hora_inicio} - {$agenda->hora_fim}"];
            } else {
                $mensagem  = "🎉 *Novo Pacote Contratado*\n\n";
                $mensagem .= "👤 *Cliente:* {$cliente->nome}\n";
                $mensagem .= "📦 *Frequência:* {$frequencia}x por semana\n";
                $mensagem .= "🗓️ *Aulas Agendadas:* {$diasTotal} treino(s)\n\n";
                $mensagem .= "Acesse seu painel para confirmar presença do cliente.";

                $assunto  = 'Novo pacote contratado — SnrFit';
                $template = 'pacote_contratado_personal';
                $params   = [$cliente->nome, (string) $frequencia, (string) $diasTotal];
            }

            \App\Services\NotificacaoService::personal($personal, $assunto, $mensagem, $template, $params);

        } catch (\Exception $e) {
            Log::error("❌ Erro ao notificar personal: " . $e->getMessage());
        }
    }

    public function minhasSolicitacoesFicha()
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        $solicitacoes = \App\Models\SolicitacaoFicha::where('cliente_id', $clienteId)
            ->with('personal')
            ->latest()
            ->get();

        return response()->json($solicitacoes);
    }
}
