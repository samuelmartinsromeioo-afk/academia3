<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed de Atividade</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#F4BE16; --bg-dark:#000; --card-bg:#111317; --text-main:#fff; --text-muted:#9a9a9a; --green:#00e676; --red:#ff5252; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); min-height:100vh; background-image:radial-gradient(circle at 12% -10%, rgba(244,190,22,0.1), transparent 45%); }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:15px 40px; background:rgba(0,0,0,0.6); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .back-btn:hover { background:var(--primary); color:#000; }
        .top-bar .title { font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px; } .top-bar .title i { color:var(--primary); }
        .container { max-width:760px; margin:26px auto; padding:0 20px; }
        h1 { font-size:1.6rem; font-weight:900; color:var(--primary); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .cards { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:22px; }
        .card { background:var(--card-bg); border:1px solid var(--border); border-radius:14px; padding:16px; text-align:center; }
        .card.alert { border-color:rgba(255,82,82,0.5); }
        .card .ico { font-size:1.2rem; color:var(--primary); margin-bottom:6px; }
        .card .v { font-size:1.7rem; font-weight:900; }
        .card .l { font-size:0.62rem; text-transform:uppercase; color:var(--text-muted); font-weight:900; margin-top:3px; }
        .card a { color:var(--primary); font-size:0.66rem; font-weight:800; }
        .panel { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:18px; }
        .panel-title { font-size:0.74rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--primary); font-weight:900; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
        .rec { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.05); }
        .rec:last-child { border-bottom:none; }
        .rec .ico { width:34px; height:34px; border-radius:9px; background:rgba(244,190,22,0.14); color:var(--primary); display:flex; align-items:center; justify-content:center; }
        .rec .txt { flex:1; font-size:0.88rem; } .rec .txt small { color:var(--text-muted); display:block; font-size:0.7rem; }
        .rec .peso { font-weight:900; color:var(--primary); }
        .act { display:flex; align-items:center; gap:12px; padding:11px 0; border-bottom:1px solid rgba(255,255,255,0.05); }
        .act:last-child { border-bottom:none; }
        .act img { width:38px; height:38px; border-radius:50%; }
        .act .txt { flex:1; font-size:0.88rem; } .act .txt small { color:var(--text-muted); display:block; font-size:0.72rem; }
        .act .quando { font-size:0.72rem; color:var(--text-muted); white-space:nowrap; }
        .empty { color:var(--text-muted); text-align:center; padding:20px 0; font-size:0.85rem; }
        @media (max-width:600px){ .top-bar{padding:15px 20px;} }
    </style>
</head>

<body>
    <div class="top-bar">
        <a href="{{ route('personal.dashboard') }}" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <span class="title"><i class="fas fa-rss"></i> Feed de Atividade</span>
    </div>

    <div class="container">
        <h1><i class="fas fa-rss"></i> FEED DE ATIVIDADE</h1>

        <div class="cards">
            <div class="card">
                <div class="ico"><i class="fas fa-dumbbell"></i></div>
                <div class="v">{{ $treinosHoje }}</div>
                <div class="l">Treinos hoje</div>
            </div>
            <div class="card">
                <div class="ico"><i class="fas fa-trophy"></i></div>
                <div class="v">{{ count($recordes) }}</div>
                <div class="l">Recordes (14d)</div>
            </div>
            <div class="card {{ $sumidos > 0 ? 'alert' : '' }}">
                <div class="ico"><i class="fas fa-user-clock"></i></div>
                <div class="v" style="{{ $sumidos > 0 ? 'color:var(--red)' : '' }}">{{ $sumidos }}</div>
                <div class="l">Sumidos</div>
                <a href="{{ route('aderencia.dashboard') }}">ver painel</a>
            </div>
        </div>

        @if(count($recordes) > 0)
        <div class="panel">
            <div class="panel-title"><i class="fas fa-trophy"></i> Recordes recentes</div>
            @foreach($recordes as $r)
                <div class="rec">
                    <div class="ico"><i class="fas fa-trophy"></i></div>
                    <div class="txt">{{ $r['cliente'] }} <small>{{ $r['exercicio'] }} · {{ $r['data']->format('d/m') }}</small></div>
                    <div class="peso">{{ rtrim(rtrim(number_format($r['peso'],2,',','.'),'0'),',') }} kg</div>
                </div>
            @endforeach
        </div>
        @endif

        <div class="panel">
            <div class="panel-title"><i class="fas fa-clock-rotate-left"></i> Atividade recente (14 dias)</div>
            @if($atividades->isEmpty())
                <div class="empty">Nenhum treino concluído pelos seus alunos nos últimos 14 dias.</div>
            @else
                @foreach($atividades as $a)
                    <div class="act">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($a->cliente->nome ?? 'Aluno') }}&background=F4BE16&color=000" alt="">
                        <div class="txt">
                            <b>{{ $a->cliente->nome ?? 'Aluno' }}</b> concluiu {{ $a->ficha->nome_treino ?? 'um treino' }}
                            <small>
                                {{ $a->sensacao && isset(\App\Models\Cadastro\TreinoConcluido::SENSACOES[$a->sensacao]) ? \App\Models\Cadastro\TreinoConcluido::SENSACOES[$a->sensacao][0].' '.\App\Models\Cadastro\TreinoConcluido::SENSACOES[$a->sensacao][1] : '' }}
                                {{ $a->rpe ? '· RPE '.$a->rpe : '' }}
                            </small>
                        </div>
                        <div class="quando">{{ $a->data_treino->format('d/m') }}</div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</body>

</html>
