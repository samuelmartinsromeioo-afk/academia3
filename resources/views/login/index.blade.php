<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema Fitness</title>

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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        header {
            padding: 2rem;
            text-align: center;
        }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 4px;
            color: var(--primary);
            text-transform: uppercase;
        }

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .login-card {
            background: var(--card-bg);
            padding: 50px;
            border-radius: 24px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
        }

        .login-card:hover {
            border-color: var(--primary);
            box-shadow: 0 0 25px rgba(212,255,0,0.1);
        }

        .login-card h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .login-card p {
            color: var(--text-dim);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

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

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(212,255,0,0.2);
        }

        .extra-links {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .extra-links a {
            color: var(--primary);
            text-decoration: none;
        }

        .error-message {
            color: var(--error);
            font-size: 0.8rem;
            margin-top: 5px;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">FIT<span>SYS</span></div>
</header>

<div class="login-container">
    <div class="login-card">
        <h2>Bem-vindo de volta</h2>
        <p>Acesse sua conta para continuar</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" class="form-control" required autofocus>
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" name="senha" class="form-control" required>
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-login">
                Entrar
            </button>

            <div class="extra-links">
                Não tem conta?
                <a href="{{ route('cadastro.SelecaoCadastro') }}">Cadastre-se</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>