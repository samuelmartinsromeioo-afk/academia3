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

class ClienteController extends Controller
{
    public function index()
    {
        $id = session('cliente_id');
        if (!$id) return redirect()->route('login.index');

        $cliente = Cliente::find($id);
        $personals = Personal::where('status', 'aprovado')
            ->with(['fotos', 'avaliacoes', 'pacotesAvaliacao' => fn($q) => $q->where('ativo', true)->orderBy('nome')])
            // Pioneiros (100 primeiros do estado) aparecem em destaque, no topo.
            ->orderByRaw('pioneiro_posicao IS NULL')
            ->orderBy('pioneiro_posicao')
            ->orderBy('id')
            ->get();
        $academias = Academia::with(['fotos', 'planos' => fn($q) => $q->where('ativo', true)->orderBy('valor')])->where('status', 'aprovado')->get();

        $meusAgendamentos = Agenda::where('cliente_id', $id)
            ->with(['personal', 'academia'])
            ->orderBy('data', 'asc')
            ->get();

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

        return view('cliente.index', compact('cliente', 'personals', 'meusAgendamentos', 'horariosDisponiveis', 'academias', 'historico', 'assinaturas'));
    }

    public function update(Request $request, $id)
    {
        if ((int)$id !== (int)session('cliente_id')) abort(403);

        $cliente = Cliente::find($id);
        if (!$cliente) return redirect()->back()->with('error', 'Cliente não encontrado.');

        $validated = $request->validate([
            'nome'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:clientes,email,' . $id,
            'sexo'        => 'required|in:Masculino,Feminino,Outro,masculino,feminino,outro',
            'cep'         => 'nullable|string|max:9',
            'altura'      => 'nullable|numeric',
            'peso'        => 'nullable|numeric',
            'rua'         => 'nullable|string|max:255',
            'bairro'      => 'nullable|string|max:255',
            'cidade'      => 'nullable|string|max:255',
            'estado'      => 'nullable|string|max:255',
            'complemento' => 'nullable|string|max:255',
            'foto'        => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'               => 'required|string|max:255',
            'email'              => 'required|email|max:255|unique:clientes,email',
            'senha'              => 'required|string|min:6|max:255',
            'idade'              => 'required|date',
            'sexo'               => 'required|in:Masculino,Feminino,Outro,masculino,feminino,outro',
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
            'condicao_clinica'   => 'nullable|string',
            'latitude'           => 'nullable|numeric',
            'longitude'          => 'nullable|numeric',
            'aceita_termos'      => 'required|accepted',
        ], [
            'aceita_termos.required' => 'Você deve concordar com os Termos de Uso',
            'aceita_termos.accepted' => 'Você deve concordar com os Termos de Uso',
        ]);

        $validated['sexo'] = strtolower($request->sexo);
        $validated['senha'] = Hash::make($validated['senha']);
        $validated['aceita_termos'] = true;
        $validated['data_aceitacao_termos'] = now();
        $validated['ip_aceitacao_termos'] = $request->ip();

        $cliente = Cliente::create($validated);
        $fb = app(MetaConversionsService::class);
        return redirect()->route('login.index')
            ->with('success', 'Cliente cadastrado com sucesso!')
            ->with('fb_event', $fb->track(
                'CompleteRegistration',
                ['content_name' => 'Cliente', 'status' => 'completo'],
                $fb->userDataFromModel($cliente)
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
            'horario_fim'    => 'required'
        ]);

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
                $fb->userDataFromModel(Cliente::find($clienteId))
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
                $fb->userDataFromModel($cliente)
            ));
    }

    public function verPrecos($id)
    {
        $personal = \App\Models\User::findOrFail($id);
        $precos   = \App\Models\Cadastro\Pacote::where('personal_id', $id)->get();
        return view('cliente.precos', compact('personal', 'precos'));
    }

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
        ]);

        $cliente  = Cliente::find($clienteId);
        $personal = Personal::find($request->personal_id);

        if (!$cliente || !$personal) return redirect()->back()->with('error', 'Dados inválidos.');

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
                $fb->userDataFromModel($cliente)
            ));
    }

    public function agendarAulasInterno(array $booking): void
    {
        $clienteId        = $booking['cliente_id'];
        $personalId       = $booking['personal_id'];
        $frequencia       = $booking['frequencia_pacote'];
        $horaInicio       = $booking['hora_inicio'];
        $horaFim          = $booking['hora_fim'];
        $diasSelecionados = json_decode($booking['dias_selecionados'], true);
        $academiaNome     = $booking['academia_nome'] ?? null;
        $valorPacote      = $booking['valor_pacote'] ?? 0;

        $cliente  = Cliente::find($clienteId);
        $personal = Personal::find($personalId);

        if (!$cliente || !$personal || empty($diasSelecionados)) {
            Log::error('agendarAulasInterno: dados inválidos', $booking);
            return;
        }

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
                $temConflito = Agenda::where('personal_id', $personalId)
                    ->where('data', $data->format('Y-m-d'))
                    ->where('cancelado', false)
                    ->where(function ($q) use ($horaInicio, $horaFim) {
                        $q->whereRaw("hora_inicio < ? AND hora_fim > ?", [$horaFim, $horaInicio]);
                    })->exists();

                if (!$temConflito) {
                    Agenda::create([
                        'cliente_id'         => $clienteId,
                        'personal_id'        => $personalId,
                        'academia_id'        => $cliente->academia_id ?? null,
                        'academia_nome'      => $academiaNome,
                        'data'               => $data->format('Y-m-d'),
                        'hora_inicio'        => $horaInicio,
                        'hora_fim'           => $horaFim,
                        'cancelado'          => false,
                        'descricao'          => "Aula agendada - {$cliente->nome}",
                        'frequencia_pacote'  => $frequencia,
                        'data_inicio_pacote' => now()->startOfMonth(),
                        'data_fim_pacote'    => now()->endOfMonth(),
                        'tipo_aula'          => 'pacote',
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
            'personal_id'   => $personalId,
            'academia_id'   => $cliente->academia_id ?? null,
            'academia_nome' => $academiaNome,
            'data'          => $data,
            'hora_inicio'   => $horaInicio,
            'hora_fim'      => $horaFim,
            'cancelado'     => false,
            'descricao'     => "Aula avulsa - {$cliente->nome}",
            'tipo_aula'     => 'avulsa',
        ]);

        $this->notificarPersonalWhatsApp($clienteId, $personalId, 'avulsa', $agenda);

        Log::info('agendarAulaAvulsaInterno: aula criada', [
            'cliente_id'  => $clienteId,
            'personal_id' => $personalId,
            'data'        => $data,
        ]);
    }

    public function buscarHorariosDisponiveis($personalId, $dia)
    {
        $personal = Personal::find($personalId);
        if (!$personal) return response()->json(['erro' => 'Personal não encontrado'], 404);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) return response()->json(['erro' => 'Formato de data inválido'], 400);

        $hora     = Carbon::createFromFormat('H:i', '06:00');
        $fimTurno = Carbon::createFromFormat('H:i', '22:00');
        $horariosDisponiveis = [];

        while ($hora < $fimTurno) {
            $horaFim             = $hora->copy()->addMinutes(60);
            $horaInicioFormatted = $hora->format('H:i');
            $horaFimFormatted    = $horaFim->format('H:i');

            $temConflito = Agenda::where('personal_id', $personalId)
                ->where('data', $dia)
                ->where('cancelado', false)
                ->whereRaw("hora_inicio < ? AND hora_fim > ?", [$horaFimFormatted, $horaInicioFormatted])
                ->exists();

            if (!$temConflito) {
                $horariosDisponiveis[] = [
                    'inicio' => $horaInicioFormatted,
                    'fim'    => $horaFimFormatted,
                    'label'  => $horaInicioFormatted . ' - ' . $horaFimFormatted,
                ];
            }
            $hora->addMinutes(60);
        }

        return response()->json($horariosDisponiveis);
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
        $cliente   = Cliente::find(session('cliente_id'));
        $personais = Personal::where('status', 'aprovado')
            ->with(['fotos', 'avaliacoes'])
            // Pioneiros (100 primeiros do estado) aparecem em destaque, no topo.
            ->orderByRaw('pioneiro_posicao IS NULL')
            ->orderBy('pioneiro_posicao')
            ->orderBy('nome')
            ->get();
        return view('cliente.personais', compact('personais', 'cliente'));
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
