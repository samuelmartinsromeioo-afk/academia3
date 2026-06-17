<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações de Ficha — {{ $personal->nome }}</title>
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
            --border: rgba(255,255,255,0.08);
            --success: #00ff88;
            --error: #ff4444;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; padding: 0; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .page-title { color: var(--primary); font-size: 1.4rem; font-weight: 900; margin: 0 0 6px; }
        .page-sub { color: var(--text-muted); font-size: 0.85rem; margin: 0 0 30px; }
        .card { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border); padding: 24px; margin-bottom: 16px; transition: 0.3s; }
        .card:hover { border-color: rgba(212,255,0,0.2); }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; }
        .badge-pendente { background: rgba(255,165,0,0.15); color: #ffaa00; border: 1px solid rgba(255,165,0,0.3); }
        .badge-concluida { background: rgba(0,255,136,0.1); color: var(--success); border: 1px solid rgba(0,255,136,0.3); }
        .cliente-nome { font-size: 1.1rem; font-weight: 900; margin: 0 0 4px; }
        .valor-tag { color: var(--primary); font-weight: 900; font-size: 0.85rem; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .info-item label { display: block; color: var(--text-muted); font-size: 0.65rem; text-transform: uppercase; font-weight: 800; margin-bottom: 4px; }
        .info-item p { margin: 0; font-size: 0.85rem; color: var(--text-main); background: rgba(255,255,255,0.04); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); line-height: 1.5; }
        .info-item.full { grid-column: span 2; }
        .btn-concluir { background: var(--primary); color: #000; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 900; font-size: 0.8rem; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-concluir:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(212,255,0,0.2); }
        .btn-back { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 3rem; color: var(--text-muted); margin-bottom: 16px; display: block; }
        .empty-state p { color: var(--text-muted); font-size: 0.9rem; }
        .section-label { font-size: 0.7rem; color: var(--primary); text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .section-label::after { content: ""; flex: 1; height: 1px; background: var(--border); }
        .alert-success { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.3); color: var(--success); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .info-item.full { grid-column: span 1; }
            .top-bar { padding: 15px 20px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('personal.dashboard') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="{{ $personal->foto ? asset('storage/'.$personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
        <span style="font-weight:700; font-size:0.9rem;">{{ $personal->nome }}</span>
    </div>
</div>

<div class="container">
    <h1 class="page-title"><i class="fas fa-clipboard-list" style="margin-right:10px;"></i>Solicitações de Ficha</h1>
    <p class="page-sub">Fichas solicitadas pelos seus alunos. Monte a ficha no sistema e depois marque como concluída.</p>

    @if(session('success'))
        <div class="alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif

    @php $pendentes = $solicitacoes->where('status', 'pendente'); $concluidas = $solicitacoes->where('status', 'concluida'); @endphp

    @if($solicitacoes->isEmpty())
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Nenhuma solicitação de ficha ainda.</p>
        </div>
    @else
        @if($pendentes->isNotEmpty())
        <div class="section-label">Pendentes ({{ $pendentes->count() }})</div>

        @foreach($pendentes as $s)
        <div class="card" style="border-left: 4px solid #ffaa00;">
            <div class="card-header">
                <div>
                    <p class="cliente-nome">{{ $s->cliente->nome ?? 'Aluno' }}</p>
                    <span class="valor-tag"><i class="fas fa-dollar-sign"></i> R$ {{ number_format($s->valor, 2, ',', '.') }} — pago</span>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <span class="badge badge-pendente">Pendente</span>
                    <span style="color:var(--text-muted); font-size:0.7rem;">{{ $s->created_at->format('d/m/Y') }}</span>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label><i class="fas fa-bullseye"></i> Objetivos</label>
                    <p>{{ $s->objetivos }}</p>
                </div>
                <div class="info-item">
                    <label><i class="fas fa-signal"></i> Nível de Experiência</label>
                    <p>{{ ucfirst($s->nivel_experiencia) }}</p>
                </div>
                @if($s->condicoes_clinicas)
                <div class="info-item full">
                    <label><i class="fas fa-heartbeat"></i> Condições Clínicas</label>
                    <p>{{ $s->condicoes_clinicas }}</p>
                </div>
                @endif
                @if($s->observacoes)
                <div class="info-item full">
                    <label><i class="fas fa-comment"></i> Observações</label>
                    <p>{{ $s->observacoes }}</p>
                </div>
                @endif
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <a href="{{ route('fichas-treino.aluno', $s->cliente_id) }}" class="btn-concluir" style="background:rgba(212,255,0,0.1); color:var(--primary); border:1px solid var(--primary);">
                    <i class="fas fa-plus"></i> Criar Ficha para {{ $s->cliente->nome ?? 'Aluno' }}
                </a>
                <form action="{{ route('personal.solicitacoes-ficha.concluir', $s->id) }}" method="POST" onsubmit="return confirm('Marcar como concluída?')">
                    @csrf
                    <button type="submit" class="btn-concluir"><i class="fas fa-check"></i> Marcar como Concluída</button>
                </form>
            </div>
        </div>
        @endforeach
        @endif

        @if($concluidas->isNotEmpty())
        <div class="section-label" style="margin-top:30px;">Concluídas ({{ $concluidas->count() }})</div>

        @foreach($concluidas as $s)
        <div class="card" style="opacity:0.7;">
            <div class="card-header">
                <div>
                    <p class="cliente-nome">{{ $s->cliente->nome ?? 'Aluno' }}</p>
                    <span class="valor-tag">R$ {{ number_format($s->valor, 2, ',', '.') }}</span>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <span class="badge badge-concluida">Concluída</span>
                    <span style="color:var(--text-muted); font-size:0.7rem;">{{ $s->updated_at->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Objetivos</label>
                    <p>{{ $s->objetivos }}</p>
                </div>
                <div class="info-item">
                    <label>Nível</label>
                    <p>{{ ucfirst($s->nivel_experiencia) }}</p>
                </div>
            </div>
        </div>
        @endforeach
        @endif
    @endif
</div>

</body>
</html>
