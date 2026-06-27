<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Loja | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
            --error: #ff4444;
            --success: #28a745;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 40px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            flex-wrap: wrap;
            gap: 12px;
        }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 3px;
            color: var(--text-main);
        }
        .logo span { color: var(--primary); }

        .menu-container { position: relative; display: flex; align-items: center; gap: 14px; }

        .dots-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--primary);
            width: 40px; height: 40px;
            border-radius: 10px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: 0.3s; font-size: 1rem;
        }
        .dots-btn:hover { background: var(--primary); color: #000; }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 50px; left: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 260px;
            z-index: 1000;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
        }
        .dropdown-menu button,
        .dropdown-menu a {
            display: flex; align-items: center; gap: 12px;
            padding: 15px 20px;
            color: #fff; font-size: 14px;
            width: 100%; text-align: left;
            background: none; border: none; cursor: pointer;
            transition: 0.2s; font-family: inherit; text-decoration: none;
        }
        .dropdown-menu button:hover,
        .dropdown-menu a:hover { background: rgba(255, 255, 255, 0.05); color: var(--primary); }
        .dropdown-menu i { width: 18px; text-align: center; }

        .container { max-width: 1200px; margin: 0 auto; padding: 36px 20px; }

        .welcome { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px; }
        .welcome h1 { font-size: 1.6rem; font-weight: 900; }
        .welcome h1 em { color: var(--primary); font-style: normal; }
        .welcome p { color: var(--text-muted); margin-top: 4px; font-size: 0.9rem; }

        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; }
        .alert-success { background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.4); color: #4caf50; }
        .alert-error { background: rgba(255,68,68,0.1); border: 1px solid rgba(255,68,68,0.3); color: #ff6b6b; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 36px;
        }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 24px; transition: 0.2s; }
        .stat-card:hover { border-color: rgba(212,255,0,0.3); }
        .stat-card .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: rgba(212, 255, 0, 0.1);
            display: flex; align-items: center; justify-content: center;
            color: var(--primary); font-size: 1.1rem; margin-bottom: 14px;
        }
        .stat-card .stat-value { font-size: 1.6rem; font-weight: 900; }
        .stat-card .stat-label { color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; margin-top: 4px; }

        .section-title {
            font-size: 1.1rem; font-weight: 900; color: var(--primary);
            margin: 36px 0 18px; display: flex; align-items: center; gap: 10px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .section-title::before { content: ''; width: 4px; height: 18px; background: var(--primary); }

        .panel { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 26px; }

        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 12px; text-align: left; font-weight: 800; font-size: 0.72rem;
            color: var(--primary); text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 2px solid var(--border);
        }
        td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.9rem; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }

        .prod-thumb {
            width: 46px; height: 46px; border-radius: 10px; object-fit: cover;
            border: 1px solid var(--border); background: rgba(255,255,255,0.03);
        }
        .prod-thumb-placeholder {
            width: 46px; height: 46px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--border); background: rgba(212,255,0,0.06); color: var(--primary);
        }

        .preco { color: var(--primary); font-weight: 800; white-space: nowrap; }

        .badge { font-size: 0.65rem; padding: 3px 8px; border-radius: 10px; font-weight: 800; text-transform: uppercase; }
        .badge-ok { background: rgba(40,167,69,0.12); color: #4caf50; }
        .badge-out { background: rgba(255,68,68,0.1); color: var(--error); }
        .badge-off { background: rgba(160,160,160,0.12); color: var(--text-muted); }

        .empty { color: var(--text-muted); text-align: center; padding: 36px 0; font-size: 0.9rem; }

        .estoque-form { display: inline-flex; gap: 6px; align-items: center; }
        .estoque-form input {
            width: 64px; background: var(--input-bg); border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 8px; color: #fff; outline: none; font-family: inherit; text-align: center;
        }
        .estoque-form input:focus { border-color: var(--primary); }
        .estoque-form button {
            background: transparent; border: 1px solid var(--border); color: var(--text-muted);
            border-radius: 8px; padding: 7px 9px; cursor: pointer; transition: 0.2s;
        }
        .estoque-form button:hover { border-color: var(--primary); color: var(--primary); }

        .btn-sm {
            border: none; border-radius: 6px; padding: 7px 11px; font-weight: 700; font-size: 0.72rem;
            text-transform: uppercase; cursor: pointer; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 5px; font-family: inherit;
        }
        .btn-sm.outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); }
        .btn-sm.outline:hover { background: var(--primary); color: #000; }
        .btn-sm.danger { background: transparent; border: 1px solid var(--error); color: var(--error); }
        .btn-sm.danger:hover { background: var(--error); color: #fff; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .full-width { grid-column: span 2; }
        .form-group label { display: block; font-size: 0.72rem; color: var(--text-muted); margin-bottom: 6px; font-weight: 700; text-transform: uppercase; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; background: var(--input-bg); border: 1px solid var(--border);
            border-radius: 10px; padding: 11px 14px; color: #fff; outline: none; font-family: inherit; font-size: 0.9rem;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--primary); }

        .btn-save {
            background: var(--primary); color: #000; border: none; border-radius: 10px;
            padding: 13px 22px; font-weight: 900; font-size: 0.85rem; text-transform: uppercase; cursor: pointer;
            transition: 0.2s; font-family: inherit;
        }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(212,255,0,0.15); }

        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85);
            z-index: 1000; overflow-y: auto; backdrop-filter: blur(6px); padding: 40px 20px;
        }
        .modal-content {
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px;
            padding: 32px; width: 100%; max-width: 560px; margin: 0 auto;
        }
        .modal-content h2 { margin-bottom: 20px; }
        .modal-close { float: right; background: transparent; border: none; color: var(--text-muted); font-size: 1.3rem; cursor: pointer; }

        @media (max-width: 768px) {
            .top-bar { padding: 14px 16px; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .panel { overflow-x: auto; }
        }
    </style>
</head>
<body class="ed-page">

{{-- TOP BAR --}}
<div class="top-bar">
    <div class="menu-container">
        <button class="dots-btn" id="btnMenu" onclick="toggleMenu()"><i class="ph ph-list"></i></button>
        <div class="dropdown-menu" id="dropdownMenu">
            <button type="button" id="btnOpenNovo"><i class="ph ph-plus" style="color: var(--primary);"></i> Novo Produto</button>
            <button type="button" id="btnOpenPedidos"><i class="ph ph-receipt"></i> Pedidos</button>
            <button type="button" id="btnOpenCarteira"><i class="ph ph-piggy-bank" style="color: var(--primary);"></i> Minha Carteira</button>
            <button type="button" id="btnOpenPerfil"><i class="ph ph-storefront"></i> Editar Loja</button>
            <a href="{{ route('lojas.detalhes', $loja->id) }}" target="_blank"><i class="ph ph-eye"></i> Ver minha vitrine</a>
            <a href="{{ route('lgpd.meus-dados') }}"><i class="ph ph-shield-check"></i> Privacidade e meus dados</a>
            <form action="{{ route('login.logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" style="color: var(--error);"><i class="ph ph-power"></i> Sair</button>
            </form>
        </div>
        <div class="logo">SNR<span>FIT</span> <span style="font-family:'Inter'; font-size:0.65rem; color:var(--text-muted); letter-spacing:1px; text-transform:uppercase;">| Loja</span></div>
    </div>
    <span style="font-weight: 700; font-size: 0.9rem;"><i class="ph ph-storefront" style="color: var(--primary); margin-right: 8px;"></i>{{ $loja->nome }}</span>
</div>

<div class="container">

    @if(session('success'))
        <div class="alert alert-success"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="welcome">
        <div>
            <div class="ed-eyebrow"><span class="ed-num">01</span> Olá, {{ $loja->nome }}</div>
            <h1 class="ed-h">Minha <span class="ed-mark">Loja</span></h1>
            <p style="margin-top:10px;">Gerencie seu catálogo, preços e estoque. Seus produtos aparecem na vitrine para os clientes.</p>
        </div>
        <button class="btn-save" onclick="document.getElementById('modalNovo').style.display='block'">
            <i class="ph ph-plus"></i> Novo Produto
        </button>
    </div>

    {{-- STATS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="ph ph-package"></i></div>
            <div class="stat-value">{{ $totalProdutos }}</div>
            <div class="stat-label">Produtos cadastrados</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="ph ph-eye"></i></div>
            <div class="stat-value">{{ $produtosAtivos }}</div>
            <div class="stat-label">Ativos na vitrine</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="ph ph-warning"></i></div>
            <div class="stat-value">{{ $semEstoque }}</div>
            <div class="stat-label">Sem estoque</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="ph ph-money"></i></div>
            <div class="stat-value" style="font-size:1.3rem;">R$ {{ number_format($valorEstoque, 2, ',', '.') }}</div>
            <div class="stat-label">Valor em estoque</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="ph ph-receipt"></i></div>
            <div class="stat-value">{{ $totalPedidos }}</div>
            <div class="stat-label">Pedidos recebidos</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="ph ph-cash-register"></i></div>
            <div class="stat-value" style="font-size:1.3rem;">R$ {{ number_format($faturamentoMes, 2, ',', '.') }}</div>
            <div class="stat-label">Vendas no mês</div>
        </div>
    </div>

    {{-- PRODUTOS --}}
    <h2 class="section-title"><i class="ph ph-package"></i> Meus Produtos</h2>
    <div class="panel">
        @if($produtos->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th style="text-align:center;">Estoque</th>
                        <th>Status</th>
                        <th style="text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produtos as $produto)
                        <tr>
                            <td>
                                @if($produto->imagem)
                                    <img src="{{ asset('storage/' . $produto->imagem) }}" alt="{{ $produto->nome }}" class="prod-thumb">
                                @else
                                    <div class="prod-thumb-placeholder"><i class="ph ph-package"></i></div>
                                @endif
                            </td>
                            <td style="font-weight: 600;">{{ $produto->nome }}</td>
                            <td style="color: var(--text-muted);">{{ $produto->categoria ?? '—' }}</td>
                            <td class="preco">R$ {{ number_format($produto->preco, 2, ',', '.') }}</td>
                            <td style="text-align:center;">
                                <form class="estoque-form" method="POST" action="{{ route('loja.produtos.estoque', $produto->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="estoque" min="0" value="{{ $produto->estoque }}" aria-label="Estoque">
                                    <button type="submit" title="Atualizar estoque"><i class="ph ph-check"></i></button>
                                </form>
                            </td>
                            <td>
                                @if(!$produto->ativo)
                                    <span class="badge badge-off">Oculto</span>
                                @elseif($produto->estoque <= 0)
                                    <span class="badge badge-out">Esgotado</span>
                                @else
                                    <span class="badge badge-ok">Ativo</span>
                                @endif
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <button type="button" class="btn-sm outline btn-editar-produto"
                                    data-id="{{ $produto->id }}"
                                    data-nome="{{ $produto->nome }}"
                                    data-descricao="{{ $produto->descricao }}"
                                    data-categoria="{{ $produto->categoria }}"
                                    data-preco="{{ $produto->preco }}"
                                    data-estoque="{{ $produto->estoque }}"
                                    data-ativo="{{ $produto->ativo ? '1' : '0' }}">
                                    <i class="ph ph-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('loja.produtos.destroy', $produto->id) }}" onsubmit="return confirm('Excluir o produto {{ addslashes($produto->nome) }}?');" style="display:inline; margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm danger"><i class="ph ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty"><i class="ph ph-package" style="font-size:2rem; display:block; margin-bottom:12px; opacity:0.4;"></i> Nenhum produto cadastrado ainda. Clique em <strong>Novo Produto</strong> para começar.</p>
        @endif
    </div>
</div>

{{-- MODAL NOVO PRODUTO --}}
<div id="modalNovo" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="document.getElementById('modalNovo').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="ph ph-plus-circle"></i> Novo Produto</h2>
        <form method="POST" action="{{ route('loja.produtos.store') }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            <div class="form-group full-width">
                <label>Nome do Produto</label>
                <input type="text" name="nome" placeholder="Ex: Whey Protein 900g" required>
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="categoria" placeholder="Ex: Proteínas, Creatina...">
            </div>
            <div class="form-group">
                <label>Preço (R$)</label>
                <input type="number" step="0.01" min="0" name="preco" required>
            </div>
            <div class="form-group">
                <label>Estoque (unidades)</label>
                <input type="number" min="0" name="estoque" value="0" required>
            </div>
            <div class="form-group">
                <label>Imagem do produto</label>
                <input type="file" name="imagem" accept="image/*">
            </div>
            <div class="form-group full-width">
                <label>Descrição</label>
                <textarea name="descricao" rows="3" placeholder="Sabor, marca, detalhes..."></textarea>
            </div>
            <div class="full-width" style="text-align: right;">
                <button type="submit" class="btn-save"><i class="ph ph-check"></i> Adicionar Produto</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR PRODUTO --}}
<div id="modalEditar" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="document.getElementById('modalEditar').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="ph ph-pen"></i> Editar Produto</h2>
        <form method="POST" id="formEditar" enctype="multipart/form-data" class="form-grid">
            @csrf
            @method('PUT')
            <div class="form-group full-width">
                <label>Nome do Produto</label>
                <input type="text" name="nome" id="editNome" required>
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="categoria" id="editCategoria">
            </div>
            <div class="form-group">
                <label>Preço (R$)</label>
                <input type="number" step="0.01" min="0" name="preco" id="editPreco" required>
            </div>
            <div class="form-group">
                <label>Estoque (unidades)</label>
                <input type="number" min="0" name="estoque" id="editEstoque" required>
            </div>
            <div class="form-group">
                <label>Trocar imagem</label>
                <input type="file" name="imagem" accept="image/*">
            </div>
            <div class="form-group full-width">
                <label>Descrição</label>
                <textarea name="descricao" id="editDescricao" rows="3"></textarea>
            </div>
            <div class="form-group full-width">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; text-transform:none; color:#fff;">
                    <input type="checkbox" name="ativo" id="editAtivo" style="width:auto;"> Visível na vitrine para clientes
                </label>
            </div>
            <div class="full-width" style="text-align: right;">
                <button type="submit" class="btn-save"><i class="ph ph-floppy-disk"></i> Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR LOJA --}}
<div id="modalPerfil" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="document.getElementById('modalPerfil').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="ph ph-storefront"></i> Editar Loja</h2>
        <form method="POST" action="{{ route('loja.update', $loja->id) }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            @method('PUT')
            <div class="form-group full-width">
                <label>Nome da Loja</label>
                <input type="text" name="nome" value="{{ old('nome', $loja->nome) }}" required>
            </div>
            <div class="form-group">
                <label>WhatsApp (para pedidos)</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp', $loja->whatsapp) }}" placeholder="(11) 99999-9999">
            </div>
            <div class="form-group">
                <label>Logo da Loja</label>
                <input type="file" name="logo" accept="image/*">
            </div>
            <div class="form-group">
                <label>Cidade</label>
                <input type="text" name="cidade" value="{{ old('cidade', $loja->cidade) }}">
            </div>
            <div class="form-group">
                <label>Estado (UF)</label>
                <input type="text" name="estado" maxlength="2" value="{{ old('estado', $loja->estado) }}">
            </div>
            <div class="form-group full-width">
                <label>Chave PIX (para receber os saques)</label>
                <input type="text" name="chave_pix" value="{{ old('chave_pix', $loja->chave_pix) }}" placeholder="CPF/CNPJ, e-mail, telefone ou chave aleatória">
            </div>
            <div class="form-group full-width">
                <label>Descrição</label>
                <textarea name="descricao" rows="3">{{ old('descricao', $loja->descricao) }}</textarea>
            </div>
            <div class="full-width" style="text-align: right;">
                <button type="submit" class="btn-save"><i class="ph ph-floppy-disk"></i> Salvar Loja</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL PEDIDOS --}}
<div id="modalPedidos" class="modal-overlay">
    <div class="modal-content" style="max-width: 820px;">
        <button type="button" class="modal-close" onclick="document.getElementById('modalPedidos').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="ph ph-receipt"></i> Pedidos ({{ $pedidos->count() }})</h2>
        @forelse($pedidos as $pedido)
            <div style="border:1px solid var(--border); border-radius:14px; padding:18px; margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                    <div>
                        <strong style="font-size:0.95rem;">Pedido #{{ $pedido->id }}</strong>
                        @if($pedido->status === 'concluido')
                            <span class="badge badge-ok">Concluído</span>
                        @else
                            <span class="badge" style="background:rgba(212,255,0,0.12); color:var(--primary);">Pago</span>
                        @endif
                        <div style="color:var(--text-muted); font-size:0.78rem; margin-top:4px;">
                            <i class="ph ph-user"></i> {{ $pedido->cliente?->nome ?? 'Cliente' }}
                            @if($pedido->cliente && $pedido->cliente?->whatsapp) · <i class="ph ph-whatsapp-logo"></i> {{ $pedido->cliente?->whatsapp }} @endif
                            · {{ $pedido->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="preco" style="font-size:1.1rem;">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                        @if($pedido->entrega_tipo === 'entrega')
                            <span class="badge" style="background:rgba(255,157,46,0.15); color:#ff9d2e;"><i class="ph ph-truck"></i> Entrega</span>
                        @else
                            <span class="badge badge-off"><i class="ph ph-storefront"></i> Retirada</span>
                        @endif
                    </div>
                </div>

                <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border); font-size:0.85rem;">
                    @foreach($pedido->itens as $item)
                        <div style="display:flex; justify-content:space-between; padding:3px 0;">
                            <span>{{ $item->quantidade }}x {{ $item->nome }}</span>
                            <span style="color:var(--text-muted);">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                @if($pedido->entrega_tipo === 'entrega')
                    <div style="margin-top:10px; font-size:0.8rem; color:var(--text-muted);">
                        <i class="ph ph-map-pin" style="color:#ff9d2e;"></i>
                        {{ $pedido->endereco_entrega }}
                        @if($pedido->entrega_nome) — Receber com: {{ $pedido->entrega_nome }} ({{ $pedido->entrega_telefone }}) @endif
                    </div>
                @endif
                @if($pedido->observacao)
                    <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted);"><i class="ph ph-chat-circle"></i> {{ $pedido->observacao }}</div>
                @endif

                @if($pedido->status === 'pago')
                    <div style="margin-top:12px; text-align:right;">
                        <form method="POST" action="{{ route('loja.pedidos.concluir', $pedido->id) }}" style="margin:0;">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn-sm outline"><i class="ph ph-check"></i> Marcar como concluído</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <p class="empty"><i class="ph ph-receipt" style="font-size:2rem; display:block; margin-bottom:12px; opacity:0.4;"></i> Nenhum pedido ainda. Quando um cliente comprar, o pedido aparece aqui.</p>
        @endforelse
    </div>
</div>

{{-- MODAL CARTEIRA --}}
<div id="modalCarteira" class="modal-overlay">
    <div class="modal-content" style="max-width:480px;">
        <button type="button" class="modal-close" onclick="document.getElementById('modalCarteira').style.display='none'">&times;</button>
        <h2 style="color:var(--primary); text-align:center;"><i class="ph ph-piggy-bank"></i> Carteira da Loja</h2>

        <div id="carteiraLoading" style="text-align:center; padding:30px 0; color:var(--text-muted);">
            <i class="ph ph-spinner ph-spin fa-2x"></i><br><br>Consultando saldo...
        </div>

        {{-- Sem conta de recebimento ainda --}}
        <div id="carteiraAtivar" style="display:none; text-align:center; padding:10px 0;">
            <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:16px;">
                Ative os recebimentos para que o dinheiro das vendas caia direto na sua conta e você possa sacar via PIX.
            </p>
            <button id="btnAtivarConta" onclick="ativarConta()" class="btn-save" style="width:100%;">
                <i class="ph ph-lightning"></i> Ativar recebimentos
            </button>
            <p id="ativarErro" style="display:none; color:#ff6b6b; font-size:0.82rem; margin-top:12px;"></p>
        </div>

        <div id="carteiraConteudo" style="display:none;">
            <div style="background:rgba(212,255,0,0.07); border:1px solid rgba(212,255,0,0.3); border-radius:16px; padding:24px; text-align:center; margin-bottom:20px;">
                <p style="margin:0; color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px;">Saldo disponível</p>
                <p id="carteiraValor" style="margin:0; color:#fff; font-size:2rem; font-weight:900;">R$ 0,00</p>
            </div>

            <div id="carteiraSemPix" style="display:none; background:rgba(255,165,0,0.1); border:1px solid rgba(255,165,0,0.3); border-radius:12px; padding:14px; margin-bottom:16px; font-size:0.82rem; color:#ffa500; text-align:center;">
                <i class="ph ph-warning"></i> Cadastre sua chave PIX em "Editar Loja" para poder sacar.
            </div>

            <div id="carteiraSaqueForm">
                <label style="color:var(--text-muted); font-size:0.75rem; display:block; margin-bottom:6px;">Valor a sacar (R$)</label>
                <div style="display:flex; gap:10px; margin-bottom:16px;">
                    <input id="carteiraValorSaque" type="number" min="0.01" step="0.01" placeholder="0,00"
                        style="flex:1; background:var(--input-bg); border:1px solid var(--border); border-radius:10px; padding:11px 14px; color:#fff; font-size:1rem; outline:none;">
                    <button onclick="sacarTudo()" style="background:rgba(255,255,255,0.07); color:#fff; border:1px solid var(--border); border-radius:10px; padding:10px 14px; font-size:0.8rem; cursor:pointer; white-space:nowrap;">Tudo</button>
                </div>
                <p id="carteiraSaqueErro" style="display:none; color:#ff6b6b; font-size:0.82rem; background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); border-radius:8px; padding:10px 14px; margin-bottom:14px;"></p>
                <p id="carteiraSaqueSucesso" style="display:none; color:#4caf50; font-weight:700; text-align:center; padding:8px 0; font-size:0.9rem;">✅ Saque solicitado! O valor chega via PIX em instantes.</p>
                <button id="btnSacar" onclick="confirmarSaque()" class="btn-save" style="width:100%;">
                    <i class="ph ph-arrow-down"></i> Sacar via PIX
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleMenu() {
        const m = document.getElementById('dropdownMenu');
        m.style.display = m.style.display === 'block' ? 'none' : 'block';
    }

    const CSRF = '{{ csrf_token() }}';

    document.getElementById('btnOpenNovo').onclick = () => {
        document.getElementById('modalNovo').style.display = 'block';
        toggleMenu();
    };
    document.getElementById('btnOpenPedidos').onclick = () => {
        document.getElementById('modalPedidos').style.display = 'block';
        toggleMenu();
    };
    document.getElementById('btnOpenCarteira').onclick = () => {
        abrirCarteira();
        toggleMenu();
    };
    document.getElementById('btnOpenPerfil').onclick = () => {
        document.getElementById('modalPerfil').style.display = 'block';
        toggleMenu();
    };

    // ── CARTEIRA ─────────────────────────────
    let carteiraSaldoAtual = 0;

    async function abrirCarteira() {
        const modal = document.getElementById('modalCarteira');
        modal.style.display = 'block';
        document.getElementById('carteiraLoading').style.display = 'block';
        document.getElementById('carteiraConteudo').style.display = 'none';
        document.getElementById('carteiraAtivar').style.display = 'none';
        document.getElementById('carteiraSaqueErro').style.display = 'none';
        document.getElementById('carteiraSaqueSucesso').style.display = 'none';

        try {
            const res = await fetch('/api/loja/saldo', { headers: { 'X-CSRF-TOKEN': CSRF } });
            const data = await res.json();
            document.getElementById('carteiraLoading').style.display = 'none';

            if (data.sem_conta) {
                document.getElementById('carteiraAtivar').style.display = 'block';
                return;
            }

            carteiraSaldoAtual = parseFloat(data.saldo) || 0;
            document.getElementById('carteiraValor').textContent = 'R$ ' + carteiraSaldoAtual.toFixed(2).replace('.', ',');
            document.getElementById('carteiraValorSaque').value = carteiraSaldoAtual > 0 ? carteiraSaldoAtual.toFixed(2) : '';
            document.getElementById('carteiraSemPix').style.display = data.tem_pix ? 'none' : 'block';
            document.getElementById('carteiraSaqueForm').style.display = data.tem_pix ? 'block' : 'none';
            document.getElementById('carteiraConteudo').style.display = 'block';
        } catch (_) {
            document.getElementById('carteiraLoading').style.display = 'none';
            document.getElementById('carteiraAtivar').style.display = 'block';
        }
    }

    async function ativarConta() {
        const btn = document.getElementById('btnAtivarConta');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Ativando...';
        document.getElementById('ativarErro').style.display = 'none';
        try {
            const res = await fetch('/api/loja/criar-conta-asaas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Falha ao ativar.');
            abrirCarteira();
        } catch (err) {
            const e = document.getElementById('ativarErro');
            e.textContent = err.message;
            e.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-lightning"></i> Ativar recebimentos';
        }
    }

    function sacarTudo() {
        document.getElementById('carteiraValorSaque').value = carteiraSaldoAtual > 0 ? carteiraSaldoAtual.toFixed(2) : '';
    }

    async function confirmarSaque() {
        const valor = parseFloat(document.getElementById('carteiraValorSaque').value);
        if (!valor || valor <= 0) {
            const e = document.getElementById('carteiraSaqueErro');
            e.textContent = 'Informe um valor válido.';
            e.style.display = 'block';
            return;
        }
        const btn = document.getElementById('btnSacar');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Processando...';
        document.getElementById('carteiraSaqueErro').style.display = 'none';
        try {
            const res = await fetch('/api/loja/sacar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ valor }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro ao sacar.');
            document.getElementById('carteiraSaqueSucesso').style.display = 'block';
            document.getElementById('carteiraSaqueForm').style.display = 'none';
            carteiraSaldoAtual = 0;
            document.getElementById('carteiraValor').textContent = 'R$ 0,00';
        } catch (err) {
            const e = document.getElementById('carteiraSaqueErro');
            e.textContent = err.message;
            e.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-arrow-down"></i> Sacar via PIX';
        }
    }

    document.addEventListener('click', (e) => {
        const menu = document.getElementById('dropdownMenu');
        if (menu.style.display === 'block' && !e.target.closest('.menu-container')) {
            menu.style.display = 'none';
        }
    });

    document.querySelectorAll('.btn-editar-produto').forEach(btn => {
        btn.addEventListener('click', () => {
            abrirEditar(
                parseInt(btn.dataset.id, 10),
                btn.dataset.nome,
                btn.dataset.descricao,
                btn.dataset.categoria,
                parseFloat(btn.dataset.preco),
                parseInt(btn.dataset.estoque, 10),
                btn.dataset.ativo === '1'
            );
        });
    });

    function abrirEditar(id, nome, descricao, categoria, preco, estoque, ativo) {
        const form = document.getElementById('formEditar');
        form.action = '/loja/produtos/' + id;
        document.getElementById('editNome').value = nome;
        document.getElementById('editDescricao').value = descricao;
        document.getElementById('editCategoria').value = categoria;
        document.getElementById('editPreco').value = preco;
        document.getElementById('editEstoque').value = estoque;
        document.getElementById('editAtivo').checked = ativo;
        document.getElementById('modalEditar').style.display = 'block';
    }

    document.querySelectorAll('.modal-overlay').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.style.display = 'none';
        });
    });
</script>

</body>
</html>
