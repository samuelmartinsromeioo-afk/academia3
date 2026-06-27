<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">
    <style>
        :root { --primary:var(--snr-lime); --bg-dark:var(--snr-bg); --card-bg:var(--snr-surface); --text-main:var(--snr-text); --text-muted:var(--snr-dim); --green:var(--snr-success); --border:var(--snr-border); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); min-height:100vh; background-image:radial-gradient(circle at 50% -10%, rgba(212,255,0,0.1), transparent 50%); }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:15px 40px; background:rgba(0,0,0,0.6); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .back-btn:hover { background:var(--primary); color:#000; }
        .top-bar .title { font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px; } .top-bar .title i { color:var(--primary); }
        .container { max-width:640px; margin:24px auto; padding:0 20px; }
        h1 { font-size:1.5rem; font-weight:900; color:var(--primary); margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        .conv { display:flex; gap:14px; align-items:center; background:var(--card-bg); border:1px solid var(--border); border-radius:14px; padding:14px; margin-bottom:10px; }
        .conv:hover { border-color:var(--primary); }
        .conv img { width:48px; height:48px; border-radius:50%; }
        .conv .info { flex:1; min-width:0; }
        .conv .info .nome { font-weight:800; }
        .conv .info .prev { color:var(--text-muted); font-size:0.82rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .conv .right { text-align:right; }
        .conv .right .hora { color:var(--text-muted); font-size:0.68rem; }
        .conv .badge { display:inline-block; background:var(--primary); color:#000; font-weight:900; font-size:0.66rem; padding:2px 8px; border-radius:20px; margin-top:5px; }
        .empty { text-align:center; padding:70px 20px; color:var(--text-muted); }
        .empty i { font-size:3rem; color:var(--primary); margin-bottom:16px; display:block; opacity:0.7; }
        @media (max-width:600px){ .top-bar{padding:15px 20px;} }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ $voltar }}" class="back-btn"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-chats"></i> Mensagens</span>
    </div>

    <div class="container">
        <h1><i class="ph ph-chats"></i> MENSAGENS</h1>

        @if($contatos->isEmpty())
            <div class="empty"><i class="ph ph-chat-slash"></i><p>{{ $eu === 'personal' ? 'Você ainda não tem alunos para conversar.' : 'Você ainda não tem um personal para conversar.' }}</p></div>
        @else
            @foreach($contatos as $c)
                <a href="{{ route('chat.conversa', $c->id) }}" class="conv">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($c->nome) }}&background=d4ff00&color=0a0b0d" alt="">
                    <div class="info">
                        <div class="nome">{{ $c->nome }}</div>
                        <div class="prev">{{ $c->ultima ? ($c->ultima->remetente === $eu ? 'Você: ' : '').\Illuminate\Support\Str::limit($c->ultima->texto, 38) : 'Iniciar conversa' }}</div>
                    </div>
                    <div class="right">
                        @if($c->ultima)<div class="hora">{{ $c->ultima->created_at->format('d/m H:i') }}</div>@endif
                        @if($c->nao_lidas > 0)<div class="badge">{{ $c->nao_lidas }}</div>@endif
                    </div>
                </a>
            @endforeach
        @endif
    </div>
</body>

</html>
