<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório · {{ $cliente->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">
    <style>
        :root { --primary:var(--snr-lime); --bg-dark:var(--snr-bg); --card-bg:var(--snr-surface); --text-main:var(--snr-text); --text-muted:var(--snr-dim); --green:var(--snr-success); --red:var(--snr-error); --border:var(--snr-border); }
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
        /* Blocos que existem só para a folha impressa: timbre, legenda,
           assinatura, rodapé e marca d'água. Ficam fora da tela para o
           relatório continuar exatamente como era. */
        .so-print { display:none; }

        /* ── Impressão: folha branca, cara de documento ──────────────────
           A tela é a do SnrFit, escura. A folha é outra coisa: papel branco,
           fio fino em vez de borda de card, hierarquia por tamanho e peso em
           vez de cor, e número tabular para alinhar na coluna. Nada aqui
           altera a tela. */
        @media print {
            @page { margin:12mm; }

            .so-print { display:block; }
            .top-bar, .acoes, .alert-ok, .bar { display:none !important; }

            body {
                background:#fff !important;
                color:#16191e !important;
                font-variant-numeric:tabular-nums;
                font-feature-settings:'tnum' 1;
            }
            .container { max-width:none; margin:0; padding:0; }
            .report { background:transparent !important; border:none !important; border-radius:0; padding:0; }

            /* Timbre: logo à esquerda, emissor à direita, fio duplo embaixo. */
            .rep-head {
                border-bottom:2px solid #16191e;
                box-shadow:0 3px 0 -2px #d4d9e0;
                padding-bottom:12px;
                margin-bottom:24px;
                page-break-after:avoid;
            }
            .rep-head .logo { color:#16191e !important; letter-spacing:-0.01em; }
            .rep-head .who { text-align:right; }
            .rep-head .who b { font-size:1rem; }
            .rep-head .who small { color:#5d6673; }

            /* Os quatro cards viram linhas de dados: rótulo à esquerda,
               valor à direita, fio fino entre elas. Card impresso é o que mais
               faz a folha parecer captura de tela. */
            .grid { display:block; margin-bottom:0; }
            .stat {
                background:transparent !important;
                border:none !important;
                border-bottom:1px solid #d4d9e0 !important;
                border-radius:0;
                padding:7px 0;
                display:flex;
                align-items:baseline;
                justify-content:space-between;
                gap:14px;
            }
            .stat:last-child { border-bottom:1px solid #16191e !important; }
            .stat .l { margin:0; font-size:0.8rem; font-weight:400; text-transform:none; color:#5d6673; }
            .stat .v { font-size:0.95rem; color:#16191e !important; text-align:right; }
            .stat .v small { color:#5d6673; }

            .sec-t {
                color:#5d6673 !important;
                border-bottom:1px solid #d4d9e0;
                padding-bottom:5px;
                margin:26px 0 10px;
                page-break-after:avoid;
            }
            .rec-item { border-bottom:1px solid #d4d9e0 !important; padding:7px 0; }
            .rec-item .p { color:#16191e !important; }
            .rec-item .muted, .muted { color:#5d6673 !important; }

            /* Marca d'água — `fixed` repete em toda página impressa. */
            .doc-marca {
                display:flex !important;
                position:fixed; inset:0;
                align-items:center; justify-content:center;
                pointer-events:none; z-index:0;
            }
            .doc-marca img {
                width:54%; max-width:400px; opacity:0.055; transform:rotate(-24deg);
                -webkit-print-color-adjust:exact; print-color-adjust:exact;
            }
            .container { position:relative; z-index:1; }

            .pr-legenda { font-size:0.78rem; color:#5d6673; margin-top:20px; padding-left:11px; border-left:2px solid #3d6b00; }
            .pr-assina { margin-top:50px; text-align:center; page-break-inside:avoid; }
            .pr-assina .linha { width:290px; margin:0 auto 6px; border-top:1px solid #16191e; }
            .pr-assina .nome { font-weight:700; font-size:0.86rem; }
            .pr-assina .reg { color:#5d6673; font-size:0.76rem; }
            .pr-pe {
                position:fixed; bottom:0; left:0; right:0;
                display:flex; justify-content:space-between; gap:14px;
                font-size:0.7rem; color:#5d6673;
                background:#fff; border-top:1px solid #d4d9e0; padding:3mm 0 0;
            }
        }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('fichas-treino.aluno', $cliente->id) }}" class="back-btn"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-file-text"></i> Relatório mensal</span>
    </div>

    <div class="container">
        @if(session('success'))<div class="alert-ok"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>@endif

        <div class="report">
            <div class="rep-head">
                <div class="logo">SnrFit</div>
                <div class="who">
                    <b>{{ $cliente->nome }}</b>
                    <small>Resumo de {{ ucfirst(now()->locale('pt_BR')->isoFormat('MMMM/YYYY')) }}</small>
                    {{-- Autoria da folha: na tela é redundante (o personal sabe
                         quem é), no papel é o que identifica quem emitiu. --}}
                    <small class="so-print">
                        {{ $personal->nome }}@if($personal->cref) · CREF {{ $personal->cref }}@endif
                    </small>
                    <small class="so-print">Emitido em {{ now()->format('d/m/Y \à\s H:i') }}</small>
                </div>
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
                    <button type="submit" class="btn btn-primary" style="width:100%;"><i class="ph ph-paper-plane-tilt"></i> Enviar ao aluno</button>
                </form>
                <button onclick="window.print()" class="btn btn-ghost"><i class="ph ph-printer"></i> Imprimir / PDF</button>
            </div>

            {{-- Daqui para baixo, só na folha impressa. --}}
            <div class="pr-legenda so-print">
                <strong>Aderência</strong> = treinos realizados ÷ planejados no período.
                <strong>Sequência</strong> = dias consecutivos com treino concluído.
                <strong>Esforço médio</strong> = média do RPE informado pelo aluno (0 a 10).
                <strong>Recorde</strong> = maior carga registrada no exercício dentro do mês.
            </div>

            <div class="pr-assina so-print">
                <div class="linha"></div>
                <div class="nome">{{ $personal->nome }}</div>
                <div class="reg">@if($personal->cref) CREF {{ $personal->cref }} @else Personal trainer @endif</div>
            </div>
        </div>
    </div>

    <div class="doc-marca so-print" aria-hidden="true">
        <img src="{{ asset('SnrFit.png') }}" alt="">
    </div>

    <div class="pr-pe so-print">
        <span>Relatório mensal · {{ $cliente->nome }} · {{ ucfirst(now()->locale('pt_BR')->isoFormat('MMMM/YYYY')) }}</span>
        <span>{{ now()->format('d/m/Y H:i') }}</span>
    </div>
</body>

</html>
