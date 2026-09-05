<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Painel') | SNR FIT Nutri</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.meta-pixel')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <style>
        :root {
            --primary:#d4ff00; --bg-dark:#0a0b0d; --card-bg:#16181d; --card-2:#1e2127;
            --text-main:#fff; --text-dim:#9ca3af; --border:rgba(255,255,255,.08);
            --input-bg:rgba(255,255,255,.04); --ok:#00ff88; --warn:#ffaa00; --err:#ff4444;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); color:var(--text-main); font-family:'Inter',sans-serif; display:flex; min-height:100vh; }
        a { color:inherit; text-decoration:none; }
        /* Sidebar */
        .sidebar {
            width:250px; background:var(--card-bg); border-right:1px solid var(--border);
            padding:24px 16px; position:fixed; inset:0 auto 0 0; display:flex; flex-direction:column; gap:6px; overflow-y:auto;
        }
        .brand { font-family:'Syncopate',sans-serif; color:var(--primary); font-size:1.2rem; letter-spacing:3px; padding:0 8px 4px; }
        .brand small { display:block; font-family:'Inter'; letter-spacing:1px; color:var(--text-dim); font-size:.6rem; margin-top:4px; }
        .nav-sec { font-size:.6rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-dim); margin:16px 8px 4px; }
        .nav-item { display:flex; align-items:center; gap:12px; padding:11px 12px; border-radius:10px; color:var(--text-dim); font-size:.85rem; transition:.2s; }
        .nav-item i { font-size:1.1rem; }
        .nav-item:hover { background:var(--card-2); color:#fff; }
        .nav-item.active { background:var(--primary); color:#000; font-weight:700; }
        /* Content */
        .content { margin-left:250px; flex:1; padding:28px 34px; max-width:100%; }
        .topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .topbar h1 { font-size:1.5rem; font-weight:800; }
        .topbar .sub { color:var(--text-dim); font-size:.85rem; }
        /* Components */
        .card { background:var(--card-bg); border:1px solid var(--border); border-radius:18px; padding:22px; }
        .grid { display:grid; gap:18px; }
        .btn { display:inline-flex; align-items:center; gap:8px; background:var(--primary); color:#000; font-weight:700; border:none;
               padding:11px 18px; border-radius:11px; cursor:pointer; font-size:.85rem; transition:.2s; }
        .btn:hover { filter:brightness(1.08); transform:translateY(-1px); }
        .btn-ghost { background:transparent; color:var(--text-main); border:1px solid var(--border); }
        .btn-sm { padding:7px 12px; font-size:.78rem; border-radius:9px; }
        .btn-danger { background:transparent; color:var(--err); border:1px solid rgba(255,68,68,.4); }
        label { display:block; font-size:.68rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
        input, select, textarea { width:100%; background:var(--input-bg); border:1px solid var(--border); border-radius:10px;
               padding:11px 13px; color:#fff; font-size:.9rem; font-family:inherit; outline:none; }
        input:focus, select:focus, textarea:focus { border-color:var(--primary); }
        select option { background:var(--card-bg); }
        table { width:100%; border-collapse:collapse; font-size:.85rem; }
        th { text-align:left; color:var(--text-dim); font-size:.68rem; text-transform:uppercase; letter-spacing:.5px; padding:10px 12px; border-bottom:1px solid var(--border); }
        td { padding:12px; border-bottom:1px solid var(--border); }
        tr:hover td { background:var(--card-2); }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; }
        .badge-ok { background:rgba(0,255,136,.12); color:var(--ok); }
        .badge-warn { background:rgba(255,170,0,.12); color:var(--warn); }
        .badge-dim { background:var(--card-2); color:var(--text-dim); }
        .flash { padding:13px 16px; border-radius:11px; margin-bottom:18px; font-size:.85rem; display:flex; gap:10px; align-items:center; }
        .flash-ok { background:rgba(0,255,136,.1); border:1px solid rgba(0,255,136,.3); color:var(--ok); }
        .flash-err { background:rgba(255,68,68,.1); border:1px solid rgba(255,68,68,.35); color:var(--err); }
        .stat { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:20px; }
        .stat .n { font-size:1.8rem; font-weight:800; }
        .stat .l { color:var(--text-dim); font-size:.78rem; margin-top:4px; }
        .muted { color:var(--text-dim); }
        .empty { text-align:center; color:var(--text-dim); padding:40px 20px; }
        .empty i { font-size:2.5rem; opacity:.4; display:block; margin-bottom:10px; }
        .mobile-top { display:none; }
        @media (max-width:900px) {
            .sidebar { transform:translateX(-100%); transition:.3s; z-index:100; box-shadow:0 0 40px rgba(0,0,0,.6); }
            .sidebar.open { transform:translateX(0); }
            .content { margin-left:0; padding:18px; }
            .mobile-top { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
        }
        @yield('estilos')
    </style>
</head>
<body>
    @php $r = Route::currentRouteName(); @endphp
    <aside class="sidebar" id="sidebar">
        <div class="brand">SNR FIT <small>Nutrição</small></div>
        <a href="{{ route('nutri.painel') }}" class="nav-item {{ $r==='nutri.painel'?'active':'' }}"><i class="ph ph-squares-four"></i> Painel</a>

        <div class="nav-sec">Atendimento</div>
        <a href="{{ route('nutri.pacientes') }}" class="nav-item {{ str_starts_with((string)$r,'nutri.pacientes')||str_starts_with((string)$r,'nutri.anamnese.form')||str_starts_with((string)$r,'nutri.antropometria')?'active':'' }}"><i class="ph ph-users-three"></i> Pacientes</a>
        <a href="{{ route('nutri.planos') }}" class="nav-item {{ str_starts_with((string)$r,'nutri.planos')?'active':'' }}"><i class="ph ph-fork-knife"></i> Planos alimentares</a>
        <a href="{{ route('nutri.agenda') }}" class="nav-item {{ $r==='nutri.agenda'?'active':'' }}"><i class="ph ph-calendar-dots"></i> Agenda</a>

        <div class="nav-sec">Ferramentas</div>
        <a href="{{ route('nutri.anamnese.modelos') }}" class="nav-item {{ $r==='nutri.anamnese.modelos'?'active':'' }}"><i class="ph ph-clipboard-text"></i> Modelos de anamnese</a>
        <a href="{{ route('nutri.alimentos.index') }}" class="nav-item {{ $r==='nutri.alimentos.index'?'active':'' }}"><i class="ph ph-carrot"></i> Alimentos</a>
        <a href="{{ route('nutri.financeiro') }}" class="nav-item {{ $r==='nutri.financeiro'?'active':'' }}"><i class="ph ph-currency-circle-dollar"></i> Financeiro</a>

        <div class="nav-sec">Mais</div>
        <a href="{{ route('nutri.roadmap') }}" class="nav-item {{ $r==='nutri.roadmap'?'active':'' }}"><i class="ph ph-megaphone-simple"></i> Roadmap / sugestões</a>
        <a href="{{ route('lgpd.meus-dados') }}" class="nav-item"><i class="ph ph-shield-check"></i> Meus dados (LGPD)</a>
        <form action="{{ route('login.logout') }}" method="POST" style="margin-top:auto; padding-top:12px;">@csrf
            <button class="nav-item" style="width:100%; background:none; border:none; cursor:pointer;"><i class="ph ph-sign-out"></i> Sair</button>
        </form>
    </aside>

    <main class="content">
        <div class="mobile-top">
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="ph ph-list"></i></button>
            <strong>SNR FIT Nutri</strong>
        </div>

        @if (session('success'))<div class="flash flash-ok"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>@endif
        @if (session('error'))<div class="flash flash-err"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>@endif
        @if ($errors->any())
            <div class="flash flash-err"><i class="ph ph-warning-circle"></i>
                <div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            </div>
        @endif

        @yield('conteudo')
    </main>
    @yield('scripts')
</body>
</html>
