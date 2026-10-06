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
        /* A folha impressa tem MARCAÇÃO PRÓPRIA, aqui embaixo, e não aparece
           na tela. A primeira tentativa foi reestilizar a marcação da tela com
           `!important` — card virando linha, barra de progresso escondida — e
           isso produziu o resultado simples e com defeito: fio sobrando no
           último item (o `!important` vencia o `:last-child`), fio duplo feito
           com box-shadow (que a impressão descarta), conteúdo correndo por
           baixo do rodapé fixo e, pior, NENHUM título — porque o título vive
           na `.top-bar`, que a impressão esconde.

           Com estrutura própria não há disputa de regra: a tela fica como
           está e a folha é desenhada como documento. */
        .so-print { display:none; }
        /* ── A FOLHA IMPRESSA ────────────────────────────────────────────
           Só existe dentro de @media print. Papel branco, tinta escura, fio
           fino em vez de borda de card, hierarquia por tamanho e peso em vez
           de cor, e número tabular para alinhar na coluna. */
        @media print {
            @page { margin: 13mm 14mm; }

            /* A tela inteira sai; entra a folha. */
            .top-bar, .container { display: none !important; }
            .so-print { display: block; }

            body {
                background: #fff;
                color: #16191e;
                font-variant-numeric: tabular-nums;
                font-feature-settings: 'tnum' 1;
                font-size: 11.2pt;
                line-height: 1.5;
            }

            /* Espaço reservado para o rodapé fixo: sem isto o último bloco
               corre por baixo dele e o texto some. */
            .folha { counter-reset: secao; padding-bottom: 16mm; position: relative; z-index: 1; }

            /* ── Timbre ── */
            .f-topo {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 24px;
                padding-bottom: 9px;
                /* Fio duplo de verdade: `border-bottom: double` imprime; a
                   sombra que eu havia usado antes é descartada. */
                border-bottom: 3px double #16191e;
            }
            .f-id { display: flex; align-items: center; gap: 9px; }
            .f-id img { width: 26px; height: 26px; }
            .f-id .nome { font-weight: 800; letter-spacing: 1.8px; font-size: 9pt; }
            .f-id .tag { display: block; font-weight: 400; letter-spacing: 0.4px; font-size: 7pt; color: #5d6673; }
            .f-emissor { text-align: right; font-size: 8.4pt; line-height: 1.45; color: #5d6673; }
            .f-emissor strong { display: block; font-size: 9.4pt; color: #16191e; }

            /* ── Título do documento ── */
            .f-cabeca { margin: 22px 0 24px; }
            .f-cabeca h1 {
                margin: 0;
                font-size: 19pt;
                font-weight: 800;
                letter-spacing: -0.02em;
                line-height: 1.1;
            }
            .f-cabeca p { margin: 5px 0 0; font-size: 10.4pt; color: #5d6673; }

            /* ── Ficha de dados ── */
            .f-ficha { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
            .f-ficha th, .f-ficha td { text-align: left; padding: 6px 0; border-bottom: 1px solid #dfe3e8; font-size: 9.4pt; }
            .f-ficha th { width: 150px; font-weight: 600; color: #5d6673; padding-right: 14px; }
            .f-ficha tr:last-child th, .f-ficha tr:last-child td { border-bottom: 1px solid #16191e; }

            /* ── Seções numeradas (o contador pula sozinho a seção ausente) ── */
            .f-secao {
                margin: 26px 0 11px;
                font-size: 9pt;
                font-weight: 700;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: #5d6673;
                padding-bottom: 5px;
                border-bottom: 1px solid #dfe3e8;
                page-break-after: avoid;
            }
            .f-secao::before { counter-increment: secao; content: counter(secao) ' · '; }

            /* ── Destaque: a aderência é o número que resume o mês ── */
            .f-destaque { display: flex; align-items: flex-end; gap: 26px; margin-bottom: 4px; }
            .f-destaque .num { font-size: 42pt; font-weight: 800; line-height: 0.9; letter-spacing: -0.03em; }
            .f-destaque .leg { font-size: 8.6pt; color: #5d6673; padding-bottom: 5px; }
            .f-destaque .leg b { display: block; font-size: 10pt; color: #16191e; }

            /* ── Linhas de dados ── */
            .f-linhas { width: 100%; border-collapse: collapse; }
            .f-linhas th, .f-linhas td { padding: 7px 0; font-size: 9.6pt; border-bottom: 1px solid #dfe3e8; }
            .f-linhas th { text-align: left; font-weight: 400; color: #5d6673; }
            .f-linhas td { text-align: right; font-weight: 700; }
            .f-linhas td .u { font-weight: 400; color: #5d6673; font-size: 8.8pt; }
            .f-linhas tr:last-child th, .f-linhas tr:last-child td { border-bottom: 1px solid #16191e; }

            .f-vazio { font-size: 9.4pt; color: #5d6673; font-style: italic; }

            /* ── Legenda e assinatura ── */
            .f-legenda { margin-top: 22px; padding-left: 11px; border-left: 2px solid #3d6b00; font-size: 8.6pt; color: #5d6673; }
            .f-assina { margin-top: 46px; text-align: center; page-break-inside: avoid; }
            .f-assina .linha { width: 280px; margin: 0 auto 6px; border-top: 1px solid #16191e; }
            .f-assina .nome { font-weight: 700; font-size: 9.6pt; }
            .f-assina .reg { font-size: 8.4pt; color: #5d6673; }

            /* ── Marca d'água e rodapé corrido ── */
            .f-marca {
                display: flex !important;
                position: fixed; inset: 0;
                align-items: center; justify-content: center;
                pointer-events: none; z-index: 0;
            }
            .f-marca img {
                width: 52%; max-width: 380px; opacity: 0.055; transform: rotate(-24deg);
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            .f-pe {
                position: fixed; bottom: 0; left: 0; right: 0;
                display: flex; justify-content: space-between; gap: 14px;
                font-size: 7.8pt; color: #5d6673;
                background: #fff;
                border-top: 1px solid #dfe3e8;
                padding: 2.5mm 0 0;
            }

            tr { page-break-inside: avoid; }
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
                    <button type="submit" class="btn btn-primary" style="width:100%;"><i class="ph ph-paper-plane-tilt"></i> Enviar ao aluno</button>
                </form>
                <button onclick="window.print()" class="btn btn-ghost"><i class="ph ph-printer"></i> Imprimir / PDF</button>
            </div>
        </div>
    </div>

{{-- ──────────────────────────────────────────────────────────────────────
     A FOLHA IMPRESSA

     Estrutura própria, invisível na tela. A tela acima não é tocada: na
     impressão ela sai inteira (`.top-bar, .container { display:none }`) e
     entra isto.

     Os mesmos dados aparecem duas vezes na marcação, e é de propósito: a
     tentativa de reestilizar os cards da tela para virar documento foi o que
     deixou a folha simples e com defeito — fio sobrando, fio duplo que não
     imprimia, texto por baixo do rodapé e nenhum título, porque o título
     vive na barra que a impressão esconde.
     ────────────────────────────────────────────────────────────────────── --}}
@php
    $mesRef = ucfirst(now()->locale('pt_BR')->isoFormat('MMMM/YYYY'));
    $naoFeitos = max(0, $planejados - $realizados);
@endphp
<div class="folha so-print">

    <header class="f-topo">
        <div class="f-id">
            <img src="{{ asset('SnrFit.png') }}" alt="">
            <span class="nome">SNR·FIT<span class="tag">Treino &amp; performance</span></span>
        </div>
        <div class="f-emissor">
            <strong>{{ $personal->nome }}</strong>
            @if($personal->cref) CREF {{ $personal->cref }}<br> @endif
            @if($personal->whatsapp) {{ $personal->whatsapp }}<br> @endif
            Emitido em {{ now()->format('d/m/Y \à\s H:i') }}
        </div>
    </header>

    <div class="f-cabeca">
        <h1>Relatório Mensal de Treino</h1>
        <p>{{ $cliente->nome }} · {{ $mesRef }}</p>
    </div>

    <table class="f-ficha">
        <tr><th>Aluno</th><td>{{ $cliente->nome }}</td></tr>
        @if($cliente->idade)
            <tr><th>Nascimento</th><td>
                {{ \Carbon\Carbon::parse($cliente->idade)->format('d/m/Y') }}
                ({{ \Carbon\Carbon::parse($cliente->idade)->age }} anos)
            </td></tr>
        @endif
        @if($cliente->resumo_objetivo)
            <tr><th>Objetivo</th><td>{{ $cliente->resumo_objetivo }}</td></tr>
        @endif
        <tr><th>Período</th><td>{{ $mesRef }}</td></tr>
    </table>

    <h2 class="f-secao">Desempenho no período</h2>

    {{-- A aderência resume o mês: vira o número grande, com a leitura ao lado. --}}
    <div class="f-destaque">
        <div class="num">{{ $aderencia }}%</div>
        <div class="leg">
            <b>{{ $realizados }} de {{ $planejados }} treinos</b>
            @if($naoFeitos > 0)
                {{ $naoFeitos }} {{ $naoFeitos === 1 ? 'treino não realizado' : 'treinos não realizados' }}
            @else
                nenhum treino perdido no período
            @endif
        </div>
    </div>

    <table class="f-linhas">
        <tr>
            <th>Treinos realizados</th>
            <td>{{ $realizados }} <span class="u">de {{ $planejados }} planejados</span></td>
        </tr>
        <tr>
            <th>Sequência atual</th>
            <td>{{ $streak['atual'] }} <span class="u">dias · recorde {{ $streak['recorde'] }}</span></td>
        </tr>
        <tr>
            <th>Esforço médio percebido</th>
            {{-- Sem interpolar HTML dentro de `{{ }}`: o Blade escapa e a tag
                 sairia como texto na folha. --}}
            <td>@if($rpeMedio){{ $rpeMedio }} <span class="u">/ 10</span>@else—@endif</td>
        </tr>
    </table>

    @if($pesoIni !== null && $pesoFim !== null)
        @php $delta = (float) $pesoFim - (float) $pesoIni; @endphp
        <h2 class="f-secao">Peso corporal</h2>
        <table class="f-linhas">
            <tr>
                <th>Início do período</th>
                <td>{{ $pesoIni }} <span class="u">kg</span></td>
            </tr>
            <tr>
                <th>Fim do período</th>
                <td>{{ $pesoFim }} <span class="u">kg</span></td>
            </tr>
            <tr>
                <th>Variação</th>
                <td>
                    {{ $delta > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($delta, 2, ',', '.'), '0'), ',') }}
                    <span class="u">kg</span>
                </td>
            </tr>
        </table>
    @endif

    <h2 class="f-secao">Recordes do mês</h2>
    @if(count($recordes) === 0)
        <p class="f-vazio">Nenhum recorde batido neste mês.</p>
    @else
        <table class="f-linhas">
            @foreach($recordes as $r)
                <tr>
                    <th>{{ $r['exercicio'] }} <span class="u">· {{ $r['data']->format('d/m/Y') }}</span></th>
                    <td>{{ rtrim(rtrim(number_format($r['peso'], 2, ',', '.'), '0'), ',') }} <span class="u">kg</span></td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="f-legenda">
        <strong>Aderência</strong> = treinos realizados ÷ planejados no período.
        <strong>Sequência</strong> = dias consecutivos com treino concluído.
        <strong>Esforço médio</strong> = média do RPE informado pelo aluno, de 0 a 10.
        <strong>Recorde</strong> = maior carga registrada no exercício dentro do mês.
    </div>

    <div class="f-assina">
        <div class="linha"></div>
        <div class="nome">{{ $personal->nome }}</div>
        <div class="reg">@if($personal->cref) CREF {{ $personal->cref }} @else Personal trainer @endif</div>
    </div>
</div>

<div class="f-marca so-print" aria-hidden="true">
    <img src="{{ asset('SnrFit.png') }}" alt="">
</div>

<div class="f-pe so-print">
    <span>Relatório mensal · {{ $cliente->nome }} · {{ $mesRef }}</span>
    <span>SnrFit · {{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>

</html>
