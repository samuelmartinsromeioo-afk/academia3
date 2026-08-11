<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnrFit — Treino que vira história</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">

    <style>
        :root {
            --primary: #d4ff00;
            --primary-soft: rgba(212, 255, 0, 0.12);
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
            --line: rgba(255, 255, 255, 0.06);
            --error: #ff4d4d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            background-color: var(--bg-dark);
            background-image:
                radial-gradient(circle at 22% -5%, rgba(212, 255, 0, 0.03) 0%, transparent 30%),
                radial-gradient(circle at 88% 104%, rgba(255, 255, 255, 0.025) 0%, transparent 38%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        @keyframes snrFadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes snrMarquee {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }

        .syncopate { font-family: 'Syncopate', sans-serif; }

        .logo-badge {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syncopate', sans-serif;
            font-weight: 700;
            font-size: 15px;
            color: var(--bg-dark);
            flex-shrink: 0;
        }

        .logo-name {
            font-family: 'Syncopate', sans-serif;
            font-size: 1.15rem;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .logo-name span { color: var(--primary); }

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

        .btn-solid {
            background: var(--primary);
            border: 1px solid var(--primary);
            color: var(--bg-dark);
        }

        .btn-solid:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 255, 0, 0.25);
        }

        .btn-lg { padding: 15px 30px; font-size: 1rem; font-weight: 700; gap: 10px; }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: 22px;
        }

        .eyebrow .num {
            color: var(--bg-dark);
            background: var(--primary);
            font-family: 'Syncopate', sans-serif;
            font-weight: 700;
            padding: 2px 8px;
            letter-spacing: 1px;
        }

        .mark {
            background: var(--primary);
            color: var(--bg-dark);
            padding: 0 0.08em;
            -webkit-box-decoration-break: clone;
            box-decoration-break: clone;
        }

        /* ===== Seções ===== */
        .lp-sec {
            max-width: 1152px;
            margin: 0 auto;
            padding: 78px 5vw;
            border-top: 1px solid var(--line);
        }

        .lp-title {
            font-family: 'Syncopate', sans-serif;
            font-weight: 700;
            font-size: clamp(1.6rem, 3.4vw, 2.6rem);
            line-height: 1.04;
            letter-spacing: -0.01em;
            text-transform: uppercase;
        }

        .lp-lead {
            max-width: 640px;
            color: var(--text-dim);
            font-size: clamp(1rem, 1.4vw, 1.08rem);
            line-height: 1.7;
            margin-top: 24px;
        }

        .lp-lead strong { color: var(--text-main); font-weight: 600; }

        .lp-split {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 56px;
            align-items: center;
        }

        .lp-img-card {
            position: relative;
            height: 420px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .lp-img-card img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .lp-img-card .shade {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(10,11,13,0.1), rgba(10,11,13,0.55));
        }

        .lp-img-card .tag {
            position: absolute;
            left: 20px;
            bottom: 20px;
            font-size: 0.82rem;
            background: rgba(10, 11, 13, 0.5);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(212, 255, 0, 0.25);
            border-radius: 10px;
            padding: 9px 14px;
        }

        .lp-img-card .tag i { color: var(--primary); }

        /* --- Escada de crescimento --- */
        .lp-ladder {
            margin-top: 52px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--line);
            border: 1px solid var(--line);
        }

        .lp-step { background: var(--bg-dark); padding: 30px 24px; transition: 0.25s; }

        .lp-step:hover { background: rgba(212, 255, 0, 0.04); }

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
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-left: 3px solid var(--primary);
            padding: 22px 24px;
            transition: 0.25s;
        }

        .lp-saca-item:hover { background: var(--primary-soft); transform: translateX(4px); }

        .lp-saca-item i { color: var(--primary); font-size: 1.45rem; flex-shrink: 0; margin-top: 1px; }
        .lp-saca-item p { font-size: 0.95rem; line-height: 1.5; font-weight: 500; }
        .lp-saca-item strong { color: var(--primary); font-weight: 700; }

        /* --- Bloco do aluno --- */
        .lp-bullets { list-style: none; margin-top: 30px; }

        .lp-bullets li {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 17px 0;
            border-bottom: 1px solid var(--line);
            font-size: 1rem;
            font-weight: 500;
        }

        .lp-bullets li i { color: var(--primary); font-size: 1.3rem; flex-shrink: 0; }

        .lp-aluno-card {
            position: relative;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(212, 255, 0, 0.25);
            min-height: 520px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .lp-aluno-card > img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .lp-aluno-card .shade {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(10,11,13,0.15) 0%, rgba(10,11,13,0.35) 45%, rgba(10,11,13,0.92) 100%);
        }

        .lp-aluno-card .content {
            position: relative;
            padding: 40px 36px;
            text-align: center;
        }

        .lp-aluno-card .quote {
            font-family: 'Syncopate', sans-serif;
            font-size: clamp(1.1rem, 2.4vw, 1.4rem);
            line-height: 1.2;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        .lp-aluno-card .btn { width: 100%; justify-content: center; }

        /* ===== Banda cinematográfica ===== */
        .lp-banda {
            position: relative;
            height: 220px;
            overflow: hidden;
            border-top: 1px solid var(--line);
        }

        .lp-banda img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .lp-banda .shade {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(10,11,13,0.9), rgba(10,11,13,0.35) 50%, rgba(10,11,13,0.9));
        }

        .lp-banda .quote {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syncopate', sans-serif;
            font-size: clamp(1.1rem, 2.4vw, 1.5rem);
            letter-spacing: 2px;
            text-transform: uppercase;
            text-align: center;
        }

        .lp-banda .quote span { color: var(--primary); }

        /* ===== Marquee ===== */
        .lp-marquee {
            overflow: hidden;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            padding: 18px 0;
            background: #0c0e11;
        }

        .lp-marquee .track {
            display: inline-flex;
            gap: 34px;
            align-items: center;
            white-space: nowrap;
            animation: snrMarquee 28s linear infinite;
            font-family: 'Syncopate', sans-serif;
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.55);
        }

        .lp-marquee .dot { color: var(--primary); }

        /* ===== Footer ===== */
        .lp-footer { text-align: center; padding: 48px 5vw; }

        .lp-footer .voice {
            font-family: 'Syncopate', sans-serif;
            font-size: 1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .lp-footer .voice strong { color: var(--primary); }

        .lp-footer .copy { margin-top: 10px; font-size: 0.72rem; opacity: 0.55; }

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

        .modal-backdrop.open { opacity: 1; visibility: visible; }

        .login-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 22px;
            width: 100%;
            max-width: 430px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            position: relative;
            transform: translateY(16px) scale(0.98);
            transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.3, 1.2);
        }

        .modal-backdrop.open .login-card { transform: translateY(0) scale(1); }

        .login-banner { position: relative; height: 132px; }

        .login-banner img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-banner .shade {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(22,24,29,0.35), var(--card-bg));
        }

        .login-banner .brand {
            position: absolute;
            left: 32px;
            bottom: 16px;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .login-banner .brand .logo-badge {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            font-size: 12px;
        }

        .login-banner .brand .logo-name { font-size: 0.8rem; letter-spacing: 2px; }

        .modal-close {
            position: absolute;
            top: 14px;
            right: 14px;
            background: rgba(10, 11, 13, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text-main);
            font-size: 1rem;
            cursor: pointer;
            padding: 7px 9px;
            border-radius: 9px;
            line-height: 0;
            transition: 0.2s;
        }

        .modal-close:hover { color: var(--primary); }

        .login-body { padding: 26px 40px 40px; }

        .login-body h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: 6px; }

        .login-body .subtitle { color: var(--text-dim); font-size: 0.9rem; margin-bottom: 24px; }

        .alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.3);
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

        .input-wrap { position: relative; display: flex; align-items: center; }

        .input-wrap > i {
            position: absolute;
            left: 15px;
            color: var(--text-dim);
            font-size: 0.85rem;
            pointer-events: none;
            transition: 0.25s;
        }

        .input-wrap:focus-within > i { color: var(--primary); }

        .form-control {
            width: 100%;
            padding: 13px 15px 13px 42px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            box-shadow: 0 0 0 3px rgba(212, 255, 0, 0.12);
        }

        .input-wrap .has-toggle { padding-right: 44px; }

        .toggle-pass {
            position: absolute;
            right: 5px;
            background: none;
            border: none;
            color: var(--text-dim);
            cursor: pointer;
            padding: 10px;
            font-size: 0.88rem;
            line-height: 0;
        }

        .toggle-pass:hover { color: var(--text-main); }

        .forgot-row { display: flex; justify-content: flex-end; margin: -4px 0 18px; }

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
            box-shadow: 0 10px 24px rgba(212, 255, 0, 0.25);
        }

        .register-hint {
            margin-top: 20px;
            text-align: center;
            font-size: 0.86rem;
            color: var(--text-dim);
        }

        .register-hint a { color: var(--primary); text-decoration: none; font-weight: 600; }

        .register-hint a:hover { text-decoration: underline; }

        .privacy-row { text-align: center; margin-top: 18px; font-size: 0.72rem; opacity: 0.6; }

        .privacy-row a { color: inherit; }

        .error-message { color: var(--error); font-size: 0.78rem; margin-top: 6px; }

        /* ===== Responsivo ===== */
        @media (max-width: 1024px) {
            .lp-split { grid-template-columns: 1fr; gap: 36px; }
            .lp-ladder { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .lp-saca { grid-template-columns: 1fr; }
            .lp-aluno-card { min-height: 440px; }
        }

        @media (max-width: 560px) {
            .logo-name { font-size: 0.95rem; letter-spacing: 2px; }
            .logo-badge { width: 32px; height: 32px; font-size: 13px; }
            .lp-ladder { grid-template-columns: 1fr; }
            .lp-sec { padding: 60px 5vw; }
            .login-body { padding: 24px 26px 32px; }
        }
    </style>
</head>
<body>

{{-- ===== Hero Cinemática (nova hero, 6 cenas) ===== --}}
@include('partials.hero-cinematica')

{{-- ===== 02 · Cresça com a gente ===== --}}
<section class="lp-sec">
    <div class="lp-split">
        <div>
            <div class="eyebrow"><span class="num">02</span> Cresça com a gente</div>
            <h2 class="lp-title">Comece com um aluno.<br>Termine com uma <span class="mark">marca</span>.</h2>
            <p class="lp-lead">
                A SnrFit cresce no seu ritmo. A cada aluno novo, a cada agenda lotada, a cada perfil
                que aparece pra mais gente, a plataforma sobe junto com você. É o seu nome
                <strong>alcançando novos lugares</strong> — com a gente do seu lado em cada passo.
            </p>
        </div>
        <div class="lp-img-card">
            <img src="{{ asset('images/landing/gestao.png') }}" alt="Personal trainer gerenciando na plataforma SnrFit" loading="lazy">
            <div class="shade"></div>
            <div class="tag">Gestão na palma da mão <i class="ph-fill ph-lightning"></i></div>
        </div>
    </div>

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

{{-- ===== 03 · Deixa com a gente ===== --}}
<section class="lp-sec">
    <div class="eyebrow"><span class="num">03</span> Deixa com a gente</div>
    <h2 class="lp-title">Você treina.<br>A gente cuida do <span class="mark">resto</span>.</h2>
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

{{-- ===== 04 · E pra quem treina? ===== --}}
<section class="lp-sec">
    <div class="lp-split" style="grid-template-columns:1.05fr 0.95fr;">
        <div>
            <div class="eyebrow"><span class="num">04</span> E pra quem treina?</div>
            <h2 class="lp-title">Pro profissional, gestão.<br>Pra você, <span class="mark">evolução</span>.</h2>
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
            <img src="{{ asset('images/landing/treino-aluna.png') }}" alt="Aluna treinando na SnrFit" loading="lazy">
            <div class="shade"></div>
            <div class="content">
                <p class="quote">Você só aparece.<br>O resto vira <span class="mark">história</span>.</p>
                <a href="{{ route('cadastro.SelecaoCadastro') }}" class="btn btn-solid btn-lg">
                    Quero treinar com a SnrFit <i class="ph ph-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ===== Banda cinematográfica ===== --}}
<div class="lp-banda">
    <img src="{{ asset('images/landing/banda.png') }}" alt="" loading="lazy">
    <div class="shade"></div>
    <div class="quote"><div>Aqui ninguém<br>treina <span>sozinho.</span></div></div>
</div>

{{-- ===== Marquee ===== --}}
<div class="lp-marquee" aria-hidden="true">
    <div class="track">
        <span>Treino que vira história</span><span class="dot">•</span><span>SNR·FIT</span><span class="dot">•</span><span>Alcançando novos lugares</span><span class="dot">•</span><span>Bora pra cima</span><span class="dot">•</span><span>Sua evolução em um só lugar</span><span class="dot">•</span>
        <span>Treino que vira história</span><span class="dot">•</span><span>SNR·FIT</span><span class="dot">•</span><span>Alcançando novos lugares</span><span class="dot">•</span><span>Bora pra cima</span><span class="dot">•</span><span>Sua evolução em um só lugar</span><span class="dot">•</span>
    </div>
</div>

{{-- ===== Footer ===== --}}
<footer class="lp-footer">
    <div class="voice">Aqui ninguém treina sozinho. <strong>Bora pra cima.</strong></div>
    <div class="copy">© {{ date('Y') }} SnrFit — Treino que vira história.</div>
</footer>

{{-- ===== Modal de login (com banner de imagem) ===== --}}
<div class="modal-backdrop" id="login-modal" role="dialog" aria-modal="true" aria-labelledby="login-title">
    <div class="login-card">
        <div class="login-banner">
            <img src="{{ asset('images/landing/banda.png') }}" alt="">
            <div class="shade"></div>
            <button type="button" class="modal-close" onclick="closeModal()" aria-label="Fechar">
                <i class="ph ph-x"></i>
            </button>
            <div class="brand">
                <div class="logo-badge">S</div>
                <div class="logo-name">SNR<span>FIT</span></div>
            </div>
        </div>

        <div class="login-body">
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
                               class="form-control has-toggle"
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
            <div class="privacy-row">
                <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>
            </div>
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
