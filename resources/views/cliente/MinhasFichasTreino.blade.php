<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Fichas de Treino</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --error: #ff4444;
            --success: #00ff88;
            --border: rgba(255, 255, 255, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font-family: inherit;
        }

        input, textarea, select {
            font-family: inherit;
        }

        /* TOP BAR */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .back-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--primary);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            font-size: 1.2rem;
        }

        .back-btn:hover {
            background: var(--primary);
            color: #000;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            object-fit: cover;
            background: #222;
        }

        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h1 {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            opacity: 0.6;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .empty-state p {
            color: var(--text-main);
            font-size: 1.1rem;
            margin: 10px 0;
        }

        .empty-state small {
            color: var(--text-muted);
        }

        /* PERSONAL SECTION */
        .personal-section {
            margin-bottom: 40px;
        }

        .personal-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(212, 255, 0, 0.3);
        }

        .personal-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .personal-header h2 {
            margin: 0;
            color: var(--primary);
            font-size: 1.3rem;
        }

        /* GRID DE FICHAS */
        .fichas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .ficha-card {
            background: var(--card-bg);
            border: 1px solid rgba(212, 255, 0, 0.2);
            border-radius: 20px;
            padding: 20px;
            transition: 0.3s;
            cursor: pointer;
        }

        .ficha-card:hover {
            border-color: rgba(212, 255, 0, 0.5);
            box-shadow: 0 0 20px rgba(212, 255, 0, 0.1);
        }

        .ficha-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .ficha-header h3 {
            margin: 0;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .btn-marcar {
            background: rgba(212, 255, 0, 0.1);
            color: var(--primary);
            border: 1px solid var(--primary);
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 900;
            transition: 0.3s;
            font-size: 0.75rem;
        }

        .btn-marcar:hover {
            background: var(--primary);
            color: #000;
        }

        .btn-marcar.concluido {
            background: var(--success);
            color: #000;
            border-color: var(--success);
        }

        .ficha-nome {
            margin: 0 0 15px 0;
            color: var(--text-main);
            font-weight: 700;
            font-size: 1.05rem;
        }

        .ficha-obs {
            margin: 0 0 15px 0;
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.5;
            border-left: 3px solid var(--primary);
            padding-left: 10px;
        }

        .exercicios-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
        }

        .exercicios-title {
            margin: 0 0 12px 0;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 900;
        }

        .exercicios-table {
            width: 100%;
            font-size: 0.8rem;
            color: var(--text-main);
            border-collapse: collapse;
        }

        .exercicios-table thead tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .exercicios-table th {
            text-align: left;
            padding: 8px 0;
            color: var(--primary);
            font-weight: 900;
        }

        .exercicios-table td {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .exercicios-table tbody tr:last-child td {
            border-bottom: none;
        }

        .ex-nome {
            font-weight: 700;
        }

        .ex-obs {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .ex-click {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: color 0.2s;
        }
        .ex-click:hover { color: var(--primary); }
        .ex-info { color: var(--primary); font-size: 0.75rem; opacity: 0.8; }

        .empty-ex {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            padding: 10px 0;
        }

        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(8px);
            z-index: 2000;
            overflow-y: auto;
            padding: 40px 0;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-bg);
            max-width: 500px;
            padding: 35px;
            border-radius: 20px;
            border: 1px solid var(--border);
            width: 90%;
        }

        .modal-content h2 {
            color: var(--primary);
            margin-top: 0;
            font-weight: 900;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-group label {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 900;
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            padding: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(212, 255, 0, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 900;
            transition: 0.2s;
            font-size: 0.85rem;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-submit {
            flex: 1;
            background: var(--success);
            color: #000;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 900;
            transition: 0.2s;
            font-size: 0.85rem;
        }

        .btn-submit:hover {
            background: #33ff99;
        }

        /* BOTÃO EVOLUÇÃO DE CARGA */
        .btn-evolucao {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #F4BE16;
            color: #000;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 900;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            margin-bottom: 30px;
            transition: 0.2s;
            box-shadow: 0 0 18px rgba(244, 190, 22, 0.25);
        }
        .btn-evolucao:hover { filter: brightness(1.1); transform: translateY(-1px); }

        /* REGISTRO DE CARGA (modal concluir) */
        .registros-wrap { margin-bottom: 15px; }
        .registros-wrap > label {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 900;
            display: block;
            margin-bottom: 8px;
        }
        .registro-row {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .registro-row .reg-nome {
            font-weight: 700;
            font-size: 0.85rem;
            color: #F4BE16;
            margin-bottom: 8px;
        }
        .registro-inputs { display: flex; gap: 8px; }
        .registro-inputs .reg-field { flex: 1; display: flex; flex-direction: column; }
        .registro-inputs .reg-field span {
            font-size: 0.6rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 900;
            margin-bottom: 3px;
        }
        .registro-inputs input {
            width: 100%;
            padding: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            border-radius: 6px;
            font-size: 0.9rem;
            text-align: center;
        }
        .registro-inputs input:focus {
            outline: none;
            border-color: #F4BE16;
        }

        /* FEEDBACK PÓS-TREINO */
        .fb-emojis { display: flex; gap: 8px; }
        .fb-emojis label { flex: 1; cursor: pointer; text-align: center; padding: 8px 4px; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; background: rgba(255,255,255,0.04); transition: 0.15s; }
        .fb-emojis label:has(input:checked) { border-color: #F4BE16; background: rgba(244,190,22,0.14); }
        .fb-emojis input { display: none; }
        .fb-emojis .emo { font-size: 1.35rem; display: block; }
        .fb-emojis .cap { font-size: 0.58rem; color: var(--text-muted); margin-top: 2px; text-transform: uppercase; font-weight: 800; }
        .rpe-chips { display: flex; gap: 5px; flex-wrap: wrap; }
        .rpe-chips label { cursor: pointer; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: rgba(255,255,255,0.04); font-size: 0.8rem; font-weight: 800; transition: 0.15s; }
        .rpe-chips label:has(input:checked) { border-color: #F4BE16; background: #F4BE16; color: #000; }
        .rpe-chips input { display: none; }
        .rpe-hint { font-size: 0.62rem; color: var(--text-muted); margin-top: 5px; }

        /* BANNER DE RECORDE */
        .recorde-banner { background: linear-gradient(135deg, rgba(244,190,22,0.18), rgba(244,190,22,0.03)); border: 1px solid #F4BE16; color: #fff; padding: 16px 18px; border-radius: 14px; margin-bottom: 20px; }
        .recorde-banner .tt { color: #F4BE16; font-weight: 900; display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .recorde-banner ul { margin: 0; padding-left: 22px; font-size: 0.9rem; line-height: 1.6; }

        @media (max-width: 768px) {
            .top-bar {
                padding: 15px 20px;
            }

            .container {
                margin: 20px auto;
            }

            h1 {
                font-size: 1.5rem;
            }

            .fichas-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="ed-page">

    @php
        // Mapa fichaId => exercícios, para o JS pré-preencher os campos de carga.
        $fichasExerciciosJs = [];
        $coletaExercicios = function ($grupos) use (&$fichasExerciciosJs) {
            foreach ($grupos as $grupo) {
                foreach ($grupo as $ficha) {
                    $fichasExerciciosJs[$ficha->id] = $ficha->exercicios->map(fn ($e) => [
                        'id' => $e->id,
                        'nome' => $e->nome_exercicio,
                        'series' => $e->series,
                        'repeticoes' => $e->repeticoes,
                        'peso' => $e->peso !== null ? (float) $e->peso : null,
                    ])->values();
                }
            }
        };
        $coletaExercicios($fichasPorPersonal);
        if (isset($fichasAcademia)) {
            $coletaExercicios($fichasAcademia);
        }
    @endphp

    <!-- TOP BAR -->
    <div class="top-bar">
        <button class="back-btn" onclick="window.location.href='{{ route('cliente.index') }}'" title="Voltar">
            <i class="ph ph-arrow-left"></i>
        </button>
        <div class="profile-header">
            <span style="font-weight: 700; font-size: 0.9rem;">Minhas Fichas de Treino</span>
            <img src="https://ui-avatars.com/api/?name={{ urlencode($cliente->nome) }}&background=d4ff00&color=000" class="avatar-img">
        </div>
    </div>

    <!-- CONTAINER -->
    <div class="container">
        <div class="ed-eyebrow"><i class="ph ph-barbell"></i> Treino</div><h1 class="ed-h">Minhas Fichas de <span class="ed-mark">Treino</span></h1>

        <a href="{{ route('evolucao-carga.minha') }}" class="btn-evolucao">
            <i class="ph ph-lightning"></i> Evolução de Carga
        </a>

        @if(session('success'))
        <div class="alert alert-success">
            <i class="ph ph-check-circle"></i>
            {{ session('success') }}
        </div>
        @endif

        @if(session('recordes') && count(session('recordes')) > 0)
        <div class="recorde-banner">
            <div class="tt"><i class="ph ph-trophy"></i> Novo recorde pessoal! 🎉</div>
            <ul>
                @foreach(session('recordes') as $r)
                    <li>{{ $r }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($fichasPorPersonal->isEmpty() && (!isset($fichasAcademia) || $fichasAcademia->isEmpty()))
            <div class="empty-state">
                <i class="ph ph-calendar"></i>
                <p>Você não possui fichas de treino.</p>
                <small>Seu personal ou sua academia criará suas fichas em breve!</small>
            </div>
        @else
            @foreach($fichasPorPersonal as $personalId => $fichasDoPersonal)
                @php
                    $personal = $fichasDoPersonal->first()->personal;
                    $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                @endphp

                <div class="personal-section">
                    <div class="personal-header">
                        <img src="{{ $personal->foto ? asset('storage/' . $personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}"
                            alt="{{ $personal->nome }}">
                        <h2>{{ $personal->nome }}</h2>
                    </div>

                    <div class="fichas-grid">
                        @foreach($fichasDoPersonal as $ficha)
                            @php
                                $concluido = $ficha->treino_de_hoje();
                                $estaConcluido = $concluido && $concluido->concluido;
                            @endphp

                            <div class="ficha-card">
                                <div class="ficha-header">
                                    <h3><i class="ph ph-calendar-dot"></i> {{ $dias[$ficha->dia_semana] }}</h3>
                                    <div style="display:flex; gap:6px;">
                                        <a href="{{ route('fichas-treino.executar', $ficha->id) }}" class="btn-marcar" style="text-decoration:none; background:#F4BE16; color:#000; border-color:#F4BE16;">
                                            <i class="ph ph-play"></i> INICIAR
                                        </a>
                                        <button class="btn-marcar {{ $estaConcluido ? 'concluido' : '' }}"
                                            onclick="marcarConcluido({{ $ficha->id }})">
                                            <i class="ph {{ $estaConcluido ? 'ph-check' : 'ph-square' }}"></i>
                                            {{ $estaConcluido ? 'CONCLUÍDO' : 'MARCAR' }}
                                        </button>
                                    </div>
                                </div>

                                <p class="ficha-nome">{{ $ficha->nome_treino }}</p>

                                @if($ficha->observacoes)
                                    <p class="ficha-obs">{{ $ficha->observacoes }}</p>
                                @endif

                                <div class="exercicios-box">
                                    <p class="exercicios-title"><i class="ph ph-list-bullets"></i> Exercícios</p>

                                    @if($ficha->exercicios->isEmpty())
                                        <p class="empty-ex">Nenhum exercício</p>
                                    @else
                                        <table class="exercicios-table resp-cards">
                                            <thead>
                                                <tr>
                                                    <th>Exercício</th>
                                                    <th style="text-align: center;">Séries</th>
                                                    <th style="text-align: center;">Reps</th>
                                                    <th style="text-align: center;">Peso</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($ficha->exercicios as $exercicio)
                                                <tr>
                                                    <td data-label="Exercício">
                                                        <div class="ex-nome ex-click" onclick="abrirExercicio({!! htmlspecialchars(json_encode($exercicio->nome_exercicio), ENT_QUOTES) !!}, {!! htmlspecialchars(json_encode($exercicio->observacoes), ENT_QUOTES) !!}, {!! htmlspecialchars(json_encode($exercicio->video ? asset('storage/' . $exercicio->video) : ''), ENT_QUOTES) !!})">
                                                            {{ $exercicio->nome_exercicio }}
                                                            <i class="ph ph-info ex-info"></i>
                                                        </div>
                                                    </td>
                                                    <td data-label="Séries" style="text-align: center;">{{ $exercicio->series }}</td>
                                                    <td data-label="Reps" style="text-align: center;">{{ $exercicio->repeticoes }}</td>
                                                    <td data-label="Peso" style="text-align: center;">{{ $exercicio->peso ? $exercicio->peso . ' kg' : '-' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if(isset($fichasAcademia))
                @foreach($fichasAcademia as $academiaId => $fichasDaAcademia)
                    @php
                        $academia = $fichasDaAcademia->first()->academia;
                        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                    @endphp

                    <div class="personal-section">
                        <div class="personal-header">
                            @if($academia && $academia->fotos && $academia->fotos->isNotEmpty())
                                <img src="{{ asset('storage/' . $academia->fotos->first()->path) }}" alt="{{ $academia->nome }}">
                            @else
                                <img src="https://cdn-icons-png.flaticon.com/512/2966/2966486.png" alt="{{ $academia->nome ?? 'Academia' }}">
                            @endif
                            <h2>{{ $academia->nome ?? 'Academia' }} <span style="font-size:0.7rem; color:var(--primary); text-transform:uppercase; letter-spacing:1px;">· Academia</span></h2>
                        </div>

                        <div class="fichas-grid">
                            @foreach($fichasDaAcademia as $ficha)
                                @php
                                    $concluido = $ficha->treino_de_hoje();
                                    $estaConcluido = $concluido && $concluido->concluido;
                                @endphp

                                <div class="ficha-card">
                                    <div class="ficha-header">
                                        <h3><i class="ph ph-calendar-dot"></i> {{ $dias[$ficha->dia_semana] }}</h3>
                                        <button class="btn-marcar {{ $estaConcluido ? 'concluido' : '' }}"
                                            onclick="marcarConcluido({{ $ficha->id }})">
                                            <i class="ph {{ $estaConcluido ? 'ph-check' : 'ph-square' }}"></i>
                                            {{ $estaConcluido ? 'CONCLUÍDO' : 'MARCAR' }}
                                        </button>
                                    </div>

                                    <p class="ficha-nome">{{ $ficha->nome_treino }}</p>

                                    @if($ficha->observacoes)
                                        <p class="ficha-obs">{{ $ficha->observacoes }}</p>
                                    @endif

                                    <div class="exercicios-box">
                                        <p class="exercicios-title"><i class="ph ph-list-bullets"></i> Exercícios</p>

                                        @if($ficha->exercicios->isEmpty())
                                            <p class="empty-ex">Nenhum exercício</p>
                                        @else
                                            <table class="exercicios-table resp-cards">
                                                <thead>
                                                    <tr>
                                                        <th>Exercício</th>
                                                        <th style="text-align: center;">Séries</th>
                                                        <th style="text-align: center;">Reps</th>
                                                        <th style="text-align: center;">Peso</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($ficha->exercicios as $exercicio)
                                                    <tr>
                                                        <td data-label="Exercício">
                                                            <div class="ex-nome ex-click" onclick="abrirExercicio({!! htmlspecialchars(json_encode($exercicio->nome_exercicio), ENT_QUOTES) !!}, {!! htmlspecialchars(json_encode($exercicio->observacoes), ENT_QUOTES) !!}, {!! htmlspecialchars(json_encode($exercicio->video ? asset('storage/' . $exercicio->video) : ''), ENT_QUOTES) !!})">
                                                                {{ $exercicio->nome_exercicio }}
                                                                <i class="ph ph-info ex-info"></i>
                                                            </div>
                                                        </td>
                                                        <td data-label="Séries" style="text-align: center;">{{ $exercicio->series }}</td>
                                                        <td data-label="Reps" style="text-align: center;">{{ $exercicio->repeticoes }}</td>
                                                        <td data-label="Peso" style="text-align: center;">{{ $exercicio->peso ? $exercicio->peso . ' kg' : '-' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        @endif
    </div>

    <!-- MODAL MARCAR COMO CONCLUÍDO -->
    <div id="modalMarcarConcluido" class="modal-overlay">
        <div class="modal-content">
            <h2><i class="ph ph-check-circle"></i> MARCAR TREINO COMO CONCLUÍDO</h2>

            <form id="formMarcarConcluido" method="POST">
                @csrf

                <div class="form-group">
                    <label>Data do Treino</label>
                    <input type="date" id="inputDataTreino" name="data_treino" value="{{ now()->format('Y-m-d') }}" required>
                </div>

                <div class="registros-wrap" id="registrosWrap">
                    <label><i class="ph ph-lightning"></i> Carga de hoje (ajuste se mudou)</label>
                    <div id="registrosContainer"></div>
                </div>

                <div class="form-group">
                    <label><i class="ph ph-smiley"></i> Como você se sentiu? (opcional)</label>
                    <div class="fb-emojis">
                        <label><input type="radio" name="sensacao" value="otimo"><span class="emo">😀</span><span class="cap">Ótimo</span></label>
                        <label><input type="radio" name="sensacao" value="bem"><span class="emo">🙂</span><span class="cap">Bem</span></label>
                        <label><input type="radio" name="sensacao" value="cansado"><span class="emo">😓</span><span class="cap">Cansado</span></label>
                        <label><input type="radio" name="sensacao" value="exausto"><span class="emo">🥵</span><span class="cap">Exausto</span></label>
                        <label><input type="radio" name="sensacao" value="dor"><span class="emo">🤕</span><span class="cap">Dor</span></label>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="ph ph-gauge"></i> Nível de esforço (opcional)</label>
                    <div class="rpe-chips">
                        @for($n = 1; $n <= 10; $n++)
                            <label><input type="radio" name="rpe" value="{{ $n }}">{{ $n }}</label>
                        @endfor
                    </div>
                    <div class="rpe-hint">1 = muito leve · 10 = máximo esforço</div>
                </div>

                <div class="form-group">
                    <label>Observações (Opcional)</label>
                    <textarea name="observacoes" placeholder="Como foi o treino? Alguma dificuldade?"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="fecharModalMarcarConcluido()">CANCELAR</button>
                    <button type="submit" class="btn-submit">CONCLUÍDO!</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Exercícios de cada ficha, para pré-preencher os campos de carga.
        const fichasExercicios = {!! json_encode($fichasExerciciosJs) !!};

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, s => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[s]));
        }

        function marcarConcluido(fichaId) {
            const form = document.getElementById('formMarcarConcluido');
            form.action = `/cliente/fichas-treino/${fichaId}/concluido`;

            // Monta os campos de carga por exercício, já preenchidos com a prescrição.
            const container = document.getElementById('registrosContainer');
            const wrap = document.getElementById('registrosWrap');
            const exercicios = fichasExercicios[fichaId] || [];
            container.innerHTML = '';

            if (exercicios.length === 0) {
                wrap.style.display = 'none';
            } else {
                wrap.style.display = 'block';
                exercicios.forEach(ex => {
                    const peso = ex.peso !== null && ex.peso !== undefined ? ex.peso : '';
                    const reps = ex.repeticoes ?? '';
                    const series = ex.series ?? '';
                    container.insertAdjacentHTML('beforeend', `
                        <div class="registro-row">
                            <div class="reg-nome">${escapeHtml(ex.nome)}</div>
                            <div class="registro-inputs">
                                <div class="reg-field">
                                    <span>Peso (kg)</span>
                                    <input type="number" step="0.5" min="0" name="registros[${ex.id}][peso]" value="${peso}">
                                </div>
                                <div class="reg-field">
                                    <span>Reps</span>
                                    <input type="number" min="0" name="registros[${ex.id}][repeticoes]" value="${reps}">
                                </div>
                                <div class="reg-field">
                                    <span>Séries</span>
                                    <input type="number" min="0" name="registros[${ex.id}][series]" value="${series}">
                                </div>
                            </div>
                        </div>
                    `);
                });
            }

            document.getElementById('modalMarcarConcluido').classList.add('active');
        }

        function fecharModalMarcarConcluido() {
            document.getElementById('modalMarcarConcluido').classList.remove('active');
            document.getElementById('formMarcarConcluido').reset();
            document.getElementById('registrosContainer').innerHTML = '';
        }

        window.addEventListener('click', (e) => {
            const modal = document.getElementById('modalMarcarConcluido');
            if (e.target === modal) {
                fecharModalMarcarConcluido();
            }
        });

        function abrirExercicio(nome, resumo, videoUrl) {
            document.getElementById('exModalNome').textContent = nome || 'Exercício';

            const video = document.getElementById('exModalVideo');
            if (videoUrl) {
                video.src = videoUrl;
                video.style.display = 'block';
            } else {
                video.pause(); video.src = '';
                video.style.display = 'none';
            }

            const resumoEl = document.getElementById('exModalResumo');
            resumoEl.textContent = resumo && resumo.trim() ? resumo : 'Sem observações para este exercício.';

            document.getElementById('exercicioModal').style.display = 'flex';
        }
        function fecharExercicio() {
            const video = document.getElementById('exModalVideo');
            video.pause(); video.src = '';
            document.getElementById('exercicioModal').style.display = 'none';
        }
    </script>

    <!-- MODAL EXERCÍCIO (vídeo + resumo) -->
    <div id="exercicioModal" onclick="if(event.target===this)fecharExercicio()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:99999; justify-content:center; align-items:center; padding:20px;">
        <div style="background:#16181d; border:1px solid rgba(255,255,255,0.1); border-radius:18px; max-width:520px; width:100%; padding:26px; position:relative; max-height:90vh; overflow-y:auto;">
            <button onclick="fecharExercicio()" style="position:absolute; top:14px; right:16px; background:none; border:none; color:#a0a0a0; font-size:1.3rem; cursor:pointer;">✕</button>
            <h2 id="exModalNome" style="color:#fff; font-size:1.2rem; font-weight:900; margin:0 32px 4px 0;"></h2>
            <video id="exModalVideo" controls playsinline style="display:none; width:100%; border-radius:12px; background:#000; margin:14px 0;"></video>
            <p style="color:#d4ff00; font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; font-weight:800; margin:16px 0 6px;"><i class="ph ph-info"></i> Resumo / Técnica</p>
            <p id="exModalResumo" style="color:#cfcfcf; font-size:0.9rem; line-height:1.6; white-space:pre-line; margin:0;"></p>
        </div>
    </div>

</body>

</html>
