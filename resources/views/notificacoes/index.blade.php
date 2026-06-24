<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#F4BE16; --bg-dark:#000; --card-bg:#111317; --text-main:#fff; --text-muted:#9a9a9a; --green:#00e676; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); min-height:100vh; background-image:radial-gradient(circle at 50% -10%, rgba(244,190,22,0.1), transparent 50%); }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:15px 40px; background:rgba(0,0,0,0.6); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .back-btn:hover { background:var(--primary); color:#000; }
        .top-bar .title { font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px; } .top-bar .title i { color:var(--primary); }
        .container { max-width:680px; margin:24px auto; padding:0 20px; }
        .head { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
        h1 { font-size:1.5rem; font-weight:900; color:var(--primary); display:flex; align-items:center; gap:10px; }
        .btn-todas { background:var(--card-bg); border:1px solid var(--border); color:var(--text-muted); padding:9px 14px; border-radius:9px; font-weight:800; font-size:0.74rem; cursor:pointer; }
        .btn-todas:hover { color:var(--primary); border-color:var(--primary); }
        .n { display:flex; gap:14px; align-items:flex-start; background:var(--card-bg); border:1px solid var(--border); border-radius:14px; padding:16px; margin-bottom:10px; width:100%; text-align:left; cursor:pointer; }
        .n.unread { border-left:3px solid var(--primary); background:rgba(244,190,22,0.05); }
        .n .ico { width:38px; height:38px; border-radius:10px; background:rgba(244,190,22,0.14); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .n .body { flex:1; }
        .n .body .t { font-weight:800; font-size:0.92rem; margin-bottom:3px; }
        .n .body .m { color:var(--text-muted); font-size:0.84rem; line-height:1.5; white-space:pre-line; }
        .n .body .q { color:var(--text-muted); font-size:0.7rem; margin-top:5px; }
        .dot { width:9px; height:9px; border-radius:50%; background:var(--primary); flex-shrink:0; margin-top:6px; }
        .empty { text-align:center; padding:70px 20px; color:var(--text-muted); }
        .empty i { font-size:3rem; color:var(--primary); margin-bottom:16px; display:block; opacity:0.7; }
        button.n { font-family:inherit; }
        @media (max-width:600px){ .top-bar{padding:15px 20px;} }
    </style>
</head>

<body>
    <div class="top-bar">
        <a href="{{ $voltar }}" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <span class="title"><i class="fas fa-bell"></i> Notificações</span>
    </div>

    <div class="container">
        <div class="head">
            <h1><i class="fas fa-bell"></i> Avisos</h1>
            @if($notificacoes->where('lida', false)->count() > 0)
                <form method="POST" action="{{ route('notificacoes.marcar-todas') }}">@csrf<button class="btn-todas"><i class="fas fa-check-double"></i> Marcar todas</button></form>
            @endif
        </div>

        @if($notificacoes->isEmpty())
            <div class="empty"><i class="fas fa-bell-slash"></i><p>Nenhuma notificação por aqui.</p></div>
        @else
            @foreach($notificacoes as $n)
                <form method="POST" action="{{ route('notificacoes.lida', $n->id) }}">
                    @csrf
                    <button type="submit" class="n {{ $n->lida ? '' : 'unread' }}">
                        <span class="ico"><i class="fas {{ $n->icone ?: 'fa-bell' }}"></i></span>
                        <span class="body">
                            <span class="t">{{ $n->titulo }}</span>
                            <span class="m">{{ \Illuminate\Support\Str::limit($n->mensagem, 220) }}</span>
                            <span class="q">{{ $n->created_at->diffForHumans() }}</span>
                        </span>
                        @if(!$n->lida)<span class="dot"></span>@endif
                    </button>
                </form>
            @endforeach
        @endif
    </div>
</body>

</html>
