<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo','Meu plano') | SNR FIT</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#d4ff00; --bg:#0a0b0d; --card:#16181d; --card2:#1e2127; --dim:#9ca3af; --border:rgba(255,255,255,.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:#fff; font-family:'Inter',sans-serif; padding-bottom:80px; }
        .wrap { max-width:640px; margin:0 auto; padding:18px; }
        .hd { text-align:center; padding:20px 0; }
        .hd .logo { color:var(--primary); font-weight:800; letter-spacing:2px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:18px; margin-bottom:14px; }
        .btn { display:inline-flex; align-items:center; gap:8px; background:var(--primary); color:#000; font-weight:700; border:none; padding:12px 18px; border-radius:12px; cursor:pointer; font-size:.9rem; text-decoration:none; }
        .btn-ghost { background:transparent; color:#fff; border:1px solid var(--border); }
        input,textarea,select { width:100%; background:rgba(255,255,255,.04); border:1px solid var(--border); border-radius:10px; padding:12px; color:#fff; font-family:inherit; font-size:.95rem; }
        label { display:block; font-size:.7rem; color:var(--primary); text-transform:uppercase; font-weight:700; margin-bottom:6px; letter-spacing:.5px; }
        .flash { background:rgba(0,255,136,.1); border:1px solid rgba(0,255,136,.3); color:#00ff88; padding:12px; border-radius:10px; margin-bottom:14px; font-size:.88rem; }
        .muted { color:var(--dim); }
        .tabbar { position:fixed; bottom:0; left:0; right:0; background:var(--card); border-top:1px solid var(--border); display:flex; justify-content:space-around; padding:10px 0; }
        .tabbar a { color:var(--dim); text-align:center; font-size:.65rem; text-decoration:none; }
        .tabbar a i { font-size:1.4rem; display:block; }
        .tabbar a.active { color:var(--primary); }
        @yield('estilos')
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hd"><div class="logo">SNR FIT</div><div class="muted" style="font-size:.8rem;">Olá, {{ explode(' ',$paciente->nome)[0] }} 👋</div></div>
        @if (session('success'))<div class="flash">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="flash" style="background:rgba(255,68,68,.1); border-color:rgba(255,68,68,.35); color:#ff4444;">@foreach ($errors->all() as $e){{ $e }}<br>@endforeach</div>@endif
        @yield('conteudo')
    </div>

    @php $r = Route::currentRouteName(); @endphp
    <nav class="tabbar">
        <a href="{{ route('portal.home',$token) }}" class="{{ $r==='portal.home'?'active':'' }}"><i class="ph ph-house"></i>Início</a>
        <a href="{{ route('portal.plano',$token) }}" class="{{ $r==='portal.plano'?'active':'' }}"><i class="ph ph-fork-knife"></i>Plano</a>
        <a href="{{ route('portal.lista-compras',$token) }}" class="{{ $r==='portal.lista-compras'?'active':'' }}"><i class="ph ph-shopping-cart"></i>Compras</a>
        <a href="{{ route('portal.diario',$token) }}" class="{{ $r==='portal.diario'?'active':'' }}"><i class="ph ph-notebook"></i>Diário</a>
        <a href="{{ route('portal.chat',$token) }}" class="{{ $r==='portal.chat'?'active':'' }}"><i class="ph ph-chat-circle"></i>Chat</a>
    </nav>
    @yield('scripts')
</body>
</html>
