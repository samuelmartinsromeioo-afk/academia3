<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Loja | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">

    <style>
        :root { --primary: #d4ff00; --bg-dark: #0a0b0d; --card-bg: #16181d; --text-main: #ffffff; --error: #ff4444; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); padding: 40px 20px; }
        .auth-card { background: var(--card-bg); max-width: 800px; margin: 0 auto; padding: 40px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        .form-group label { display: block; font-size: 0.8rem; color: var(--primary); margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
        .input-wrapper { display: flex; align-items: center; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 0 15px; }
        .input-wrapper input, .input-wrapper textarea, .input-wrapper select { flex: 1; background: transparent; border: none; padding: 12px 0; color: #fff; outline: none; }
        .input-wrapper i { margin-right: 10px; color: #666; width: 20px; text-align: center; }
        .btn-submit { background: var(--primary); color: #000; width: 100%; padding: 18px; border-radius: 12px; font-weight: 800; cursor: pointer; border: none; margin-top: 20px; text-transform: uppercase; transition: 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(212, 255, 0, 0.2); }
        .loading { font-size: 0.75rem; color: var(--primary); display: none; margin-top: 5px; font-weight: bold; }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: var(--primary); color: #000; border: 1px solid #000; }
        .alert-error { background: var(--error); color: #fff; }
        .hint { font-size: 0.72rem; color: #9ca3af; margin-top: 6px; display: block; }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body class="ed-page">

<div class="auth-card">
    <h2 style="text-align: center; margin-bottom: 8px; letter-spacing: 2px;"><i class="ph ph-storefront" style="color: var(--primary);"></i> CADASTRO DE LOJA</h2>
    <p style="text-align:center; color:#9ca3af; font-size:0.88rem; margin-bottom:28px;">Suplementos e produtos fitness</p>

    @if (session('sucesso'))
        <div class="alert alert-success">
            <i class="ph ph-check-circle"></i> {{ session('sucesso') }}
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

    <form action="{{ route('loja.store') }}" method="POST" class="form-grid">
        @csrf

        <div class="form-group">
            <label>CNPJ</label>
            <div class="input-wrapper">
                <i class="ph ph-building"></i>
                <input type="text" name="cnpj" value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00"
                        oninput="this.value = mascaras.cnpj(this.value)" maxlength="18" required>
            </div>
        </div>

        <div class="form-group">
            <label>Nome da Loja</label>
            <div class="input-wrapper">
                <i class="ph ph-storefront"></i>
                <input type="text" name="nome" id="nome" value="{{ old('nome') }}" placeholder="Ex: Suplementos Power" required>
            </div>
        </div>

        <div class="form-group">
            <label>WhatsApp</label>
            <div class="input-wrapper">
                <i class="ph ph-whatsapp-logo"></i>
                <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="(11) 99999-9999"
                        oninput="this.value = mascaras.telefone(this.value)" maxlength="15">
            </div>
            <span class="hint">Usado para os clientes fazerem pedidos.</span>
        </div>

        <div class="form-group">
            <label>CEP</label>
            <div class="input-wrapper">
                <i class="ph ph-map-pin-area"></i>
                <input type="text" name="cep" id="cep" value="{{ old('cep') }}" placeholder="00000-000"
                        oninput="this.value = mascaras.cep(this.value)" maxlength="9" required>
            </div>
            <span id="cep-loading" class="loading"><i class="ph ph-spinner ph-spin"></i> Buscando CEP...</span>
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
                <input type="text" name="estado" id="estado" value="{{ old('estado') }}" placeholder="UF" maxlength="2" style="width: 30%; text-align: center" required>
            </div>
        </div>

        <div class="form-group">
            <label>Número/Complemento</label>
            <div class="input-wrapper">
                <i class="ph ph-house"></i>
                <input type="text" name="complemento" value="{{ old('complemento') }}" placeholder="Ex: 123 ou Loja 2">
            </div>
        </div>

        <div class="form-group full-width">
            <label>Endereço Completo</label>
            <div class="input-wrapper">
                <i class="ph ph-map-trifold"></i>
                <input type="text" name="endereco" id="endereco_completo" value="{{ old('endereco') }}" placeholder="Rua, Bairro - Cidade/UF" required>
            </div>
        </div>

        <div class="form-group">
            <label>E-mail</label>
            <div class="input-wrapper">
                <i class="ph ph-envelope"></i>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>Senha</label>
            <div class="input-wrapper">
                <i class="ph ph-key"></i>
                <input type="password" name="senha" minlength="8" required>
            </div>
        </div>

        <div class="form-group full-width">
            <label>Confirmar Senha</label>
            <div class="input-wrapper">
                <i class="ph ph-shield-check"></i>
                <input type="password" name="senha_confirmation" minlength="8" required>
            </div>
        </div>

        <div class="form-group full-width">
            <label>Descrição Curta</label>
            <div class="input-wrapper">
                <textarea name="descricao" rows="2" placeholder="Conte um pouco sobre a sua loja e os produtos que vende">{{ old('descricao') }}</textarea>
            </div>
        </div>

        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

        <button type="submit" class="btn-submit full-width">
            FINALIZAR CADASTRO <i class="ph ph-paper-plane-tilt" style="margin-left: 10px;"></i>
        </button>

        <p class="full-width" style="text-align:center; color:#9ca3af; font-size:0.85rem; margin-top:5px;">
            <i class="ph ph-lightning"></i> Acesso imediato: após o cadastro você já entra no painel para adicionar seus produtos.
        </p>
    </form>
</div>

<script>
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

                const enderecoQuery = `${data.street || ''}, ${data.city || ''}, ${data.state || ''}, Brasil`;
                fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(enderecoQuery)}&format=json&limit=1`)
                    .then(r => r.json())
                    .then(results => {
                        if (results.length > 0) {
                            document.getElementById('latitude').value  = results[0].lat;
                            document.getElementById('longitude').value = results[0].lon;
                            document.getElementById('cep-loading').innerHTML =
                                '<i class="ph ph-check" style="color:#d4ff00"></i> Localização encontrada!';
                        }
                    })
                    .catch(() => {})
                    .finally(() => {
                        setTimeout(() => {
                            document.getElementById('cep-loading').style.display = 'none';
                            document.getElementById('cep-loading').innerHTML =
                                '<i class="ph ph-spinner ph-spin"></i> Buscando CEP...';
                        }, 2000);
                    });
            } else {
                document.getElementById('cep-loading').style.display = 'none';
            }
        })
        .catch(() => {
            document.getElementById('cep-loading').style.display = 'none';
        });
});
</script>
<script>
    const mascaras = {
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
