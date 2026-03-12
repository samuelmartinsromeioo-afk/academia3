<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Personal | FIT SYS</title>

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
            --input-bg: rgba(255, 255, 255, 0.05);
            --error: #ff4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 10% 20%, rgba(212, 255, 0, 0.05) 0%, transparent 20%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .auth-container { width: 100%; max-width: 850px; }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .logo { font-family: 'Syncopate', sans-serif; font-size: 2rem; letter-spacing: 6px; color: var(--primary); }

        .auth-card {
            background: var(--card-bg);
            padding: 35px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 20px; }
        .full-width { grid-column: span 2; }

        .form-group label {
            display: block;
            font-size: .65rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0 15px;
        }

        .input-wrapper:focus-within { border-color: var(--primary); }

        .input-wrapper i { color: var(--text-dim); width: 20px; margin-right: 10px; }

        .input-wrapper input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 0;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
        }

        /* Estilo para erros do Laravel */
        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }

        .btn-register {
            grid-column: span 2;
            background: var(--primary);
            color: #000;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-weight: 800;
            cursor: pointer;
            text-transform: uppercase;
            margin-top: 15px;
        }

        .loading-cep { font-size: 0.7rem; color: var(--primary); display: none; margin-top: 4px; }
        
        input[type="file"] { color: var(--text-dim); font-size: 0.8rem; }
    </style>
</head>
<body>

<main class="auth-container">
    <div class="auth-header">
        <span class="logo">FIT SYS</span>
        <p style="color:var(--text-dim); margin-top:5px; font-size: 0.8rem;">CADASTRO DE PERSONAL TRAINER</p>
    </div>

    <div class="auth-card">
        @if ($errors->any())
            <div class="alert-error">
                <ul style="list-style: none;">
                    @foreach ($errors->all() as $error)
                        <li><i class="fa-solid fa-triangle-exclamation"></i> {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('personal.store') }}" method="POST" enctype="multipart/form-data" class="form-grid">
            @csrf

            <div class="form-group full-width">
                <label>Nome Completo</label>
                <div class="input-wrapper">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="nome" value="{{ old('nome') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>CPF</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" name="cpf" id="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00" required>
                </div>
            </div>

            <div class="form-group">
                <label>Data de Nascimento</label>
                <div class="input-wrapper">
                    <i class="fa-regular fa-calendar"></i>
                    <input type="date" name="idade" value="{{ old('idade') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>CEP</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-map-pin"></i>
                    <input type="text" name="cep" id="cep" value="{{ old('cep') }}" maxlength="9" placeholder="00000-000" required>
                </div>
                <div id="cep-loading" class="loading-cep"><i class="fa-solid fa-spinner fa-spin"></i> Buscando...</div>
            </div>

            <div class="form-group">
                <label>Cidade / Estado</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-city"></i>
                    <input type="text" id="display_cidade_estado" placeholder="Cidade - UF" readonly>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Logradouro / Bairro</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <input type="text" id="display_rua_bairro" placeholder="Rua, Bairro" readonly>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Número / Complemento</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-house-user"></i>
                    <input type="text" name="complemento" value="{{ old('complemento') }}" placeholder="Ex: Casa 10 ou Apto 12 Bloco B" required>
                </div>
            </div>

            <div class="form-group full-width">
                <label>E-mail Profissional</label>
                <div class="input-wrapper">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Senha</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="senha" required>
                </div>
            </div>

            <div class="form-group">
                <label>Confirmar Senha</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input type="password" name="senha_confirmation" required>
                </div>
            </div>

            <div class="form-group">
                <label>Valor por Sessão (R$)</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-dollar-sign"></i>
                    <input type="number" step="0.01" name="valor_secao" value="{{ old('valor_secao') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Certificado (PDF/IMG)</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-file-contract"></i>
                    <input type="file" name="certificado" required>
                </div>
            </div>

            <input type="hidden" name="rua" id="rua" value="{{ old('rua') }}">
            <input type="hidden" name="bairro" id="bairro" value="{{ old('bairro') }}">
            <input type="hidden" name="cidade" id="cidade" value="{{ old('cidade') }}">
            <input type="hidden" name="estado" id="estado" value="{{ old('estado') }}">

            <button type="submit" class="btn-register">Finalizar Cadastro Profissional</button>
        </form>
    </div>
</main>

<script>
    const cepInput = document.getElementById('cep');

    cepInput.addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length !== 8) return;

        document.getElementById('cep-loading').style.display = 'block';

        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(res => res.json())
            .then(data => {
                if (!data.erro) {
                    // Preenche os campos ocultos (que o Laravel valida)
                    document.getElementById('rua').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('estado').value = data.uf;

                    // Preenche o visual (que o usuário vê)
                    document.getElementById('display_rua_bairro').value = `${data.logradouro}, ${data.bairro}`;
                    document.getElementById('display_cidade_estado').value = `${data.localidade} - ${data.uf}`;
                }
            })
            .finally(() => {
                document.getElementById('cep-loading').style.display = 'none';
            });
    });
</script>

</body>
</html>