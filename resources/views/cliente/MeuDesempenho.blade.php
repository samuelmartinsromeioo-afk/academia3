<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Desempenho</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #F4BE16;
            --bg-dark: #000000;
            --card-bg: #111317;
            --text-main: #ffffff;
            --text-muted: #9a9a9a;
            --green: #00e676;
            --yellow: #ffb300;
            --red: #ff5252;
            --border: rgba(255, 255, 255, 0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            background-image: radial-gradient(circle at 50% -10%, rgba(244, 190, 22, 0.12), transparent 50%);
        }
        a { color: inherit; text-decoration: none; }

        .top-bar {
            display: flex; align-items: center; gap: 15px;
            padding: 15px 40px;
            background: rgba(0, 0, 0, 0.6);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);
        }
        .back-btn {
            background: var(--card-bg); border: 1px solid var(--border); color: var(--primary);
            width: 40px; height: 40px; border-radius: 10px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: 0.3s; font-size: 1.1rem;
        }
        .back-btn:hover { background: var(--primary); color: #000; }
        .top-bar .title { font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .top-bar .title i { color: var(--primary); }

        .container { max-width: 760px; margin: 30px auto; padding: 0 20px; }

        /* HERO STREAK */
        .streak-hero {
            background: linear-gradient(135deg, rgba(244, 190, 22, 0.16), rgba(244, 190, 22, 0.02));
            border: 1px solid rgba(244, 190, 22, 0.35);
            border-radius: 22px;
            padding: 32px 28px;
            text-align: center;
            margin-bottom: 22px;
            position: relative;
            overflow: hidden;
        }
        .streak-hero .bolt-bg {
            position: absolute; right: -10px; top: -20px; font-size: 9rem; color: rgba(244,190,22,0.08);
        }
        .streak-hero .num { font-size: 4rem; font-weight: 900; color: var(--primary); line-height: 1; }
        .streak-hero .num i { font-size: 2.4rem; vertical-align: middle; margin-right: 6px; }
        .streak-hero .lbl { font-size: 0.95rem; color: var(--text-main); font-weight: 700; margin-top: 6px; }
        .streak-hero .sub { font-size: 0.78rem; color: var(--text-muted); margin-top: 4px; }

        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 22px; }
        .card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 18px; text-align: center;
        }
        .card .ico { font-size: 1.2rem; color: var(--primary); margin-bottom: 8px; }
        .card .v { font-size: 1.6rem; font-weight: 900; }
        .card .v small { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; }
        .card .l { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 900; margin-top: 4px; }

        .aderencia-box {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 20px; margin-bottom: 22px;
        }
        .aderencia-box .head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; }
        .aderencia-box .head .t { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 900; }
        .aderencia-box .head .p { font-size: 1.5rem; font-weight: 900; }
        .bar { height: 10px; background: rgba(255,255,255,0.08); border-radius: 6px; overflow: hidden; }
        .bar > span { display: block; height: 100%; border-radius: 6px; }
        .aderencia-box .msg { font-size: 0.82rem; color: var(--text-muted); margin-top: 12px; }

        .actions { display: flex; gap: 12px; }
        .actions a {
            flex: 1; text-align: center; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 0.85rem;
            display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s;
        }
        .actions a.primary { background: var(--primary); color: #000; }
        .actions a.primary:hover { filter: brightness(1.1); }
        .actions a.ghost { background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border); }
        .actions a.ghost:hover { background: rgba(255,255,255,0.05); }

        /* NÍVEL */
        .nivel-chip {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(244, 190, 22, 0.15); color: var(--primary);
            border: 1px solid rgba(244, 190, 22, 0.5); border-radius: 20px;
            padding: 5px 14px; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 16px; position: relative; z-index: 1;
        }

        /* SEÇÕES */
        .secao {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 20px; margin-bottom: 22px;
        }
        .secao-titulo {
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-muted); font-weight: 900; margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .secao-titulo i { color: var(--primary); }

        /* PRÓXIMA META */
        .meta { display: flex; align-items: center; gap: 14px; }
        .meta .meta-ico {
            width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
            background: rgba(244, 190, 22, 0.12); border: 1px solid rgba(244,190,22,0.4);
            display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.2rem;
        }
        .meta .meta-txt { flex: 1; }
        .meta .meta-txt .top { font-weight: 800; font-size: 0.95rem; margin-bottom: 6px; }
        .meta .meta-txt .top b { color: var(--primary); }
        .meta .meta-bar { height: 8px; background: rgba(255,255,255,0.08); border-radius: 6px; overflow: hidden; }
        .meta .meta-bar > span { display: block; height: 100%; background: var(--primary); border-radius: 6px; }

        /* MEDALHAS */
        .medalhas { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
        .medalha { text-align: center; opacity: 0.35; transition: 0.2s; }
        .medalha.on { opacity: 1; }
        .medalha .disco {
            width: 52px; height: 52px; margin: 0 auto 7px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
            background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text-muted);
        }
        .medalha.on .disco {
            background: radial-gradient(circle at 35% 30%, #ffe27a, var(--primary));
            border-color: var(--primary); color: #000;
            box-shadow: 0 0 16px rgba(244, 190, 22, 0.4);
        }
        .medalha .dias { font-size: 0.72rem; font-weight: 900; }
        .medalha .nome { font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; }

        /* HEATMAP */
        .heatmap { display: grid; grid-auto-flow: column; grid-template-rows: repeat(7, 1fr); gap: 4px; overflow-x: auto; padding-bottom: 4px; }
        .hm-cell { width: 15px; height: 15px; border-radius: 3px; background: rgba(255,255,255,0.06); }
        .hm-cell.on { background: var(--primary); box-shadow: 0 0 5px rgba(244,190,22,0.5); }
        .hm-cell.futuro { background: transparent; }
        .hm-legenda { display: flex; align-items: center; gap: 8px; margin-top: 12px; font-size: 0.68rem; color: var(--text-muted); }
        .hm-legenda .hm-cell { width: 12px; height: 12px; }

        /* RECORDES */
        .esforco-tag { margin-left: auto; font-size: 0.68rem; color: var(--primary); background: rgba(244,190,22,0.12); border: 1px solid rgba(244,190,22,0.4); padding: 3px 10px; border-radius: 20px; }
        .recordes-list { display: flex; flex-direction: column; }
        .recorde-item { display: flex; justify-content: space-between; align-items: center; padding: 11px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .recorde-item:last-child { border-bottom: none; }
        .recorde-item .rx-nome { font-size: 0.9rem; }
        .recorde-item .rx-peso { font-weight: 900; color: var(--primary); }
        .recorde-item .rx-peso i { font-size: 0.7rem; margin-right: 5px; color: var(--primary); }

        @media (max-width: 600px) {
            .top-bar { padding: 15px 20px; }
            .streak-hero .num { font-size: 3.2rem; }
            .medalhas { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('cliente.index') }}" class="back-btn" title="Voltar"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-lightning"></i> Meu Desempenho</span>
    </div>

    <div class="container">
        @php
            $perc = $resumo['aderencia'];
            $cor = $perc === null ? 'var(--text-muted)' : ($perc >= 80 ? 'var(--green)' : ($perc >= 50 ? 'var(--yellow)' : 'var(--red)'));
        @endphp

        <div class="streak-hero">
            <i class="ph ph-lightning bolt-bg"></i>
            <div class="nivel-chip"><i class="ph {{ $game['nivel']['icon'] }}"></i> Nível {{ $game['nivel']['nome'] }}</div>
            <div class="num"><i class="ph ph-lightning"></i>{{ $streak['atual'] }}</div>
            <div class="lbl">{{ $streak['atual'] === 1 ? 'dia de sequência' : 'dias de sequência' }}</div>
            <div class="sub">
                @if($streak['atual'] === 0)
                    Treine hoje para começar uma nova sequência!
                @else
                    Continue assim, {{ explode(' ', trim($cliente->nome))[0] }}! 🔥
                @endif
            </div>
        </div>

        <div class="cards">
            <div class="card">
                <div class="ico"><i class="ph ph-barbell"></i></div>
                <div class="v">{{ $resumo['realizados'] }}<small>/{{ $resumo['planejados'] }}</small></div>
                <div class="l">Treinos no mês</div>
            </div>
            <div class="card">
                <div class="ico"><i class="ph ph-percent"></i></div>
                <div class="v" style="color: {{ $cor }};">{{ $perc !== null ? $perc.'%' : '–' }}</div>
                <div class="l">Aderência</div>
            </div>
            <div class="card">
                <div class="ico"><i class="ph ph-trophy"></i></div>
                <div class="v">{{ $streak['recorde'] }}</div>
                <div class="l">Recorde de dias</div>
            </div>
        </div>

        @if($game['proxima'])
        <div class="secao">
            <div class="secao-titulo"><i class="ph ph-flag-checkered"></i> Próxima meta</div>
            <div class="meta">
                <div class="meta-ico"><i class="ph {{ $game['proxima']['icon'] }}"></i></div>
                <div class="meta-txt">
                    <div class="top">Faltam <b>{{ $game['proxima']['faltam'] }}</b> dia(s) para <b>{{ $game['proxima']['label'] }}</b> · {{ $game['proxima']['dias'] }} dias seguidos</div>
                    <div class="meta-bar"><span style="width: {{ min(100, (int) round($streak['atual'] / $game['proxima']['dias'] * 100)) }}%;"></span></div>
                </div>
            </div>
        </div>
        @endif

        <div class="secao">
            <div class="secao-titulo"><i class="ph ph-medal"></i> Medalhas</div>
            <div class="medalhas">
                @foreach($game['medalhas'] as $m)
                <div class="medalha {{ $m['atingido'] ? 'on' : '' }}">
                    <div class="disco"><i class="ph {{ $m['icon'] }}"></i></div>
                    <div class="dias">{{ $m['dias'] }}d</div>
                    <div class="nome">{{ $m['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="secao">
            <div class="secao-titulo"><i class="ph ph-fire"></i> Consistência · últimas 12 semanas</div>
            <div class="heatmap">
                @foreach($heatmap as $semana)
                    @foreach($semana as $cell)
                        <div class="hm-cell {{ $cell['futuro'] ? 'futuro' : ($cell['treinou'] ? 'on' : '') }}"
                             title="{{ $cell['rotulo'] }}{{ $cell['treinou'] ? ' • treinou' : '' }}"></div>
                    @endforeach
                @endforeach
            </div>
            <div class="hm-legenda">
                <span class="hm-cell"></span> sem treino
                <span class="hm-cell on" style="margin-left:10px;"></span> treinou
            </div>
        </div>

        <div class="secao">
            <div class="secao-titulo">
                <i class="ph ph-trophy"></i> Recordes pessoais
                @if($rpeMedio)
                    <span class="esforco-tag">Esforço médio: {{ number_format($rpeMedio, 1, ',', '.') }}/10</span>
                @endif
            </div>
            @if($recordes->isEmpty())
                <p style="color: var(--text-muted); font-size: 0.85rem;">Registre a carga ao concluir os treinos para ver seus recordes aqui.</p>
            @else
                <div class="recordes-list">
                    @foreach($recordes as $r)
                        <div class="recorde-item">
                            <span class="rx-nome">{{ $r->nome_exercicio }}</span>
                            <span class="rx-peso"><i class="ph ph-trophy"></i>{{ rtrim(rtrim(number_format($r->recorde, 2, ',', '.'), '0'), ',') }} kg</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="aderencia-box">
            <div class="head">
                <span class="t">Aderência do mês</span>
                <span class="p" style="color: {{ $cor }};">{{ $perc !== null ? $perc.'%' : '–' }}</span>
            </div>
            <div class="bar"><span style="width: {{ $perc !== null ? $perc : 0 }}%; background: {{ $cor }};"></span></div>
            <p class="msg">
                @if($perc === null)
                    Você ainda não tem fichas de treino ativas com dias definidos.
                @elseif($perc >= 80)
                    Excelente! Você está mandando muito bem este mês. 💪
                @elseif($perc >= 50)
                    Bom ritmo — falta pouco para bater sua meta do mês.
                @else
                    Bora retomar o ritmo? Cada treino conta.
                @endif
            </p>
        </div>

        <div class="actions">
            <a href="{{ route('evolucao-carga.minha') }}" class="primary"><i class="ph ph-lightning"></i> Evolução de Carga</a>
            <a href="{{ route('fichas-treino.minhas') }}" class="ghost"><i class="ph ph-barbell"></i> Minhas Fichas</a>
        </div>
    </div>
</body>

</html>
