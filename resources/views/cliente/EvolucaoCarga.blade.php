<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evolução de Carga</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #F4BE16;     /* SNR FIT amarelo */
            --bg-dark: #000000;
            --card-bg: #111317;
            --text-main: #ffffff;
            --text-muted: #9a9a9a;
            --border: rgba(255, 255, 255, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            background-image: radial-gradient(circle at 15% -10%, rgba(244, 190, 22, 0.10), transparent 45%);
        }
        a { color: inherit; text-decoration: none; }
        button, select, input { font-family: inherit; }

        .top-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 40px;
            background: rgba(0, 0, 0, 0.6);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        .back-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--primary);
            width: 40px; height: 40px;
            border-radius: 10px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: 0.3s; font-size: 1.1rem;
        }
        .back-btn:hover { background: var(--primary); color: #000; }
        .top-bar .title { font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .top-bar .title i { color: var(--primary); }

        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }

        h1 {
            font-size: 1.8rem; font-weight: 900; color: var(--primary);
            margin-bottom: 6px; display: flex; align-items: center; gap: 10px;
        }
        .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 26px; }

        .filters {
            display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 22px;
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 200px; }
        .filter-group label {
            font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;
            font-weight: 900; color: var(--text-muted);
        }
        select.input {
            padding: 11px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text-main);
            border-radius: 10px; font-size: 0.95rem; cursor: pointer;
        }
        select.input:focus { outline: none; border-color: var(--primary); }

        .period-btns { display: flex; gap: 8px; }
        .period-btns button {
            padding: 11px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text-muted);
            border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.2s;
            font-size: 0.85rem;
        }
        .period-btns button.active {
            background: var(--primary); color: #000; border-color: var(--primary);
        }

        .stats { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 22px; }
        .stat-card {
            flex: 1; min-width: 140px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--primary);
            border-radius: 14px; padding: 16px 18px;
        }
        .stat-card .label {
            font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-muted); font-weight: 900; margin-bottom: 6px;
        }
        .stat-card .value { font-size: 1.5rem; font-weight: 900; color: var(--text-main); }
        .stat-card .value.up { color: #00e676; }
        .stat-card .value.down { color: #ff5252; }

        .chart-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            min-height: 360px;
            position: relative;
        }
        .empty {
            text-align: center; padding: 70px 20px; color: var(--text-muted);
        }
        .empty i { font-size: 3rem; color: var(--primary); margin-bottom: 16px; display: block; opacity: 0.8; }
        .empty p { font-size: 1.05rem; color: var(--text-main); margin-bottom: 8px; }

        @media (max-width: 768px) {
            .top-bar { padding: 15px 20px; }
            h1 { font-size: 1.4rem; }
            .filter-group { min-width: 100%; }
        }
    </style>
</head>

<body>

    <div class="top-bar">
        <a href="{{ $voltarUrl }}" class="back-btn" title="Voltar"><i class="fas fa-arrow-left"></i></a>
        <span class="title"><i class="fas fa-bolt"></i> Evolução de Carga</span>
    </div>

    <div class="container">
        <h1><i class="fas fa-bolt"></i> EVOLUÇÃO DE CARGA</h1>
        <p class="subtitle">
            @if($modo === 'personal')
                Progressão de carga de <strong style="color:var(--primary)">{{ $cliente->nome }}</strong> por exercício.
            @else
                Acompanhe sua progressão de carga em cada exercício ao longo do tempo.
            @endif
        </p>

        @if(empty($exercicios))
            <div class="chart-card">
                <div class="empty">
                    <i class="fas fa-bolt"></i>
                    <p>Ainda não há cargas registradas.</p>
                    <small>Os dados aparecem aqui conforme os treinos forem concluídos informando a carga de cada exercício.</small>
                </div>
            </div>
        @else
            <div class="filters">
                <div class="filter-group">
                    <label><i class="fas fa-dumbbell"></i> Exercício</label>
                    <select id="selExercicio" class="input" onchange="carregar()">
                        @foreach($exercicios as $ex)
                            <option value="{{ $ex }}">{{ $ex }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="flex: 0;">
                    <label><i class="fas fa-calendar"></i> Período</label>
                    <div class="period-btns" id="periodBtns">
                        <button type="button" data-dias="30" onclick="setPeriodo(30)">30 dias</button>
                        <button type="button" data-dias="90" class="active" onclick="setPeriodo(90)">90 dias</button>
                        <button type="button" data-dias="180" onclick="setPeriodo(180)">180 dias</button>
                    </div>
                </div>
            </div>

            <div class="stats" id="stats" style="display:none;">
                <div class="stat-card">
                    <div class="label">Carga inicial</div>
                    <div class="value" id="statInicial">–</div>
                </div>
                <div class="stat-card">
                    <div class="label">Carga atual</div>
                    <div class="value" id="statAtual">–</div>
                </div>
                <div class="stat-card">
                    <div class="label">Variação</div>
                    <div class="value" id="statVar">–</div>
                </div>
                <div class="stat-card">
                    <div class="label">Maior carga</div>
                    <div class="value" id="statMax">–</div>
                </div>
            </div>

            <div class="chart-card">
                <canvas id="chartCarga" style="max-height: 360px;"></canvas>
                <div class="empty" id="emptyPeriodo" style="display:none;">
                    <i class="fas fa-chart-line"></i>
                    <p>Sem registros neste período.</p>
                    <small>Tente um período maior ou outro exercício.</small>
                </div>
            </div>
        @endif
    </div>

    @if(!empty($exercicios))
    <script>
        const dadosUrl = @json($dadosUrl);
        let periodo = 90;
        let chart = null;

        function setPeriodo(dias) {
            periodo = dias;
            document.querySelectorAll('#periodBtns button').forEach(b => {
                b.classList.toggle('active', Number(b.dataset.dias) === dias);
            });
            carregar();
        }

        function fmt(n) {
            return (Math.round(n * 100) / 100).toString().replace('.', ',') + ' kg';
        }

        function atualizarStats(pesos) {
            const stats = document.getElementById('stats');
            if (!pesos.length) { stats.style.display = 'none'; return; }
            stats.style.display = 'flex';

            const inicial = pesos[0];
            const atual = pesos[pesos.length - 1];
            const max = Math.max(...pesos);
            const diff = atual - inicial;

            document.getElementById('statInicial').textContent = fmt(inicial);
            document.getElementById('statAtual').textContent = fmt(atual);
            document.getElementById('statMax').textContent = fmt(max);

            const varEl = document.getElementById('statVar');
            const sinal = diff > 0 ? '+' : '';
            varEl.textContent = sinal + fmt(diff);
            varEl.classList.remove('up', 'down');
            if (diff > 0) varEl.classList.add('up');
            else if (diff < 0) varEl.classList.add('down');
        }

        function render(json) {
            const empty = document.getElementById('emptyPeriodo');
            const canvas = document.getElementById('chartCarga');

            if (!json.pesos || json.pesos.length === 0) {
                if (chart) { chart.destroy(); chart = null; }
                canvas.style.display = 'none';
                empty.style.display = 'block';
                atualizarStats([]);
                return;
            }

            canvas.style.display = 'block';
            empty.style.display = 'none';
            atualizarStats(json.pesos);

            const ctx = canvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 360);
            grad.addColorStop(0, 'rgba(244, 190, 22, 0.35)');
            grad.addColorStop(1, 'rgba(244, 190, 22, 0)');

            const data = {
                labels: json.labels,
                datasets: [{
                    label: 'Carga (kg)',
                    data: json.pesos,
                    borderColor: '#F4BE16',
                    backgroundColor: grad,
                    borderWidth: 3,
                    pointBackgroundColor: '#F4BE16',
                    pointBorderColor: '#000',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    tension: 0.3,
                    fill: true,
                }]
            };

            const options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111317',
                        borderColor: '#F4BE16',
                        borderWidth: 1,
                        titleColor: '#F4BE16',
                        bodyColor: '#fff',
                        padding: 12,
                        callbacks: {
                            afterBody: (items) => {
                                const i = items[0].dataIndex;
                                const reps = json.reps[i];
                                const series = json.series[i];
                                const linhas = [];
                                if (series != null) linhas.push('Séries: ' + series);
                                if (reps != null) linhas.push('Reps: ' + reps);
                                return linhas;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: { color: '#9a9a9a', callback: (v) => v + ' kg' },
                        grid: { color: 'rgba(255,255,255,0.06)' }
                    },
                    x: {
                        ticks: { color: '#9a9a9a', maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                        grid: { color: 'rgba(255,255,255,0.04)' }
                    }
                }
            };

            if (chart) {
                chart.data = data;
                chart.options = options;
                chart.update();
            } else {
                chart = new Chart(ctx, { type: 'line', data, options });
            }
        }

        async function carregar() {
            const exercicio = document.getElementById('selExercicio').value;
            const url = new URL(dadosUrl, window.location.origin);
            url.searchParams.set('exercicio', exercicio);
            url.searchParams.set('dias', periodo);
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                render(json);
            } catch (e) {
                console.error('Falha ao carregar evolução de carga', e);
            }
        }

        carregar();
    </script>
    @endif

</body>

</html>
