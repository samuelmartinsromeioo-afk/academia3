<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliação Física — {{ $academia->nome }}</title>
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
            --border: rgba(255,255,255,0.08);
            --success: #00ff88;
            --error: #ff4444;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; padding: 0; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .page-sub { color: var(--text-muted); font-size: 0.85rem; margin: 0 0 30px; }
        .card { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border); padding: 24px; margin-bottom: 16px; transition: 0.3s; }
        .card:hover { border-color: rgba(212,255,0,0.2); }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; }
        .badge-plano { background: rgba(212,255,0,0.1); color: var(--primary); border: 1px solid rgba(212,255,0,0.3); }
        .badge-sem { background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border); }
        .cliente-nome { font-size: 1.1rem; font-weight: 900; margin: 0 0 4px; }
        .btn-primary { background: var(--primary); color: #000; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 900; font-size: 0.8rem; cursor: pointer; text-transform: uppercase; transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(212,255,0,0.2); }
        .btn-back { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 3rem; color: var(--text-muted); margin-bottom: 16px; display: block; }
        .empty-state p { color: var(--text-muted); font-size: 0.9rem; }
        .section-label { font-size: 0.7rem; color: var(--primary); text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .section-label::after { content: ""; flex: 1; height: 1px; background: var(--border); }
        .alert-success { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.3); color: var(--success); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }
        .alert-error { background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.3); color: var(--error); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }
        .search-wrapper { display: flex; align-items: center; background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 12px; padding: 0 14px; margin-bottom: 24px; max-width: 460px; }
        .search-wrapper i { color: var(--primary); }
        .search-wrapper input { flex: 1; background: transparent; border: none; padding: 13px 12px; color: #fff; outline: none; font-size: 0.9rem; font-family: inherit; }
        .aluno-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .aluno-meta { color: var(--text-muted); font-size: 0.75rem; }
        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('academia.alunos') }}" class="btn-back"><i class="ph ph-arrow-left"></i> Voltar aos alunos</a>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        <span style="font-weight:900; letter-spacing:2px;">SNR<span style="color:var(--primary);">FIT</span></span>
        <span style="font-weight:700; font-size:0.9rem;">{{ $academia->nome }}</span>
    </div>
</div>

<div class="container">
    <div class="ed-eyebrow"><i class="ph ph-heartbeat"></i> Saúde</div><h1 class="ed-h">Avaliação <span class="ed-mark">Física</span></h1>
    <p class="page-sub">Registre e acompanhe a evolução física dos alunos da sua academia.</p>

    @if(session('success'))
        <div class="alert-success"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>
    @endif

    <div class="section-label">Alunos ({{ $clientes->count() }})</div>

    @if($clientes->isEmpty())
        <div class="empty-state">
            <i class="ph ph-users"></i>
            <p>Nenhum aluno vinculado à academia ainda. Cadastre um aluno para começar.</p>
        </div>
    @else
        <div class="search-wrapper">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="buscaAluno" placeholder="Buscar aluno por nome...">
        </div>

        <div id="listaAlunos">
        @foreach($clientes as $c)
            @php $stats = $totaisRegistros->get($c->id); @endphp
            <div class="card" data-busca="{{ strtolower($c->nome) }}">
                <div class="aluno-row">
                    <div>
                        <p class="cliente-nome">{{ $c->nome }}</p>
                        <span class="badge {{ $c->plano ? 'badge-plano' : 'badge-sem' }}">
                            {{ $c->plano ?: 'Sem plano' }}
                        </span>
                        @if($c->filial)
                            <span class="badge badge-sem"><i class="ph ph-git-branch"></i> {{ $c->filial->nome }}</span>
                        @endif
                        <div class="aluno-meta" style="margin-top:8px;">
                            @if($stats)
                                <i class="ph ph-clipboard-text"></i> {{ $stats->total }} registro(s) — última em {{ \Carbon\Carbon::parse($stats->ultima)->format('d/m/Y') }}
                            @else
                                <i class="ph ph-clipboard"></i> Nenhum registro ainda
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('academia.avaliacao-fisica.aluno', $c->id) }}" class="btn-primary">
                        <i class="ph ph-chart-line"></i> Ver Avaliações
                    </a>
                </div>
            </div>
        @endforeach
        </div>
        <div class="empty-state" id="semResultados" style="display:none;">
            <i class="ph ph-magnifying-glass"></i>
            <p>Nenhum aluno encontrado para a sua busca.</p>
        </div>
    @endif
</div>

<script>
    const inputBusca = document.getElementById('buscaAluno');
    if (inputBusca) {
        inputBusca.addEventListener('input', () => {
            const termo = inputBusca.value.toLowerCase().trim();
            let visiveis = 0;
            document.querySelectorAll('#listaAlunos .card').forEach(card => {
                const ok = !termo || card.dataset.busca.includes(termo);
                card.style.display = ok ? '' : 'none';
                if (ok) visiveis++;
            });
            const vazio = document.getElementById('semResultados');
            if (vazio) vazio.style.display = visiveis === 0 ? '' : 'none';
        });
    }
</script>

</body>
</html>
