<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ $academia->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        * { box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 0;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: rgba(0,0,0,0.4);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .menu-container { position: relative; }

        .dots-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--primary);
            width: 40px; height: 40px;
            border-radius: 10px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: 0.3s;
        }
        .dots-btn:hover { background: var(--primary); color: #000; }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 50px; left: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 240px;
            z-index: 1000;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        }

        .dropdown-menu button {
            display: flex; align-items: center; gap: 12px;
            padding: 15px 20px;
            color: #fff; font-size: 14px;
            width: 100%; text-align: left;
            background: none; border: none; cursor: pointer;
            transition: 0.2s;
        }
        .dropdown-menu button:hover { background: rgba(255,255,255,0.05); color: var(--primary); }

        .profile-header { display: flex; align-items: center; gap: 15px; }

        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid var(--border);
            text-align: center;
        }
        .stat-card i { color: var(--primary); font-size: 1.5rem; margin-bottom: 10px; display: block; }
        .stat-card span { display: block; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 800; }
        .stat-card h2 { margin: 5px 0 0; font-size: 1.6rem; font-weight: 900; }

        .section-title {
            color: var(--primary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 800;
            margin: 30px 0 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title::after { content: ""; flex: 1; height: 1px; background: var(--border); }

        .planos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .plano-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px;
            position: relative;
            transition: 0.3s;
        }
        .plano-card:hover { border-color: rgba(212,255,0,0.3); }
        .plano-card.inativo { opacity: 0.5; }

        .plano-nome { font-size: 1.1rem; font-weight: 900; margin-bottom: 5px; }
        .plano-valor { font-size: 2rem; font-weight: 900; color: var(--primary); }
        .plano-valor small { font-size: 0.75rem; color: var(--text-muted); font-weight: 400; }
        .plano-duracao { font-size: 0.75rem; color: var(--text-muted); margin: 5px 0 10px; }
        .plano-descricao { font-size: 0.8rem; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 10px; margin-top: 10px; }

        .plano-actions { display: flex; gap: 8px; margin-top: 15px; }
        .btn-plano-edit {
            flex: 1; padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;
            cursor: pointer; transition: 0.2s; border: 1px solid var(--border);
            background: rgba(255,255,255,0.04); color: #fff;
        }
        .btn-plano-edit:hover { border-color: var(--primary); color: var(--primary); }

        .btn-plano-del {
            padding: 8px 12px; border-radius: 8px; font-size: 0.75rem;
            cursor: pointer; transition: 0.2s;
            background: rgba(255,68,68,0.1); color: var(--error);
            border: 1px solid rgba(255,68,68,0.2);
        }
        .btn-plano-del:hover { background: var(--error); color: #fff; }

        .badge-inativo {
            position: absolute; top: 15px; right: 15px;
            background: rgba(255,68,68,0.15); color: var(--error);
            font-size: 0.6rem; font-weight: 800; padding: 3px 8px;
            border-radius: 20px; text-transform: uppercase;
        }

        .btn-novo-plano {
            background: rgba(212,255,0,0.08);
            border: 1px dashed rgba(212,255,0,0.4);
            border-radius: 20px;
            padding: 25px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            min-height: 150px;
        }
        .btn-novo-plano:hover { background: rgba(212,255,0,0.12); }
        .btn-novo-plano i { font-size: 1.5rem; }

        .alunos-list { display: flex; flex-direction: column; gap: 10px; }
        .aluno-item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .aluno-item img { width: 40px; height: 40px; border-radius: 50%; }
        .aluno-item h4 { margin: 0 0 3px; font-size: 0.9rem; }
        .aluno-item p { margin: 0; font-size: 0.75rem; color: var(--text-muted); }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.92);
            backdrop-filter: blur(8px);
            z-index: 2000;
            overflow-y: auto;
            padding: 40px 0;
        }

        .modal-content {
            background: var(--card-bg);
            max-width: 500px;
            margin: auto;
            padding: 35px;
            border-radius: 28px;
            border: 1px solid var(--border);
        }

        .input-wrapper {
            display: flex; align-items: center;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 12px;
            margin-top: 5px;
        }
        .input-wrapper input, .input-wrapper textarea, .input-wrapper select {
            flex: 1; background: transparent; border: none;
            padding: 12px; color: #fff; outline: none; font-size: 0.9rem;
            font-family: inherit;
        }
        .input-wrapper textarea { resize: none; height: 80px; }
        .input-wrapper i { color: var(--primary); width: 20px; text-align: center; }

        label {
            font-size: 0.65rem; color: var(--text-muted);
            text-transform: uppercase; font-weight: 800;
            margin-top: 12px; display: block; letter-spacing: 0.5px;
        }

        .btn-save {
            background: var(--primary); color: #000;
            width: 100%; padding: 15px; border-radius: 12px;
            font-weight: 900; border: none; cursor: pointer;
            text-transform: uppercase; transition: 0.3s; margin-top: 20px;
        }
        .btn-save:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .btn-cancel { background: rgba(255,255,255,0.05); color: #fff; }

        .toggle-ativo {
            display: flex; align-items: center; gap: 10px;
            margin-top: 15px; font-size: 0.8rem; color: var(--text-muted);
            cursor: pointer;
        }
        .toggle-ativo input { accent-color: var(--primary); width: 16px; height: 16px; }

        /* Galeria */
        .galeria-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        .galeria-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .galeria-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .galeria-item-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,0.6);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 5px;
            opacity: 0; transition: 0.2s;
        }
        .galeria-item:hover .galeria-item-overlay { opacity: 1; }
        .galeria-item-legenda { font-size: 0.7rem; color: #fff; text-align: center; padding: 0 8px; }
        .galeria-upload-slot {
            aspect-ratio: 1;
            border-radius: 12px;
            border: 1px dashed rgba(212,255,0,0.4);
            background: rgba(212,255,0,0.05);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 5px; cursor: pointer;
            color: var(--primary); font-size: 0.7rem;
            font-weight: 700; text-transform: uppercase;
            transition: 0.2s;
        }
        .galeria-upload-slot:hover { background: rgba(212,255,0,0.1); }
        .galeria-upload-slot i { font-size: 1.2rem; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="menu-container">
        <button class="dots-btn" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
        <div class="dropdown-menu" id="dropdownMenu">
            <button type="button" onclick="document.getElementById('modalUpdate').style.display='block'; toggleMenu();">
                <i class="fas fa-building"></i> Meu Perfil
            </button>
            <button type="button" onclick="window.location.href='{{ route('academia.alunos') }}'">
                <i class="fas fa-users"></i> Alunos
            </button>
            <button type="button" onclick="abrirCarteira(); toggleMenu();">
                <i class="fas fa-piggy-bank" style="color: var(--primary);"></i> Minha Carteira
            </button>
            <button type="button" onclick="window.location.href='{{ route('academia.filiais') }}'">
                <i class="fas fa-map-marker-alt"></i> Filiais
            </button>
            <button type="button" onclick="window.location.href='{{ route('academia.gestao') }}'">
                <i class="fas fa-user-tie"></i> Profissionais & Aulas
            </button>
            <button type="button" onclick="document.getElementById('modalGaleriaAcademia').style.display='block'; toggleMenu();">
                <i class="fas fa-images"></i> Galeria de Fotos
            </button>
            <button type="button" onclick="window.location.href='{{ route('lgpd.meus-dados') }}'">
                <i class="fas fa-user-shield"></i> Privacidade e meus dados
            </button>
            <form action="{{ route('login.logout') }}" method="POST">
                @csrf
                <button type="submit" style="color: var(--error);">
                    <i class="fas fa-power-off"></i> Sair
                </button>
            </form>
        </div>
    </div>
    <div class="profile-header">
        <i class="fas fa-dumbbell" style="color: var(--primary); font-size: 1.2rem;"></i>
        <span style="font-weight: 700; font-size: 0.9rem;">{{ $academia->nome }}</span>
    </div>
</div>

<div class="container">
    @if(session('success'))
    <div style="background: rgba(0,255,136,0.1); color: var(--success); padding: 15px; border-radius: 12px; border: 1px solid var(--success); margin-bottom: 20px; font-size: 0.9rem;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background: rgba(255,68,68,0.1); color: var(--error); padding: 15px; border-radius: 12px; border: 1px solid var(--error); margin-bottom: 20px; font-size: 0.9rem;">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    <header style="margin-bottom: 30px;">
        <h1 style="margin:0; font-size: 2rem; font-weight: 900;">Dashboard</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 5px 0 0;">Bem-vindo, {{ $academia->nome }}</p>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <span>Total de Alunos</span>
            <h2>{{ $totalAlunos }}</h2>
        </div>
        <div class="stat-card">
            <i class="fas fa-id-card"></i>
            <span>Planos Ativos</span>
            <h2>{{ $planosAtivos }}</h2>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-tie"></i>
            <span>Personals</span>
            <h2>{{ $personals }}</h2>
        </div>
        <div class="stat-card">
            <i class="fas fa-dollar-sign"></i>
            <span>Faturamento Est.</span>
            <h2 style="font-size: 1.2rem;">R$ {{ number_format($faturamento, 0, ',', '.') }}</h2>
        </div>
    </div>

    <div class="section-title">
        <i class="fas fa-tags"></i> Meus Planos
        <span style="font-size: 0.75rem; font-weight: 700; color: {{ $planos->count() >= 5 ? 'var(--error)' : 'var(--primary)' }}; margin-left: 8px;">
            {{ $planos->count() }}/5
        </span>
    </div>
    @if(session('error'))
    <div style="background: rgba(255,68,68,0.1); color: var(--error); padding: 12px 16px; border-radius: 10px; border: 1px solid var(--error); margin-bottom: 15px; font-size: 0.85rem;">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif
    <div class="planos-grid">
        @forelse($planos as $plano)
        <div class="plano-card {{ !$plano->ativo ? 'inativo' : '' }}">
            @if(!$plano->ativo)
                <span class="badge-inativo">Inativo</span>
            @endif
            <div class="plano-nome">{{ $plano->nome }}</div>
            <div class="plano-valor">
                R$ {{ number_format($plano->valor, 2, ',', '.') }}
                <small>/mês</small>
            </div>
            <div class="plano-duracao">
                <i class="fas fa-calendar-alt"></i>
                {{ $plano->duracao_meses }} {{ $plano->duracao_meses == 1 ? 'mês' : 'meses' }}
            </div>
            @if($plano->descricao)
            <div class="plano-descricao">{{ $plano->descricao }}</div>
            @endif
            <div class="plano-actions">
                <button class="btn-plano-edit" onclick="abrirEditarPlano({{ $plano->id }}, '{{ addslashes($plano->nome) }}', {{ $plano->valor }}, {{ $plano->duracao_meses }}, '{{ addslashes($plano->descricao ?? '') }}', {{ $plano->ativo ? 'true' : 'false' }})">
                    <i class="fas fa-pen"></i> Editar
                </button>
                <form action="{{ route('academia.planos.destroy', $plano->id) }}" method="POST" onsubmit="return confirm('Remover este plano?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-plano-del"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
        @empty
        @endforelse
        @if($planos->count() < 5)
        <button class="btn-novo-plano" onclick="document.getElementById('modalNovoPLano').style.display='block'">
            <i class="fas fa-plus-circle"></i>
            Novo Plano
        </button>
        @endif
    </div>

    <div class="section-title"><i class="fas fa-users"></i> Últimos Alunos Cadastrados</div>
    <div class="alunos-list">
        @forelse($alunos as $aluno)
        <div class="aluno-item">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($aluno->nome) }}&background=d4ff00&color=000">
            <div>
                <h4>{{ $aluno->nome }}</h4>
                <p>{{ $aluno->email }}</p>
            </div>
            <span style="margin-left: auto; font-size: 0.7rem; color: var(--text-muted);">
                {{ \Carbon\Carbon::parse($aluno->created_at)->format('d/m/Y') }}
            </span>
        </div>
        @empty
        <p style="color: var(--text-muted); text-align: center; padding: 20px;">Nenhum aluno cadastrado ainda.</p>
        @endforelse
    </div>
</div>

{{-- MODAL NOVO PLANO --}}
<div id="modalNovoPLano" class="modal-overlay">
    <div class="modal-content">
        <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">
            <i class="fas fa-plus-circle"></i> NOVO PLANO
        </h2>
        <form action="{{ route('academia.planos.store') }}" method="POST">
            @csrf
            <label>Nome do Plano</label>
            <div class="input-wrapper"><i class="fas fa-tag"></i><input type="text" name="nome" placeholder="Ex: Básico, Premium, Black" required></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Valor Mensal (R$)</label>
                    <div class="input-wrapper"><i class="fas fa-dollar-sign"></i><input type="number" step="0.01" name="valor" min="0" required></div>
                </div>
                <div style="min-width: 0;">
                    <label>Duração (meses)</label>
                    <div class="input-wrapper"><i class="fas fa-calendar"></i><input type="number" name="duracao_meses" min="1" value="1" required></div>
                </div>
            </div>
            <label>Descrição (o que inclui)</label>
            <div class="input-wrapper"><i class="fas fa-list"></i><textarea name="descricao" placeholder="Ex: Musculação + Aulas coletivas + Vestiário"></textarea></div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="document.getElementById('modalNovoPLano').style.display='none'" class="btn-save btn-cancel">Cancelar</button>
                <button type="submit" class="btn-save">Criar Plano</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR PLANO --}}
<div id="modalEditarPlano" class="modal-overlay">
    <div class="modal-content">
        <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">
            <i class="fas fa-pen"></i> EDITAR PLANO
        </h2>
        <form id="formEditarPlano" method="POST">
            @csrf @method('PUT')
            <label>Nome do Plano</label>
            <div class="input-wrapper"><i class="fas fa-tag"></i><input type="text" id="edit_nome" name="nome" required></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Valor Mensal (R$)</label>
                    <div class="input-wrapper"><i class="fas fa-dollar-sign"></i><input type="number" step="0.01" id="edit_valor" name="valor" min="0" required></div>
                </div>
                <div>
                    <label>Duração (meses)</label>
                    <div class="input-wrapper"><i class="fas fa-calendar"></i><input type="number" id="edit_duracao" name="duracao_meses" min="1" required></div>
                </div>
            </div>
            <label>Descrição</label>
            <div class="input-wrapper"><i class="fas fa-list"></i><textarea id="edit_descricao" name="descricao"></textarea></div>
            <label class="toggle-ativo">
                <input type="checkbox" id="edit_ativo" name="ativo" value="1">
                Plano ativo (visível para clientes)
            </label>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="document.getElementById('modalEditarPlano').style.display='none'" class="btn-save btn-cancel">Cancelar</button>
                <button type="submit" class="btn-save">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL ATUALIZAR PERFIL --}}
<div id="modalUpdate" class="modal-overlay">
    <div class="modal-content">
        <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">ATUALIZAR PERFIL</h2>
        <form action="{{ route('academia.update', $academia->id) }}" method="POST">
            @csrf @method('PUT')
            <label>Nome da Academia</label>
            <div class="input-wrapper"><i class="fas fa-building"></i><input type="text" name="nome" value="{{ $academia->nome }}" required></div>
            <label>Cidade</label>
            <div class="input-wrapper"><i class="fas fa-city"></i><input type="text" name="cidade" value="{{ $academia->cidade }}" required></div>
            <label>Valor Mensalidade (R$)</label>
            <div class="input-wrapper"><i class="fas fa-dollar-sign"></i><input type="number" step="0.01" name="valor_mensalidade" value="{{ $academia->valor_mensalidade }}"></div>
            <label>Descrição</label>
            <div class="input-wrapper"><i class="fas fa-pen"></i><textarea name="descricao">{{ $academia->descricao }}</textarea></div>
            <label>Chave PIX (para receber os repasses)</label>
            <div class="input-wrapper"><i class="fas fa-key"></i><input type="text" name="chave_pix" value="{{ $academia->chave_pix }}" placeholder="CPF/CNPJ, e-mail, telefone ou chave aleatória"></div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="document.getElementById('modalUpdate').style.display='none'" class="btn-save btn-cancel">Cancelar</button>
                <button type="submit" class="btn-save">Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL GALERIA ACADEMIA --}}
<div id="modalGaleriaAcademia" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">
            <i class="fas fa-images"></i> GALERIA DE FOTOS
        </h2>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 15px;">
            Mostre sua estrutura para os alunos!
            <span style="float: right; color: var(--primary); font-weight: 700;">
                {{ $academia->fotos->count() }}/5
            </span>
        </p>

        <div class="galeria-grid">
            @foreach($academia->fotos as $foto)
            <div class="galeria-item">
                <img src="{{ asset('storage/' . $foto->path) }}" alt="{{ $foto->legenda }}">
                <div class="galeria-item-overlay">
                    @if($foto->legenda)
                        <span class="galeria-item-legenda">{{ $foto->legenda }}</span>
                    @endif
                    <form action="{{ route('fotos.destroy', $foto->id) }}" method="POST" onsubmit="return confirm('Remover esta foto?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="background: var(--error); color: #fff; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 0.75rem;">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </form>
                </div>
            </div>
            @endforeach

            @if($academia->fotos->count() < 5)
            <label class="galeria-upload-slot" for="inputFotoGaleriaAcademia">
                <i class="fas fa-plus-circle"></i>
                Adicionar
            </label>
            @endif
        </div>

        @if($academia->fotos->count() < 5)
        <form action="{{ route('academia.fotos.store') }}" method="POST" enctype="multipart/form-data" id="formGaleriaAcademia" style="margin-top: 15px;">
            @csrf
            <input type="file" id="inputFotoGaleriaAcademia" name="foto" accept="image/*" style="display: none;" onchange="document.getElementById('formGaleriaAcademia').submit()">
            <label style="margin-top: 10px;">Legenda (opcional)</label>
            <div class="input-wrapper">
                <i class="fas fa-pen"></i>
                <input type="text" name="legenda" placeholder="Ex: Sala de musculação, Vestiário...">
            </div>
            <p style="color: var(--text-muted); font-size: 0.72rem; margin-top: 8px;">
                <i class="fas fa-info-circle"></i> Preencha a legenda antes de selecionar a foto.
            </p>
        </form>
        @endif

        <button type="button" onclick="document.getElementById('modalGaleriaAcademia').style.display='none'" class="btn-save">Fechar</button>
    </div>
</div>

{{-- MODAL CARTEIRA ASAAS --}}
<div id="modalCarteira" class="modal-overlay">
    <div class="modal-content" style="max-width: 480px;">
        <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900; text-align: center;">
            <i class="fas fa-piggy-bank"></i> CARTEIRA DA ACADEMIA
        </h2>

        <div id="carteiraLoading" style="text-align:center; padding:30px 0; color: var(--text-muted);">
            <i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Consultando saldo...
        </div>

        <div id="carteiraConteudo" style="display:none;">
            <div style="background:rgba(212,255,0,0.07); border:1px solid rgba(212,255,0,0.3); border-radius:16px; padding:24px; text-align:center; margin-bottom:20px;">
                <p style="margin:0; color: var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px;">Saldo disponível</p>
                <p id="carteiraValor" style="margin:0; color:#fff; font-size:2rem; font-weight:900;">R$ 0,00</p>
            </div>

            <div id="carteiraSemPix" style="display:none; background:rgba(255,165,0,0.1); border:1px solid rgba(255,165,0,0.3); border-radius:12px; padding:14px; margin-bottom:16px; font-size:0.82rem; color:#ffa500; text-align:center;">
                <i class="fas fa-exclamation-triangle"></i> Cadastre sua chave PIX em "Meu Perfil" para poder sacar.
            </div>

            <div id="carteiraSaqueForm">
                <label style="margin-top:0;">Valor a sacar (R$)</label>
                <div style="display:flex; gap:10px; margin-bottom:16px;">
                    <input id="carteiraValorSaque" type="number" min="0.01" step="0.01" placeholder="0,00"
                        style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:11px 14px; color:#fff; font-size:1rem; outline:none;">
                    <button type="button" onclick="sacarTudo()" style="background:rgba(255,255,255,0.07); color:#fff; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:10px 14px; font-size:0.8rem; cursor:pointer; white-space:nowrap;">
                        Tudo
                    </button>
                </div>

                <p id="carteiraSaqueErro" style="display:none; color:#ff6b6b; font-size:0.82rem; background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); border-radius:8px; padding:10px 14px; margin-bottom:14px;"></p>
                <p id="carteiraSaqueSucesso" style="display:none; color: var(--success); font-weight:700; text-align:center; padding:8px 0; font-size:0.9rem;">✅ Saque solicitado! O valor chegará via PIX em instantes.</p>

                <button type="button" id="btnSacar" onclick="confirmarSaque()"
                    style="width:100%; background:var(--primary); color:#000; border:none; border-radius:12px; padding:14px; font-weight:900; font-size:0.95rem; cursor:pointer;">
                    <i class="fas fa-arrow-down"></i> Sacar via PIX
                </button>
            </div>

            <p style="color: var(--text-muted); font-size:0.7rem; text-align:center; margin-top:14px; margin-bottom:0;">
                O valor é transferido direto para a chave PIX cadastrada no seu perfil.
            </p>
        </div>

        <div id="carteiraSemConta" style="display:none; text-align:center; padding:10px 0;">
            <i class="fas fa-piggy-bank fa-2x" style="margin-bottom:12px; display:block; color: var(--primary);"></i>
            <p style="color: var(--text-muted); font-size:0.85rem; margin-bottom:18px;">
                Ative os recebimentos para que as mensalidades dos planos caiam direto na sua conta (com repasse automático de 90%).
            </p>
            <p id="carteiraContaErro" style="display:none; color:#ff6b6b; font-size:0.82rem; background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); border-radius:8px; padding:10px 14px; margin-bottom:14px;"></p>
            <button type="button" id="btnAtivarConta" onclick="ativarRecebimentos()" class="btn-save" style="margin-top:0;">
                <i class="fas fa-bolt"></i> Ativar recebimentos
            </button>
        </div>

        <button type="button" onclick="fecharCarteira()" class="btn-save btn-cancel" style="margin-top:14px;">Fechar</button>
    </div>
</div>

<script>
    function toggleMenu() {
        const m = document.getElementById('dropdownMenu');
        m.style.display = m.style.display === 'block' ? 'none' : 'block';
    }

    function abrirEditarPlano(id, nome, valor, duracao, descricao, ativo) {
        document.getElementById('formEditarPlano').action = `/academia/planos/${id}`;
        document.getElementById('edit_nome').value = nome;
        document.getElementById('edit_valor').value = valor;
        document.getElementById('edit_duracao').value = duracao;
        document.getElementById('edit_descricao').value = descricao;
        document.getElementById('edit_ativo').checked = ativo;
        document.getElementById('modalEditarPlano').style.display = 'block';
    }

    window.onclick = (e) => {
        ['modalNovoPLano','modalEditarPlano','modalUpdate','modalGaleriaAcademia','modalCarteira'].forEach(id => {
            const el = document.getElementById(id);
            if (e.target == el) el.style.display = 'none';
        });
        if (!e.target.closest('.menu-container')) {
            document.getElementById('dropdownMenu').style.display = 'none';
        }
    }

    // ── CARTEIRA ASAAS ──────────────────────────────
    let carteiraSaldoAtual = 0;

    async function abrirCarteira() {
        document.getElementById('modalCarteira').style.display = 'block';
        document.getElementById('carteiraLoading').style.display = 'block';
        document.getElementById('carteiraConteudo').style.display = 'none';
        document.getElementById('carteiraSemConta').style.display = 'none';
        document.getElementById('carteiraSaqueErro').style.display = 'none';
        document.getElementById('carteiraSaqueSucesso').style.display = 'none';
        document.getElementById('carteiraContaErro').style.display = 'none';

        try {
            const res  = await fetch('/api/academia/saldo', {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await res.json();

            document.getElementById('carteiraLoading').style.display = 'none';

            if (data.sem_conta) {
                document.getElementById('carteiraSemConta').style.display = 'block';
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
            document.getElementById('carteiraSemConta').style.display = 'block';
        }
    }

    function fecharCarteira() {
        document.getElementById('modalCarteira').style.display = 'none';
    }

    function sacarTudo() {
        document.getElementById('carteiraValorSaque').value = carteiraSaldoAtual > 0 ? carteiraSaldoAtual.toFixed(2) : '';
    }

    async function ativarRecebimentos() {
        const btn = document.getElementById('btnAtivarConta');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ativando...';
        document.getElementById('carteiraContaErro').style.display = 'none';

        try {
            const res  = await fetch('/api/academia/criar-conta-asaas', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            const data = await res.json();

            if (!res.ok || !data.success) throw new Error(data.error || 'Não foi possível ativar os recebimentos.');

            // Conta criada — recarrega a carteira já no estado de saldo.
            abrirCarteira();
        } catch (err) {
            document.getElementById('carteiraContaErro').textContent = err.message;
            document.getElementById('carteiraContaErro').style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt"></i> Ativar recebimentos';
        }
    }

    async function confirmarSaque() {
        const valor = parseFloat(document.getElementById('carteiraValorSaque').value);
        if (!valor || valor <= 0) {
            document.getElementById('carteiraSaqueErro').textContent = 'Informe um valor válido.';
            document.getElementById('carteiraSaqueErro').style.display = 'block';
            return;
        }

        const btn = document.getElementById('btnSacar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        document.getElementById('carteiraSaqueErro').style.display = 'none';

        try {
            const res  = await fetch('/api/academia/sacar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body:    JSON.stringify({ valor }),
            });
            const data = await res.json();

            if (!res.ok) throw new Error(data.error || 'Erro ao processar saque.');

            document.getElementById('carteiraSaqueSucesso').style.display = 'block';
            document.getElementById('carteiraSaqueForm').style.display = 'none';
            carteiraSaldoAtual = 0;
            document.getElementById('carteiraValor').textContent = 'R$ 0,00';
        } catch (err) {
            document.getElementById('carteiraSaqueErro').textContent = err.message;
            document.getElementById('carteiraSaqueErro').style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-arrow-down"></i> Sacar via PIX';
        }
    }
</script>
</body>
</html>
