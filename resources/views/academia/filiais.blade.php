<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filiais — {{ $academia->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --error: #ff4444;
            --success: #00ff88;
            --border: rgba(255, 255, 255, 0.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; }
        input, textarea, select { font-family: inherit; }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 40px;
            background: rgba(0,0,0,0.4);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(10px);
        }
        .back-btn {
            background: var(--card-bg); border: 1px solid var(--border);
            color: var(--primary); width: 40px; height: 40px;
            border-radius: 10px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: 0.3s; font-size: 1.1rem;
        }
        .back-btn:hover { background: var(--primary); color: #000; }
        .profile-header { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 0.9rem; }

        .container { max-width: 1100px; margin: 35px auto; padding: 0 20px; }
        h1 { font-size: 1.8rem; font-weight: 900; color: var(--primary); margin-bottom: 25px; }

        .alert-success {
            background: rgba(0,255,136,0.1); color: var(--success);
            border: 1px solid var(--success); padding: 14px 18px;
            border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;
        }
        .alert-error {
            background: rgba(255,68,68,0.1); color: var(--error);
            border: 1px solid var(--error); padding: 14px 18px;
            border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;
        }

        .filiais-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .filial-card {
            background: var(--card-bg);
            border: 1px solid rgba(212,255,0,0.15);
            border-radius: 18px;
            padding: 22px;
            transition: 0.3s;
        }
        .filial-card:hover { border-color: rgba(212,255,0,0.4); }
        .filial-nome {
            font-size: 1.1rem; font-weight: 800; color: var(--primary);
            margin-bottom: 10px;
        }
        .filial-info { font-size: 0.82rem; color: var(--text-muted); line-height: 1.8; }
        .filial-info i { width: 16px; color: var(--primary); margin-right: 4px; }
        .filial-actions { display: flex; gap: 10px; margin-top: 16px; }
        .btn-edit {
            flex: 1; background: rgba(212,255,0,0.1); color: var(--primary);
            border: 1px solid var(--primary); padding: 8px; border-radius: 8px;
            cursor: pointer; font-weight: 700; font-size: 0.78rem; transition: 0.2s;
        }
        .btn-edit:hover { background: var(--primary); color: #000; }
        .btn-del {
            background: rgba(255,68,68,0.1); color: var(--error);
            border: 1px solid var(--error); padding: 8px 14px;
            border-radius: 8px; cursor: pointer; font-size: 0.78rem; transition: 0.2s;
        }
        .btn-del:hover { background: var(--error); color: #fff; }

        .btn-nova-filial {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            background: rgba(212,255,0,0.05); border: 2px dashed rgba(212,255,0,0.3);
            color: var(--primary); border-radius: 18px; padding: 22px;
            cursor: pointer; font-weight: 700; font-size: 0.9rem; transition: 0.3s;
            width: 100%;
        }
        .btn-nova-filial:hover { background: rgba(212,255,0,0.1); border-color: var(--primary); }

        /* MODAL */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.88); z-index: 2000;
            justify-content: center; align-items: center;
            backdrop-filter: blur(8px); padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: var(--card-bg); max-width: 560px; width: 100%;
            border-radius: 20px; border: 1px solid var(--border); padding: 32px;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-content h2 {
            color: var(--primary); font-size: 1.3rem; font-weight: 900;
            margin: 0 0 22px;
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-grid .full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label {
            font-size: 0.72rem; text-transform: uppercase; font-weight: 800;
            color: var(--text-muted);
        }
        .input-wrap {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; padding: 10px 14px; transition: 0.2s;
        }
        .input-wrap:focus-within { border-color: var(--primary); }
        .input-wrap i { color: var(--text-muted); font-size: 0.85rem; }
        .input-wrap input { background: none; border: none; outline: none; color: var(--text-main); font-size: 0.9rem; width: 100%; }
        .modal-actions { display: flex; gap: 10px; margin-top: 22px; }
        .btn-cancel-modal {
            flex: 1; background: rgba(255,255,255,0.05); color: var(--text-main);
            border: 1px solid rgba(255,255,255,0.1); padding: 12px;
            border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.85rem;
        }
        .btn-submit-modal {
            flex: 1; background: var(--primary); color: #000; border: none;
            padding: 12px; border-radius: 8px; cursor: pointer;
            font-weight: 900; font-size: 0.85rem; transition: 0.2s;
        }
        .btn-submit-modal:hover { background: #e8ff40; }

        @media (max-width: 768px) {
            .top-bar { padding: 15px 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .full { grid-column: 1; }
        }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <button class="back-btn" onclick="window.location.href='{{ route('academia.dashboard') }}'" title="Voltar">
        <i class="ph ph-arrow-left"></i>
    </button>
    <div class="profile-header">
        <i class="ph ph-map-pin" style="color: var(--primary);"></i>
        Filiais — {{ $academia->nome }}
    </div>
</div>

<div class="container">
    <h1><i class="ph ph-map-pin"></i> MINHAS FILIAIS</h1>

    @if(session('success'))
    <div class="alert-success"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert-error"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>
    @endif

    <div class="filiais-grid">
        @foreach($filiais as $filial)
        <div class="filial-card">
            <div class="filial-nome">{{ $filial->nome }}</div>
            <div class="filial-info">
                <div><i class="ph ph-map-pin"></i> {{ $filial->rua }}, {{ $filial->bairro }}</div>
                <div><i class="ph ph-buildings"></i> {{ $filial->cidade }} — {{ $filial->estado }}</div>
                <div><i class="ph ph-mailbox"></i> CEP: {{ $filial->cep }}</div>
                @if($filial->complemento)
                <div><i class="ph ph-info"></i> {{ $filial->complemento }}</div>
                @endif
                @if($filial->telefone)
                <div><i class="ph ph-phone"></i> {{ $filial->telefone }}</div>
                @endif
            </div>
            <div class="filial-actions">
                <button class="btn-edit" onclick="abrirEditar(
                    {{ $filial->id }},
                    '{{ addslashes($filial->nome) }}',
                    '{{ addslashes($filial->cep) }}',
                    '{{ addslashes($filial->rua) }}',
                    '{{ addslashes($filial->bairro) }}',
                    '{{ addslashes($filial->cidade) }}',
                    '{{ addslashes($filial->estado) }}',
                    '{{ addslashes($filial->complemento ?? '') }}',
                    '{{ addslashes($filial->telefone ?? '') }}',
                    '{{ $filial->latitude ?? '' }}',
                    '{{ $filial->longitude ?? '' }}'
                )">
                    <i class="ph ph-pen"></i> Editar
                </button>
                <form action="{{ route('academia.filiais.destroy', $filial->id) }}" method="POST"
                      onsubmit="return confirm('Remover esta filial?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-del"><i class="ph ph-trash"></i></button>
                </form>
            </div>
        </div>
        @endforeach

        <button class="btn-nova-filial" onclick="document.getElementById('modalNovaFilial').classList.add('active')">
            <i class="ph ph-plus-circle"></i> Nova Filial
        </button>
    </div>
</div>

{{-- MODAL NOVA FILIAL --}}
<div id="modalNovaFilial" class="modal-overlay">
    <div class="modal-content">
        <h2><i class="ph ph-plus-circle"></i> NOVA FILIAL</h2>
        <form action="{{ route('academia.filiais.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nome da Filial</label>
                    <div class="input-wrap"><i class="ph ph-building"></i><input type="text" name="nome" placeholder="Ex: Unidade Centro" required></div>
                </div>
                <div class="form-group">
                    <label>CEP</label>
                    <div class="input-wrap"><i class="ph ph-envelope"></i><input type="text" name="cep" placeholder="00000-000" maxlength="9" oninput="mascaraCep(this)" required></div>
                </div>
                <div class="form-group">
                    <label>Telefone (opcional)</label>
                    <div class="input-wrap"><i class="ph ph-phone"></i><input type="text" name="telefone" placeholder="(00) 00000-0000" maxlength="20"></div>
                </div>
                <div class="form-group full">
                    <label>Rua</label>
                    <div class="input-wrap"><i class="ph ph-road-horizon"></i><input type="text" name="rua" id="nova_rua" required></div>
                </div>
                <div class="form-group">
                    <label>Bairro</label>
                    <div class="input-wrap"><i class="ph ph-house"></i><input type="text" name="bairro" id="nova_bairro" required></div>
                </div>
                <div class="form-group">
                    <label>Cidade</label>
                    <div class="input-wrap"><i class="ph ph-buildings"></i><input type="text" name="cidade" id="nova_cidade" required></div>
                </div>
                <div class="form-group">
                    <label>Estado (UF)</label>
                    <div class="input-wrap"><i class="ph ph-map-trifold"></i><input type="text" name="estado" id="nova_estado" maxlength="2" required></div>
                </div>
                <div class="form-group">
                    <label>Complemento (opcional)</label>
                    <div class="input-wrap"><i class="ph ph-info"></i><input type="text" name="complemento"></div>
                </div>
            </div>
            <input type="hidden" id="nova_latitude" name="latitude">
            <input type="hidden" id="nova_longitude" name="longitude">
            <div class="modal-actions">
                <button type="button" class="btn-cancel-modal" onclick="document.getElementById('modalNovaFilial').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-submit-modal">Adicionar Filial</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR FILIAL --}}
<div id="modalEditarFilial" class="modal-overlay">
    <div class="modal-content">
        <h2><i class="ph ph-pen"></i> EDITAR FILIAL</h2>
        <form id="formEditarFilial" method="POST">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nome da Filial</label>
                    <div class="input-wrap"><i class="ph ph-building"></i><input type="text" id="e_nome" name="nome" required></div>
                </div>
                <div class="form-group">
                    <label>CEP</label>
                    <div class="input-wrap"><i class="ph ph-envelope"></i><input type="text" id="e_cep" name="cep" maxlength="9" oninput="mascaraCep(this)" required></div>
                </div>
                <div class="form-group">
                    <label>Telefone (opcional)</label>
                    <div class="input-wrap"><i class="ph ph-phone"></i><input type="text" id="e_telefone" name="telefone" maxlength="20"></div>
                </div>
                <div class="form-group full">
                    <label>Rua</label>
                    <div class="input-wrap"><i class="ph ph-road-horizon"></i><input type="text" id="e_rua" name="rua" required></div>
                </div>
                <div class="form-group">
                    <label>Bairro</label>
                    <div class="input-wrap"><i class="ph ph-house"></i><input type="text" id="e_bairro" name="bairro" required></div>
                </div>
                <div class="form-group">
                    <label>Cidade</label>
                    <div class="input-wrap"><i class="ph ph-buildings"></i><input type="text" id="e_cidade" name="cidade" required></div>
                </div>
                <div class="form-group">
                    <label>Estado (UF)</label>
                    <div class="input-wrap"><i class="ph ph-map-trifold"></i><input type="text" id="e_estado" name="estado" maxlength="2" required></div>
                </div>
                <div class="form-group">
                    <label>Complemento (opcional)</label>
                    <div class="input-wrap"><i class="ph ph-info"></i><input type="text" id="e_complemento" name="complemento"></div>
                </div>
            </div>
            <input type="hidden" id="e_latitude" name="latitude">
            <input type="hidden" id="e_longitude" name="longitude">
            <div class="modal-actions">
                <button type="button" class="btn-cancel-modal" onclick="document.getElementById('modalEditarFilial').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-submit-modal">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirEditar(id, nome, cep, rua, bairro, cidade, estado, complemento, telefone, lat, lng) {
        document.getElementById('formEditarFilial').action = `/academia/filiais/${id}`;
        document.getElementById('e_nome').value        = nome;
        document.getElementById('e_cep').value         = cep;
        document.getElementById('e_rua').value         = rua;
        document.getElementById('e_bairro').value      = bairro;
        document.getElementById('e_cidade').value      = cidade;
        document.getElementById('e_estado').value      = estado;
        document.getElementById('e_complemento').value = complemento;
        document.getElementById('e_telefone').value    = telefone;
        document.getElementById('e_latitude').value    = lat || '';
        document.getElementById('e_longitude').value   = lng || '';
        document.getElementById('modalEditarFilial').classList.add('active');
    }

    async function mascaraCep(input) {
        let v = input.value.replace(/\D/g, '').slice(0, 8);
        if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
        input.value = v;

        if (v.replace('-', '').length === 8) {
            const cepLimpo = v.replace('-', '');
            const prefix   = input.closest('.modal-overlay').id === 'modalNovaFilial' ? 'nova_' : 'e_';

            // 1. Preenche endereço via ViaCEP
            try {
                const res = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                const d   = await res.json();
                if (!d.erro) {
                    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
                    set(prefix + 'rua',    d.logradouro || '');
                    set(prefix + 'bairro', d.bairro     || '');
                    set(prefix + 'cidade', d.localidade || '');
                    set(prefix + 'estado', d.uf         || '');
                }
            } catch (_) {}

            // 2. Geocodifica coordenadas via Nominatim
            try {
                const geo = await fetch(
                    `https://nominatim.openstreetmap.org/search?postalcode=${cepLimpo}&country=BR&format=json&limit=1`,
                    { headers: { 'Accept-Language': 'pt-BR' } }
                );
                const geoData = await geo.json();
                if (geoData.length > 0) {
                    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
                    set(prefix + 'latitude',  geoData[0].lat);
                    set(prefix + 'longitude', geoData[0].lon);
                }
            } catch (_) {}
        }
    }

    window.addEventListener('click', e => {
        ['modalNovaFilial', 'modalEditarFilial'].forEach(id => {
            const el = document.getElementById(id);
            if (e.target === el) el.classList.remove('active');
        });
    });
</script>
</body>
</html>
