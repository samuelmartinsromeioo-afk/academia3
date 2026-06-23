<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periodização</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #F4BE16; --bg-dark: #000000; --card-bg: #111317;
            --text-main: #ffffff; --text-muted: #9a9a9a; --green: #00e676; --red: #ff5252;
            --border: rgba(255, 255, 255, 0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-dark); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: var(--text-main); min-height: 100vh; background-image: radial-gradient(circle at 12% -10%, rgba(244, 190, 22, 0.10), transparent 45%); }
        a { color: inherit; text-decoration: none; }
        .top-bar { display: flex; align-items: center; gap: 15px; padding: 15px 40px; background: rgba(0,0,0,0.6); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .back-btn { background: var(--card-bg); border: 1px solid var(--border); color: var(--primary); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: 0.3s; }
        .back-btn:hover { background: var(--primary); color: #000; }
        .top-bar .title { font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .top-bar .title i { color: var(--primary); }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        h1 { font-size: 1.8rem; font-weight: 900; color: var(--primary); margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
        .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px; }

        .alert { padding: 14px; border-radius: 12px; margin-bottom: 18px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(0,230,118,0.1); color: var(--green); border: 1px solid var(--green); }
        .alert-venc { background: rgba(255,82,82,0.12); color: var(--red); border: 1px solid var(--red); }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; padding: 20px; transition: 0.2s; }
        .card.venc { border-color: rgba(255,82,82,0.5); box-shadow: 0 0 18px rgba(255,82,82,0.08); }
        .card .top { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .card .top img { width: 46px; height: 46px; border-radius: 50%; border: 2px solid var(--primary); }
        .card .top .nome { font-weight: 800; }
        .card .top .nome small { display: block; color: var(--text-muted); font-weight: 600; font-size: 0.72rem; }
        .tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .tag { font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 20px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text-muted); }
        .tag.ativo { background: rgba(0,230,118,0.12); color: var(--green); border-color: rgba(0,230,118,0.4); }
        .tag.venc { background: rgba(255,82,82,0.15); color: var(--red); border-color: var(--red); }
        .tag.ok { background: rgba(244,190,22,0.12); color: var(--primary); border-color: rgba(244,190,22,0.4); }
        .card .acao { display: block; text-align: center; padding: 11px; border-radius: 10px; background: var(--primary); color: #000; font-weight: 900; font-size: 0.82rem; }
        .card .acao:hover { filter: brightness(1.1); }

        .empty { text-align: center; padding: 70px 20px; color: var(--text-muted); background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; }
        .empty i { font-size: 3rem; color: var(--primary); margin-bottom: 16px; display: block; opacity: 0.8; }
        .empty p { font-size: 1.05rem; color: var(--text-main); }

        @media (max-width: 768px) { .top-bar { padding: 15px 20px; } h1 { font-size: 1.4rem; } .grid { grid-template-columns: 1fr; } }
    </style>
</head>

<body>
    <div class="top-bar">
        <a href="{{ route('personal.dashboard') }}" class="back-btn" title="Voltar"><i class="fas fa-arrow-left"></i></a>
        <span class="title"><i class="fas fa-bolt"></i> Periodização</span>
    </div>

    <div class="container">
        <h1><i class="fas fa-bolt"></i> PERIODIZAÇÃO</h1>
        <p class="subtitle">Mesociclos A/B/C/D com troca automática do treino do dia.</p>

        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-venc"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif

        @if($vencidos > 0)
            <div class="alert alert-venc">
                <i class="fas fa-triangle-exclamation"></i>
                {{ $vencidos }} mesociclo(s) vencido(s) — hora de montar o próximo ciclo.
            </div>
        @endif

        @if($mesociclos->isEmpty())
            <div class="empty">
                <i class="fas fa-bolt"></i>
                <p>Você ainda não criou nenhuma periodização.</p>
                <small>Abra um aluno pela tela de Fichas e clique em "Periodização" para começar.</small>
            </div>
        @else
            <div class="grid">
                @foreach($mesociclos as $m)
                    @php $venc = $m->estaVencido(); $dias = $m->diasRestantes(); @endphp
                    <div class="card {{ $venc ? 'venc' : '' }}">
                        <div class="top">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($m->cliente->nome ?? 'Aluno') }}&background=F4BE16&color=000" alt="">
                            <div class="nome">{{ $m->cliente->nome ?? 'Aluno' }}<small>{{ $m->nome }}</small></div>
                        </div>
                        <div class="tags">
                            <span class="tag {{ $m->ativo ? 'ativo' : '' }}">{{ $m->ativo ? 'Ativo' : 'Inativo' }}</span>
                            <span class="tag">{{ $m->treinos->count() }} treinos</span>
                            @if($venc)
                                <span class="tag venc"><i class="fas fa-triangle-exclamation"></i> Vencido</span>
                            @elseif($dias !== null)
                                <span class="tag ok">{{ $dias }} dia(s)</span>
                            @else
                                <span class="tag">Sem prazo</span>
                            @endif
                        </div>
                        <a href="{{ route('periodizacao.aluno', $m->cliente_id) }}" class="acao"><i class="fas fa-sliders"></i> Gerenciar</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>

</html>
