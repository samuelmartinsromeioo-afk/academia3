<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequência e Aderência</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background-image: radial-gradient(circle at 12% -10%, rgba(244, 190, 22, 0.10), transparent 45%);
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

        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        h1 { font-size: 1.8rem; font-weight: 900; color: var(--primary); margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
        .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 26px; }

        .resumo { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 28px; }
        .resumo-card {
            flex: 1; min-width: 160px;
            background: var(--card-bg); border: 1px solid var(--border);
            border-left: 3px solid var(--primary); border-radius: 14px; padding: 18px 20px;
        }
        .resumo-card.alert { border-left-color: var(--red); }
        .resumo-card .label { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 900; margin-bottom: 8px; }
        .resumo-card .value { font-size: 1.9rem; font-weight: 900; }
        .resumo-card .value small { font-size: 0.9rem; color: var(--text-muted); font-weight: 600; }

        .alunos { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 18px; }
        .aluno-card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 18px; padding: 20px; transition: 0.2s;
        }
        .aluno-card.sumido { border-color: rgba(255, 82, 82, 0.5); box-shadow: 0 0 18px rgba(255, 82, 82, 0.08); }
        .aluno-top { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
        .aluno-top img { width: 52px; height: 52px; border-radius: 50%; border: 2px solid var(--primary); }
        .aluno-top .nome { font-weight: 800; font-size: 1.05rem; }
        .aluno-top .sub { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
        .badge-sumido {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255, 82, 82, 0.15); color: var(--red);
            border: 1px solid var(--red); border-radius: 20px;
            padding: 3px 10px; font-size: 0.62rem; font-weight: 900; text-transform: uppercase;
        }

        .aderencia-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; }
        .aderencia-row .perc { font-size: 1.4rem; font-weight: 900; }
        .aderencia-row .frac { font-size: 0.8rem; color: var(--text-muted); }
        .bar { height: 8px; background: rgba(255,255,255,0.08); border-radius: 6px; overflow: hidden; margin-bottom: 16px; }
        .bar > span { display: block; height: 100%; border-radius: 6px; }

        .aluno-meta { display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 16px; }
        .aluno-meta b { color: var(--text-main); }

        .aluno-actions { display: flex; gap: 8px; }
        .aluno-actions a {
            flex: 1; text-align: center; padding: 10px; border-radius: 9px; font-weight: 800; font-size: 0.78rem;
            border: 1px solid var(--border); transition: 0.2s;
        }
        .aluno-actions a.fichas { background: rgba(255,255,255,0.04); color: var(--text-main); }
        .aluno-actions a.fichas:hover { background: rgba(255,255,255,0.08); }
        .aluno-actions a.evolucao { background: var(--primary); color: #000; border-color: var(--primary); }
        .aluno-actions a.evolucao:hover { filter: brightness(1.1); }

        .empty { text-align: center; padding: 70px 20px; color: var(--text-muted); background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; }
        .empty i { font-size: 3rem; color: var(--primary); margin-bottom: 16px; display: block; opacity: 0.8; }
        .empty p { font-size: 1.05rem; color: var(--text-main); }

        @media (max-width: 768px) {
            .top-bar { padding: 15px 20px; }
            h1 { font-size: 1.4rem; }
            .alunos { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <div class="top-bar">
        <a href="{{ route('personal.dashboard') }}" class="back-btn" title="Voltar"><i class="fas fa-arrow-left"></i></a>
        <span class="title"><i class="fas fa-bolt"></i> Frequência &amp; Aderência</span>
    </div>

    <div class="container">
        <h1><i class="fas fa-bolt"></i> FREQUÊNCIA &amp; ADERÊNCIA</h1>
        <p class="subtitle">Treinos realizados x planejados no mês atual.</p>

        <div class="resumo">
            <div class="resumo-card">
                <div class="label">Alunos</div>
                <div class="value">{{ $resumoGeral['totalAlunos'] }}</div>
            </div>
            <div class="resumo-card">
                <div class="label">Aderência média</div>
                <div class="value">{{ $resumoGeral['mediaAderencia'] !== null ? $resumoGeral['mediaAderencia'].'%' : '–' }}</div>
            </div>
            <div class="resumo-card {{ $resumoGeral['sumidos'] > 0 ? 'alert' : '' }}">
                <div class="label">Sumidos (+{{ 7 }} dias)</div>
                <div class="value" style="{{ $resumoGeral['sumidos'] > 0 ? 'color:var(--red)' : '' }}">{{ $resumoGeral['sumidos'] }}</div>
            </div>
        </div>

        @if(count($alunos) === 0)
            <div class="empty">
                <i class="fas fa-user-slash"></i>
                <p>Você ainda não montou fichas para nenhum aluno.</p>
                <small>A aderência aparece aqui assim que houver fichas de treino ativas.</small>
            </div>
        @else
            <div class="alunos">
                @foreach($alunos as $a)
                    @php
                        $perc = $a['aderencia'];
                        $cor = $perc === null ? 'var(--text-muted)' : ($perc >= 80 ? 'var(--green)' : ($perc >= 50 ? 'var(--yellow)' : 'var(--red)'));
                        $cliente = $a['cliente'];
                    @endphp
                    <div class="aluno-card {{ $a['sumido'] ? 'sumido' : '' }}">
                        <div class="aluno-top">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($cliente->nome) }}&background=F4BE16&color=000" alt="">
                            <div style="flex:1;">
                                <div class="nome">{{ $cliente->nome }}</div>
                                <div class="sub">{{ $a['fichasAtivas'] }} ficha(s) ativa(s)</div>
                            </div>
                            @if($a['sumido'])
                                <span class="badge-sumido"><i class="fas fa-triangle-exclamation"></i> Sumido</span>
                            @endif
                        </div>

                        <div class="aderencia-row">
                            <span class="perc" style="color: {{ $cor }};">{{ $perc !== null ? $perc.'%' : '–' }}</span>
                            <span class="frac">{{ $a['realizados'] }} de {{ $a['planejados'] }} treinos</span>
                        </div>
                        <div class="bar"><span style="width: {{ $perc !== null ? $perc : 0 }}%; background: {{ $cor }};"></span></div>

                        <div class="aluno-meta">
                            <span>Último treino:
                                <b>
                                    @if($a['ultimo'])
                                        {{ $a['diasSemTreino'] === 0 ? 'hoje' : 'há '.$a['diasSemTreino'].' dia'.($a['diasSemTreino'] > 1 ? 's' : '') }}
                                    @else
                                        nunca
                                    @endif
                                </b>
                            </span>
                        </div>

                        <div class="aluno-actions">
                            <a href="{{ route('fichas-treino.aluno', $cliente->id) }}" class="fichas"><i class="fas fa-dumbbell"></i> Fichas</a>
                            <a href="{{ route('evolucao-carga.aluno', $cliente->id) }}" class="evolucao"><i class="fas fa-bolt"></i> Evolução</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>

</html>
