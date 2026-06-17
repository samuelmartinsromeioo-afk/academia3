<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horários | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
            --error: #ff4444;
            --success: #28a745;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 40px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            flex-wrap: wrap;
            gap: 12px;
        }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 3px;
            color: var(--text-main);
        }
        .logo span { color: var(--primary); }
        .logo small { font-family: 'Inter', sans-serif; letter-spacing: 0; color: var(--text-muted); font-size: 0.7rem; font-weight: 700; margin-left: 8px; }

        .btn-top {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 9px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.78rem;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
        }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 1100px; margin: 0 auto; padding: 36px 20px; }

        .welcome { margin-bottom: 28px; }
        .welcome h1 { font-size: 1.6rem; font-weight: 900; }
        .welcome h1 em { color: var(--primary); font-style: normal; }
        .welcome p { color: var(--text-muted); margin-top: 4px; font-size: 0.9rem; }

        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; }
        .alert-success { background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.4); color: #4caf50; }
        .alert-error { background: rgba(255,68,68,0.1); border: 1px solid rgba(255,68,68,0.3); color: #ff6b6b; }
        .alert-error ul { margin: 6px 0 0 18px; font-weight: 400; }

        .section {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: var(--primary); }
        .section-title .hint { font-size: 0.75rem; color: var(--text-muted); font-weight: 400; margin-left: auto; }

        /* FORM */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            align-items: end;
        }

        label { display: block; font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; }

        input, select {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 13px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.88rem;
            outline: none;
            transition: 0.2s;
            color-scheme: dark;
        }
        input:focus, select:focus { border-color: var(--primary); }
        select option { background: var(--card-bg); }

        .btn {
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 800;
            font-size: 0.85rem;
            cursor: pointer;
            transition: 0.2s;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(212,255,0,0.25); }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
        }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); box-shadow: none; }

        .btn-danger {
            background: transparent;
            border: 1px solid rgba(255,68,68,0.4);
            color: #ff6b6b;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            font-family: inherit;
        }
        .btn-danger:hover { background: rgba(255,68,68,0.12); }

        /* TABELA DE FUNCIONAMENTO */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); padding: 10px 12px; border-bottom: 1px solid var(--border); }
        td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        tr:last-child td { border-bottom: none; }

        .badge-cap {
            background: rgba(212,255,0,0.1);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .empty { color: var(--text-muted); font-size: 0.88rem; padding: 14px 0; }

        /* SLOTS */
        .date-form { display: flex; gap: 12px; align-items: end; flex-wrap: wrap; margin-bottom: 20px; }
        .date-form .field { min-width: 180px; }

        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 14px;
        }

        .slot-card {
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            transition: 0.2s;
        }
        .slot-card:hover { border-color: rgba(212,255,0,0.3); }
        .slot-card.lotado { opacity: 0.6; }

        .slot-hora { font-weight: 800; font-size: 0.95rem; margin-bottom: 6px; }
        .slot-vagas { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px; }
        .slot-vagas strong { color: var(--primary); }
        .slot-card.lotado .slot-vagas strong { color: #ff6b6b; }

        .vagas-bar { height: 5px; background: rgba(255,255,255,0.08); border-radius: 4px; overflow: hidden; margin-bottom: 12px; }
        .vagas-bar div { height: 100%; background: var(--primary); border-radius: 4px; }
        .slot-card.lotado .vagas-bar div { background: #ff6b6b; }

        /* BLOQUEIOS */
        .bloqueio-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,68,68,0.06);
            border: 1px solid rgba(255,68,68,0.2);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 10px;
        }
        .bloqueio-item .hora { font-weight: 800; }
        .bloqueio-item .desc { color: var(--text-muted); font-size: 0.8rem; margin-top: 2px; }

        @media (max-width: 600px) {
            .top-bar { padding: 14px 20px; }
            .section { padding: 20px; }
        }

        /* PROFISSIONAIS */
        .prof-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
        }
        .prof-item .nome { font-weight: 800; font-size: 0.92rem; }
        .prof-item .resumo { color: var(--text-muted); font-size: 0.8rem; margin-top: 4px; line-height: 1.5; white-space: pre-line; }
        .prof-actions { display: flex; gap: 8px; flex-shrink: 0; }
        textarea {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 13px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.88rem;
            outline: none;
            resize: vertical;
            min-height: 80px;
        }
        textarea:focus { border-color: var(--primary); }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(6px); padding: 20px; }
        .modal-overlay.aberto { display: flex; }
        .modal-box { background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; padding: 28px; width: 100%; max-width: 480px; position: relative; }
        .modal-box h3 { font-size: 1.05rem; font-weight: 800; margin-bottom: 18px; }
        .modal-box .fg { margin-bottom: 14px; }
        .modal-close { position: absolute; top: 16px; right: 18px; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; background: none; border: none; }
        .modal-close:hover { color: var(--error); }
    </style>
</head>
<body>

@php
    $diasSemana = [0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'];
    $dataCarbon = \Carbon\Carbon::parse($dataSelecionada);
@endphp

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span><small>| Studio</small></div>
    <a href="{{ route('studio.dashboard') }}" class="btn-top"><i class="fas fa-arrow-left"></i> Voltar ao painel</a>
</div>

<div class="container">
    <div class="welcome">
        <h1>Horários do <em>{{ $studio->nome }}</em></h1>
        <p>Defina o funcionamento por dia da semana, acompanhe as vagas dos horários e bloqueie slots quando precisar.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> Corrija os erros abaixo:
            <ul>
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- HORÁRIO DE FUNCIONAMENTO -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-clock"></i> Horário de funcionamento
            <span class="hint">Capacidade em branco usa o padrão do studio ({{ $studio->capacidade_padrao }} alunos)</span>
        </div>

        <form method="POST" action="{{ route('studio.horarios.store') }}">
            @csrf
            <div class="form-grid">
                <div>
                    <label for="dia_semana">Dia da semana</label>
                    <select name="dia_semana" id="dia_semana" required>
                        @foreach ($diasSemana as $num => $nome)
                            <option value="{{ $num }}" @selected(old('dia_semana') !== null && (int) old('dia_semana') === $num)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="hora_abertura">Abertura</label>
                    <input type="time" name="hora_abertura" id="hora_abertura" value="{{ old('hora_abertura', '08:00') }}" required>
                </div>
                <div>
                    <label for="hora_fechamento">Fechamento</label>
                    <input type="time" name="hora_fechamento" id="hora_fechamento" value="{{ old('hora_fechamento', '18:00') }}" required>
                </div>
                <div>
                    <label for="capacidade">Capacidade por horário</label>
                    <input type="number" name="capacidade" id="capacidade" min="1" max="500" placeholder="Padrão: {{ $studio->capacidade_padrao }}" value="{{ old('capacidade') }}">
                </div>
                <button type="submit" class="btn"><i class="fas fa-save"></i> Salvar</button>
            </div>
        </form>

        <div style="margin-top: 24px;">
            @if ($horarios->isEmpty())
                <p class="empty"><i class="fas fa-info-circle"></i> Nenhum horário cadastrado ainda. Enquanto não houver funcionamento definido, os clientes não verão horários disponíveis.</p>
            @else
                <table class="resp-cards">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Funcionamento</th>
                            <th>Capacidade</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($horarios as $h)
                            <tr>
                                <td data-label="Dia"><strong>{{ $diasSemana[$h->dia_semana] ?? $h->dia_semana }}</strong></td>
                                <td data-label="Funcionamento">{{ substr($h->hora_abertura, 0, 5) }} às {{ substr($h->hora_fechamento, 0, 5) }}</td>
                                <td data-label="Capacidade"><span class="badge-cap">{{ $h->capacidade ?? $studio->capacidade_padrao }} alunos/horário</span></td>
                                <td data-label="Ações" style="text-align:right;">
                                    <form method="POST" action="{{ route('studio.horarios.destroy', $h->id) }}" style="display:inline;" onsubmit="return confirm('Remover o funcionamento de {{ $diasSemana[$h->dia_semana] ?? '' }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger"><i class="fas fa-trash"></i> Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- PROFISSIONAIS -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-user-tie"></i> Profissionais
            <span class="hint">Cadastre uma vez com um breve resumo e reutilize em vários horários de aula.</span>
        </div>

        <form method="POST" action="{{ route('studio.professores.store') }}">
            @csrf
            <div class="form-grid" style="align-items: start;">
                <div>
                    <label for="prof_nome">Nome do profissional</label>
                    <input type="text" name="nome" id="prof_nome" placeholder="Ex: Prof. Ana Souza" maxlength="120" required>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="prof_resumo">Breve resumo</label>
                    <textarea name="resumo" id="prof_resumo" maxlength="2000" placeholder="Ex: Educadora física (CREF 12345), especialista em pilates e reabilitação, 8 anos de experiência."></textarea>
                </div>
                <button type="submit" class="btn"><i class="fas fa-plus"></i> Cadastrar profissional</button>
            </div>
        </form>

        <div style="margin-top: 24px;">
            @if ($professores->isEmpty())
                <p class="empty"><i class="fas fa-info-circle"></i> Nenhum profissional cadastrado ainda.</p>
            @else
                @foreach ($professores as $prof)
                    <div class="prof-item">
                        <div>
                            <div class="nome"><i class="fas fa-user-tie" style="color: var(--primary);"></i> {{ $prof->nome }}</div>
                            @if ($prof->resumo)
                                <div class="resumo">{{ $prof->resumo }}</div>
                            @else
                                <div class="resumo"><em>Sem resumo cadastrado.</em></div>
                            @endif
                        </div>
                        <div class="prof-actions">
                            <button type="button" class="btn-danger" style="border-color: var(--border); color: var(--text-main);"
                                onclick='abrirEditarProfessor(@json($prof->id), @json($prof->nome), @json($prof->resumo))'><i class="fas fa-pen"></i></button>
                            <form method="POST" action="{{ route('studio.professores.destroy', $prof->id) }}" onsubmit="return confirm('Remover este profissional? Ele será desvinculado das aulas.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- HORÁRIOS DE AULA -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-chalkboard-teacher"></i> Horários de aula
            <span class="hint">Defina horário de início, duração e o profissional. Quando há aulas num dia, elas substituem os slots automáticos de 1h.</span>
        </div>

        <form method="POST" action="{{ route('studio.aulas.store') }}">
            @csrf
            <div class="form-grid">
                <div>
                    <label for="aula_dia">Dia da semana</label>
                    <select name="dia_semana" id="aula_dia" required>
                        @foreach ($diasSemana as $num => $nome)
                            <option value="{{ $num }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="aula_inicio">Início da aula</label>
                    <input type="time" name="hora_inicio" id="aula_inicio" value="08:00" required>
                </div>
                <div>
                    <label for="aula_duracao">Duração (min)</label>
                    <input type="number" name="duracao_min" id="aula_duracao" min="5" max="600" step="5" value="60" required>
                </div>
                <div>
                    <label for="aula_prof">Profissional</label>
                    <select name="professor_id" id="aula_prof">
                        <option value="">— Sem profissional —</option>
                        @foreach ($professores as $prof)
                            <option value="{{ $prof->id }}">{{ $prof->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="aula_cap">Capacidade</label>
                    <input type="number" name="capacidade" id="aula_cap" min="1" max="500" placeholder="Padrão: {{ $studio->capacidade_padrao }}">
                </div>
                <button type="submit" class="btn"><i class="fas fa-plus"></i> Adicionar aula</button>
            </div>
        </form>

        <div style="margin-top: 24px;">
            @if ($aulas->isEmpty())
                <p class="empty"><i class="fas fa-info-circle"></i> Nenhum horário de aula cadastrado. Sem aulas, os clientes verão slots automáticos de 1h dentro do funcionamento.</p>
            @else
                <table class="resp-cards">
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Início</th>
                            <th>Duração</th>
                            <th>Profissional</th>
                            <th>Capacidade</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($aulas as $a)
                            <tr>
                                <td data-label="Dia"><strong>{{ $diasSemana[$a->dia_semana] ?? $a->dia_semana }}</strong></td>
                                <td data-label="Início">{{ substr($a->hora_inicio, 0, 5) }}</td>
                                <td data-label="Duração">{{ $a->duracao_min }} min</td>
                                <td data-label="Profissional">{{ $a->profissional ?: '—' }}</td>
                                <td data-label="Capacidade"><span class="badge-cap">{{ $a->capacidade ?? $studio->capacidade_padrao }} alunos</span></td>
                                <td data-label="Ações" style="text-align:right;">
                                    <form method="POST" action="{{ route('studio.aulas.destroy', $a->id) }}" style="display:inline;" onsubmit="return confirm('Remover esta aula?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger"><i class="fas fa-trash"></i> Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- SLOTS DO DIA -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-calendar-day"></i> Vagas por horário — {{ $dataCarbon->format('d/m/Y') }} ({{ $diasSemana[$dataCarbon->dayOfWeek] }})
        </div>

        <form method="GET" action="{{ route('studio.horarios') }}" class="date-form">
            <div class="field">
                <label for="data">Escolher data</label>
                <input type="date" name="data" id="data" value="{{ $dataSelecionada }}">
            </div>
            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> Ver horários</button>
        </form>

        @if (empty($slots))
            <p class="empty"><i class="fas fa-info-circle"></i> Sem horários para este dia. Cadastre o funcionamento de {{ $diasSemana[$dataCarbon->dayOfWeek] }} acima ou verifique se todos os slots estão bloqueados.</p>
        @else
            <div class="slots-grid">
                @foreach ($slots as $slot)
                    <div class="slot-card {{ $slot['vagas'] === 0 ? 'lotado' : '' }}">
                        <div class="slot-hora"><i class="far fa-clock" style="color: var(--primary);"></i> {{ $slot['label'] }} <span style="color:var(--text-muted); font-weight:600; font-size:0.78rem;">· {{ $slot['duracao'] ?? 60 }}min</span></div>
                        @if (!empty($slot['profissional']))
                            <div class="slot-vagas" style="margin-bottom:6px;"><i class="fas fa-chalkboard-teacher" style="color: var(--primary);"></i> {{ $slot['profissional'] }}</div>
                        @endif
                        <div class="slot-vagas">
                            <strong>{{ $slot['vagas'] }}</strong> de {{ $slot['capacidade'] }} vagas livres
                            ({{ $slot['ocupacao'] }} ocupada{{ $slot['ocupacao'] === 1 ? '' : 's' }})
                        </div>
                        <div class="vagas-bar">
                            <div style="width: {{ $slot['capacidade'] > 0 ? round(($slot['ocupacao'] / $slot['capacidade']) * 100) : 0 }}%;"></div>
                        </div>
                        <form method="POST" action="{{ route('studio.bloquear-slot') }}" onsubmit="return confirm('Bloquear o horário {{ $slot['label'] }} em {{ $dataCarbon->format('d/m/Y') }}? Ele deixará de aparecer para os clientes.');">
                            @csrf
                            <input type="hidden" name="data" value="{{ $dataSelecionada }}">
                            <input type="hidden" name="hora_inicio" value="{{ $slot['inicio'] }}">
                            <input type="hidden" name="hora_fim" value="{{ $slot['fim'] }}">
                            <button type="submit" class="btn-danger" style="width:100%;"><i class="fas fa-ban"></i> Bloquear</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- BLOQUEIOS DO DIA -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-ban"></i> Bloqueios em {{ $dataCarbon->format('d/m/Y') }}
        </div>

        @if ($bloqueios->isEmpty())
            <p class="empty">Nenhum horário bloqueado nesta data.</p>
        @else
            @foreach ($bloqueios as $b)
                <div class="bloqueio-item">
                    <div>
                        <div class="hora"><i class="far fa-clock"></i> {{ substr($b->hora_inicio, 0, 5) }} - {{ substr($b->hora_fim, 0, 5) }}</div>
                        <div class="desc">{{ $b->descricao }}</div>
                    </div>
                    <form method="POST" action="{{ route('studio.desbloquear-slot', $b->id) }}" onsubmit="return confirm('Liberar este horário novamente?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger"><i class="fas fa-unlock"></i> Desbloquear</button>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- MODAL EDITAR PROFISSIONAL -->
<div class="modal-overlay" id="modalProfessor">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="fecharEditarProfessor()"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-user-pen" style="color: var(--primary);"></i> Editar profissional</h3>
        <form method="POST" id="formEditarProfessor">
            @csrf
            @method('PUT')
            <div class="fg">
                <label for="edit_prof_nome">Nome</label>
                <input type="text" name="nome" id="edit_prof_nome" maxlength="120" required>
            </div>
            <div class="fg">
                <label for="edit_prof_resumo">Breve resumo</label>
                <textarea name="resumo" id="edit_prof_resumo" maxlength="2000"></textarea>
            </div>
            <button type="submit" class="btn" style="width: 100%;"><i class="fas fa-save"></i> Salvar alterações</button>
        </form>
    </div>
</div>

<script>
    const rotaProfessorUpdateBase = "{{ url('/studio/profissionais') }}";

    function abrirEditarProfessor(id, nome, resumo) {
        const form = document.getElementById('formEditarProfessor');
        form.action = rotaProfessorUpdateBase + '/' + id;
        document.getElementById('edit_prof_nome').value = nome || '';
        document.getElementById('edit_prof_resumo').value = resumo || '';
        document.getElementById('modalProfessor').classList.add('aberto');
    }
    function fecharEditarProfessor() {
        document.getElementById('modalProfessor').classList.remove('aberto');
    }
    document.getElementById('modalProfessor').addEventListener('click', function (e) {
        if (e.target === this) fecharEditarProfessor();
    });
</script>

</body>
</html>
