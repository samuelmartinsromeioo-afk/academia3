<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Academia | FIT SYS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { --primary: #d4ff00; --bg-dark: #0a0b0d; --card-bg: #16181d; --text-main: #ffffff; --error: #ff4444; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); padding: 40px 20px; }
        .auth-card { background: var(--card-bg); max-width: 800px; margin: 0 auto; padding: 40px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        .form-group label { display: block; font-size: 0.8rem; color: var(--primary); margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
        .input-wrapper { display: flex; align-items: center; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 0 15px; }
        .input-wrapper input, .input-wrapper textarea { flex: 1; background: transparent; border: none; padding: 12px 0; color: #fff; outline: none; }
        .input-wrapper i { margin-right: 10px; color: #666; width: 20px; text-align: center; }
        .btn-submit { background: var(--primary); color: #000; width: 100%; padding: 18px; border-radius: 12px; font-weight: 800; cursor: pointer; border: none; margin-top: 20px; text-transform: uppercase; transition: 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(212, 255, 0, 0.2); }
        .loading { font-size: 0.75rem; color: var(--primary); display: none; margin-top: 5px; font-weight: bold; }
        
        /* Alertas */
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: var(--primary); color: #000; border: 1px solid #000; }
        .alert-error { background: var(--error); color: #fff; }
    </style>
</head>
<body>

<div class="auth-card">
    <h2 style="text-align: center; margin-bottom: 30px; letter-spacing: 2px;">CADASTRO DE ACADEMIA</h2>
    
    @if (session('sucesso'))
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ session('sucesso') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('academia.store') }}" method="POST" class="form-grid">
        @csrf

        <div class="form-group">
            <label>CNPJ (Somente números)</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-building"></i>
                <input type="text" name="cnpj" id="cnpj" value="{{ old('cnpj') }}" placeholder="Ex: 12345678000199" required>
            </div>
            <span id="cnpj-loading" class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Consultando CNPJ...</span>
        </div>

        <div class="form-group">
            <label>Nome da Academia</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-dumbbell"></i>
                <input type="text" name="nome" id="nome" value="{{ old('nome') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>CEP</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-map-location-dot"></i>
                <input type="text" name="cep" id="cep" value="{{ old('cep') }}" placeholder="00000000" required>
            </div>
            <span id="cep-loading" class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Buscando CEP...</span>
        </div>

        <div class="form-group">
            <label>Rua</label>
            <div class="input-wrapper">
                <input type="text" name="rua" id="rua" value="{{ old('rua') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>Bairro</label>
            <div class="input-wrapper">
                <input type="text" name="bairro" id="bairro" value="{{ old('bairro') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>Cidade / Estado</label>
            <div class="input-wrapper">
                <input type="text" name="cidade" id="cidade" value="{{ old('cidade') }}" placeholder="Cidade" style="width: 70%" required>
                <input type="text" name="estado" id="estado" value="{{ old('estado') }}" placeholder="UF" style="width: 30%; text-align: center" required>
            </div>
        </div>

        <div class="form-group">
            <label>Número/Complemento</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-house-chimney"></i>
                <input type="text" name="complemento" value="{{ old('complemento') }}" placeholder="Ex: 123 ou Sala 2" required>
            </div>
        </div>

        <div class="form-group">
            <label>Endereço Completo</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-map"></i>
                <input type="text" name="endereco" id="endereco_completo" value="{{ old('endereco') }}" placeholder="Rua, Bairro - Cidade/UF" required>
            </div>
        </div>

        <div class="form-group">
            <label>Valor Mensalidade (R$)</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-dollar-sign"></i>
                <input type="number" step="0.01" name="valor_mensalidade" value="{{ old('valor_mensalidade') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>Senha</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-key"></i>
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

        <div class="form-group full-width">
            <label>Descrição Curta</label>
            <div class="input-wrapper">
                <textarea name="descricao" rows="2">{{ old('descricao') }}</textarea>
            </div>
        </div>

        <div class="form-group full-width">
            <label>Diferenciais (Infraestrutura e Aulas)</label>
            <div class="form-grid" style="gap: 10px;">
                <div class="input-wrapper">
                    <input type="text" name="infraestrutura" value="{{ old('infraestrutura') }}" placeholder="Infraestrutura (ex: Ar condicionado)" required>
                </div>
                <div class="input-wrapper">
                    <input type="text" name="tipos_aulas" value="{{ old('tipos_aulas') }}" placeholder="Aulas (ex: Zumba, Pilates)" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-submit full-width">
            FINALIZAR CADASTRO <i class="fa-solid fa-paper-plane" style="margin-left: 10px;"></i>
        </button>
    </form>
</div>

<script>
    // --- LÓGICA DA BRASILAPI PARA CEP ---
    document.getElementById('cep').addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length !== 8) return;

        document.getElementById('cep-loading').style.display = 'block';

        fetch(`https://brasilapi.com.br/api/cep/v1/${cep}`)
            .then(res => res.json())
            .then(data => {
                if (!data.errors) {
                    document.getElementById('rua').value = data.street || '';
                    document.getElementById('bairro').value = data.neighborhood || '';
                    document.getElementById('cidade').value = data.city || '';
                    document.getElementById('estado').value = data.state || '';
                    document.getElementById('endereco_completo').value = 
                        `${data.street || ''}, ${data.neighborhood || ''} - ${data.city || ''}/${data.state || ''}`;
                }
            })
            .catch(err => console.error("Erro ao buscar CEP"))
            .finally(() => document.getElementById('cep-loading').style.display = 'none');
    });

    // --- LÓGICA DA BRASILAPI PARA CNPJ ---
    document.getElementById('cnpj').addEventListener('blur', function() {
        const cnpj = this.value.replace(/\D/g, '');
        if (cnpj.length !== 14) return;

        document.getElementById('cnpj-loading').style.display = 'block';

        fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpj}`)
            .then(res => res.json())
            .then(data => {
                if (!data.errors) {
                    // Preenche o nome da academia com o Nome Fantasia ou Razão Social vindo da API
                    document.getElementById('nome').value = data.nome_fantasia || data.razao_social || '';
                }
            })
            .catch(err => console.error("Erro ao buscar CNPJ"))
            .finally(() => document.getElementById('cnpj-loading').style.display = 'none');
    });
</script>

</body>
</html>