<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações de personais — {{ $academia->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #d4ff00;
            --accent: #F4BE16;
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
        button { font-family: inherit; }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 40px; background: rgba(0,0,0,0.4);
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(10px);
        }
        .back-btn {
            background: var(--card-bg); border: 1px solid var(--border); color: var(--primary);
            width: 40px; height: 40px; border-radius: 10px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: 0.3s; font-size: 1.1rem;
        }
        .back-btn:hover { background: var(--primary); color: #000; }
        .profile-header { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 0.9rem; }

        .container { max-width: 1000px; margin: 35px auto; padding: 0 20px; }
        h1 { font-size: 1.7rem; font-weight: 900; color: var(--primary); margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
        .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 26px; }

        .section-title {
            font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;
            color: var(--text-muted); margin: 30px 0 14px; display: flex; align-items: center; gap: 8px;
        }
        .section-title i { color: var(--accent); }

        .alert-success { background: rgba(0,255,136,0.1); color: var(--success); border: 1px solid var(--success); padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-error { background: rgba(255,68,68,0.1); color: var(--error); border: 1px solid var(--error); padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; }

        .card {
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px;
            padding: 18px 20px; margin-bottom: 14px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .card.pendente { border-color: rgba(244,190,22,0.35); }
        .avatar { width: 54px; height: 54px; border-radius: 14px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0; }
        .avatar-ph {
            width: 54px; height: 54px; border-radius: 14px; flex-shrink: 0;
            background: rgba(244,190,22,0.12); color: var(--accent);
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }
        .info { flex: 1; min-width: 180px; }
        .info .nome { font-weight: 800; font-size: 1rem; }
        .info .meta { color: var(--text-muted); font-size: 0.8rem; margin-top: 2px; }

        .badge { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; padding: 5px 12px; border-radius: 20px; letter-spacing: 0.5px; }
        .badge-pendente { background: rgba(244,190,22,0.15); color: var(--accent); border: 1px solid rgba(244,190,22,0.4); }
        .badge-aprovado { background: rgba(0,255,136,0.12); color: var(--success); border: 1px solid rgba(0,255,136,0.35); }

        .actions { display: flex; gap: 10px; }
        .btn-aprovar { background: var(--primary); color: #000; border: none; padding: 9px 16px; border-radius: 9px; cursor: pointer; font-weight: 800; font-size: 0.78rem; }
        .btn-aprovar:hover { background: #e8ff40; }
        .btn-rejeitar { background: rgba(255,68,68,0.1); color: var(--error); border: 1px solid rgba(255,68,68,0.35); padding: 9px 16px; border-radius: 9px; cursor: pointer; font-weight: 800; font-size: 0.78rem; }
        .btn-rejeitar:hover { background: var(--error); color: #fff; }

        .empty { color: var(--text-muted); font-size: 0.9rem; background: var(--card-bg); border: 1px dashed var(--border); border-radius: 14px; padding: 26px; text-align: center; }

        @media (max-width: 640px) { .top-bar { padding: 15px 20px; } .actions { width: 100%; } .actions form, .actions button { flex: 1; } }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <button class="back-btn" onclick="window.location.href='{{ route('academia.dashboard') }}'" title="Voltar">
        <i class="ph ph-arrow-left"></i>
    </button>
    <div class="profile-header">
        <i class="ph ph-user-list" style="color: var(--primary);"></i>
        {{ $academia->nome }}
    </div>
</div>

<div class="container">
    <h1><i class="ph ph-lightning"></i> Solicitações de personais</h1>
    <p class="subtitle">Personais que pediram para se vincular à sua academia. Ao aprovar, eles passam a aparecer na sua página pública, onde os alunos podem fechar pacotes direto com eles.</p>

    @if(session('success'))
        <div class="alert-success"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>
    @endif

    <div class="section-title"><i class="ph ph-hourglass-medium"></i> Pendentes ({{ $pendentes->count() }})</div>

    @forelse($pendentes as $p)
        <div class="card pendente">
            @if($p->foto)
                <img src="{{ asset('storage/' . $p->foto) }}" alt="{{ $p->nome }}" class="avatar">
            @elseif($p->fotos->isNotEmpty())
                <img src="{{ asset('storage/' . $p->fotos->first()->path) }}" alt="{{ $p->nome }}" class="avatar">
            @else
                <div class="avatar-ph"><i class="ph ph-user"></i></div>
            @endif
            <div class="info">
                <div class="nome">{{ $p->nome }}</div>
                <div class="meta">
                    <i class="ph ph-map-pin"></i> {{ $p->cidade ?? 'Cidade não informada' }}{{ $p->estado ? ' - ' . $p->estado : '' }}
                    @if($p->cref) &nbsp;•&nbsp; CREF {{ $p->cref }} @endif
                </div>
            </div>
            <span class="badge badge-pendente">Pendente</span>
            <div class="actions">
                <form action="{{ route('academia.solicitacoes.aprovar', $p->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-aprovar"><i class="ph ph-check"></i> Aprovar</button>
                </form>
                <form action="{{ route('academia.solicitacoes.rejeitar', $p->id) }}" method="POST" onsubmit="return confirm('Rejeitar a solicitação de {{ addslashes($p->nome) }}?');">
                    @csrf
                    <button type="submit" class="btn-rejeitar"><i class="ph ph-x"></i> Rejeitar</button>
                </form>
            </div>
        </div>
    @empty
        <div class="empty"><i class="ph ph-check-circle" style="color: var(--success);"></i> Nenhuma solicitação pendente no momento.</div>
    @endforelse

    <div class="section-title"><i class="ph ph-users-three"></i> Personais vinculados ({{ $aprovados->count() }})</div>

    @forelse($aprovados as $p)
        <div class="card">
            @if($p->foto)
                <img src="{{ asset('storage/' . $p->foto) }}" alt="{{ $p->nome }}" class="avatar">
            @elseif($p->fotos->isNotEmpty())
                <img src="{{ asset('storage/' . $p->fotos->first()->path) }}" alt="{{ $p->nome }}" class="avatar">
            @else
                <div class="avatar-ph"><i class="ph ph-user"></i></div>
            @endif
            <div class="info">
                <div class="nome">{{ $p->nome }}</div>
                <div class="meta"><i class="ph ph-map-pin"></i> {{ $p->cidade ?? 'Cidade não informada' }}{{ $p->estado ? ' - ' . $p->estado : '' }}</div>
            </div>
            <span class="badge badge-aprovado"><i class="ph ph-check"></i> Aprovado</span>
        </div>
    @empty
        <div class="empty">Você ainda não aprovou nenhum personal.</div>
    @endforelse
</div>

</body>
</html>
