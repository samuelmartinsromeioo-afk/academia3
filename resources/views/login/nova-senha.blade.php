<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha | FitSys</title>
 
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
        .card p { color: var(--text-dim); margin-bottom: 30px; }
 
        .icon-key {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: rgba(212,255,0,0.1);
            border-radius: 16px;
            margin-bottom: 20px;
        }
 
        .icon-key i { font-size: 1.6rem; color: var(--primary); }
 
        .form-group { margin-bottom: 20px; position: relative; }
 
        .form-group label {
            font-size: 0.85rem;
            color: var(--text-dim);
            display: block;
            margin-bottom: 6px;
        }
 
        .input-wrapper { position: relative; }
 
        .form-control {
            width: 100%;
            padding: 12px 44px 12px 15px;
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
 
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-dim);
            background: none;
            border: none;
            padding: 0;
            font-size: 1rem;
            transition: 0.2s;
        }
 
        .toggle-password:hover { color: var(--primary); }
 
        .password-hint {
            font-size: 0.75rem;
            color: var(--text-dim);
            margin-top: 5px;
        }
 
        .strength-bar {
            height: 4px;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            margin-top: 8px;
            overflow: hidden;
        }
 
        .strength-fill {
            height: 100%;
            border-radius: 4px;
            transition: 0.3s;
            width: 0%;
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
 
        .error-message { color: var(--error); font-size: 0.8rem; margin-top: 5px; }
 
        .alert-error {
            background: rgba(255,77,77,0.1);
            border: 1px solid rgba(255,77,77,0.3);
            color: var(--error);
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
 
        @media (max-width: 480px) { .card { padding: 30px; } }
    </style>
</head>
<body>
 
<header>
    <a href="{{ route('login.create') }}" style="text-decoration:none;">
        <div class="logo">FIT<span>SYS</span></div>
    </a>
</header>
 
<div class="container">
    <div class="card">
 
        <div class="icon-key">
            <i class="fa-solid fa-key"></i>
        </div>
 
        <h2>Nova senha</h2>
        <p>Crie uma senha forte para proteger sua conta.</p>
 
        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $error)
                    <div><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif
 
        <form method="POST" action="{{ route('senha.resetar') }}">
            @csrf
 
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
 
            <div class="form-group">
                <label for="senha">Nova senha</label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        name="senha"
                        id="senha"
                        class="form-control"
                        placeholder="Mínimo 6 caracteres"
                        required
                        autofocus
                    >
                    <button type="button" class="toggle-password" onclick="toggleVisibility('senha', this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <div class="password-hint" id="strengthText">Digite sua senha</div>
                @error('senha')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
 
            <div class="form-group">
                <label for="senha_confirmation">Confirmar nova senha</label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        name="senha_confirmation"
                        id="senha_confirmation"
                        class="form-control"
                        placeholder="Repita a nova senha"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="toggleVisibility('senha_confirmation', this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                @error('senha_confirmation')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
 
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-check me-2"></i> Salvar nova senha
            </button>
        </form>
 
    </div>
</div>
 
<script>
    function toggleVisibility(fieldId, btn) {
        const input = document.getElementById(fieldId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
 
    // Indicador de força da senha
    document.getElementById('senha').addEventListener('input', function () {
        const val = this.value;
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        let strength = 0;
 
        if (val.length >= 6)  strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;
 
        const levels = [
            { pct: '0%',   color: 'transparent',  label: 'Digite sua senha' },
            { pct: '25%',  color: '#ff4d4d',       label: 'Muito fraca' },
            { pct: '50%',  color: '#ffa500',       label: 'Fraca' },
            { pct: '75%',  color: '#d4ff00',       label: 'Boa' },
            { pct: '90%',  color: '#22c55e',       label: 'Forte' },
            { pct: '100%', color: '#22c55e',       label: 'Muito forte' },
        ];
 
        const level = levels[strength] || levels[0];
        fill.style.width = level.pct;
        fill.style.background = level.color;
        text.textContent = level.label;
        text.style.color = level.color === 'transparent' ? 'var(--text-dim)' : level.color;
    });
</script>
 
</body>
</html>