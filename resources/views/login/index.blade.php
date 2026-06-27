<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnrFit — Plataforma para Personal Trainers</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">

    <style>
        :root {
            --primary: #d4ff00;
            --primary-soft: rgba(212, 255, 0, 0.12);
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
            --error: #ff4d4d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            background-color: var(--bg-dark);
            background-image:
                radial-gradient(circle at 20% 0%, rgba(212, 255, 0, 0.07) 0%, transparent 35%),
                radial-gradient(circle at 85% 100%, rgba(212, 255, 0, 0.05) 0%, transparent 40%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== Navbar ===== */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 6vw;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Syncopate', sans-serif;
            font-size: 1.15rem;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .logo img { height: 34px; width: 34px; object-fit: contain; }
        .logo span { color: var(--primary); }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 999px;
            padding: 10px 22px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.15);
            color: var(--text-main);
        }

        .btn-ghost:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-solid {
            background: var(--primary);
            border: 1px solid var(--primary);
            color: var(--bg-dark);
        }

        .btn-solid:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212,255,0,0.25);
        }

        /* ===== Hero ===== */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px 6vw 64px;
        }

        .hero-badge {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--primary);
            background: var(--primary-soft);
            border: 1px solid rgba(212,255,0,0.25);
            border-radius: 999px;
            padding: 8px 18px;
            margin-bottom: 24px;
            animation: fadeUp 0.6s ease both;
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.4rem);
            font-weight: 800;
            line-height: 1.12;
            max-width: 760px;
            margin-bottom: 18px;
            animation: fadeUp 0.6s ease 0.1s both;
        }

        .hero h1 em {
            font-style: normal;
            color: var(--primary);
        }

        .hero > p {
            color: var(--text-dim);
            font-size: clamp(1rem, 1.6vw, 1.15rem);
            max-width: 560px;
            line-height: 1.65;
            margin-bottom: 36px;
            animation: fadeUp 0.6s ease 0.2s both;
        }

        .hero-cta {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 64px;
            animation: fadeUp 0.6s ease 0.3s both;
        }

        .hero-cta .btn { padding: 14px 30px; font-size: 1rem; }

        /* ===== Features ===== */
        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            max-width: 820px;
            animation: fadeUp 0.6s ease 0.4s both;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 999px;
            padding: 12px 20px;
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-dim);
            transition: 0.25s;
            cursor: default;
        }

        .feature:hover {
            color: var(--text-main);
            border-color: rgba(212,255,0,0.45);
            background: var(--primary-soft);
            transform: translateY(-3px);
        }

        .feature i { color: var(--primary); font-size: 0.95rem; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===== Modal de login ===== */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(5, 6, 8, 0.75);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s;
            z-index: 50;
        }

        .modal-backdrop.open {
            opacity: 1;
            visibility: visible;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 22px;
            padding: 40px;
            width: 100%;
            max-width: 410px;
            transform: translateY(16px) scale(0.98);
            transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.3, 1.2);
            position: relative;
        }

        .modal-backdrop.open .login-card {
            transform: translateY(0) scale(1);
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            color: var(--text-dim);
            font-size: 1.05rem;
            cursor: pointer;
            padding: 8px;
            transition: 0.2s;
        }

        .modal-close:hover { color: var(--text-main); }

        .login-card h2 {
            font-size: 1.55rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .login-card .subtitle {
            color: var(--text-dim);
            font-size: 0.9rem;
            margin-bottom: 26px;
        }

        .alert-success {
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.3);
            color: #22c55e;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 0.86rem;
        }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-dim);
            display: block;
            margin-bottom: 7px;
        }

        .input-wrap { position: relative; }

        .input-wrap > i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 0.85rem;
            pointer-events: none;
            transition: 0.25s;
        }

        .form-control {
            width: 100%;
            padding: 13px 15px 13px 42px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            background: var(--bg-dark);
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.92rem;
            outline: none;
            transition: 0.25s;
        }

        .form-control::placeholder { color: #565d6b; }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(212,255,0,0.12);
        }

        .input-wrap:focus-within > i { color: var(--primary); }

        .toggle-pass {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-dim);
            cursor: pointer;
            padding: 10px;
            font-size: 0.88rem;
        }

        .toggle-pass:hover { color: var(--text-main); }

        .forgot-row {
            display: flex;
            justify-content: flex-end;
            margin: -4px 0 18px;
        }

        .forgot-row a {
            color: var(--text-dim);
            font-size: 0.82rem;
            text-decoration: none;
            transition: 0.2s;
        }

        .forgot-row a:hover { color: var(--primary); }

        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: var(--bg-dark);
            font-family: inherit;
            font-weight: 700;
            font-size: 0.97rem;
            cursor: pointer;
            transition: 0.25s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(212,255,0,0.25);
        }

        .register-hint {
            margin-top: 20px;
            text-align: center;
            font-size: 0.86rem;
            color: var(--text-dim);
        }

        .register-hint a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .register-hint a:hover { text-decoration: underline; }

        .error-message {
            color: var(--error);
            font-size: 0.78rem;
            margin-top: 6px;
        }

        /* ===== Seções de narrativa (story da landing) ===== */
        .lp-sec {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 78px 6vw;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .lp-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: 16px;
        }

        .lp-eyebrow .num {
            color: var(--bg-dark);
            background: var(--primary);
            font-family: 'Syncopate', sans-serif;
            font-weight: 700;
            padding: 2px 8px;
            letter-spacing: 1px;
        }

        .lp-title {
            font-family: 'Syncopate', sans-serif;
            font-weight: 700;
            font-size: clamp(1.7rem, 5vw, 3.1rem);
            line-height: 1.04;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            margin: 0;
        }

        .lp-title .mark {
            background: var(--primary);
            color: var(--bg-dark);
            padding: 0 0.08em;
            -webkit-box-decoration-break: clone;
            box-decoration-break: clone;
        }

        .lp-lead {
            max-width: 640px;
            color: var(--text-dim);
            font-size: clamp(1rem, 1.5vw, 1.12rem);
            line-height: 1.7;
            margin: 24px 0 0;
        }

        .lp-lead strong { color: var(--text-main); font-weight: 600; }

        /* --- Escada de crescimento --- */
        .lp-ladder {
            margin-top: 52px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.06);
        }

        .lp-step {
            background: var(--bg-dark);
            padding: 30px 24px;
            transition: 0.25s;
        }

        .lp-step:hover { background: rgba(212,255,0,0.04); }

        .lp-step .step-num {
            display: inline-block;
            font-family: 'Syncopate', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--bg-dark);
            background: var(--primary);
            padding: 3px 9px;
        }

        .lp-step h3 {
            font-family: 'Syncopate', sans-serif;
            font-size: 0.92rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 20px 0 10px;
        }

        .lp-step p { color: var(--text-dim); font-size: 0.86rem; line-height: 1.6; }

        /* --- Sacadas rápidas --- */
        .lp-saca {
            margin-top: 48px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .lp-saca-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-left: 3px solid var(--primary);
            padding: 22px 24px;
            transition: 0.25s;
        }

        .lp-saca-item:hover {
            background: var(--primary-soft);
            transform: translateX(4px);
        }

        .lp-saca-item i { color: var(--primary); font-size: 1.45rem; flex-shrink: 0; margin-top: 1px; }
        .lp-saca-item p { font-size: 0.95rem; line-height: 1.5; font-weight: 500; color: var(--text-main); }
        .lp-saca-item strong { color: var(--primary); font-weight: 700; }

        /* --- Bloco do aluno --- */
        .lp-split {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 56px;
            align-items: center;
        }

        .lp-bullets { list-style: none; margin: 30px 0 0; padding: 0; }

        .lp-bullets li {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 17px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 1rem;
            font-weight: 500;
        }

        .lp-bullets li i { color: var(--primary); font-size: 1.3rem; flex-shrink: 0; }

        .lp-aluno-card {
            background: linear-gradient(160deg, rgba(212,255,0,0.10), rgba(212,255,0,0.02));
            border: 1px solid rgba(212,255,0,0.25);
            border-radius: 22px;
            padding: 40px 36px;
            text-align: center;
        }

        .lp-aluno-card .quote {
            font-family: 'Syncopate', sans-serif;
            font-size: clamp(1.1rem, 2.6vw, 1.5rem);
            line-height: 1.2;
            text-transform: uppercase;
            margin: 0 0 28px;
        }

        .lp-aluno-card .quote .mark {
            background: var(--primary);
            color: var(--bg-dark);
            padding: 0 0.08em;
        }

        .lp-aluno-card .ed-btn,
        .lp-aluno-card .btn { width: 100%; justify-content: center; }

        @media (max-width: 860px) {
            .lp-ladder { grid-template-columns: 1fr 1fr; }
            .lp-saca { grid-template-columns: 1fr; }
            .lp-split { grid-template-columns: 1fr; gap: 36px; }
        }

        @media (max-width: 480px) {
            .lp-ladder { grid-template-columns: 1fr; }
            .lp-sec { padding: 60px 6vw; }
        }

        /* ===== Responsivo ===== */
        @media (max-width: 560px) {
            .navbar { padding: 18px 5vw; }
            .nav-actions .btn { padding: 9px 16px; font-size: 0.84rem; }
            .logo { font-size: 0.95rem; letter-spacing: 2px; }
            .logo img { height: 28px; width: 28px; }
            .login-card { padding: 30px 24px; }
            .hero-cta .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body class="ed-page">

<nav class="navbar">
    <div class="logo">
        <img src="{{ asset('SnrFit.png') }}" alt="SnrFit">
        <div style="display:flex; flex-direction:column; gap:3px; line-height:1;">
            <div>SNR<span>FIT</span></div>
            <span style="font-family:'Inter',sans-serif; font-size:0.58rem; font-weight:600; letter-spacing:2px; color:var(--text-dim); text-transform:uppercase;">Treino que vira história</span>
        </div>
    </div>

    <div class="nav-actions">
        <button type="button" class="btn btn-ghost" onclick="openModal()">
            <i class="ph ph-sign-in"></i> Entrar
        </button>
        <a href="{{ route('cadastro.SelecaoCadastro') }}" class="btn btn-solid">
            Cadastrar-se
        </a>
    </div>
</nav>

<main class="ed-hero">
    <div class="ed-side">SNR·FIT — EST. 2026</div>

    <div class="ed-kicker"><span class="ed-num">01</span> A plataforma completa do mundo fitness</div>

    <h1 class="ed-display">
        Treino<br>
        que <span class="ed-mark">vira</span><br>
        história<span class="ed-dot">.</span>
    </h1>

    <p class="ed-lead">
        Agenda, alunos, financeiro, fichas e divulgação num só painel — pro profissional.
        Personais, academias, studios e planos reunidos num só lugar — pra quem treina.
        Chega de espalhar sua rotina por mil apps.
    </p>

    <div style="display:flex; gap:14px; flex-wrap:wrap; margin:36px 0 8px;">
        <a href="{{ route('cadastro.SelecaoCadastro') }}" class="ed-btn ed-btn-solid">
            Quero expandir meu negócio <i class="ph ph-arrow-right"></i>
        </a>
        <button type="button" class="ed-btn ed-btn-line" onclick="openModal()">Já tenho conta</button>
    </div>

    <div class="ed-index">
        <div class="ed-item"><span class="ed-i-num">01</span><span class="ed-i-txt">Agenda sob controle</span></div>
        <div class="ed-item"><span class="ed-i-num">02</span><span class="ed-i-txt">Gestão completa de alunos</span></div>
        <div class="ed-item"><span class="ed-i-num">03</span><span class="ed-i-txt">Financeiro em tempo real</span></div>
        <div class="ed-item"><span class="ed-i-num">04</span><span class="ed-i-txt">Divulgação do seu perfil</span></div>
        <div class="ed-item"><span class="ed-i-num">05</span><span class="ed-i-txt">Fichas de treino pro</span></div>
        <div class="ed-item"><span class="ed-i-num">06</span><span class="ed-i-txt">Pagamentos online</span></div>
    </div>
</main>

{{-- ===== 02 · Cresça com a gente ===== --}}
<section class="lp-sec">
    <div class="lp-eyebrow"><span class="num">02</span> Cresça com a gente</div>
    <h2 class="lp-title">
        Comece com um aluno.<br>
        Termine com uma <span class="mark">marca</span>.
    </h2>
    <p class="lp-lead">
        A SnrFit cresce no seu ritmo. A cada aluno novo, a cada agenda lotada, a cada perfil
        que aparece pra mais gente, a plataforma sobe junto com você. É o seu nome
        <strong>alcançando novos lugares</strong> — com a gente do seu lado em cada passo.
    </p>

    <div class="lp-ladder">
        <div class="lp-step">
            <span class="step-num">01</span>
            <h3>Comece</h3>
            <p>Perfil no ar e seus primeiros alunos cadastrados em minutos. Sem dor de cabeça.</p>
        </div>
        <div class="lp-step">
            <span class="step-num">02</span>
            <h3>Organize</h3>
            <p>Agenda, fichas, financeiro e pagamentos num painel só. Sua rotina para de viver espalhada.</p>
        </div>
        <div class="lp-step">
            <span class="step-num">03</span>
            <h3>Cresça</h3>
            <p>Apareça na busca pra quem procura personal na região. Mais visibilidade vira mais aluno.</p>
        </div>
        <div class="lp-step">
            <span class="step-num">04</span>
            <h3>Alcance</h3>
            <p>De autônomo a referência na cidade. Cada conquista registrada, cada evolução virando história.</p>
        </div>
    </div>
</section>

{{-- ===== 03 · Deixa com a gente (sacadas rápidas) ===== --}}
<section class="lp-sec">
    <div class="lp-eyebrow"><span class="num">03</span> Deixa com a gente</div>
    <h2 class="lp-title">
        Você treina.<br>
        A gente cuida do <span class="mark">resto</span>.
    </h2>
    <p class="lp-lead">
        Enquanto você foca no aluno, a plataforma trabalha nos bastidores. Olha o que roda no automático:
    </p>

    <div class="lp-saca">
        <div class="lp-saca-item">
            <i class="ph ph-whatsapp-logo"></i>
            <p>Acabou a sessão? O aluno recebe o resumo no <strong>WhatsApp</strong>, sozinho.</p>
        </div>
        <div class="lp-saca-item">
            <i class="ph ph-bell-ringing"></i>
            <p>Aula marcada vira <strong>lembrete automático</strong>. Ninguém mais esquece o horário.</p>
        </div>
        <div class="lp-saca-item">
            <i class="ph ph-chart-line-up"></i>
            <p>Seu <strong>financeiro se calcula</strong> mês a mês. Pacote e avulsa, tudo somado.</p>
        </div>
        <div class="lp-saca-item">
            <i class="ph ph-credit-card"></i>
            <p><strong>Pagamento online</strong> integrado. Chega de cobrar na unha.</p>
        </div>
        <div class="lp-saca-item">
            <i class="ph ph-clipboard-text"></i>
            <p>Montou a ficha aqui, o aluno <strong>abre no celular</strong> na mesma hora.</p>
        </div>
        <div class="lp-saca-item">
            <i class="ph ph-magnifying-glass"></i>
            <p>Seu perfil <strong>aparece na busca</strong> pra quem procura personal por perto.</p>
        </div>
    </div>
</section>

{{-- ===== 04 · E pra quem treina? (o aluno) ===== --}}
<section class="lp-sec">
    <div class="lp-split">
        <div>
            <div class="lp-eyebrow"><span class="num">04</span> E pra quem treina?</div>
            <h2 class="lp-title">
                Pro profissional, gestão.<br>
                Pra você, <span class="mark">evolução</span>.
            </h2>
            <p class="lp-lead">
                O aluno não fica de fora. Quem treina na SnrFit tem o treino do dia sempre pronto,
                a ficha no bolso e cada repetição virando gráfico. Você só precisa aparecer —
                o progresso a gente registra.
            </p>

            <ul class="lp-bullets">
                <li><i class="ph ph-calendar-check"></i> Treino do dia sempre pronto</li>
                <li><i class="ph ph-barbell"></i> Sua ficha no bolso, onde estiver</li>
                <li><i class="ph ph-chart-line-up"></i> Evolução de carga em gráfico</li>
                <li><i class="ph ph-chat-circle-dots"></i> Conversa direta com seu personal</li>
            </ul>
        </div>

        <div class="lp-aluno-card">
            <p class="quote">Você só aparece.<br>O resto vira <span class="mark">história</span>.</p>
            <a href="{{ route('cadastro.SelecaoCadastro') }}" class="ed-btn ed-btn-solid">
                Quero treinar com a SnrFit <i class="ph ph-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<div class="ed-marquee" aria-hidden="true">
    <div class="track">
        <span>Treino que vira história</span><span>•</span><span>SNR·FIT</span><span>•</span><span>Alcançando novos lugares</span><span>•</span><span>Bora pra cima</span><span>•</span><span>Sua evolução em um só lugar</span><span>•</span><span>Treino que vira história</span><span>•</span><span>SNR·FIT</span><span>•</span><span>Alcançando novos lugares</span><span>•</span><span>Bora pra cima</span><span>•</span><span>Sua evolução em um só lugar</span><span>•</span>
    </div>
</div>

<footer class="snr-footer">
    <div class="snr-voice">Aqui ninguém treina sozinho. <strong>Bora pra cima.</strong></div>
    <div style="margin-top:10px; font-size:0.72rem; opacity:0.55;">© {{ date('Y') }} SnrFit — Treino que vira história.</div>
</footer>

<div class="modal-backdrop" id="login-modal" role="dialog" aria-modal="true" aria-labelledby="login-title">
    <div class="login-card">
        <button type="button" class="modal-close" onclick="closeModal()" aria-label="Fechar">
            <i class="ph ph-x"></i>
        </button>

        <h2 id="login-title">Que bom te ver de volta</h2>
        <p class="subtitle">Sua evolução continua de onde parou. Bora?</p>

        @if(session('sucesso'))
        <div class="alert-success">
            <i class="ph ph-check-circle" style="margin-right:8px;"></i>{{ session('sucesso') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="form-group">
                <label for="login">E-mail ou CNPJ</label>
                <div class="input-wrap">
                    <i class="ph ph-envelope"></i>
                    <input type="text" name="login" id="login"
                           class="form-control"
                           placeholder="ex@email.com ou 00.000.000/0001-00"
                           value="{{ old('login') }}"
                           required>
                </div>
                @error('login')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrap">
                    <i class="ph ph-lock"></i>
                    <input type="password" name="senha" id="password"
                           class="form-control"
                           placeholder="Sua senha"
                           required>
                    <button type="button" class="toggle-pass" onclick="togglePassword()" aria-label="Mostrar senha">
                        <i class="ph ph-eye" id="toggle-icon"></i>
                    </button>
                </div>
                @error('senha')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="forgot-row">
                <a href="{{ route('senha.solicitar.form') }}">Esqueceu sua senha?</a>
            </div>

            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <div class="register-hint">
            Não tem conta?
            <a href="{{ route('cadastro.SelecaoCadastro') }}">Cadastre-se</a>
        </div>
        <div style="text-align:center; margin-top:18px; font-size:0.72rem; opacity:0.6;">
            <a href="{{ route('lgpd.politica') }}" style="color:inherit;">Política de Privacidade</a>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('login-modal');

    function openModal() {
        modal.classList.add('open');
        setTimeout(() => document.getElementById('login').focus(), 250);
    }

    function closeModal() {
        modal.classList.remove('open');
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('toggle-icon');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('ph-eye', !show);
        icon.classList.toggle('ph-eye-slash', show);
    }

    @if($errors->any() || session('sucesso'))
        openModal();
    @endif
</script>

</body>
</html>
