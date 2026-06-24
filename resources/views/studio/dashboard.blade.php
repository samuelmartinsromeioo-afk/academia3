<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Studio | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .top-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        .btn-top {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 9px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.78rem;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
        }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }
        .btn-top.primary { background: var(--primary); color: #000; border-color: var(--primary); }
        .btn-top.primary:hover { transform: translateY(-1px); }

        /* MENU HAMBÚRGUER */
        .menu-container { position: relative; display: flex; align-items: center; gap: 14px; }

        .dots-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--primary);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            font-size: 1rem;
        }
        .dots-btn:hover { background: var(--primary); color: #000; }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 50px;
            left: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 260px;
            z-index: 1000;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
        }

        .menu-rating-header {
            padding: 20px;
            background: rgba(212, 255, 0, 0.05);
            border-bottom: 1px solid var(--border);
            text-align: center;
        }
        .menu-rating-header .rating-number { font-size: 1.6rem; font-weight: 900; display: block; }
        .menu-rating-header .stars-row { color: var(--primary); margin-bottom: 5px; font-size: 0.9rem; }
        .menu-rating-header .rating-label { color: var(--text-muted); font-size: 0.72rem; text-transform: uppercase; font-weight: 700; }

        .dropdown-menu button,
        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: #fff;
            font-size: 14px;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            font-family: inherit;
            text-decoration: none;
        }
        .dropdown-menu button:hover,
        .dropdown-menu a:hover { background: rgba(255, 255, 255, 0.05); color: var(--primary); }
        .dropdown-menu i { width: 18px; text-align: center; }

        .container { max-width: 1200px; margin: 0 auto; padding: 36px 20px; }

        .welcome { margin-bottom: 28px; }
        .welcome h1 { font-size: 1.6rem; font-weight: 900; }
        .welcome h1 em { color: var(--primary); font-style: normal; }
        .welcome p { color: var(--text-muted); margin-top: 4px; font-size: 0.9rem; }

        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; }
        .alert-success { background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.4); color: #4caf50; }
        .alert-error { background: rgba(255,68,68,0.1); border: 1px solid rgba(255,68,68,0.3); color: #ff6b6b; }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            transition: 0.2s;
        }
        .stat-card:hover { border-color: rgba(212,255,0,0.3); }

        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(212, 255, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.1rem;
            margin-bottom: 14px;
        }

        .stat-card .stat-value { font-size: 1.6rem; font-weight: 900; }
        .stat-card .stat-label { color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; margin-top: 4px; }

        .section-title {
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--primary);
            margin: 36px 0 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section-title::before { content: ''; width: 4px; height: 18px; background: var(--primary); }

        .panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 26px;
        }

        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 12px;
            text-align: left;
            font-weight: 800;
            font-size: 0.72rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--border);
        }
        td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        tbody tr:last-child td { border-bottom: none; }

        .empty { color: var(--text-muted); text-align: center; padding: 24px 0; font-size: 0.9rem; }

        .btn-sm {
            border: none;
            border-radius: 6px;
            padding: 7px 12px;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-family: inherit;
        }
        .btn-sm.outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); }
        .btn-sm.outline:hover { background: var(--primary); color: #000; }
        .btn-sm.danger { background: transparent; border: 1px solid var(--error); color: var(--error); }
        .btn-sm.danger:hover { background: var(--error); color: #fff; }

        /* FORMULÁRIOS */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .full-width { grid-column: span 2; }

        .form-group label {
            display: block;
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            outline: none;
            font-family: inherit;
            font-size: 0.9rem;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--primary); }

        .btn-save {
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 13px 22px;
            font-weight: 900;
            font-size: 0.85rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.2s;
            font-family: inherit;
        }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(212,255,0,0.15); }

        /* PLANOS */
        .plano-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .plano-card:last-child { margin-bottom: 0; }
        .plano-info h4 { font-size: 1rem; margin-bottom: 4px; }
        .plano-info p { color: var(--text-muted); font-size: 0.8rem; }
        .plano-valor { color: var(--primary); font-weight: 900; font-size: 1.1rem; white-space: nowrap; }
        .plano-actions { display: flex; gap: 8px; }

        .badge-inativo {
            background: rgba(255,68,68,0.1);
            color: var(--error);
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 800;
            text-transform: uppercase;
            margin-left: 8px;
        }

        /* GALERIA */
        .galeria-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; margin-top: 16px; }
        .galeria-item { position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--border); aspect-ratio: 1; }
        .galeria-item img { width: 100%; height: 100%; object-fit: cover; }
        .galeria-item button {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.7);
            border: 1px solid var(--error);
            color: var(--error);
            border-radius: 8px;
            width: 30px;
            height: 30px;
            cursor: pointer;
        }

        /* MODAIS */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            overflow-y: auto;
            backdrop-filter: blur(6px);
            padding: 40px 20px;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
        }
        .modal-content h2 { margin-bottom: 20px; }

        .modal-close {
            float: right;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.3rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .top-bar { padding: 14px 16px; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .panel { overflow-x: auto; }
        }
    </style>
</head>
<body>

{{-- TOP BAR --}}
<div class="top-bar">
    <div class="menu-container">
        <button class="dots-btn" id="btnMenu" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
        <div class="dropdown-menu" id="dropdownMenu">
            <div class="menu-rating-header">
                @php
                    $media = (float) ($studio->media_avaliacao ?? 0);
                    $totalAvals = $studio->avaliacoes ? $studio->avaliacoes->count() : 0;
                @endphp
                <span class="rating-number">{{ number_format($media, 1) }}</span>
                <div class="stars-row">
                    @for ($i = 1; $i <= 5; $i++)
                        @if ($i <= $media) <i class="fas fa-star"></i>
                        @elseif ($i - 0.5 <= $media) <i class="fas fa-star-half-alt"></i>
                        @else <i class="far fa-star"></i>
                        @endif
                    @endfor
                </div>
                <span class="rating-label">{{ $totalAvals }} Avaliações</span>
            </div>
            <button type="button" id="btnOpenPlanos"><i class="fas fa-id-card" style="color: var(--primary);"></i> Meus Planos</button>
            <button type="button" id="btnOpenAlunos"><i class="fas fa-users"></i> Meus Alunos</button>
            <button type="button" id="btnOpenPerfil"><i class="fas fa-user-edit"></i> Editar Perfil</button>
            <button type="button" id="btnOpenGaleria"><i class="fas fa-images"></i> Minha Galeria</button>
            <button type="button" id="btnOpenCarteira"><i class="fas fa-piggy-bank" style="color: var(--primary);"></i> Minha Carteira</button>
            <a href="{{ route('studio.horarios') }}"><i class="fas fa-clock"></i> Meus Horários</a>
            <a href="{{ route('lgpd.meus-dados') }}"><i class="fas fa-user-shield"></i> Privacidade e meus dados</a>
            <form action="{{ route('login.logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" style="color: var(--error);"><i class="fas fa-power-off"></i> Sair</button>
            </form>
        </div>
        <div class="logo">SNR<span>FIT</span> <span style="font-family:'Inter'; font-size:0.65rem; color:var(--text-muted); letter-spacing:1px; text-transform:uppercase;">| Studio</span></div>
    </div>
    <span style="font-weight: 700; font-size: 0.9rem;"><i class="fas fa-spa" style="color: var(--primary); margin-right: 8px;"></i>{{ $studio->nome }}</span>
</div>

<div class="container">

    @if(session('success'))
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
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
        <h1>Olá, <em>{{ $studio->nome }}</em> 👋</h1>
        <p>Acompanhe seus alunos, a agenda do dia e suas avaliações. Use o menu para gerenciar o resto.</p>
    </div>

    {{-- STATS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value">{{ $totalAlunos }}</div>
            <div class="stat-label">Alunos vinculados</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-value">{{ $agendaHoje->count() }}</div>
            <div class="stat-label">Aulas hoje</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-value">{{ $studio->media_avaliacao }} <span style="font-size:0.9rem; color:var(--text-muted);">/ 5</span></div>
            <div class="stat-label">Avaliação média</div>
        </div>
    </div>

    {{-- AGENDA DE HOJE --}}
    <h2 class="section-title"><i class="fas fa-calendar-day"></i> Agenda de hoje ({{ today()->format('d/m/Y') }})</h2>
    <div class="panel">
        @if($agendaHoje->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Horário</th>
                        <th>Aluno</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agendaHoje as $aula)
                        <tr>
                            <td style="color: var(--primary); font-weight: 700;">
                                {{ substr($aula->hora_inicio, 0, 5) }} - {{ substr($aula->hora_fim, 0, 5) }}
                            </td>
                            <td>{{ $aula->cliente?->nome ?? 'N/A' }}</td>
                            <td style="text-transform: capitalize;">{{ $aula->tipo_aula }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty"><i class="fas fa-mug-hot"></i> Nenhuma aula agendada para hoje.</p>
        @endif
    </div>

    {{-- AVALIAÇÕES RECENTES --}}
    <h2 class="section-title"><i class="fas fa-star"></i> Avaliações recentes</h2>
    <div class="panel">
        @php $avaliacoesRecentes = $studio->avaliacoes->sortByDesc('created_at')->take(5); @endphp
        @forelse($avaliacoesRecentes as $av)
            <div style="padding: 14px 0; border-bottom: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span style="font-weight: 700; font-size: 0.88rem;"><i class="fas fa-user-circle" style="color: var(--primary);"></i> {{ $av->cliente?->nome ?? 'Aluno' }}</span>
                    <span style="color: #ffc107; font-size: 0.75rem;">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="fa-star {{ $i <= $av->nota ? 'fas' : 'far' }}"></i>
                        @endfor
                    </span>
                </div>
                @if($av->comentario)
                    <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">{{ $av->comentario }}</p>
                @endif
                <div style="color: var(--text-muted); font-size: 0.7rem; margin-top: 4px;">{{ $av->created_at->format('d/m/Y') }}</div>
            </div>
        @empty
            <p class="empty">Nenhuma avaliação ainda. Elas aparecem aqui depois que seus alunos avaliarem o studio.</p>
        @endforelse
    </div>
</div>

{{-- MODAL PLANOS --}}
<div id="modalPlanos" class="modal-overlay">
    <div class="modal-content" style="max-width: 720px;">
        <button type="button" class="modal-close" onclick="document.getElementById('modalPlanos').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="fas fa-id-card"></i> Meus Planos ({{ $planos->count() }}/5)</h2>
        @forelse($planos as $plano)
            <div class="plano-card">
                <div class="plano-info">
                    <h4>{{ $plano->nome }} @if(!$plano->ativo)<span class="badge-inativo">Inativo</span>@endif</h4>
                    <p>{{ $plano->duracao_meses }} {{ $plano->duracao_meses > 1 ? 'meses' : 'mês' }} • {{ $plano->descricao ?? 'Sem descrição' }}</p>
                </div>
                <div class="plano-valor">R$ {{ number_format($plano->valor, 2, ',', '.') }}/mês</div>
                <div class="plano-actions">
                    <button type="button" class="btn-sm outline" onclick="abrirEditarPlano({{ $plano->id }}, {!! json_encode($plano->nome) !!}, {{ $plano->valor }}, {{ $plano->duracao_meses }}, {!! json_encode($plano->descricao ?? '') !!}, {{ $plano->ativo ? 'true' : 'false' }})">
                        <i class="fas fa-pen"></i> Editar
                    </button>
                    <form method="POST" action="{{ route('studio.planos.destroy', $plano->id) }}" onsubmit="return confirm('Excluir este plano?');" style="margin:0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-sm danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        @empty
            <p class="empty">Nenhum plano criado ainda. Crie seu primeiro plano para receber alunos mensalistas.</p>
        @endforelse

        @if($planos->count() < 5)
            <div style="margin-top: 18px; text-align: right;">
                <button type="button" class="btn-save" onclick="document.getElementById('modalNovoPlano').style.display='block'">
                    <i class="fas fa-plus"></i> Novo Plano
                </button>
            </div>
        @endif
    </div>
</div>

{{-- MODAL ALUNOS --}}
<div id="modalAlunos" class="modal-overlay">
    <div class="modal-content" style="max-width: 760px;">
        <button type="button" class="modal-close" onclick="document.getElementById('modalAlunos').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="fas fa-users"></i> Meus Alunos</h2>
        @if($alunos->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Plano</th>
                        <th>Status</th>
                        <th style="text-align:center;">Aulas este mês</th>
                        <th style="text-align:center;">Total de aulas</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alunos as $aluno)
                        <tr>
                            <td style="font-weight: 600;">{{ $aluno->nome }}</td>
                            <td style="color: var(--text-muted);">{{ $aluno->email }}</td>
                            <td>{{ $aluno->studioPlano?->nome ?? 'Sem plano' }}</td>
                            <td>
                                @if($aluno->studio_plano_ativo)
                                    <span style="color: var(--success); font-weight: 700; font-size: 0.8rem;"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Ativo</span>
                                @else
                                    <span style="color: var(--text-muted); font-weight: 700; font-size: 0.8rem;"><i class="fas fa-circle" style="font-size:0.5rem;"></i> Inativo</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <span style="font-weight:700; color:{{ $aluno->frequencia_mes > 0 ? 'var(--primary)' : 'var(--text-muted)' }};">
                                    {{ $aluno->frequencia_mes }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <span style="font-weight:700; color:var(--text-muted);">{{ $aluno->frequencia_total }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('studio.alunos.desvincular', $aluno->id) }}" onsubmit="return confirm('Desvincular {{ $aluno->nome }} do studio?');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm danger"><i class="fas fa-user-minus"></i> Desvincular</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Nenhum aluno vinculado ainda. Seus alunos aparecem aqui após contratarem um plano.</p>
        @endif
    </div>
</div>

{{-- MODAL GALERIA --}}
<div id="modalGaleria" class="modal-overlay">
    <div class="modal-content" style="max-width: 720px;">
        <button type="button" class="modal-close" onclick="document.getElementById('modalGaleria').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="fas fa-images"></i> Minha Galeria ({{ $studio->fotos->count() }}/5)</h2>
        @if($studio->fotos->count() < 5)
            <form method="POST" action="{{ route('studio.fotos.store') }}" enctype="multipart/form-data" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                @csrf
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Adicionar foto do espaço</label>
                    <input type="file" name="foto" accept="image/*" required>
                </div>
                <div class="form-group" style="flex:1; min-width:200px;">
                    <label>Legenda (opcional)</label>
                    <input type="text" name="legenda" maxlength="255" placeholder="Ex: Sala de pilates">
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-upload"></i> Enviar</button>
            </form>
        @endif

        @if($studio->fotos->count() > 0)
            <div class="galeria-grid">
                @foreach($studio->fotos as $foto)
                    <div class="galeria-item" id="foto-{{ $foto->id }}">
                        <img src="{{ asset('storage/' . $foto->path) }}" alt="{{ $foto->legenda ?? 'Foto do studio' }}">
                        <button type="button" onclick="excluirFoto({{ $foto->id }})" title="Excluir"><i class="fas fa-trash"></i></button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="empty" style="margin-top:16px;">Nenhuma foto ainda. Fotos ajudam os alunos a conhecerem seu espaço!</p>
        @endif
    </div>
</div>

{{-- MODAL PERFIL --}}
<div id="modalPerfil" class="modal-overlay">
    <div class="modal-content" style="max-width: 720px;">
        <button type="button" class="modal-close" onclick="document.getElementById('modalPerfil').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="fas fa-user-edit"></i> Editar Perfil</h2>
        <form method="POST" action="{{ route('studio.update', $studio->id) }}" class="form-grid">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>Nome do Studio</label>
                <input type="text" name="nome" value="{{ old('nome', $studio->nome) }}" required>
            </div>

            <div class="form-group">
                <label>WhatsApp</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp', $studio->whatsapp) }}" placeholder="(11) 99999-9999">
            </div>

            <div class="form-group">
                <label>Valor da Aula Avulsa (R$)</label>
                <input type="number" step="0.01" min="0" name="valor_aula" value="{{ old('valor_aula', $studio->valor_aula) }}" required>
            </div>

            <div class="form-group">
                <label>Capacidade por Horário</label>
                <input type="number" min="1" max="500" name="capacidade_padrao" value="{{ old('capacidade_padrao', $studio->capacidade_padrao) }}" required>
            </div>

            <div class="form-group full-width">
                <label>Chave PIX (para receber os saques)</label>
                <input type="text" name="chave_pix" value="{{ old('chave_pix', $studio->chave_pix) }}" placeholder="CPF/CNPJ, e-mail, telefone ou chave aleatória">
            </div>

            <div class="form-group">
                <label>Tipo de Studio</label>
                <select name="tipo" style="width:100%; background:var(--input-bg); border:1px solid var(--border); border-radius:10px; padding:12px; color:#fff;">
                    <option value="yoga_pilates" {{ ($studio->tipo ?? '') === 'yoga_pilates' ? 'selected' : '' }}>🧘 Yoga / Pilates</option>
                    <option value="luta"         {{ ($studio->tipo ?? '') === 'luta'         ? 'selected' : '' }}>🥊 Luta / Artes Marciais</option>
                    <option value="crossfit"     {{ ($studio->tipo ?? '') === 'crossfit'     ? 'selected' : '' }}>🏋️ CrossFit / Funcional</option>
                    <option value="fitness"      {{ ($studio->tipo ?? 'fitness') === 'fitness' ? 'selected' : '' }}>💪 Fitness / Musculação</option>
                    <option value="danca"        {{ ($studio->tipo ?? '') === 'danca'        ? 'selected' : '' }}>💃 Dança</option>
                    <option value="outros"       {{ ($studio->tipo ?? '') === 'outros'       ? 'selected' : '' }}>🏃 Outros</option>
                </select>
            </div>

            <div class="form-group">
                <label>Modalidades</label>
                <input type="text" name="modalidades" value="{{ old('modalidades', $studio->modalidades) }}" placeholder="Ex: Pilates, Funcional, Yoga">
            </div>

            <div class="form-group full-width">
                <label>Descrição</label>
                <textarea name="descricao" rows="3">{{ old('descricao', $studio->descricao) }}</textarea>
            </div>

            <div class="full-width" style="text-align: right;">
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Salvar Perfil</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL NOVO PLANO --}}
<div id="modalNovoPlano" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="document.getElementById('modalNovoPlano').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="fas fa-plus-circle"></i> Novo Plano</h2>
        <form method="POST" action="{{ route('studio.planos.store') }}" class="form-grid">
            @csrf
            <div class="form-group">
                <label>Nome do Plano</label>
                <input type="text" name="nome" placeholder="Ex: Mensal Ilimitado" required>
            </div>
            <div class="form-group">
                <label>Valor Mensal (R$)</label>
                <input type="number" step="0.01" min="0" name="valor" required>
            </div>
            <div class="form-group">
                <label>Duração (meses)</label>
                <input type="number" min="1" name="duracao_meses" value="1" required>
            </div>
            <div class="form-group full-width">
                <label>Descrição</label>
                <textarea name="descricao" rows="2" placeholder="Ex: 3x por semana, todas as modalidades"></textarea>
            </div>
            <div class="full-width" style="text-align: right;">
                <button type="submit" class="btn-save"><i class="fas fa-check"></i> Criar Plano</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR PLANO --}}
<div id="modalEditarPlano" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="document.getElementById('modalEditarPlano').style.display='none'">&times;</button>
        <h2 style="color: var(--primary);"><i class="fas fa-pen"></i> Editar Plano</h2>
        <form method="POST" id="formEditarPlano" class="form-grid">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>Nome do Plano</label>
                <input type="text" name="nome" id="editNome" required>
            </div>
            <div class="form-group">
                <label>Valor Mensal (R$)</label>
                <input type="number" step="0.01" min="0" name="valor" id="editValor" required>
            </div>
            <div class="form-group">
                <label>Duração (meses)</label>
                <input type="number" min="1" name="duracao_meses" id="editDuracao" required>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0;">
                    <input type="checkbox" name="ativo" id="editAtivo" style="width:auto;"> Plano ativo
                </label>
            </div>
            <div class="form-group full-width">
                <label>Descrição</label>
                <textarea name="descricao" id="editDescricao" rows="2"></textarea>
            </div>
            <div class="full-width" style="text-align: right;">
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL CARTEIRA ASAAS --}}
<div id="modalCarteira" class="modal-overlay">
    <div class="modal-content" style="max-width:480px;">
        <button type="button" class="modal-close" onclick="fecharCarteira()">&times;</button>
        <h2 style="color:var(--primary); font-size:1.4rem; font-weight:900; text-align:center;">
            <i class="fas fa-piggy-bank"></i> CARTEIRA DO STUDIO
        </h2>

        <div id="carteiraLoading" style="text-align:center; padding:30px 0; color:#a0a0a0;">
            <i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Consultando saldo...
        </div>

        <div id="carteiraConteudo" style="display:none;">
            <div style="background:rgba(212,255,0,0.07); border:1px solid rgba(212,255,0,0.3); border-radius:16px; padding:24px; text-align:center; margin-bottom:20px;">
                <p style="margin:0; color:#a0a0a0; font-size:0.75rem; text-transform:uppercase; margin-bottom:8px;">Saldo disponível</p>
                <p id="carteiraValor" style="margin:0; color:#fff; font-size:2rem; font-weight:900;">R$ 0,00</p>
            </div>

            <div id="carteiraSemPix" style="display:none; background:rgba(255,165,0,0.1); border:1px solid rgba(255,165,0,0.3); border-radius:12px; padding:14px; margin-bottom:16px; font-size:0.82rem; color:#ffa500; text-align:center;">
                <i class="fas fa-exclamation-triangle"></i> Cadastre sua chave PIX no perfil para poder sacar.
            </div>

            <div id="carteiraSaqueForm">
                <label style="color:#a0a0a0; font-size:0.75rem; display:block; margin-bottom:6px;">Valor a sacar (R$)</label>
                <div style="display:flex; gap:10px; margin-bottom:16px;">
                    <input id="carteiraValorSaque" type="number" min="0.01" step="0.01" placeholder="0,00"
                        style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:11px 14px; color:#fff; font-size:1rem; outline:none;">
                    <button onclick="sacarTudo()" style="background:rgba(255,255,255,0.07); color:#fff; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:10px 14px; font-size:0.8rem; cursor:pointer; white-space:nowrap;">
                        Tudo
                    </button>
                </div>

                <p id="carteiraSaqueErro" style="display:none; color:#ff6b6b; font-size:0.82rem; background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); border-radius:8px; padding:10px 14px; margin-bottom:14px;"></p>
                <p id="carteiraSaqueSucesso" style="display:none; color:#4caf50; font-weight:700; text-align:center; padding:8px 0; font-size:0.9rem;">✅ Saque solicitado! O valor chegará via PIX em instantes.</p>

                <button id="btnSacar" onclick="confirmarSaque()"
                    style="width:100%; background:var(--primary); color:#000; border:none; border-radius:12px; padding:14px; font-weight:900; font-size:0.95rem; cursor:pointer;">
                    <i class="fas fa-arrow-down"></i> Sacar via PIX
                </button>
            </div>

            <p style="color:#a0a0a0; font-size:0.7rem; text-align:center; margin-top:14px; margin-bottom:0;">
                O valor é transferido direto para a chave PIX cadastrada no seu perfil.
            </p>
        </div>

        <div id="carteiraSemConta" style="display:none; text-align:center; padding:20px 0; color:#a0a0a0; font-size:0.85rem;">
            <i class="fas fa-info-circle fa-2x" style="margin-bottom:12px; display:block;"></i>
            Sua conta de repasse ainda está sendo configurada pelo administrador.<br>
            Os saques estarão disponíveis em breve.
        </div>
    </div>
</div>

<script>
    // ── MENU ──────────────────────────────
    function toggleMenu() {
        const m = document.getElementById('dropdownMenu');
        m.style.display = m.style.display === 'block' ? 'none' : 'block';
    }

    document.getElementById('btnOpenPlanos').onclick = () => {
        document.getElementById('modalPlanos').style.display = 'block';
        toggleMenu();
    };
    document.getElementById('btnOpenAlunos').onclick = () => {
        document.getElementById('modalAlunos').style.display = 'block';
        toggleMenu();
    };
    document.getElementById('btnOpenPerfil').onclick = () => {
        document.getElementById('modalPerfil').style.display = 'block';
        toggleMenu();
    };
    document.getElementById('btnOpenGaleria').onclick = () => {
        document.getElementById('modalGaleria').style.display = 'block';
        toggleMenu();
    };
    document.getElementById('btnOpenCarteira').onclick = () => {
        abrirCarteira();
        toggleMenu();
    };

    document.addEventListener('click', (e) => {
        const menu = document.getElementById('dropdownMenu');
        if (menu.style.display === 'block' && !e.target.closest('.menu-container')) {
            menu.style.display = 'none';
        }
    });

    // ── PLANOS ──────────────────────────────
    function abrirEditarPlano(id, nome, valor, duracao, descricao, ativo) {
        const form = document.getElementById('formEditarPlano');
        form.action = '/studio/planos/' + id;
        document.getElementById('editNome').value = nome;
        document.getElementById('editValor').value = valor;
        document.getElementById('editDuracao').value = duracao;
        document.getElementById('editDescricao').value = descricao;
        document.getElementById('editAtivo').checked = ativo;
        document.getElementById('modalEditarPlano').style.display = 'block';
    }

    // ── GALERIA ──────────────────────────────
    async function excluirFoto(id) {
        if (!confirm('Excluir esta foto?')) return;
        try {
            const res = await fetch('/fotos/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.sucesso) {
                document.getElementById('foto-' + id).remove();
            } else {
                alert(data.erro || 'Erro ao excluir foto.');
            }
        } catch (_) {
            alert('Erro ao excluir foto.');
        }
    }

    // ── CARTEIRA ASAAS ──────────────────────────────
    let carteiraSaldoAtual = 0;

    async function abrirCarteira() {
        const modal = document.getElementById('modalCarteira');
        modal.style.display = 'block';
        document.getElementById('carteiraLoading').style.display = 'block';
        document.getElementById('carteiraConteudo').style.display = 'none';
        document.getElementById('carteiraSemConta').style.display = 'none';
        document.getElementById('carteiraSaqueErro').style.display = 'none';
        document.getElementById('carteiraSaqueSucesso').style.display = 'none';

        try {
            const res  = await fetch('/api/studio/saldo', {
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
            const res  = await fetch('/api/studio/sacar', {
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

    // Fechar modais clicando fora
    document.querySelectorAll('.modal-overlay').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.style.display = 'none';
        });
    });
</script>

</body>
</html>
