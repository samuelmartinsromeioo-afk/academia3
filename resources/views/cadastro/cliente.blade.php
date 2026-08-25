<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Cliente | SNR</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">

    <style>
        :root {
            --primary: #d4ff00;
            --primary-hover: #b8de00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
            --input-bg: rgba(255, 255, 255, 0.05);
            --error: #ff4444;
            --success: #00c853;
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
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .auth-container { width: 100%; max-width: 850px; animation: fadeIn 0.6s ease-out; }

        .auth-header { text-align: center; margin-bottom: 30px; }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 2.2rem;
            letter-spacing: 6px;
            color: var(--primary);
            text-transform: uppercase;
        }

        .auth-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .section-title {
            grid-column: span 2;
            font-size: 0.8rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 15px 0 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 5px;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 20px; }
        .full-width { grid-column: span 2; }

        .form-group label {
            display: block;
            font-size: .65rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 6px;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0 15px;
            transition: 0.3s;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary);
            background: rgba(212, 255, 0, 0.03);
        }

        .input-wrapper i { color: var(--text-dim); width: 20px; text-align: center; margin-right: 10px; font-size: 0.9rem; }

        .input-wrapper input, .input-wrapper select, .input-wrapper textarea {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 0;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
        }

        textarea { resize: none; }

        .btn-register {
            grid-column: span 2;
            background: var(--primary);
            color: #000;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-weight: 800;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-register:hover { transform: translateY(-2px); background: var(--primary-hover); }

        .loading-text { font-size: 0.7rem; color: var(--primary); display: none; margin-top: 4px; }

        /* ✅ NOVO: ESTILOS DO CHECKBOX DE TERMOS */
        .terms-section {
            grid-column: span 2;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 25px 0;
            padding: 15px;
            background: rgba(212, 255, 0, 0.03);
            border: 1px solid rgba(212, 255, 0, 0.2);
            border-radius: 12px;
        }

        .terms-section input[type="checkbox"] {
            width: 20px;
            height: 20px;
            min-width: 20px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .terms-section label {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-main);
            text-transform: none;
            font-weight: 400;
            letter-spacing: normal;
            line-height: 1.5;
            cursor: pointer;
        }

        .terms-section a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }

        .terms-section a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 600px) { 
            .form-grid { grid-template-columns: 1fr; } 
            .full-width, .section-title { grid-column: span 1; }
            .terms-section { flex-direction: column; }
        }
    </style>
</head>
<body class="ed-page">

<main class="auth-container">
    <div class="auth-header">
        <span class="logo">SNR</span>
        <p style="color:var(--text-dim); margin-top:5px; font-size: 0.9rem;">CADASTRO DE NOVO ALUNO</p>
    </div>

    <div class="auth-card">
        <form action="{{ route('cliente.store') }}" method="POST" class="form-grid">
                @if ($errors->any())
                    <div style="background: rgba(255,0,0,0.2); border: 1px solid #ff4444; color: #fff; padding: 15px; border-radius: 10px; margin-bottom: 20px; grid-column: span 2;">
                        <strong>Erro ao cadastrar:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @csrf
            <div class="section-title">Informações Pessoais</div>

            <div class="form-group full-width">
                <label>Nome Completo</label>
                <div class="input-wrapper">
                    <i class="ph ph-user"></i>
                    <input type="text" name="nome" value="{{ old('nome') }}" required placeholder="Ex: João Silva">
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="input-wrapper">
                    <i class="ph ph-envelope"></i>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="email@exemplo.com">
                </div>
            </div>

            <div class="form-group">
                <label>Senha</label>
                <div class="input-wrapper">
                    <i class="ph ph-lock"></i>
                    <input type="password" name="senha" required placeholder="••••••••">
                </div>
            </div>
            
            <div class="form-group">
                <label>Data de Nascimento</label>
                <div class="input-wrapper">
                    <i class="ph ph-calendar"></i>
                    <input type="date" name="idade" value="{{ old('idade') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Sexo</label>
                <div class="input-wrapper">
                    <i class="ph ph-gender-intersex"></i>
                    <select name="sexo" required>
                        <option value="">Selecione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Feminino">Feminino</option>
                        <option value="Prefiro não informar">Outro</option>
                    </select>
                </div>
            </div>

            <div class="section-title">Localização</div>

            <div class="form-group">
                <label>CEP</label>
                <div class="input-wrapper">
                    <i class="ph ph-map-pin"></i>
                    <input type="text" name="cep" id="cep" placeholder="00000-000" 
                        oninput="this.value = mascaras.cep(this.value)" maxlength="9" required>
                </div>
                <div id="cep-loading" class="loading-text"><i class="ph ph-spinner ph-spin"></i> Buscando endereço...</div>
            </div>

            <div class="form-group">
                <label>Rua/Logradouro</label>
                <div class="input-wrapper">
                    <input type="text" name="rua" id="rua" readonly>
                </div>
            </div>

            <div class="form-group">
                <label>Bairro</label>
                <div class="input-wrapper">
                    <input type="text" name="bairro" id="bairro" readonly>
                </div>
            </div>

            <div class="form-group">
                <label>Cidade / UF</label>
                <div class="input-wrapper">
                    <input type="text" name="cidade" id="cidade" readonly style="width:70%">
                    <input type="text" name="estado" id="estado" readonly style="width:30%; text-align:center; border-left:1px solid rgba(255,255,255,0.1)">
                </div>
            </div>

            <div class="form-group">
                <label>Número / Complemento</label>
                <div class="input-wrapper">
                    <i class="ph ph-house"></i>
                    <input type="text" name="complemento" placeholder="Ex: 120 ou Ap 12">
                </div>
            </div>

            <div class="section-title">Perfil Físico & Objetivo</div>

            <div class="form-group">
                <label>Altura (m)</label>
                <div class="input-wrapper">
                    <i class="ph ph-ruler"></i>
                    <input type="number" step="0.01" name="altura" value="{{ old('altura') }}" placeholder="1.75">
                </div>
            </div>

            <div class="form-group">
                <label>Peso (kg)</label>
                <div class="input-wrapper">
                    <i class="ph ph-scales"></i>
                    <input type="number" step="0.01" name="peso" value="{{ old('peso') }}" placeholder="70.5">
                </div>
            </div>

            <div class="form-group full-width">
                <label>Resumo do Objetivo</label>
                <div class="input-wrapper">
                    <textarea name="resumo_objetivo" rows="2" placeholder="Ex: Ganho de massa muscular e melhora no condicionamento.">{{ old('resumo_objetivo') }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label>Frequência Semanal</label>
                <div class="input-wrapper">
                    <i class="ph ph-barbell"></i>
                    <input type="number" name="frequencia_semanal" value="{{ old('frequencia_semanal') }}" required>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Condição Clínica</label>
                <div class="input-wrapper">
                    <textarea name="condicao_clinica" rows="2" placeholder="Ex: Hipertensão, lesão no joelho, etc.">{{ old('condicao_clinica') }}</textarea>
                </div>
            </div>

            <!-- ✅ NOVO: CHECKBOX TERMOS DE USO -->
            <div class="terms-section">
                <input type="checkbox" id="termsCheckbox" name="aceita_termos" value="1" required>
                <label for="termsCheckbox">
                    Li e concordo com os
                    <a href="{{ route('termos.aluno') }}" target="_blank">Termos de Uso</a>
                    e a
                    <a href="{{ route('lgpd.politica') }}" target="_blank">Política de Privacidade</a>
                </label>
            </div>

            <button type="submit" class="btn-register">
                Finalizar Cadastro <i class="ph ph-arrow-right"></i>
            </button>
        </form>
    </div>
</main>

<script>
    // Máscara e Busca de CEP
    const cepInput = document.getElementById('cep');
    
    cepInput.addEventListener('input', (e) => {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 5) value = value.replace(/^(\d{5})(\d)/, '$1-$2');
        e.target.value = value;
    });

    cepInput.addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length !== 8) return;

        const loading = document.getElementById('cep-loading');
        loading.style.display = 'block';

        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(res => res.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('rua').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('estado').value = data.uf;
                }
            })
            .catch(() => console.error("Erro ao buscar CEP"))
            .finally(() => loading.style.display = 'none');
    });
</script>
<script>
    const mascaras = {
        cpf: function(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        },
        cnpj: function(value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1/$2')
                .replace(/(\d{4})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        },
        telefone: function(value) {
            return value
                .replace(/\D/g, '') 
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4,5})(\d{4})/, '$1-$2')
                .replace(/(-\d{4})\d+?$/, '$1');
        },
        cep: function(value) {
            return value
                .replace(/\D/g, '') 
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{3})\d+?$/, '$1');
        }
    }
</script>

</body>
</html>
