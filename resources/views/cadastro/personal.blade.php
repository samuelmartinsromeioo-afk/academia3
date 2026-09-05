<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Personal | SNR</title>
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

        /* --- PASSO 1: SELETOR DE TIPO DE PROFISSIONAL --- */
        .type-step { grid-column: span 2; margin-bottom: 10px; }
        .type-step-title {
            font-size: 0.75rem; color: var(--primary); font-weight: 800;
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;
        }
        .type-step-help { font-size: 0.72rem; color: var(--text-dim); margin-bottom: 14px; }
        .type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .type-card {
            background: var(--input-bg);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 16px; padding: 18px; cursor: pointer;
            display: flex; gap: 14px; align-items: flex-start; transition: 0.25s;
        }
        .type-card:hover { border-color: rgba(212,255,0,0.4); }
        .type-card.active {
            border-color: var(--primary);
            background: rgba(212,255,0,0.06);
            box-shadow: 0 0 0 1px var(--primary) inset;
        }
        .type-card i { font-size: 1.7rem; color: var(--primary); }
        .type-card h4 { font-size: 0.92rem; margin: 0 0 4px; }
        .type-card p { font-size: 0.72rem; color: var(--text-dim); line-height: 1.4; margin: 0; }

        /* --- CHIPS DE ESPECIALIDADE --- */
        .chips-section { grid-column: span 2; }
        .chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.75rem; padding: 8px 14px; border-radius: 20px;
            background: var(--input-bg); border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-dim); cursor: pointer; user-select: none; transition: 0.2s;
        }
        .chip.active { background: var(--primary); color: #000; border-color: var(--primary); font-weight: 700; }

        /* --- SELECT ESTILIZADO --- */
        .select-wrapper select {
            flex: 1; background: transparent; border: none; color: #fff;
            font-size: 0.9rem; outline: none; padding: 12px 0;
        }
        .select-wrapper select option { background: var(--card-bg); color: #fff; }

        /* --- BANNER DE DIFERENCIAIS (nutricionista) --- */
        .diferenciais {
            grid-column: span 2; display: none; gap: 10px;
            grid-template-columns: 1fr 1fr; margin-top: 6px;
        }
        .diferenciais.show { display: grid; }
        .dif-item {
            background: rgba(212,255,0,0.04); border: 1px solid rgba(212,255,0,0.18);
            border-radius: 12px; padding: 12px 14px; font-size: 0.74rem;
            color: var(--text-main); line-height: 1.45; display: flex; gap: 10px;
        }
        .dif-item i { color: var(--primary); font-size: 1rem; flex-shrink: 0; margin-top: 2px; }
        @media (max-width: 600px) { .type-grid, .diferenciais { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="ed-page">

<main class="auth-container">
    <div class="auth-header">
        <span class="logo"> SNR</span>
        <p style="color:var(--text-dim); margin-top:5px; font-size: 0.8rem;">CADASTRO DE PROFISSIONAL DA EDUCAÇÃO FÍSICA</p>
    </div>

    <div class="auth-card">
        @if ($errors->any())
            <div class="alert-error">
                <ul style="list-style: none;">
                    @foreach ($errors->all() as $error)
                        <li><i class="ph ph-warning"></i> {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('personal.store') }}" method="POST" enctype="multipart/form-data" class="form-grid">
            @csrf

            @php
                $tipos = config('textos.profissional.tipos');
                $tipoSel = old('professional_type', 'PERSONAL_TRAINER');
            @endphp

            <input type="hidden" name="professional_type" id="professional_type" value="{{ $tipoSel }}">

            <!-- PASSO 1: TIPO DE PROFISSIONAL -->
            <div class="type-step">
                <div class="type-step-title">{{ config('textos.profissional.seletor_titulo') }}</div>
                <div class="type-step-help">{{ config('textos.profissional.seletor_ajuda') }}</div>
                <div class="type-grid">
                    <div class="type-card" data-tipo="PERSONAL_TRAINER" onclick="selecionarTipo('PERSONAL_TRAINER')">
                        <i class="ph ph-barbell"></i>
                        <div>
                            <h4>{{ $tipos['PERSONAL_TRAINER']['label'] }}</h4>
                            <p>{{ $tipos['PERSONAL_TRAINER']['descricao'] }}</p>
                        </div>
                    </div>
                    <div class="type-card" data-tipo="NUTRITIONIST" onclick="selecionarTipo('NUTRITIONIST')">
                        <i class="ph ph-carrot"></i>
                        <div>
                            <h4>{{ $tipos['NUTRITIONIST']['label'] }}</h4>
                            <p>{{ $tipos['NUTRITIONIST']['descricao'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="full-width" style="border-top:1px solid rgba(255,255,255,0.06); padding-top:6px;">
                <div class="type-step-title" id="formTitulo">{{ $tipos[$tipoSel]['form_titulo'] }}</div>
            </div>

            <div class="form-group full-width">
                <label>Nome Completo</label>
                <div class="input-wrapper">
                    <i class="ph ph-user"></i>
                    <input type="text" name="nome" value="{{ old('nome') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>CPF</label>
                <div class="input-wrapper">
                    <i class="ph ph-identification-card"></i>
                    <input type="text" id="inputCpf" name="cpf" placeholder="000.000.000-00"
                        oninput="this.value = mascaras.cpf(this.value)"
                        onblur="validarCampoCpf(this)"
                        maxlength="14" required>
                </div>
                <span id="erroCpf" style="display:none; color:#ff4444; font-size:0.78rem; margin-top:4px;">
                    <i class="ph ph-warning-circle"></i> CPF inválido. Verifique os dígitos.
                </span>
            </div>

            <div class="form-group">
                <label>Data de Nascimento</label>
                <div class="input-wrapper">
                    <i class="ph ph-calendar"></i>
                    <input type="date" name="idade" value="{{ old('idade') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>CEP (de uma das academias em que atua)</label>
                <div class="input-wrapper">
                    <i class="ph ph-map-pin"></i>
                    <input type="text" name="cep" id="cep" placeholder="00000-000" 
                        oninput="this.value = mascaras.cep(this.value)" maxlength="9" required>
                </div>
                <div id="cep-loading" class="loading-cep"><i class="ph ph-spinner ph-spin"></i> Buscando...</div>
            </div>

            <div class="form-group">
                <label>Cidade / Estado (de uma das academias em que atua)</label>
                <div class="input-wrapper">
                    <i class="ph ph-buildings"></i>
                    <input type="text" id="display_cidade_estado" placeholder="Cidade - UF" readonly>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Logradouro / Bairro (de uma das academias em que atua)</label>
                <div class="input-wrapper">
                    <i class="ph ph-map-pin-area"></i>
                    <input type="text" id="display_rua_bairro" placeholder="Rua, Bairro" readonly>
                </div>
            </div>

            <div class="form-group full-width">
                <label>Número / Complemento (de uma das academias em que atua)</label>
                <div class="input-wrapper">
                    <i class="ph ph-house"></i>
                    <input type="text" name="complemento" value="{{ old('complemento') }}" placeholder="Ex: Casa 10 ou Apto 12 Bloco B" required>
                </div>
            </div>

            <div class="form-group full-width">
                <label>E-mail Profissional</label>
                <div class="input-wrapper">
                    <i class="ph ph-envelope"></i>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Senha</label>
                <div class="input-wrapper">
                    <i class="ph ph-lock"></i>
                    <input type="password" name="senha" required>
                </div>
            </div>

            <div class="form-group">
                <label>Confirmar Senha</label>
                <div class="input-wrapper">
                    <i class="ph ph-shield-check"></i>
                    <input type="password" name="senha_confirmation" required>
                </div>
            </div>

            <div class="form-group">
                <label><span id="valorLabel">Valor por Sessão</span> (R$)</label>
                <div class="input-wrapper">
                    <i class="ph ph-currency-dollar"></i>
                    <input type="number" step="0.01" name="valor_secao" value="{{ old('valor_secao') }}" required>
                </div>
            </div>

            <!-- CREF (Personal Trainer) -->
            <div class="form-group" data-tipo-only="PERSONAL_TRAINER" id="grupoCref">
                <label>CREF</label>
                <div class="input-wrapper">
                    <i class="ph ph-identification-badge"></i>
                    <input type="text" name="cref" value="{{ old('cref') }}" placeholder="{{ $tipos['PERSONAL_TRAINER']['conselho_placeholder'] }}">
                </div>
            </div>

            <!-- CRN (Nutricionista) -->
            <div class="form-group" data-tipo-only="NUTRITIONIST" id="grupoCrn" style="display:none;">
                <label>CRN <span style="color:var(--text-dim); font-weight:400; text-transform:none;">(com a região)</span></label>
                <div class="input-wrapper">
                    <i class="ph ph-identification-badge"></i>
                    <input type="text" name="crn" value="{{ old('crn') }}" placeholder="{{ $tipos['NUTRITIONIST']['conselho_placeholder'] }}">
                </div>
            </div>

            <div class="form-group full-width">
                <label>Foto do Profissional (IMG)</label>
                <div class="input-wrapper">
                    <i class="ph ph-image"></i>
                    <input type="file" name="foto" required>
                </div>
            </div>

            <!-- MODALIDADE DE ATENDIMENTO -->
            <div class="form-group full-width">
                <label>Modalidade de Atendimento</label>
                <div class="input-wrapper select-wrapper">
                    <i class="ph ph-devices"></i>
                    <select name="modalidade">
                        <option value="">Selecione...</option>
                        @foreach (config('textos.profissional.modalidades') as $mod)
                            <option value="{{ $mod }}" @selected(old('modalidade') === $mod)>{{ $mod }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- ESPECIALIDADES (multi-seleção via chips) -->
            @php
                $espPersonal = config('textos.profissional.especialidades.PERSONAL_TRAINER');
                $espNutri    = config('textos.profissional.especialidades.NUTRITIONIST');
                $espSel      = collect(old('especialidades', []));
            @endphp
            <div class="chips-section form-group">
                <label>Especialidades</label>
                <div class="chips" id="chipsEspecialidades"></div>
            </div>

            <!-- BIO / APRESENTAÇÃO -->
            <div class="form-group full-width">
                <label>Bio / Apresentação Profissional</label>
                <div class="textarea-wrapper">
                    <i class="ph ph-user-focus"></i>
                    <textarea name="bio" maxlength="2000" placeholder="Fale um pouco sobre a sua experiência e a forma como você atende.">{{ old('bio') }}</textarea>
                </div>
            </div>

            <!-- DIFERENCIAIS (nutricionista) -->
            <div class="diferenciais" id="diferenciais">
                <div class="dif-item"><i class="ph ph-shield-check"></i><span>{{ config('textos.profissional.diferenciais.confiabilidade') }}</span></div>
                <div class="dif-item"><i class="ph ph-export"></i><span>{{ config('textos.profissional.diferenciais.portabilidade') }}</span></div>
                <div class="dif-item"><i class="ph ph-megaphone"></i><span>{{ config('textos.profissional.diferenciais.escuta') }}</span></div>
                <div class="dif-item"><i class="ph ph-handshake"></i><span>{{ config('textos.profissional.diferenciais.transparencia') }}</span></div>
            </div>

            <!-- ✅ SEÇÃO DE ACADEMIAS -->
            <div class="academias-section" id="academiasSection" data-tipo-only="PERSONAL_TRAINER">
                <div class="academias-title">
                    <i class="ph ph-building"></i> Academias onde você atua
                </div>
                <div class="academias-hint">
                    <i class="ph ph-info"></i> Digite o nome de cada academia em uma linha separada. Máximo 10 academias. Não é necessário ser uma academia cadastrada na plataforma.
                </div>
                <div class="textarea-wrapper">
                    <i class="ph ph-note-pencil"></i>
                    <textarea name="academias" placeholder="Academia XYZ&#10;Fit Club Downtown&#10;Smart Fitness&#10;Strong Gym&#10;..." maxlength="1000">{{ old('academias') }}</textarea>
                </div>
            </div>

            <!-- CHECKBOX TERMOS DE USO -->
            <div class="terms-section">
                <input type="checkbox" id="termsCheckbox" name="aceita_termos" value="1" required>
                <label for="termsCheckbox">
                    Li e concordo com os
                    <a href="{{ route('termos.personal') }}" target="_blank">Termos de Uso do Personal</a>
                    e a
                    <a href="{{ route('lgpd.politica') }}" target="_blank">Política de Privacidade</a>
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
        loadingIcon.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Buscando endereço...';

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

                    loadingIcon.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Buscando localização no mapa...';

                    const enderecoQuery = `${data.logradouro}, ${data.localidade}, ${data.uf}, Brasil`;

                    fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(enderecoQuery)}&format=json&limit=1`)
                        .then(r => r.json())
                        .then(results => {
                            if (results.length > 0) {
                                document.getElementById('latitude').value  = results[0].lat;
                                document.getElementById('longitude').value = results[0].lon;
                                loadingIcon.innerHTML = '<i class="ph ph-check" style="color:#d4ff00"></i> Localização confirmada!';
                            } else {
                                loadingIcon.innerHTML = '<i class="ph ph-warning" style="color:orange"></i> Endereço achado, mas sem GPS preciso.';
                            }
                        })
                        .catch(() => {
                            loadingIcon.innerHTML = '<i class="ph ph-x" style="color:red"></i> Erro ao buscar GPS.';
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
    function validarCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        for (let t = 9; t < 11; t++) {
            let soma = 0;
            for (let i = 0; i < t; i++) soma += parseInt(cpf[i]) * ((t + 1) - i);
            let resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf[t])) return false;
        }
        return true;
    }

    function validarCampoCpf(input) {
        const cpf = input.value.replace(/\D/g, '');
        const erro = document.getElementById('erroCpf');
        if (cpf.length === 11 && !validarCPF(cpf)) {
            input.style.borderColor = '#ff4444';
            if (erro) { erro.style.display = 'block'; }
        } else {
            input.style.borderColor = '';
            if (erro) { erro.style.display = 'none'; }
        }
    }

    // Bloqueia envio do form se CPF inválido
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form.form-grid');
        if (form) {
            form.addEventListener('submit', function (e) {
                const input = document.getElementById('inputCpf');
                if (input && !validarCPF(input.value)) {
                    e.preventDefault();
                    input.style.borderColor = '#ff4444';
                    const erro = document.getElementById('erroCpf');
                    if (erro) { erro.style.display = 'block'; }
                    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }
    });

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

<script>
    // Dados vindos do config/textos.php (memory: usar json_encode em <script>).
    const ESPECIALIDADES = {!! json_encode([
        'PERSONAL_TRAINER' => $espPersonal,
        'NUTRITIONIST'     => $espNutri,
    ]) !!};
    const TIPOS = {!! json_encode($tipos) !!};
    const ESP_SELECIONADAS = new Set({!! json_encode($espSel->values()) !!});

    function renderChips(tipo) {
        const box = document.getElementById('chipsEspecialidades');
        box.innerHTML = '';
        (ESPECIALIDADES[tipo] || []).forEach(function (nome) {
            const chip = document.createElement('div');
            chip.className = 'chip' + (ESP_SELECIONADAS.has(nome) ? ' active' : '');
            chip.innerHTML = '<i class="ph ph-check"></i>' + nome;
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'especialidades[]';
            input.value = nome;
            input.style.display = 'none';
            input.checked = ESP_SELECIONADAS.has(nome);
            chip.appendChild(input);
            chip.addEventListener('click', function () {
                input.checked = !input.checked;
                chip.classList.toggle('active', input.checked);
                if (input.checked) ESP_SELECIONADAS.add(nome); else ESP_SELECIONADAS.delete(nome);
            });
            box.appendChild(chip);
        });
    }

    function selecionarTipo(tipo) {
        document.getElementById('professional_type').value = tipo;

        document.querySelectorAll('.type-card').forEach(function (c) {
            c.classList.toggle('active', c.dataset.tipo === tipo);
        });

        // Mostra/esconde blocos condicionais e ajusta o "required".
        document.querySelectorAll('[data-tipo-only]').forEach(function (el) {
            const mostra = el.dataset.tipoOnly === tipo;
            el.style.display = mostra ? '' : 'none';
            el.querySelectorAll('input, select, textarea').forEach(function (f) {
                f.disabled = !mostra; // desabilita não envia (evita CREF vazio p/ nutri)
            });
        });

        // Conselho obrigatório conforme o tipo.
        const cref = document.querySelector('#grupoCref input');
        const crn  = document.querySelector('#grupoCrn input');
        if (cref) cref.required = (tipo === 'PERSONAL_TRAINER');
        if (crn)  crn.required  = (tipo === 'NUTRITIONIST');

        document.getElementById('formTitulo').textContent = TIPOS[tipo].form_titulo;
        document.getElementById('valorLabel').textContent =
            (tipo === 'NUTRITIONIST') ? 'Valor por Consulta' : 'Valor por Sessão';
        document.getElementById('diferenciais').classList.toggle('show', tipo === 'NUTRITIONIST');

        renderChips(tipo);
    }

    // Estado inicial (respeita old() após erro de validação).
    document.addEventListener('DOMContentLoaded', function () {
        selecionarTipo(document.getElementById('professional_type').value || 'PERSONAL_TRAINER');
    });
</script>

</body>
</html>
