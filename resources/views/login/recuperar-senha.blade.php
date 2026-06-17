<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
            --error: #ff4d4d;
            --success: #22c55e;
        }
 
        * { margin: 0; padding: 0; box-sizing: border-box; }
 
        body {
            background-color: var(--bg-dark);
            background-image:
                radial-gradient(circle at 10% 20%, rgba(212, 255, 0, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(212, 255, 0, 0.05) 0%, transparent 20%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
 
        header { padding: 2rem; text-align: center; }
 
        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 4px;
            color: var(--primary);
            text-transform: uppercase;
        }
 
        .container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
 
        .card {
            background: var(--card-bg);
            padding: 50px;
            border-radius: 24px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
        }
 
        .card:hover {
            border-color: var(--primary);
            box-shadow: 0 0 25px rgba(212,255,0,0.1);
        }
 
        .card h2 { font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; }
        .card p { color: var(--text-dim); margin-bottom: 30px; line-height: 1.5; }
 
        .icon-lock {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: rgba(212,255,0,0.1);
            border-radius: 16px;
            margin-bottom: 20px;
        }
 
        .icon-lock i { font-size: 1.6rem; color: var(--primary); }
 
        .form-group { margin-bottom: 20px; }
 
        .form-group label {
            font-size: 0.85rem;
            color: var(--text-dim);
            display: block;
            margin-bottom: 6px;
        }
 
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            background: #0f1115;
            color: var(--text-main);
            outline: none;
            transition: 0.3s;
        }
 
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(212,255,0,0.2);
        }
 
        .btn-primary {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: var(--bg-dark);
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
 
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(212,255,0,0.2);
        }
 
        .back-link {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9rem;
        }
 
        .back-link a { color: var(--primary); text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
 
        .error-message { color: var(--error); font-size: 0.8rem; margin-top: 5px; }
 
        .alert-success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            color: var(--success);
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
 
        @media (max-width: 480px) { .card { padding: 30px; } }
    </style>
</head>
<body>
 
<header>
    <a href="{{ route('login.create') }}" style="text-decoration:none;">
        <div class="logo">SNR<span>FIT</span></div>
    </a>
</header>
 
<div class="container">
    <div class="card">
 
        <div class="icon-lock">
            <i class="fa-solid fa-lock"></i>
        </div>
 
        <h2>Esqueceu a senha?</h2>
        <p>Informe o e-mail da sua conta e enviaremos um link para redefinir sua senha.</p>
 
        @if(session('sucesso'))
            <div class="alert-success">
                <i class="fa-solid fa-circle-check" style="margin-top:2px; flex-shrink:0;"></i>
                <span>{{ session('sucesso') }}</span>
            </div>
        @endif
 
        <form method="POST" action="{{ route('senha.solicitar') }}">
            @csrf
 
            <div class="form-group">
                <label for="email">E-mail cadastrado</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control"
                    placeholder="seu@email.com"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
 
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-paper-plane me-2"></i> Enviar link de recuperação
            </button>
        </form>
 
        <div class="back-link">
            <a href="{{ route('login.create') }}">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao login
            </a>
        </div>
 
    </div>
</div>
 
</body>
</html>
