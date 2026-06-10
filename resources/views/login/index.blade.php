<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitSys — Encontre seu personal</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
<body>

<nav class="navbar">
    <div class="logo">
        <img src="{{ asset('SnrFit.png') }}" alt="FitSys">
        <div>FIT<span>SYS</span></div>
    </div>

    <div class="nav-actions">
        <button type="button" class="btn btn-ghost" onclick="openModal()">
            <i class="fa-solid fa-right-to-bracket"></i> Entrar
        </button>
        <a href="{{ route('cadastro.SelecaoCadastro') }}" class="btn btn-solid">
            Cadastrar-se
        </a>
    </div>
</nav>

<main class="hero">
    <div class="hero-badge">Marketplace fitness</div>

    <h1>Conecte-se ao <em>personal ideal</em> e evolua de verdade.</h1>

    <p>Alunos encontram o profissional certo. Personais gerenciam agenda, alunos, financeiro, divulgação e fichas de treino — tudo em um só lugar.</p>

    <div class="hero-cta">
        <button type="button" class="btn btn-solid" onclick="openModal()">
            Começar agora <i class="fa-solid fa-arrow-right"></i>
        </button>
        <a href="{{ route('cadastro.SelecaoCadastro') }}" class="btn btn-ghost">
            Sou personal trainer
        </a>
    </div>

    <div class="features">
        <div class="feature"><i class="fa-solid fa-calendar-check"></i> Agenda inteligente</div>
        <div class="feature"><i class="fa-solid fa-users"></i> Gestão de alunos</div>
        <div class="feature"><i class="fa-solid fa-chart-line"></i> Controle financeiro</div>
        <div class="feature"><i class="fa-solid fa-bullhorn"></i> Divulgação do serviço</div>
        <div class="feature"><i class="fa-solid fa-dumbbell"></i> Fichas de treino</div>
    </div>
</main>

<div class="modal-backdrop" id="login-modal" role="dialog" aria-modal="true" aria-labelledby="login-title">
    <div class="login-card">
        <button type="button" class="modal-close" onclick="closeModal()" aria-label="Fechar">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <h2 id="login-title">Bem-vindo de volta</h2>
        <p class="subtitle">Acesse sua conta para continuar</p>

        @if(session('sucesso'))
        <div class="alert-success">
            <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i>{{ session('sucesso') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="form-group">
                <label for="login">E-mail ou CNPJ</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>
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
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="senha" id="password"
                           class="form-control"
                           placeholder="Sua senha"
                           required>
                    <button type="button" class="toggle-pass" onclick="togglePassword()" aria-label="Mostrar senha">
                        <i class="fa-solid fa-eye" id="toggle-icon"></i>
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
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    }

    @if($errors->any() || session('sucesso'))
        openModal();
    @endif
</script>

</body>
</html>
