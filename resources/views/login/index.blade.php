<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | FitSys — Encontre seu personal</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #d4ff00;
            --primary-soft: rgba(212, 255, 0, 0.12);
            --bg-dark: #0a0b0d;
            --panel-bg: #0d0f12;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
            --error: #ff4d4d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        .page {
            display: flex;
            min-height: 100vh;
        }

        /* ===== Painel de marca (esquerda) ===== */
        .brand-panel {
            flex: 1.1;
            background:
                radial-gradient(circle at 15% 15%, rgba(212, 255, 0, 0.10) 0%, transparent 40%),
                radial-gradient(circle at 85% 90%, rgba(212, 255, 0, 0.07) 0%, transparent 45%),
                var(--panel-bg);
            border-right: 1px solid rgba(255,255,255,0.05);
            padding: 56px 64px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        .brand-panel > * { position: relative; z-index: 1; }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Syncopate', sans-serif;
            font-size: 1.35rem;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .logo img { height: 38px; width: 38px; object-fit: contain; }
        .logo span { color: var(--primary); }

        .hero h1 {
            font-size: clamp(1.9rem, 3vw, 2.7rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 16px;
        }

        .hero h1 em {
            font-style: normal;
            color: var(--primary);
        }

        .hero > p {
            color: var(--text-dim);
            font-size: 1.05rem;
            max-width: 480px;
            line-height: 1.6;
            margin-bottom: 36px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            max-width: 520px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.25s;
        }

        .feature:hover {
            border-color: rgba(212,255,0,0.4);
            transform: translateY(-2px);
        }

        .feature i {
            color: var(--primary);
            background: var(--primary-soft);
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .panel-footer {
            color: var(--text-dim);
            font-size: 0.85rem;
        }

        .panel-footer strong { color: var(--text-main); font-weight: 600; }

        /* ===== Lado do formulário (direita) ===== */
        .form-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        .mobile-logo { display: none; }

        .login-card h2 {
            font-size: 1.85rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .login-card .subtitle {
            color: var(--text-dim);
            margin-bottom: 32px;
            font-size: 0.95rem;
        }

        .alert-success {
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.3);
            color: #22c55e;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.88rem;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-dim);
            display: block;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrap { position: relative; }

        .input-wrap > i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 0.9rem;
            pointer-events: none;
            transition: 0.25s;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 44px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            background: var(--card-bg);
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
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
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-dim);
            cursor: pointer;
            padding: 10px;
            font-size: 0.9rem;
        }

        .toggle-pass:hover { color: var(--text-main); }

        .row-between {
            display: flex;
            justify-content: flex-end;
            margin: -8px 0 20px;
        }

        .row-between a {
            color: var(--text-dim);
            font-size: 0.85rem;
            text-decoration: none;
            transition: 0.2s;
        }

        .row-between a:hover { color: var(--primary); }

        .btn-login {
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: var(--bg-dark);
            font-family: inherit;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(212,255,0,0.25);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 26px 0;
            color: #565d6b;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid rgba(212,255,0,0.35);
            background: transparent;
            color: var(--primary);
            font-family: inherit;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: 0.25s;
        }

        .btn-register:hover {
            background: var(--primary-soft);
            border-color: var(--primary);
        }

        .error-message {
            color: var(--error);
            font-size: 0.8rem;
            margin-top: 6px;
        }

        /* ===== Responsivo ===== */
        @media (max-width: 900px) {
            .brand-panel { display: none; }

            .form-side {
                background:
                    radial-gradient(circle at 10% 10%, rgba(212, 255, 0, 0.08) 0%, transparent 35%),
                    radial-gradient(circle at 90% 90%, rgba(212, 255, 0, 0.06) 0%, transparent 40%);
            }

            .mobile-logo {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                font-family: 'Syncopate', sans-serif;
                font-size: 1.15rem;
                letter-spacing: 3px;
                text-transform: uppercase;
                margin-bottom: 28px;
            }

            .mobile-logo img { height: 32px; width: 32px; object-fit: contain; }
            .mobile-logo span { color: var(--primary); }
        }
    </style>
</head>
<body>

<div class="page">

    <aside class="brand-panel">
        <div class="logo">
            <img src="{{ asset('SnrFit.png') }}" alt="FitSys">
            <div>FIT<span>SYS</span></div>
        </div>

        <div class="hero">
            <h1>Conecte-se ao <em>personal ideal</em> e evolua de verdade.</h1>
            <p>O marketplace que aproxima alunos e personal trainers — e dá ao profissional o controle total do seu negócio.</p>

            <div class="features">
                <div class="feature">
                    <i class="fa-solid fa-calendar-check"></i>
                    Agenda inteligente
                </div>
                <div class="feature">
                    <i class="fa-solid fa-users"></i>
                    Gestão de alunos
                </div>
                <div class="feature">
                    <i class="fa-solid fa-chart-line"></i>
                    Controle financeiro
                </div>
                <div class="feature">
                    <i class="fa-solid fa-bullhorn"></i>
                    Divulgação do serviço
                </div>
                <div class="feature">
                    <i class="fa-solid fa-dumbbell"></i>
                    Fichas de treino
                </div>
                <div class="feature">
                    <i class="fa-solid fa-handshake"></i>
                    Alunos e personais conectados
                </div>
            </div>
        </div>

        <div class="panel-footer">
            <strong>Treine. Gerencie. Cresça.</strong> — tudo em um só lugar.
        </div>
    </aside>

    <main class="form-side">
        <div class="login-card">

            <div class="mobile-logo">
                <img src="{{ asset('SnrFit.png') }}" alt="FitSys">
                <div>FIT<span>SYS</span></div>
            </div>

            <h2>Bem-vindo de volta</h2>
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
                               required autofocus>
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

                <div class="row-between">
                    <a href="{{ route('senha.solicitar.form') }}">Esqueceu sua senha?</a>
                </div>

                <button type="submit" class="btn-login">
                    Entrar <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="divider">Novo por aqui?</div>

            <a href="{{ route('cadastro.SelecaoCadastro') }}" class="btn-register">
                Criar minha conta gratuitamente
            </a>
        </div>
    </main>

</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('toggle-icon');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    }
</script>

</body>
</html>
