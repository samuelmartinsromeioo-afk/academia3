<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Personal | NR Fit</title>

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

        /* Estilo para textarea de academias */
        .textarea-wrapper {
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 15px;
            display: flex;
            gap: 10px;
        }

        .textarea-wrapper:focus-within { border-color: var(--primary); }
        .textarea-wrapper i { color: var(--text-dim); flex-shrink: 0; margin-top: 5px; }
        .textarea-wrapper textarea {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            font-family: 'Inter', sans-serif;
            resize: vertical;
            min-height: 100px;
        }

        .textarea-wrapper textarea::placeholder {
            color: var(--text-dim);
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
            transition: 0.3s;
        }

        .btn-register:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        .loading-cep { font-size: 0.7rem; color: var(--primary); display: none; margin-top: 4px; }
        input[type="file"] { color: var(--text-dim); font-size: 0.8rem; }

        /* --- NOVOS ESTILOS PARA ACADEMIAS --- */
        .academias-section {
            grid-column: span 2;
            margin-top: 10px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 16px;
            border: 1px dashed rgba(212, 255, 0, 0.2);
        }

        .academias-title {
            font-size: 0.75rem;
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .academias-hint {
            font-size: 0.6rem;
            color: var(--text-dim);
            margin-bottom: 12px;
            line-height: 1.4;
        }

        /* --- NOVOS ESTILOS PARA PACOTES --- */
        .packages-section {
            grid-column: span 2;
            margin-top: 10px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 16px;
            border: 1px dashed rgba(212, 255, 0, 0.2);
        }

        .packages-title {
            font-size: 0.75rem;
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 15px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .package-item {
            background: var(--bg-dark);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 10px;
        }

        .package-item label {
            font-size: 0.55rem !important;
            color: var(--text-dim) !important;
            margin-bottom: 4px !important;
        }

        .package-input-group {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .package-input-group span { color: var(--primary); font-weight: bold; font-size: 0.8rem; }
        .package-input-group input {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 0.85rem;
            width: 100%;
            outline: none;
        }

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

        @media (max-width: 600px) {
            .packages-grid { grid-template-columns: repeat(2, 1fr); }
            .terms-section { flex-direction: column; }
            .textarea-wrapper { flex-direction: column; }
        }
    </style>
</head>
<body>

<main class="auth-container">
    <div class="auth-header">
        <span class="logo"> NR Fit</span>
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
                    <input type="text" name="cpf" placeholder="000.000.000-00" 
                        oninput="this.value = mascaras.cpf(this.value)" maxlength="14" required>
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
                <label>CEP (de uma das academias em que atua)</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-map-pin"></i>
                    <input type="text" name="cep" id="cep" placeholder="00000-000" 
                        oninput="this.value = mascaras.cep(this.value)" maxlength="9" required>
                </div>
                <div id="cep-loading" class="loading-cep"><i class="fa-solid fa-spinner fa-spin"></i> Buscando...</div>
            </div>

            <div class="form-group">
                <label>Cidade / Estado (de uma das academias em que atua)</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-city"></i>
                    <input type="text" id="display_cidade_estado" placeholder="Cidade - UF" readonly>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Logradouro / Bairro (de uma das academias em que atua)</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <input type="text" id="display_rua_bairro" placeholder="Rua, Bairro" readonly>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Número / Complemento (de uma das academias em que atua)</label>
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

            <div class="form-group full-width">
                <label>Foto do Profissional (IMG)</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-image"></i>
                    <input type="file" name="foto" required>
                </div>
            </div>

            <!-- ✅ SEÇÃO DE ACADEMIAS -->
            <div class="academias-section">
                <div class="academias-title">
                    <i class="fa-solid fa-building"></i> Academias onde você atua
                </div>
                <div class="academias-hint">
                    <i class="fa-solid fa-info-circle"></i> Digite o nome de cada academia em uma linha separada. Máximo 10 academias. Não é necessário ser uma academia cadastrada na plataforma.
                </div>
                <div class="textarea-wrapper">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <textarea name="academias" placeholder="Academia XYZ&#10;Fit Club Downtown&#10;Smart Fitness&#10;Strong Gym&#10;..." maxlength="1000">{{ old('academias') }}</textarea>
                </div>
            </div>

            <!-- SEÇÃO DE PACOTES -->
            <div class="packages-section">
                <div class="packages-title">
                    <i class="fa-solid fa-tags"></i> Definir Pacotes Mensais
                </div>
                <div class="packages-grid">
                    @for ($i = 1; $i <= 5; $i++)
                    <div class="package-item">
                        <label>{{ $i }}x na Semana</label>
                        <div class="package-input-group">
                            <span>R$</span>
                            <input type="number" step="0.01" name="precos[{{ $i }}]" placeholder="0,00" value="{{ old('precos.'.$i) }}">
                        </div>
                    </div>
                    @endfor
                </div>
                <p style="font-size: 0.6rem; color: var(--text-dim); margin-top: 10px;">* Informe o valor total mensal para cada frequência.</p>
            </div>

            <!-- CHECKBOX TERMOS DE USO -->
            <div class="terms-section">
                <input type="checkbox" id="termsCheckbox" name="aceita_termos" value="1" required>
                <label for="termsCheckbox">
                    Li e concordo com os
                    <a href="{{ route('termos') }}" target="_blank">Termos de Uso</a>
                    e a
                    <a href="{{ route('privacidade') }}" target="_blank">Política de Privacidade</a>
                </label>
            </div>

            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
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

        const loadingIcon = document.getElementById('cep-loading');
        loadingIcon.style.display = 'block';
        loadingIcon.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Buscando endereço...';

        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(res => res.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('rua').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('estado').value = data.uf;

                    document.getElementById('display_rua_bairro').value = `${data.logradouro}, ${data.bairro}`;
                    document.getElementById('display_cidade_estado').value = `${data.localidade} - ${data.uf}`;

                    loadingIcon.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Buscando localização no mapa...';

                    const enderecoQuery = `${data.logradouro}, ${data.localidade}, ${data.uf}, Brasil`;

                    fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(enderecoQuery)}&format=json&limit=1`)
                        .then(r => r.json())
                        .then(results => {
                            if (results.length > 0) {
                                document.getElementById('latitude').value  = results[0].lat;
                                document.getElementById('longitude').value = results[0].lon;
                                loadingIcon.innerHTML = '<i class="fa-solid fa-check" style="color:#d4ff00"></i> Localização confirmada!';
                            } else {
                                loadingIcon.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color:orange"></i> Endereço achado, mas sem GPS preciso.';
                            }
                        })
                        .catch(() => {
                            loadingIcon.innerHTML = '<i class="fa-solid fa-xmark" style="color:red"></i> Erro ao buscar GPS.';
                        })
                        .finally(() => {
                            setTimeout(() => loadingIcon.style.display = 'none', 3000);
                        });
                } else {
                    loadingIcon.style.display = 'none';
                }
            })
            .catch(err => {
                console.error("Erro no CEP");
                loadingIcon.style.display = 'none';
            });
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