<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ $personal->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">
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

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

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

        .menu-container {
            position: relative;
        }

        .dots-btn {
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
        }

        .dots-btn:hover {
            background: var(--primary);
            color: #000;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 50px;
            left: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 260px;
            z-index: 1000;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
        }

        .menu-rating-header {
            padding: 20px;
            background: rgba(212, 255, 0, 0.05);
            border-bottom: 1px solid var(--border);
            text-align: center;
        }

        .stars-row {
            color: var(--primary);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .rating-number {
            font-size: 1.5rem;
            font-weight: 900;
            display: block;
            color: #fff;
        }

        .rating-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dropdown-menu button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: #fff;
            font-size: 14px;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .dropdown-menu button:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--primary);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar-img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            object-fit: cover;
            background: #222;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .nav-link {
            color: var(--text-main);
            text-decoration: none;
            background: var(--card-bg);
            padding: 8px 15px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 0.8rem;
            transition: 0.3s;
        }

        .nav-link:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .agenda-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .calendar-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid var(--border);
        }

        .week-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            text-align: center;
        }

        .day-col {
            background: rgba(255, 255, 255, 0.02);
            padding: 15px 5px;
            border-radius: 12px;
            border: 1px solid var(--border);
            min-height: 100px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .day-col:hover {
            background: rgba(212, 255, 0, 0.05);
            border-color: rgba(212, 255, 0, 0.3);
        }

        .day-col.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #000;
        }

        .day-col.active .day-name,
        .day-col.active .day-number {
            color: #000;
        }

        .has-compromise::before {
            content: '';
            display: block;
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            position: absolute;
            bottom: 8px;
            box-shadow: 0 0 12px var(--primary);
            animation: pulse-yellow 2s infinite;
        }

        @keyframes pulse-yellow {
            0%, 100% {
                box-shadow: 0 0 12px var(--primary);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 20px var(--primary), inset 0 0 8px var(--primary);
                transform: scale(1.1);
            }
        }

        .agenda-notes {
            background: linear-gradient(135deg, #16181d 0%, #0a0b0d 100%);
            padding: 25px;
            border-radius: 24px;
            border: 1px solid var(--border);
            min-height: 400px;
        }

        .summary-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 15px;
            margin-top: 12px;
            border-left: 4px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-delete-agenda {
            background: rgba(255, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(255, 68, 68, 0.2);
            width: 35px;
            height: 35px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-delete-agenda:hover:not(:disabled) {
            background: var(--error);
            color: #fff;
        }

        .btn-delete-agenda:disabled {
            opacity: 0.2;
            cursor: not-allowed;
            filter: grayscale(1);
        }

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

        .modal-content {
            background: var(--card-bg);
            max-width: 750px;
            margin: auto;
            padding: 35px;
            border-radius: 28px;
            border: 1px solid var(--border);
        }

        .finance-card {
            background: rgba(212, 255, 0, 0.05);
            border: 1px solid var(--primary);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .finance-value {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 12px;
            margin-top: 5px;
        }

        .input-wrapper input,
        .input-wrapper select,
        .input-wrapper textarea {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px;
            color: #fff;
            outline: none;
            font-size: 0.9rem;
        }

        .input-wrapper i {
            color: var(--primary);
            width: 20px;
            text-align: center;
        }

        label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 800;
            margin-top: 12px;
            display: block;
            letter-spacing: 0.5px;
        }

        .btn-save {
            background: var(--primary);
            color: #000;
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            font-weight: 900;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.3s;
            margin-top: 20px;
        }

        .btn-save:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .alunos-list::-webkit-scrollbar {
            width: 6px;
        }

        .alunos-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        .alunos-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .avals-list::-webkit-scrollbar {
            width: 6px;
        }

        .avals-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        .avals-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .galeria-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .galeria-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .galeria-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .galeria-item-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            opacity: 0;
            transition: 0.2s;
        }

        .galeria-item:hover .galeria-item-overlay {
            opacity: 1;
        }

        .galeria-item-legenda {
            font-size: 0.7rem;
            color: #fff;
            text-align: center;
            padding: 0 8px;
        }

        .galeria-upload-slot {
            aspect-ratio: 1;
            border-radius: 12px;
            border: 1px dashed rgba(212, 255, 0, 0.4);
            background: rgba(212, 255, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            color: var(--primary);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            transition: 0.2s;
        }

        .galeria-upload-slot:hover {
            background: rgba(212, 255, 0, 0.1);
        }

        .galeria-upload-slot i {
            font-size: 1.2rem;
        }

        .nota-bar-bg {
            flex: 1;
            height: 6px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            overflow: hidden;
        }

        .nota-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        .nav-buttons-group {
            display: flex;
            gap: 10px;
        }

        .planos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .fichas-lista {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .ficha-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ficha-info {
            flex: 1;
        }

        .ficha-dia {
            color: var(--primary);
            font-weight: 900;
            font-size: 0.9rem;
        }

        .ficha-nome {
            color: #fff;
            font-weight: 700;
            margin-top: 5px;
        }

        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: 1px solid;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            margin-left: 10px;
        }

        .btn-view {
            background: rgba(212, 255, 0, 0.1);
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-view:hover {
            background: var(--primary);
            color: #000;
        }

        @media (max-width: 600px) {
            .planos-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="ed-page">

    <div class="top-bar">
        <div class="menu-container">
            <button class="dots-btn" id="btnMenu" onclick="toggleMenu()"><i class="ph ph-list"></i></button>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="menu-rating-header">
                    @php
                    $media = (float)($personal->media_avaliacao ?? 0);
                    $totalAvals = $personal->avaliacoes ? $personal->avaliacoes->count() : 0;
                    @endphp
                    <span class="rating-number">{{ number_format($media, 1) }}</span>
                    <div class="stars-row">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <=$media) <i class="ph-fill ph-star"></i>
                            @elseif ($i - 0.5 <= $media) <i class="ph-fill ph-star-half"></i>
                                @else <i class="ph ph-star"></i>
                                @endif
                                @endfor
                    </div>
                    <span class="rating-label">{{ $totalAvals }} Avaliações</span>
                </div>
                <button type="button" id="btnOpenUpdate"><i class="ph ph-user-gear"></i> Meu Perfil</button>
                <button type="button" id="btnOpenPlanos"><i class="ph ph-tag" style="color: var(--primary);"></i> Meus Pacotes</button>
                <a href="{{ route('personal.avaliacao-fisica.valores') }}" style="display:flex; align-items:center; gap:12px; padding:15px 20px; color:#fff; text-decoration:none; font-size:14px; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#d4ff00'" onmouseout="this.style.background='';this.style.color='#fff'">
                    <i class="ph ph-tag" style="color: var(--primary);"></i> Valores Avaliações
                </a>
                <button type="button" id="btnOpenFinance"><i class="ph ph-wallet" style="color: var(--success)"></i> Minhas Finanças</button>
                <button type="button" id="btnOpenCarteira"><i class="ph ph-piggy-bank" style="color: var(--primary)"></i> Minha Carteira</button>
                <button type="button" id="btnOpenGaleria"><i class="ph ph-images"></i> Minha Galeria</button>
                <button type="button" id="btnOpenAvaliacoes"><i class="ph-fill ph-star" style="color: var(--primary)"></i> Minhas Avaliações</button>
                <a href="{{ route('lgpd.meus-dados') }}" style="display:flex; align-items:center; gap:12px; padding:15px 20px; color:#fff; text-decoration:none; font-size:14px; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)';this.style.color='#d4ff00'" onmouseout="this.style.background='';this.style.color='#fff'">
                    <i class="ph ph-shield-check" style="color: var(--primary);"></i> Privacidade e meus dados
                </a>
                <form action="{{ route('login.logout') }}" method="POST"> @csrf <button type="submit" style="color: var(--error)"><i class="ph ph-power"></i> Sair</button></form>
            </div>
        </div>
        <a href="#" class="snr-logo" style="position:absolute; left:50%; transform:translateX(-50%);">SNR<span>FIT</span></a>
        <div class="profile-header">
            <img src="{{ $personal->foto ? asset('storage/' . $personal->foto) . '?t=' . time() : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" class="avatar-img">
            <span style="font-weight: 700; font-size: 0.9rem;">{{ $personal->nome }}</span>
        </div>
    </div>

    <div class="container">
        @if(session('success'))
        <div style="background: rgba(0, 255, 136, 0.1); color: var(--success); padding: 15px; border-radius: 12px; border: 1px solid var(--success); margin-bottom: 20px; font-size: 0.9rem;">
            <i class="ph ph-check-circle"></i> {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div style="background: rgba(255, 68, 68, 0.1); color: var(--error); padding: 15px; border-radius: 12px; border: 1px solid var(--error); margin-bottom: 20px; font-size: 0.9rem;">
            <i class="ph ph-warning-circle"></i> {{ session('error') }}
        </div>
        @endif

        {{-- ATALHOS RÁPIDOS (estilo iFood) --}}
        <style>
            .qa-scroll { display:flex; gap:14px; overflow-x:auto; padding:4px 2px 16px; margin-bottom:14px; scrollbar-width:none; }
            .qa-scroll::-webkit-scrollbar { display:none; }
            .qa-item { flex:0 0 auto; width:80px; display:flex; flex-direction:column; align-items:center; gap:8px; text-decoration:none; background:none; border:none; cursor:pointer; font-family:inherit; padding:0; }
            .qa-ico { width:58px; height:58px; border-radius:50%; background:#16181d; border:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:1.3rem; transition:0.2s; }
            .qa-item:hover .qa-ico { background:var(--primary); color:#000; transform:translateY(-2px); }
            .qa-lbl { font-size:0.66rem; color:#fff; text-align:center; line-height:1.2; font-weight:600; }
        </style>
        <div class="qa-scroll">
            <a href="{{ route('notificacoes.index') }}" class="qa-item">
                <span class="qa-ico" style="position:relative;">
                    <i class="ph ph-bell"></i>
                    <span data-notif-badge style="display:none; position:absolute; top:-4px; right:-4px; background:#ff3b30; color:#fff; font-size:0.6rem; font-weight:900; min-width:16px; height:16px; border-radius:8px; align-items:center; justify-content:center; padding:0 4px;">0</span>
                </span>
                <span class="qa-lbl">Avisos</span>
            </a>
            <a href="{{ route('chat.index') }}" class="qa-item"><span class="qa-ico"><i class="ph ph-chats"></i></span><span class="qa-lbl">Chat</span></a>
            <button type="button" class="qa-item" onclick="document.getElementById('modalAlunos').style.display='block'"><span class="qa-ico"><i class="ph ph-users"></i></span><span class="qa-lbl">Meus Alunos</span></button>
            <a href="{{ route('personal.frequencia') }}" class="qa-item"><span class="qa-ico"><i class="ph ph-user-check"></i></span><span class="qa-lbl">Frequência</span></a>
            <a href="{{ route('personal.avaliacao-fisica') }}" class="qa-item"><span class="qa-ico"><i class="ph ph-heartbeat"></i></span><span class="qa-lbl">Avaliação Física</span></a>
            <a href="{{ route('personal.solicitacoes-ficha') }}" class="qa-item"><span class="qa-ico"><i class="ph ph-clipboard-text"></i></span><span class="qa-lbl">Solicitações</span></a>
        </div>

        <div class="ed-eyebrow"><span class="ed-num">01</span> <span id="snrSaud">Olá</span>, {{ explode(' ', trim($personal->nome))[0] }} — bora pra cima</div>
        @include('partials.brand-greeting-js')
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; gap:16px; flex-wrap:wrap;">
            <h1 class="ed-h">Minha <span class="ed-mark">Agenda</span></h1>
            <button id="btnOpenBloqueio" class="btn-save" style="width: auto; padding: 12px 25px; margin: 0; font-size: 0.75rem;">
                <i class="ph ph-calendar-plus"></i> BLOQUEAR HORÁRIO
            </button>
        </header>

        <div class="calendar-nav">
            <a href="?data={{ $inicioSemana->copy()->subWeek()->format('Y-m-d') }}" class="nav-link"><i class="ph ph-caret-left"></i> Semana Anterior</a>
            <span style="font-weight: 800; font-size: 0.9rem; color: var(--primary);">{{ $inicioSemana->format('d/m') }} até {{ $inicioSemana->copy()->endOfWeek()->format('d/m') }}</span>
            <div class="nav-buttons-group">
                <a href="?data={{ now()->format('Y-m-d') }}" class="nav-link" title="Voltar para a semana atual">
                    <i class="ph ph-calendar-check"></i> Hoje
                </a>
                <a href="?data={{ $inicioSemana->copy()->addWeek()->format('Y-m-d') }}" class="nav-link">Próxima Semana <i class="ph ph-caret-right"></i></a>
            </div>
        </div>

        <div class="agenda-section">
            <div class="calendar-card">
                <div class="week-grid">
                    @php
                    $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                    @endphp

                    @for($i = 0; $i < 7; $i++)
                        @php
                        $dataLoop = $inicioSemana->copy()->addDays($i);
                        $dataStr = $dataLoop->format('Y-m-d');
                        $ehHoje = now()->format('Y-m-d') === $dataStr;
                        @endphp

                        <div class="day-col {{ $ehHoje ? 'active' : '' }}" onclick="selectDay('{{ $dataStr }}', this)">
                            <span class="day-name">{{ $diasSemana[$i] }}</span>
                            <span class="day-number">{{ $dataLoop->day }}</span>
                        </div>
                        @endfor
                </div>
            </div>

            <div class="agenda-notes">
                <h3 id="summary-title" style="color: var(--primary); font-size: 0.8rem; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="ph ph-list-bullets"></i> Compromissos do Dia
                </h3>
                <div id="summary-content"></div>
            </div>
        </div>
    </div>

    <input type="hidden" name="_token" value="{{ csrf_token() }}">

    {{-- MODAL DETALHES DO ALUNO --}}
    <div id="modalDetalhesAluno" class="modal-overlay" style="z-index: 2001;">
        <div class="modal-content" style="max-width: 500px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-user-check"></i> DETALHES DO ALUNO
            </h2>
            <div id="detalhesAlunoContent" style="display: flex; flex-direction: column; gap: 15px;">
                <p style="text-align: center; color: var(--text-muted);">Carregando...</p>
            </div>
            <button type="button" onclick="closeModalDetalhesAluno()" class="btn-save">Fechar</button>
        </div>
    </div>

    {{-- MODAL ALUNOS --}}
    <div id="modalAlunos" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-users"></i> MEUS ALUNOS
            </h2>
            <div class="alunos-list" style="display: flex; flex-direction: column; gap: 15px; max-height: 400px; overflow-y: auto; padding-right: 10px;">
                @php
                $meusAlunos = \App\Models\Agenda::with('cliente')
                ->where('personal_id', session('personal_id'))
                ->where('cancelado', false)
                ->where('tipo_aula', '!=', 'bloqueio')
                ->get()
                ->unique('cliente_id');
                @endphp
                @forelse($meusAlunos as $agendamento)
                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 15px; border-radius: 16px; display: flex; align-items: center; gap: 15px;">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($agendamento->cliente?->nome ?? 'Aluno') }}&background=d4ff00&color=000" style="width: 50px; height: 50px; border-radius: 50%;">
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 5px 0; font-size: 1.1rem; color: #fff;">{{ $agendamento->cliente?->nome ?? 'Aluno Sem Nome' }}</h3>
                        <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted);">
                            <i class="ph ph-target" style="color: var(--primary);"></i>
                            {{ $agendamento->cliente?->resumo_objetivo ?? 'Objetivo não informado' }}
                        </p>
                    </div>
                    <button type="button" onclick="abrirDetalhesAluno({{ $agendamento->cliente?->id }})" style="background: rgba(212,255,0,0.15); color: var(--primary); border: 1px solid rgba(212,255,0,0.4); width: 40px; height: 40px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; font-weight: 900;" title="Ver informações do aluno">
                        <i class="ph ph-user-circle"></i>
                    </button>
                    <a href="{{ route('fichas-treino.aluno', $agendamento->cliente?->id) }}" style="background: rgba(212,255,0,0.15); color: var(--primary); border: 1px solid rgba(212,255,0,0.4); width: 40px; height: 40px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; font-weight: 900; text-decoration: none;" title="Visualizar fichas">
                        <i class="ph ph-eye"></i>
                    </a>
                    <button type="button"
                        data-cliente-id="{{ $agendamento->cliente?->id }}"
                        data-cliente-nome="{{ $agendamento->cliente?->nome }}"
                        onclick="abrirModalCriarFichaAluno(this.dataset.clienteId, this.dataset.clienteNome)"
                        style="background: rgba(212,255,0,0.2); color: var(--primary); border: 1px solid var(--primary); width: 40px; height: 40px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; font-weight: 900;" title="Criar ficha de treino">
                        <i class="ph ph-barbell"></i>
                    </button>
                </div>
                @empty
                <div style="text-align: center; padding: 40px 20px; opacity: 0.5;">
                    <i class="ph ph-user-minus" style="font-size: 3rem; margin-bottom: 15px; color: var(--text-muted);"></i>
                    <p style="color: var(--text-muted);">Você ainda não possui alunos vinculados.</p>
                </div>
                @endforelse
            </div>
            <button type="button" onclick="closeModalAlunos()" class="btn-save">Fechar Relatório</button>
        </div>
    </div>


    <div id="modalCriarFichaAluno" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">
                <i class="ph ph-plus"></i> CRIAR FICHA DE TREINO
            </h2>
            <p id="nomeAlunoModalFicha" style="color: var(--text-muted); margin-bottom: 20px;"></p>
            
            <form action="{{ route('fichas-treino.criar') }}" method="POST">
                @csrf
                <input type="hidden" id="clienteIdModalFicha" name="cliente_id" value="">

                <label>Dia da Semana</label>
                <div class="input-wrapper" style="padding: 0;">
                    <select name="dia_semana" required style="flex: 1; background: transparent; border: none; padding: 12px; color: #fff; outline: none; font-size: 0.9rem;">
                        <option value="">Selecione um dia</option>
                        <option value="0">Domingo</option>
                        <option value="1">Segunda</option>
                        <option value="2">Terça</option>
                        <option value="3">Quarta</option>
                        <option value="4">Quinta</option>
                        <option value="5">Sexta</option>
                        <option value="6">Sábado</option>
                    </select>
                </div>

                <label>Nome do Treino</label>
                <div class="input-wrapper"><i class="ph ph-barbell"></i><input type="text" name="nome_treino" placeholder="Ex: Peito e Costas" required></div>

                <label>Observações</label>
                <div class="input-wrapper" style="padding: 12px; min-height: 80px;">
                    <textarea name="observacoes" placeholder="Aquecimento, alongamento, etc" style="flex: 1; background: transparent; border: none; color: #fff; outline: none; font-size: 0.9rem; resize: vertical; min-height: 60px;"></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="fecharModalCriarFichaAluno()" class="btn-save btn-cancel" style="flex: 1; margin-top: 0;">CANCELAR</button>
                    <button type="submit" class="btn-save" style="flex: 1; margin-top: 0;">CRIAR FICHA</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL FINANCEIRO --}}
    <div id="modalFinance" class="modal-overlay">
        <div class="modal-content" style="max-width: 550px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900; text-align: center;">FINANCEIRO</h2>
            
            @php
            // ✅ RESULTADO JÁ VEM DO CONTROLLER
            // Não precisa fazer nada aqui, $resultado já está disponível
            @endphp

            {{-- Total --}}
            <div class="finance-card">
                <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Total do Mês Atual</span>
                <span class="finance-value">R$ {{ number_format($resultado['total'] ?? 0, 2, ',', '.') }}</span>
            </div>

            {{-- Pacotes --}}
            <div class="finance-card" style="background: rgba(212, 255, 0, 0.08); border: 1px solid rgba(212, 255, 0, 0.3);">
                <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">
                    <i class="ph ph-barbell"></i> Pacotes Contratados
                </span>
                <span class="finance-value">R$ {{ number_format($resultado['pacotes'] ?? 0, 2, ',', '.') }}</span>
                <small style="color: var(--text-muted); font-size: 0.7rem; margin-top: 8px; display: block;">
                    {{ $resultado['detalhes']['quantidade_aulas_pacote'] ?? 0 }} aula(s) de pacote
                </small>
            </div>

            {{-- Aulas Avulsas --}}
            <div class="finance-card" style="background: rgba(0, 255, 136, 0.08); border: 1px solid rgba(0, 255, 136, 0.3);">
                <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">
                    <i class="ph-fill ph-star"></i> Aulas Avulsas
                </span>
                <span class="finance-value" style="color: var(--success);">R$ {{ number_format($resultado['avulsas'] ?? 0, 2, ',', '.') }}</span>
                <small style="color: var(--text-muted); font-size: 0.7rem; margin-top: 8px; display: block;">
                    {{ $resultado['detalhes']['quantidade_aulas_avulsa'] ?? 0 }} aula(s) × R$ {{ number_format($resultado['detalhes']['valor_secao'] ?? 0, 2, ',', '.') }}/hora
                </small>
            </div>

            {{-- Resumo --}}
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 16px; padding: 15px; margin-bottom: 20px; text-align: center;">
                <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Resumo do Mês</p>
                <p style="margin: 0; font-size: 0.9rem; color: #fff;">
                    <span style="color: var(--primary); font-weight: 900;">{{ ($resultado['detalhes']['quantidade_aulas_pacote'] ?? 0) + ($resultado['detalhes']['quantidade_aulas_avulsa'] ?? 0) }}</span> aula(s) total
                </p>
            </div>

            <button type="button" onclick="closeModalFinance()" class="btn-save">Fechar Relatório</button>
        </div>
    </div>

    {{-- MODAL CARTEIRA ASAAS --}}
    <div id="modalCarteira" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="max-width:480px;">
            <h2 style="color:var(--primary); font-size:1.4rem; margin-top:0; font-weight:900; text-align:center;">
                <i class="ph ph-piggy-bank"></i> MINHA CARTEIRA
            </h2>

            <div id="carteiraLoading" style="text-align:center; padding:30px 0; color:#a0a0a0;">
                <i class="ph ph-spinner ph-spin fa-2x"></i><br><br>Consultando saldo...
            </div>

            <div id="carteiraConteudo" style="display:none;">
                <div style="background:rgba(212,255,0,0.07); border:1px solid rgba(212,255,0,0.3); border-radius:16px; padding:24px; text-align:center; margin-bottom:20px;">
                    <p style="margin:0; color:#a0a0a0; font-size:0.75rem; text-transform:uppercase; margin-bottom:8px;">Saldo disponível</p>
                    <p id="carteiraValor" style="margin:0; color:#fff; font-size:2rem; font-weight:900;">R$ 0,00</p>
                </div>

                <div id="carteiraSemPix" style="display:none; background:rgba(255,165,0,0.1); border:1px solid rgba(255,165,0,0.3); border-radius:12px; padding:14px; margin-bottom:16px; font-size:0.82rem; color:#ffa500; text-align:center;">
                    <i class="ph ph-warning"></i> Cadastre sua chave PIX no perfil para poder sacar.
                </div>

                <div id="carteiraSaqueForm">
                    <label style="color:#a0a0a0; font-size:0.75rem; display:block; margin-bottom:6px;">Valor a sacar (R$)</label>
                    <div style="display:flex; gap:10px; margin-bottom:16px;">
                        <input id="carteiraValorSaque" type="number" min="0.01" step="0.01" placeholder="0,00"
                            style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:11px 14px; color:#fff; font-size:1rem; outline:none;">
                        <button onclick="sacarTudo()" style="background:rgba(255,255,255,0.07); color:#fff; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:10px 14px; font-size:0.8rem; cursor:pointer; white-space:nowrap;">
                            Tudo
                        </button>
                    </div>

                    <p id="carteiraSaqueErro" style="display:none; color:#ff6b6b; font-size:0.82rem; background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); border-radius:8px; padding:10px 14px; margin-bottom:14px;"></p>
                    <p id="carteiraSaqueSucesso" style="display:none; color:#4caf50; font-weight:700; text-align:center; padding:8px 0; font-size:0.9rem;">✅ Saque solicitado! O valor chegará via PIX em instantes.</p>

                    <button id="btnSacar" onclick="confirmarSaque()"
                        style="width:100%; background:var(--primary); color:#000; border:none; border-radius:12px; padding:14px; font-weight:900; font-size:0.95rem; cursor:pointer;">
                        <i class="ph ph-arrow-down"></i> Sacar via PIX
                    </button>
                </div>

                <p style="color:#a0a0a0; font-size:0.7rem; text-align:center; margin-top:14px; margin-bottom:0;">
                    O valor é transferido direto para a chave PIX cadastrada no seu perfil.
                </p>
            </div>

            <div id="carteiraSemConta" style="display:none; text-align:center; padding:20px 0; color:#a0a0a0; font-size:0.85rem;">
                <i class="ph ph-info fa-2x" style="margin-bottom:12px; display:block;"></i>
                Sua conta de repasse ainda está sendo configurada.<br>
                Os saques estarão disponíveis em breve.
            </div>

            <button type="button" onclick="fecharCarteira()" class="btn-save" style="margin-top:16px;">Fechar</button>
        </div>
    </div>

    {{-- MODAL ATUALIZAR PERFIL --}}
    <div id="modalUpdate" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">ATUALIZAR MEUS DADOS</h2>
            <form action="{{ route('personal.update', $personal->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <input type="hidden" name="cep" value="{{ $personal->cep }}">
                <input type="hidden" name="cidade" value="{{ $personal->cidade }}">
                <input type="hidden" name="aceita_termos_update" value="1">
                <div class="form-grid">
                    <div class="full-width">
                        <label>Foto de Perfil</label>
                        <div class="input-wrapper"><i class="ph ph-camera"></i><input type="file" name="foto" accept="image/*"></div>
                    </div>
                    <div>
                        <label>Nome Completo</label>
                        <div class="input-wrapper"><i class="ph ph-user"></i><input type="text" name="nome" value="{{ $personal->nome }}" required></div>
                    </div>
                    <div>
                        <label>CREF (não editável)</label>
                        <div class="input-wrapper" style="opacity: 0.7;"><i class="ph ph-identification-badge"></i><input type="text" value="{{ $personal->cref ?? 'Não informado' }}" disabled></div>
                    </div>
                    <div>
                        <label>Valor por Seção (R$)</label>
                        <div class="input-wrapper"><i class="ph ph-currency-dollar"></i><input type="number" step="0.01" name="valor_secao" value="{{ $personal->valor_secao }}"></div>
                    </div>
                    <div>
                        <label>Valor da Ficha Personalizada (R$)</label>
                        <div class="input-wrapper"><i class="ph ph-clipboard-text"></i><input type="number" step="0.01" name="valor_ficha" value="{{ $personal->valor_ficha }}" placeholder="0.00"></div>
                    </div>
                    <div class="full-width">
                        <label>Chave Pix</label>
                        <div class="input-wrapper"><i class="ph ph-key"></i><input type="text" name="chave_pix" value="{{ $personal->chave_pix }}" placeholder="CPF, e-mail, telefone ou chave aleatória"></div>
                    </div>
                    <div class="full-width">
                        <label>Academias em que atua</label>
                        <p style="color: var(--text-muted); font-size: 0.73rem; margin: 3px 0 8px;">
                            <i class="ph ph-info"></i> Uma por linha. Não precisa ser uma academia cadastrada na plataforma.
                        </p>
                        <div class="input-wrapper" style="height: auto; align-items: flex-start; padding: 12px 15px;">
                            <i class="ph ph-barbell" style="margin-top: 2px; flex-shrink: 0;"></i>
                            <textarea name="academias" rows="4"
                                      placeholder="Academia XYZ&#10;Smart Fitness&#10;Strong Gym&#10;..."
                                      style="background: transparent; border: none; color: #fff; width: 100%; outline: none; resize: vertical; font-size: 0.9rem; line-height: 1.6;">{{ $personal->academias }}</textarea>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModalUpdate()" class="btn-save btn-cancel">Cancelar</button>
                    <button type="submit" class="btn-save">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDITAR PLANOS --}}
    <div id="modalPlanos" class="modal-overlay">
        <div class="modal-content" style="max-width: 650px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-tag"></i> MEUS PACOTES
            </h2>

            <form action="{{ route('pacotes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="personal_id" value="{{ session('personal_id') ?? $personal->id }}">

                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">
                    <i class="ph ph-info"></i> Defina os valores mensais para cada frequência de treino. Deixe em branco ou 0 para não oferecer essa opção.
                </p>

                <div class="planos-grid">
                    @php
                    $personalId = session('personal_id') ?? $personal->id;
                    $precosSalvos = \App\Models\Cadastro\Pacote::where('personal_id', $personalId)
                    ->pluck('valor_mensal', 'frequencia')
                    ->toArray();
                    @endphp
                    @foreach([1, 2, 3, 4, 5, 6, 7] as $vezes)
                    <div>
                        <label>{{ $vezes }}x na Semana</label>
                        <div class="input-wrapper">
                            <i class="ph ph-barbell"></i>
                            <input type="number" step="0.01" name="precos[{{ $vezes }}]"
                                value="{{ $precosSalvos[$vezes] ?? '' }}" placeholder="R$ 0,00">
                        </div>
                    </div>
                    @endforeach
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="button" onclick="closeModalPlanos()" class="btn-save btn-cancel" style="flex: 1;">Cancelar</button>
                    <button type="submit" class="btn-save" style="flex: 1;">Salvar Planos</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL BLOQUEAR HORÁRIO --}}
    <div id="modalHorario" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">BLOQUEAR HORÁRIO</h2>
            <form action="{{ route('personal.storeHorario') }}" method="POST">
                @csrf
                <label>Data</label>
                <div class="input-wrapper"><i class="ph ph-calendar"></i><input type="date" name="data" id="modal_data" required></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div><label>Início</label>
                        <div class="input-wrapper"><i class="ph ph-clock"></i><input type="time" name="hora_inicio" required></div>
                    </div>
                    <div><label>Fim</label>
                        <div class="input-wrapper"><i class="ph ph-clock"></i><input type="time" name="hora_fim" required></div>
                    </div>
                </div>
                <label>Motivo</label>
                <div class="input-wrapper"><i class="ph ph-pen"></i><input type="text" name="descricao" placeholder="Ex: Almoço / Ocupado"></div>
                <button type="submit" class="btn-save">Confirmar Bloqueio</button>
                <button type="button" onclick="closeModalHorario()" class="btn-save btn-cancel">Voltar</button>
            </form>
        </div>
    </div>

    {{-- MODAL GALERIA --}}
    <div id="modalGaleria" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">
                <i class="ph ph-images"></i> MINHA GALERIA
            </h2>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 15px;">
                Fotos visíveis para clientes no seu perfil. Máximo 9 fotos.
                <span id="galeriaContador" style="float: right; color: var(--primary); font-weight: 700;">
                    {{ $personal->fotos->count() }}/9
                </span>
            </p>

            {{-- Mensagem de feedback --}}
            <div id="galeriaMsg" style="display:none; padding: 10px 14px; border-radius: 10px; margin-bottom: 12px; font-size: 0.85rem;"></div>

            {{-- Grid de fotos --}}
            <div class="galeria-grid" id="galeriaGrid">
                @foreach($personal->fotos as $foto)
                <div class="galeria-item" id="foto-{{ $foto->id }}">
                    <img src="{{ asset('storage/' . $foto->path) }}" alt="{{ $foto->legenda }}">
                    <div class="galeria-item-overlay">
                        @if($foto->legenda)
                        <span class="galeria-item-legenda">{{ $foto->legenda }}</span>
                        @endif
                        <button type="button"
                            onclick="removerFoto({{ $foto->id }}, this)"
                            style="background: var(--error); color: #fff; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 0.75rem;">
                            <i class="ph ph-trash"></i> Remover
                        </button>
                    </div>
                </div>
                @endforeach

                {{-- Slot de upload --}}
                <div class="galeria-upload-slot" id="galeriaSlot" onclick="document.getElementById('inputFotoGaleria').click()"
                     style="{{ $personal->fotos->count() >= 9 ? 'display:none' : '' }}">
                    <i class="ph ph-plus-circle"></i>
                    <span id="galeriaSlotLabel">Adicionar</span>
                </div>
            </div>

            {{-- Inputs ocultos --}}
            <input type="file" id="inputFotoGaleria" name="foto" accept="image/*" style="display:none;" onchange="uploadFoto(this)">

            {{-- Campo de legenda --}}
            <div id="galeriaLegendaArea" style="margin-top: 15px; {{ $personal->fotos->count() >= 9 ? 'display:none' : '' }}">
                <label>Legenda (opcional — preencha antes de escolher a foto)</label>
                <div class="input-wrapper">
                    <i class="ph ph-pen"></i>
                    <input type="text" id="galeriaLegenda" placeholder="Ex: Treino funcional, Avaliação...">
                </div>
            </div>

            <button type="button" onclick="closeModalGaleria()" class="btn-save" style="margin-top: 15px;">Fechar</button>
        </div>
    </div>

    {{-- MODAL AVALIAÇÕES --}}
    <div id="modalAvaliacoes" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                <i class="ph-fill ph-star"></i> MINHAS AVALIAÇÕES
            </h2>

            @php
            $avaliacoes = $personal->avaliacoes()->with('cliente')->latest()->get();
            $mediaAval = $avaliacoes->avg('nota') ?? 0;
            $totalAval = $avaliacoes->count();
            $contNotas = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
            foreach ($avaliacoes as $av) {
            $contNotas[(int)$av->nota] = ($contNotas[(int)$av->nota] ?? 0) + 1;
            }
            @endphp

            <div style="display: grid; grid-template-columns: auto 1fr; gap: 25px; align-items: center; background: rgba(212,255,0,0.05); border: 1px solid rgba(212,255,0,0.2); border-radius: 16px; padding: 20px; margin-bottom: 20px;">

                <div style="text-align: center; min-width: 90px;">
                    <span style="font-size: 3rem; font-weight: 900; color: var(--primary); line-height: 1;">{{ number_format($mediaAval, 1) }}</span>
                    <div style="color: var(--primary); font-size: 0.8rem; margin: 6px 0 4px;">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <=$mediaAval) <i class="ph-fill ph-star"></i>
                            @elseif ($i - 0.5 <= $mediaAval) <i class="ph-fill ph-star-half"></i>
                                @else <i class="ph ph-star" style="opacity:0.3"></i>
                                @endif
                                @endfor
                    </div>
                    <span style="font-size: 0.7rem; color: var(--text-muted);">{{ $totalAval }} avaliação(ões)</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    @foreach ([5,4,3,2,1] as $estrela)
                    @php
                    $qtd = $contNotas[$estrela] ?? 0;
                    $pct = $totalAval > 0 ? ($qtd / $totalAval) * 100 : 0;
                    @endphp
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.75rem;">
                        <span style="color: var(--text-muted); width: 10px; text-align: right;">{{ $estrela }}</span>
                        <i class="ph-fill ph-star" style="color: var(--primary); font-size: 0.65rem;"></i>
                        <div class="nota-bar-bg">
                            <div class="nota-bar-fill" style="width: {{ $pct }}%;"></div>
                        </div>
                        <span style="color: var(--text-muted); width: 20px;">{{ $qtd }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="avals-list" style="display: flex; flex-direction: column; gap: 12px; max-height: 380px; overflow-y: auto; padding-right: 8px;">
                @forelse($avaliacoes as $avaliacao)
                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 15px; border-radius: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($avaliacao->cliente?->nome ?? 'Cliente') }}&background=d4ff00&color=000"
                                style="width: 38px; height: 38px; border-radius: 50%; flex-shrink:0;">
                            <span style="font-weight: 700; font-size: 0.9rem;">
                                {{ $avaliacao->cliente?->nome ?? 'Cliente' }}
                            </span>
                        </div>
                        <div style="color: var(--primary); font-size: 0.8rem; flex-shrink:0;">
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <=$avaliacao->nota) <i class="ph-fill ph-star"></i>
                                @else <i class="ph ph-star" style="opacity: 0.25"></i>
                                @endif
                                @endfor
                        </div>
                    </div>

                    @if($avaliacao->comentario)
                    <p style="margin: 0 0 8px 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; border-left: 3px solid var(--primary); padding-left: 10px;">
                        "{{ $avaliacao->comentario }}"
                    </p>
                    @endif

                    <small style="color: rgba(255,255,255,0.2); font-size: 0.7rem;">
                        <i class="ph ph-clock"></i>
                        {{ \Carbon\Carbon::parse($avaliacao->created_at)->format('d/m/Y') }}
                    </small>
                </div>
                @empty
                <div style="text-align: center; padding: 50px 20px; opacity: 0.4;">
                    <i class="ph-fill ph-star" style="font-size: 3rem; margin-bottom: 15px; color: var(--text-muted); display: block;"></i>
                    <p style="color: var(--text-muted); margin: 0;">Você ainda não recebeu avaliações.</p>
                </div>
                @endforelse
            </div>

            <button type="button" onclick="closeModalAvaliacoes()" class="btn-save" style="margin-top: 20px;">Fechar</button>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const m = document.getElementById('dropdownMenu');
            m.style.display = m.style.display === 'block' ? 'none' : 'block';
        }

        const modalUpdate = document.getElementById('modalUpdate');
        const modalPlanos = document.getElementById('modalPlanos');
        const modalHorario = document.getElementById('modalHorario');
        const modalFinance = document.getElementById('modalFinance');
        const modalAlunos = document.getElementById('modalAlunos');
        const modalGaleria = document.getElementById('modalGaleria');
        const modalAvaliacoes = document.getElementById('modalAvaliacoes');
        const modalDetalhesAluno = document.getElementById('modalDetalhesAluno');
        const modalCriarFichaAluno = document.getElementById('modalCriarFichaAluno');

        document.getElementById('btnOpenUpdate').onclick = () => {
            modalUpdate.style.display = 'block';
            toggleMenu();
        };
        document.getElementById('btnOpenPlanos').onclick = () => {
            modalPlanos.style.display = 'block';
            toggleMenu();
        };
        document.getElementById('btnOpenFinance').onclick = () => {
            modalFinance.style.display = 'block';
            toggleMenu();
        };
        document.getElementById('btnOpenCarteira').onclick = () => {
            abrirCarteira();
            toggleMenu();
        };
        document.getElementById('btnOpenGaleria').onclick = () => {
            modalGaleria.style.display = 'block';
            toggleMenu();
        };
        document.getElementById('btnOpenAvaliacoes').onclick = () => {
            modalAvaliacoes.style.display = 'block';
            toggleMenu();
        };

        document.getElementById('btnOpenBloqueio').onclick = () => {
            document.getElementById('modal_data').value = new Date().toISOString().split('T')[0];
            modalHorario.style.display = 'block';
        };

        function closeModalUpdate() {
            modalUpdate.style.display = 'none';
        }

        function closeModalPlanos() {
            modalPlanos.style.display = 'none';
        }

        function closeModalHorario() {
            modalHorario.style.display = 'none';
        }

        function closeModalFinance() {
            modalFinance.style.display = 'none';
        }

        // ── CARTEIRA ASAAS ──────────────────────────────
        let carteiraSaldoAtual = 0;

        async function abrirCarteira() {
            const modal = document.getElementById('modalCarteira');
            modal.style.display = 'block';
            document.getElementById('carteiraLoading').style.display = 'block';
            document.getElementById('carteiraConteudo').style.display = 'none';
            document.getElementById('carteiraSemConta').style.display = 'none';
            document.getElementById('carteiraSaqueErro').style.display = 'none';
            document.getElementById('carteiraSaqueSucesso').style.display = 'none';

            try {
                const res  = await fetch('/api/personal/saldo', {
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();

                document.getElementById('carteiraLoading').style.display = 'none';

                if (data.sem_conta) {
                    document.getElementById('carteiraSemConta').style.display = 'block';
                    return;
                }

                carteiraSaldoAtual = parseFloat(data.saldo) || 0;
                document.getElementById('carteiraValor').textContent = 'R$ ' + carteiraSaldoAtual.toFixed(2).replace('.', ',');
                document.getElementById('carteiraValorSaque').value = carteiraSaldoAtual > 0 ? carteiraSaldoAtual.toFixed(2) : '';
                document.getElementById('carteiraSemPix').style.display = data.tem_pix ? 'none' : 'block';
                document.getElementById('carteiraSaqueForm').style.display = data.tem_pix ? 'block' : 'none';
                document.getElementById('carteiraConteudo').style.display = 'block';
            } catch (_) {
                document.getElementById('carteiraLoading').style.display = 'none';
                document.getElementById('carteiraSemConta').style.display = 'block';
            }
        }

        function fecharCarteira() {
            document.getElementById('modalCarteira').style.display = 'none';
        }

        function sacarTudo() {
            document.getElementById('carteiraValorSaque').value = carteiraSaldoAtual > 0 ? carteiraSaldoAtual.toFixed(2) : '';
        }

        async function confirmarSaque() {
            const valor = parseFloat(document.getElementById('carteiraValorSaque').value);
            if (!valor || valor <= 0) {
                document.getElementById('carteiraSaqueErro').textContent = 'Informe um valor válido.';
                document.getElementById('carteiraSaqueErro').style.display = 'block';
                return;
            }

            const btn = document.getElementById('btnSacar');
            btn.disabled = true;
            btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Processando...';
            document.getElementById('carteiraSaqueErro').style.display = 'none';

            try {
                const res  = await fetch('/api/personal/sacar', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body:    JSON.stringify({ valor }),
                });
                const data = await res.json();

                if (!res.ok) throw new Error(data.error || 'Erro ao processar saque.');

                document.getElementById('carteiraSaqueSucesso').style.display = 'block';
                document.getElementById('carteiraSaqueForm').style.display = 'none';
                carteiraSaldoAtual = 0;
                document.getElementById('carteiraValor').textContent = 'R$ 0,00';
            } catch (err) {
                document.getElementById('carteiraSaqueErro').textContent = err.message;
                document.getElementById('carteiraSaqueErro').style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-arrow-down"></i> Sacar via PIX';
            }
        }

        function closeModalAlunos() {
            modalAlunos.style.display = 'none';
        }

        function closeModalGaleria() {
            modalGaleria.style.display = 'none';
        }

        function closeModalAvaliacoes() {
            modalAvaliacoes.style.display = 'none';
        }

        function closeModalDetalhesAluno() {
            modalDetalhesAluno.style.display = 'none';
        }

        // ✅ CRIAR FICHA
        function abrirModalCriarFichaAluno(clienteId, nomeAluno) {
            document.getElementById('clienteIdModalFicha').value = clienteId;
            const p = document.getElementById('nomeAlunoModalFicha');
            p.textContent = 'Criando ficha para: ';
            const strong = document.createElement('strong');
            strong.style.color = 'var(--primary)';
            strong.textContent = nomeAluno;
            p.appendChild(strong);
            modalCriarFichaAluno.style.display = 'block';
        }

        function fecharModalCriarFichaAluno() {
            modalCriarFichaAluno.style.display = 'none';
        }

        function escHtml(str) {
            const d = document.createElement('div');
            d.textContent = String(str ?? '');
            return d.innerHTML;
        }

        function abrirDetalhesAluno(clienteId) {
            const content = document.getElementById('detalhesAlunoContent');
            content.innerHTML = '<p style="text-align: center; color: var(--text-muted);">Carregando...</p>';
            modalDetalhesAluno.style.display = 'block';

            fetch(`/cliente/${clienteId}/detalhes`)
                .then(r => {
                    if (!r.ok) throw new Error(`Erro: ${r.status}`);
                    return r.json();
                })
                .then(dados => {
                    if (dados.error) {
                        content.innerHTML = '<p style="text-align: center; color: var(--error);">Erro ao carregar dados.</p>';
                        return;
                    }

                    if (dados) {
                        const dataNascimento = new Date(dados.idade);
                        const hoje = new Date();
                        let idade = hoje.getFullYear() - dataNascimento.getFullYear();
                        const mes = hoje.getMonth() - dataNascimento.getMonth();
                        if (mes < 0 || (mes === 0 && hoje.getDate() < dataNascimento.getDate())) {
                            idade--;
                        }

                        const pacoteInfo = dados.pacote
                            ? `${escHtml(dados.pacote.frequencia)}x por semana - R$ ${escHtml(dados.pacote.valor_mensal)}`
                            : 'Sem pacote ativo';

                        content.innerHTML = `
                            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 16px; padding: 20px; text-align: center;">
                                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(dados.nome)}&background=d4ff00&color=000"
                                    style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 15px;">
                                <h3 style="color: var(--primary); font-size: 1.3rem; margin: 0 0 20px 0;">${escHtml(dados.nome)}</h3>
                            </div>

                            <div style="background: rgba(212,255,0,0.05); border: 1px solid var(--primary); border-radius: 16px; padding: 15px;">
                                <p style="margin: 0 0 12px 0; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="ph ph-cake" style="color: var(--primary);"></i> Idade
                                </p>
                                <p style="margin: 0 0 15px 0; font-size: 1.4rem; color: var(--primary); font-weight: 900;">
                                    ${escHtml(idade)} anos
                                </p>
                            </div>

                            <div style="background: rgba(212,255,0,0.05); border: 1px solid var(--primary); border-radius: 16px; padding: 15px; margin-top: 12px;">
                                <p style="margin: 0 0 12px 0; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="ph ph-heartbeat" style="color: var(--primary);"></i> Condição Clínica
                                </p>
                                <p style="margin: 0 0 15px 0; font-size: 0.95rem; color: #fff; line-height: 1.6;">
                                    ${escHtml(dados.condicao_clinica || 'Nenhuma condição registrada')}
                                </p>
                            </div>

                            <div style="background: rgba(212,255,0,0.05); border: 1px solid var(--primary); border-radius: 16px; padding: 15px; margin-top: 12px;">
                                <p style="margin: 0 0 12px 0; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="ph ph-barbell" style="color: var(--primary);"></i> Pacote Contratado
                                </p>
                                <p style="margin: 0 0 15px 0; font-size: 1.1rem; color: var(--primary); font-weight: 900;">
                                    ${pacoteInfo}
                                </p>
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<p style="text-align: center; color: var(--error);">Dados inválidos</p>';
                    }
                })
                .catch(err => {
                    console.error('Erro:', err);
                    content.innerHTML = `<p style="text-align: center; color: var(--error);">Erro ao buscar informações: ${err.message}</p>`;
                });
        }

        function cancelarComJustificativa(id) {
            const justificativa = prompt("Por favor, digite a justificativa para o cancelamento (mínimo 10 caracteres):");

            if (!justificativa) {
                console.log('Cancelamento abortado pelo usuário');
                return;
            }

            if (justificativa.length < 10) {
                alert("A justificativa deve ter pelo menos 10 caracteres.");
                return;
            }

            const token = document.querySelector('input[name="_token"]')?.value;

            if (!token) {
                alert("Token CSRF não encontrado!");
                console.error('Token CSRF não encontrado');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/agenda/cancelar/${id}`;
            form.enctype = 'application/x-www-form-urlencoded';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = token;

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PUT';

            const justificativaInput = document.createElement('input');
            justificativaInput.type = 'hidden';
            justificativaInput.name = 'justificativa';
            justificativaInput.value = justificativa;

            form.appendChild(tokenInput);
            form.appendChild(methodInput);
            form.appendChild(justificativaInput);
            form.style.display = 'none';

            console.log('Enviando cancelamento para:', form.action);
            console.log('ID:', id);
            console.log('Justificativa:', justificativa);

            document.body.appendChild(form);
            form.submit();
        }

        function selectDay(dateStr, element) {
            document.querySelectorAll('.day-col').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            const content = document.getElementById('summary-content');
            const parts = dateStr.split('-');
            document.getElementById('summary-title').innerHTML = `<i class="ph ph-calendar-dot"></i> AGENDA (${parts[2]}/${parts[1]})`;
            content.innerHTML = `<p style="text-align:center; color:var(--text-muted); padding:30px; font-size:0.8rem;">Buscando...</p>`;

            fetch(`/personal/agenda/${dateStr}`)
                .then(r => r.json())
                .then(dados => {
                    content.innerHTML = "";
                    if (!dados || dados.length === 0) {
                        content.innerHTML = `<div style="text-align:center; padding-top:40px; opacity:0.3;"><i class="ph ph-coffee" style="font-size:2rem;"></i><p>Nada para hoje! Horário livre.</p></div>`;
                        return;
                    }
                    dados.forEach(item => {
                        const agora = new Date();
                        const [ano, mes, dia] = item.data.split('-');
                        const [hora, minuto] = item.hora_inicio.split(':');
                        const dataAula = new Date(Date.UTC(ano, mes - 1, dia, hora, minuto, 0));
                        const diffHoras = (dataAula - agora) / (1000 * 60 * 60);
                        const podeCancelar = diffHoras >= 24;

                        let nomeExibicao = "";
                        let icone = '<i class="ph ph-lock" style="opacity:0.5"></i>';

                        if (item.cliente && (item.cliente.nome || item.cliente.name)) {
                            nomeExibicao = `<strong>${item.cliente.nome || item.cliente.name}</strong>`;
                            icone = '<i class="ph ph-user-circle" style="color:var(--primary)"></i>';
                        } else {
                            nomeExibicao = item.descricao || "Horário Bloqueado";
                        }

                        content.innerHTML += `
                            <div class="summary-item">
                                <div>
                                    <strong style="color:var(--primary);">${item.hora_inicio.substring(0,5)} - ${item.hora_fim.substring(0,5)}</strong>
                                    <p style="margin:5px 0 0 0; font-size:0.85rem;">${icone} ${nomeExibicao}</p>
                                    <small style="color:var(--text-muted); font-size:0.7rem; display:block; margin-top:5px;">
                                        <i class="ph ph-barbell"></i>
                                        ${item.academia_nome || (item.academia ? (item.academia.nome || item.academia.name) : 'Local não informado')}
                                    </small>
                                </div>
                                <button type="button" class="btn-delete-agenda"
                                    onclick="cancelarComJustificativa(${item.id})"
                                    ${!podeCancelar ? 'disabled title="Mínimo 24h de antecedência"' : ''}>
                                    <i class="ph ph-trash"></i>
                                </button>
                            </div>`;
                    });
                })
                .catch(err => {
                    content.innerHTML = `<p style="color:var(--error); text-align:center;">Erro ao carregar dados.</p>`;
                });
        }

        window.onclick = (e) => {
            if (e.target == modalUpdate) closeModalUpdate();
            if (e.target == modalPlanos) closeModalPlanos();
            if (e.target == modalHorario) closeModalHorario();
            if (e.target == modalFinance) closeModalFinance();
            if (e.target == modalAlunos) closeModalAlunos();
            if (e.target == modalGaleria) closeModalGaleria();
            if (e.target == modalAvaliacoes) closeModalAvaliacoes();
            if (e.target == modalDetalhesAluno) closeModalDetalhesAluno();
            if (e.target == modalCriarFichaAluno) fecharModalCriarFichaAluno();
            if (e.target == document.getElementById('modalCarteira')) fecharCarteira();
        }

        function adicionarBolinhasAmareladas() {
            const diasElements = document.querySelectorAll('.day-col');
            
            diasElements.forEach((dayEl) => {
                const onclickAttr = dayEl.getAttribute('onclick');
                const dataMatch = onclickAttr.match(/'(\d{4}-\d{2}-\d{2})'/);
                
                if (dataMatch) {
                    const dataStr = dataMatch[1];
                    
                    fetch(`/personal/agenda/${dataStr}`)
                        .then(r => r.json())
                        .then(dados => {
                            if (dados && dados.length > 0) {
                                const temAula = dados.some(item => {
                                    return item.cliente && item.cliente.id;
                                });
                                
                                if (temAula) {
                                    dayEl.classList.add('has-compromise');
                                }
                            }
                        })
                        .catch(err => console.error('Erro ao buscar agenda:', err));
                }
            });
        }

        window.onload = () => {
            adicionarBolinhasAmareladas();

            const todayStr = new Date().toISOString().split('T')[0];
            const todayElement = document.querySelector(`.day-col[onclick*="${todayStr}"]`);
            if (todayElement) {
                todayElement.click();
            } else {
                const firstDay = document.querySelector('.day-col');
                if (firstDay) firstDay.click();
            }
        }

        // ============ GALERIA AJAX ============

        function galeriaMsg(texto, tipo) {
            const el = document.getElementById('galeriaMsg');
            el.style.display = 'block';
            el.style.background = tipo === 'ok'
                ? 'rgba(0,255,136,0.12)'
                : 'rgba(255,68,68,0.12)';
            el.style.border = tipo === 'ok'
                ? '1px solid var(--success)'
                : '1px solid var(--error)';
            el.style.color = tipo === 'ok' ? 'var(--success)' : 'var(--error)';
            el.innerHTML = texto;
            setTimeout(() => el.style.display = 'none', 4000);
        }

        function atualizarContador(total) {
            document.getElementById('galeriaContador').textContent = total + '/9';
            const slot    = document.getElementById('galeriaSlot');
            const legArea = document.getElementById('galeriaLegendaArea');
            if (total >= 9) {
                slot.style.display    = 'none';
                legArea.style.display = 'none';
            } else {
                slot.style.display    = 'flex';
                legArea.style.display = 'block';
            }
        }

        function uploadFoto(input) {
            if (!input.files || !input.files[0]) return;

            const legenda = document.getElementById('galeriaLegenda').value;
            const slot    = document.getElementById('galeriaSlot');
            const label   = document.getElementById('galeriaSlotLabel');

            label.textContent = 'Enviando...';
            slot.style.opacity = '0.5';
            slot.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('foto', input.files[0]);
            formData.append('legenda', legenda);
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            fetch('{{ route("personal.fotos.store") }}', {
                method: 'POST',
                body: formData,
            })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    // Adiciona foto no grid antes do slot
                    const grid = document.getElementById('galeriaGrid');
                    const novoItem = document.createElement('div');
                    novoItem.className = 'galeria-item';
                    novoItem.id = 'foto-' + data.foto.id;
                    novoItem.innerHTML = `
                        <img src="${data.foto.url}" alt="${data.foto.legenda || ''}">
                        <div class="galeria-item-overlay">
                            ${data.foto.legenda ? `<span class="galeria-item-legenda">${data.foto.legenda}</span>` : ''}
                            <button type="button" onclick="removerFoto(${data.foto.id}, this)"
                                style="background:var(--error);color:#fff;border:none;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:0.75rem;">
                                <i class="ph ph-trash"></i> Remover
                            </button>
                        </div>`;
                    grid.insertBefore(novoItem, slot);

                    document.getElementById('galeriaLegenda').value = '';
                    input.value = '';
                    atualizarContador(data.total);
                    galeriaMsg('<i class="ph ph-check-circle"></i> Foto adicionada com sucesso!', 'ok');
                } else {
                    galeriaMsg('<i class="ph ph-warning-circle"></i> ' + (data.erro || 'Erro ao enviar.'), 'err');
                }
            })
            .catch(() => galeriaMsg('<i class="ph ph-warning-circle"></i> Erro de conexão ao enviar a foto.', 'err'))
            .finally(() => {
                label.textContent = 'Adicionar';
                slot.style.opacity = '1';
                slot.style.pointerEvents = 'auto';
            });
        }

        function removerFoto(fotoId, btn) {
            if (!confirm('Remover esta foto da galeria?')) return;
            btn.disabled = true;
            btn.innerHTML = '...';

            fetch(`/fotos/${fotoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    const item = document.getElementById('foto-' + fotoId);
                    if (item) item.remove();
                    const total = document.querySelectorAll('#galeriaGrid .galeria-item').length;
                    atualizarContador(total);
                    galeriaMsg('<i class="ph ph-check-circle"></i> Foto removida.', 'ok');
                } else {
                    galeriaMsg('<i class="ph ph-warning-circle"></i> ' + (data.erro || 'Erro ao remover.'), 'err');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ph ph-trash"></i> Remover';
                }
            })
            .catch(() => {
                galeriaMsg('<i class="ph ph-warning-circle"></i> Erro de conexão.', 'err');
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-trash"></i> Remover';
            });
        }
    </script>
@include('partials.push-notif')
</body>

</html>
