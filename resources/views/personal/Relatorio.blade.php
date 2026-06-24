<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório · {{ $cliente->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#F4BE16; --bg-dark:#000; --card-bg:#111317; --text-main:#fff; --text-muted:#9a9a9a; --green:#00e676; --red:#ff5252; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); min-height:100vh; }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:15px 40px; background:rgba(0,0,0,0.6); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .back-btn:hover { background:var(--primary); color:#000; }
        .top-bar .title { font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px; } .top-bar .title i { color:var(--primary); }
        .container { max-width:720px; margin:26px auto; padding:0 20px; }
        .report { background:var(--card-bg); border:1px solid var(--border); border-radius:18px; padding:30px; }
        .rep-head { display:flex; align-items:center; gap:14px; border-bottom:1px solid var(--border); padding-bottom:18px; margin-bottom:20px; }
        .rep-head .logo { color:var(--primary); font-weight:900; font-size:1.4rem; }
        .rep-head .who { margin-left:auto; text-align:right; } .rep-head .who b { display:block; font-size:1.05rem; } .rep-head .who small { color:var(--text-muted); }
        .grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; margin-bottom:22px; }
        .stat { background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:12px; padding:16px; }
        .stat .l { font-size:0.64rem; text-transform:uppercase; color:var(--text-muted); font-weight:900; margin-bottom:6px; }
        .stat .v { font-size:1.6rem; font-weight:900; } .stat .v small { font-size:0.85rem; color:var(--text-muted); }
        .bar { height:9px; background:rgba(255,255,255,0.08); border-radius:6px; overflow:hidden; margin-top:8px; } .bar > span { display:block; height:100%; background:var(--primary); }
        .sec-t { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--primary); font-weight:900; margin:18px 0 12px; }
        .rec-item { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.9rem; }
        .rec-item:last-child { border-bottom:none; } .rec-item .p { font-weight:900; color:var(--primary); }
        .acoes { display:flex; gap:12px; margin-top:22px; }
        .btn { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:14px; border:none; border-radius:12px; font-weight:900; font-size:0.85rem; cursor:pointer; }
        .btn-primary { background:var(--primary); color:#000; } .btn-ghost { background:var(--card-bg); color:#fff; border:1px solid var(--border); }
        .alert-ok { background:rgba(0,230,118,0.1); color:var(--green); border:1px solid var(--green); padding:14px; border-radius:12px; margin-bottom:16px; display:flex; gap:10px; align-items:center; font-size:0.9rem; }
        .muted { color:var(--text-muted); font-size:0.85rem; }
        @media (max-width:600px){ .top-bar{padding:15px 20px;} }
        @media print { body{background:#fff; color:#000;} .top-bar,.acoes{display:none;} .report{border:none;} .stat{border:1px solid #ddd;} }
    </style>
</head>

<body>
    <div class="top-bar">
        <a href="{{ route('fichas-treino.aluno', $cliente->id) }}" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <span class="title"><i class="fas fa-file-lines"></i> Relatório mensal</span>
    </div>

    <div class="container">
        @if(session('success'))<div class="alert-ok"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif

        <div class="report">
            <div class="rep-head">
                <div class="logo">SnrFit</div>
                <div class="who"><b>{{ $cliente->nome }}</b><small>Resumo de {{ ucfirst(now()->locale('pt_BR')->isoFormat('MMMM/YYYY')) }}</small></div>
            </div>

            <div class="grid">
                <div class="stat">
                    <div class="l">Aderência</div>
                    <div class="v" style="color:{{ $aderencia >= 80 ? 'var(--green)' : ($aderencia >= 50 ? 'var(--primary)' : 'var(--red)') }};">{{ $aderencia }}%</div>
                    <div class="bar"><span style="width:{{ $aderencia }}%;"></span></div>
                </div>
                <div class="stat">
                    <div class="l">Treinos no mês</div>
                    <div class="v">{{ $realizados }}<small>/{{ $planejados }} planejados</small></div>
                </div>
                <div class="stat">
                    <div class="l">Sequência (streak)</div>
                    <div class="v">{{ $streak['atual'] }}<small> atual · recorde {{ $streak['recorde'] }}</small></div>
                </div>
                <div class="stat">
                    <div class="l">Esforço médio</div>
                    <div class="v">{{ $rpeMedio ? $rpeMedio.'/10' : '—' }}</div>
                </div>
            </div>

            @if($pesoIni !== null && $pesoFim !== null)
                @php $delta = (float)$pesoFim - (float)$pesoIni; @endphp
                <div class="sec-t">Peso corporal</div>
                <div class="rec-item">
                    <span>{{ $pesoIni }} kg → {{ $pesoFim }} kg</span>
                    <span class="p" style="color:{{ $delta < 0 ? 'var(--green)' : ($delta > 0 ? 'var(--red)' : 'var(--primary)') }};">{{ $delta > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($delta,2,',','.'),'0'),',') }} kg</span>
                </div>
            @endif

            <div class="sec-t">Recordes do mês</div>
            @if(count($recordes) === 0)
                <p class="muted">Nenhum recorde batido neste mês.</p>
            @else
                @foreach($recordes as $r)
                    <div class="rec-item"><span>{{ $r['exercicio'] }} <small class="muted">· {{ $r['data']->format('d/m') }}</small></span><span class="p">{{ rtrim(rtrim(number_format($r['peso'],2,',','.'),'0'),',') }} kg</span></div>
                @endforeach
            @endif

            <div class="acoes">
                <form method="POST" action="{{ route('relatorio.enviar', $cliente->id) }}" style="flex:1;">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-paper-plane"></i> Enviar ao aluno</button>
                </form>
                <button onclick="window.print()" class="btn btn-ghost"><i class="fas fa-print"></i> Imprimir / PDF</button>
            </div>
        </div>
    </div>
</body>

</html>
