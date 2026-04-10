<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\cadastro\Cliente;
use App\Models\cadastro\Personal;
use App\Models\cadastro\academia as Academia; // Ajustado para evitar erro de caixa alta
use App\Models\Agenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ClienteController extends Controller
{
    public function index()
    {
        $id = session('cliente_id');
        if (!$id) return redirect()->route('login.index');

        $cliente = Cliente::find($id);
        $personals = Personal::all();
        $academias = Academia::all();

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

        return view('cliente.index', compact('cliente', 'personals', 'meusAgendamentos', 'horariosDisponiveis', 'academias', 'historico'));
    }

    // MÉTODO UPDATE CORRIGIDO
    public function update(Request $request, $id)
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return redirect()->back()->with('error', 'Cliente não encontrado.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clientes,email,' . $id,
            'sexo' => 'required|in:Masculino,Feminino,Outro,masculino,feminino,outro',
            'cep' => 'nullable|string|max:9',
            'altura' => 'nullable|numeric',
            'peso' => 'nullable|numeric',
            'rua' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'complemento' => 'nullable|string|max:255',
        ]);

        //   dados validados
        $data = $request->all();

        //  Ajusta o sexo para o que está na sua Migration 
        $data['sexo'] = strtolower($request->sexo);

        //  Tratamento da senha
        if ($request->filled('senha')) {
            $data['senha'] = Hash::make($request->senha);
        } else {
            unset($data['senha']);
        }

        //  Salva no banco
        $cliente->update($data);

        //  Retorna com a mensagem de sucesso (ajustado para 'success' que é o que você usa na view)
        return redirect()->route('cliente.index')->with('success', 'Perfil atualizado com sucesso!');
    }

    public function create()
    {
        return view('cadastro.cliente');
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:clientes,email',
            'senha'       => 'required|string|min:6|max:255',
            'sexo'        => 'required|in:Masculino,Feminino,Outro,masculino,feminino,outro',
            'cep'         => 'required|string|max:9',
            'rua'         => 'nullable|string|max:255',
            'bairro'      => 'nullable|string|max:255',
            'cidade'      => 'nullable|string|max:255',
            'estado'      => 'nullable|string|max:255',
            'complemento' => 'nullable|string|max:255',
            'altura'      => 'nullable|numeric',
            'peso'        => 'nullable|numeric',
        ]);

        $validated['sexo'] = strtolower($request->sexo);
        $validated['senha'] = Hash::make($validated['senha']);

        Cliente::create($validated);

        return redirect()->route('login.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function reservarHorario(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index')->with('erro', 'Sessão expirada.');

        $request->validate([
            'personal_id' => 'required|exists:personals,id',
            'academia_id' => 'nullable|exists:academias,id',
            'data' => 'required|date',
            'horario_inicio' => 'required',
            'horario_fim' => 'required'
        ]);

        Agenda::create([
            'cliente_id'  => $clienteId,
            'personal_id' => $request->personal_id,
            'academia_id' => $request->academia_id ?? null,
            'data'        => $request->data,
            'hora_inicio' => $request->horario_inicio,
            'hora_fim'    => $request->horario_fim,
            'cancelado'   => false
        ]);

        // Notifica o personal por e-mail
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
                    $message->to($personal->email)
                        ->subject('📅 Nova aula agendada - FitSys');
                });
            } catch (\Exception $e) {
                // Não bloqueia o fluxo se o e-mail falhar
            }
        }

        return redirect()->back()->with('sucesso', 'Horário agendado com sucesso!');
    }

    public function contratarAcademia(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index')->with('erro', 'Você precisa estar logado.');

        $request->validate([
            'academia_id' => 'required|exists:academias,id'
        ]);

        $cliente  = Cliente::find($clienteId);
        $academia = Academia::find($request->academia_id);

        $cliente->update(['academia_id' => $request->academia_id]);

        // Notifica a academia por e-mail
        if ($academia && $academia->email) {
            try {
                Mail::send('emails.academia-contratada', [
                    'academia_nome'  => $academia->nome,
                    'cliente_nome'   => $cliente->nome,
                    'cliente_email'  => $cliente->email,
                    'cliente_cidade' => $cliente->cidade ?? null,
                    'cliente_idade'  => $cliente->idade ?? null,
                ], function ($message) use ($academia) {
                    $message->to($academia->email)
                        ->subject('🎉 Novo aluno contratou sua academia - FitSys');
                });
            } catch (\Exception $e) {
                // Não bloqueia o fluxo se o e-mail falhar
            }
        }

        return redirect()->back()->with('sucesso', 'Academia contratada com sucesso!');
    }

    public function verPrecos($id)
    {
        // 1. Busca o Personal
        $personal = \App\Models\User::findOrFail($id);

        // 2. Busca os preços dele
        $precos = \App\Models\cadastro\Pacote::where('personal_id', $id)->get();

        // 3. Retorna a view (vamos criar ela abaixo)
        return view('cliente.precos', compact('personal', 'precos'));
    }

    public function contratarPacote(Request $request)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index')->with('erro', 'Sessão expirada.');

        $request->validate([
            'personal_id' => 'required|exists:personals,id',
            'frequencia_pacote' => 'required|integer|min:1|max:7',
            'valor_pacote' => 'required|numeric|min:0',
            'dias_selecionados' => 'required|json',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i',
        ]);

        $cliente = Cliente::find($clienteId);
        $personal = Personal::find($request->personal_id);

        if (!$cliente || !$personal) {
            return redirect()->back()->with('error', 'Dados inválidos.');
        }

        $diasSelecionados = json_decode($request->dias_selecionados, true);

        if (empty($diasSelecionados)) {
            return redirect()->back()->with('error', 'Selecione pelo menos um dia.');
        }

        $frequencia = $request->frequencia_pacote;

        if (count($diasSelecionados) > $frequencia) {
            return redirect()->back()->with('error', "Você selecionou " . count($diasSelecionados) . " dia(s), mas o pacote permite apenas {$frequencia}x na semana.");
        }

        $horaInicio = $request->hora_inicio;  // "07:00"
        $horaFim = $request->hora_fim;        // "08:00"

        $agendamentosCriados = 0;

        foreach ($diasSelecionados as $dia) {
            // Cria data base com o dia selecionado
            $dataPrimeira = Carbon::create(now()->year, now()->month, (int)$dia);

            // Se a data já passou, pula pra próximo mês
            if ($dataPrimeira < now()->startOfDay()) {
                $dataPrimeira = $dataPrimeira->addMonth();
            }

            // Identifica o dia da semana
            $diaDaSemana = $dataPrimeira->dayOfWeek;

            // Encontra TODAS as datas com o mesmo dia da semana (mês atual e próximo)
            $datasComMesmoDia = [];

            // Mês atual
            $dataAtual = Carbon::create(now()->year, now()->month, 1);
            $dataFimMesAtual = now()->endOfMonth();

            while ($dataAtual <= $dataFimMesAtual) {
                if ($dataAtual->dayOfWeek === $diaDaSemana && $dataAtual >= now()->startOfDay()) {
                    $datasComMesmoDia[] = $dataAtual->copy();
                }
                $dataAtual->addDay();
            }

            // Próximo mês
            $proxMes = now()->addMonth();
            $dataAtual = Carbon::create($proxMes->year, $proxMes->month, 1);
            $dataFimProxMes = $proxMes->endOfMonth();

            while ($dataAtual <= $dataFimProxMes) {
                if ($dataAtual->dayOfWeek === $diaDaSemana) {
                    $datasComMesmoDia[] = $dataAtual->copy();
                }
                $dataAtual->addDay();
            }

            // ✅ Cria agendamentos APENAS se o horário não estiver ocupado
            foreach ($datasComMesmoDia as $data) {
                // Verifica conflito de horário ESPECÍFICO
                $temConflito = Agenda::where('personal_id', $request->personal_id)
                    ->where('data', $data->format('Y-m-d'))
                    ->where('cancelado', false)
                    ->where(function ($query) use ($horaInicio, $horaFim) {
                        // Se há sobreposição de horário
                        $query->whereRaw("hora_inicio < ? AND hora_fim > ?", [$horaFim, $horaInicio]);
                    })
                    ->exists();

                if (!$temConflito) {
                    Agenda::create([
                        'cliente_id' => $clienteId,
                        'personal_id' => $request->personal_id,
                        'academia_id' => $cliente->academia_id ?? null,
                        'data' => $data->format('Y-m-d'),
                        'hora_inicio' => $horaInicio,
                        'hora_fim' => $horaFim,
                        'cancelado' => false,
                        'descricao' => "Aula agendada - {$cliente->nome}",
                        'frequencia_pacote' => $frequencia,
                        'data_inicio_pacote' => now()->startOfMonth(),
                        'data_fim_pacote' => now()->endOfMonth(),
                    ]);
                    $agendamentosCriados++;
                }
            }
        }

        if ($agendamentosCriados === 0) {
            return redirect()->back()->with('warning', 'Nenhum horário pôde ser agendado. Verifique conflitos de horários.');
        }

        // Notifica personal
        if ($personal && $personal->email) {
            try {
                Mail::send('emails.pacote-contratado', [
                    'personal_nome' => $personal->nome,
                    'cliente_nome' => $cliente->nome,
                    'cliente_email' => $cliente->email,
                    'frequencia' => $frequencia,
                    'valor_mensal' => $request->valor_pacote,
                    'dias_total' => $agendamentosCriados,
                    'hora_inicio' => $horaInicio,
                    'hora_fim' => $horaFim,
                    'data_inicio' => now()->startOfMonth()->format('d/m/Y'),
                    'data_fim' => now()->endOfMonth()->format('d/m/Y'),
                ], function ($message) use ($personal) {
                    $message->to($personal->email)
                        ->subject('🎉 Novo pacote contratado - FitSys');
                });
            } catch (\Exception $e) {
                // Silent fail
            }
        }

        return redirect()->back()->with('success', "Pacote contratado com sucesso! {$agendamentosCriados} treino(s) agendado(s).");
    }

    // ✅ MÉTODO BUSCAR HORÁRIOS - CORRIGIDO
    public function buscarHorariosDisponiveis($personalId, $dia)
    {
        $personal = Personal::find($personalId);

        if (!$personal) {
            return response()->json(['erro' => 'Personal não encontrado'], 404);
        }

        // Validar formato da data
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) {
            return response()->json(['erro' => 'Formato de data inválido'], 400);
        }

        $turnoInicio = '06:00';
        $turnoFim = '22:00';
        $duracao = 60;

        $horariosDisponiveis = [];

        $hora = Carbon::createFromFormat('H:i', $turnoInicio);
        $fimTurno = Carbon::createFromFormat('H:i', $turnoFim);

        while ($hora < $fimTurno) {
            $horaFim = $hora->copy()->addMinutes($duracao);

            $horaInicioFormatted = $hora->format('H:i');
            $horaFimFormatted = $horaFim->format('H:i');

            // ✅ Verifica se há CONFLITO DE HORÁRIO específico
            $temConflito = Agenda::where('personal_id', $personalId)
                ->where('data', $dia)
                ->where('cancelado', false)
                ->whereRaw("hora_inicio < ? AND hora_fim > ?", [$horaFimFormatted, $horaInicioFormatted])
                ->exists();

            if (!$temConflito) {
                $horariosDisponiveis[] = [
                    'inicio' => $horaInicioFormatted,
                    'fim' => $horaFimFormatted,
                    'label' => $horaInicioFormatted . ' - ' . $horaFimFormatted,
                ];
            }

            $hora->addMinutes($duracao);
        }

        return response()->json($horariosDisponiveis);
    }
}
