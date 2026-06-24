<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progresso · {{ $cliente->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root { --primary:#F4BE16; --bg-dark:#000; --card-bg:#111317; --field:#1a1d23; --text-main:#fff; --text-muted:#9a9a9a; --green:#00e676; --red:#ff5252; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); min-height:100vh; background-image:radial-gradient(circle at 12% -10%, rgba(244,190,22,0.1), transparent 45%); }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:15px 40px; background:rgba(0,0,0,0.6); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .back-btn:hover { background:var(--primary); color:#000; }
        .top-bar .title { font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px; }
        .top-bar .title i { color:var(--primary); }
        .container { max-width:860px; margin:26px auto; padding:0 20px; }
        h1 { font-size:1.5rem; font-weight:900; color:var(--primary); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-ok { background:rgba(0,230,118,0.1); color:var(--green); border:1px solid var(--green); padding:14px; border-radius:12px; margin-bottom:18px; display:flex; gap:10px; align-items:center; font-size:0.9rem; }
        .panel { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:20px; }
        .panel-title { font-size:0.74rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--primary); font-weight:900; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        label.lbl { font-size:0.62rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); font-weight:900; display:block; margin-bottom:4px; }
        input, select { width:100%; padding:10px; background:var(--field); border:1px solid rgba(255,255,255,0.1); color:var(--text-main); border-radius:9px; font-size:0.9rem; font-family:inherit; }
        input:focus, select:focus { outline:none; border-color:var(--primary); }
        .row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
        .row .fg { flex:1; min-width:130px; display:flex; flex-direction:column; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 16px; border:none; border-radius:10px; font-weight:900; font-size:0.8rem; cursor:pointer; }
        .btn-primary { background:var(--primary); color:#000; } .btn-primary:hover { filter:brightness(1.1); }
        table { width:100%; border-collapse:collapse; font-size:0.82rem; }
        th { text-align:left; padding:8px 6px; color:var(--primary); font-size:0.66rem; text-transform:uppercase; font-weight:900; }
        td { padding:9px 6px; border-bottom:1px solid rgba(255,255,255,0.05); }
        .scroll-x { overflow-x:auto; }
        .fotos-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(120px,1fr)); gap:12px; }
        .foto-card { background:var(--field); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
        .foto-card img { width:100%; aspect-ratio:3/4; object-fit:cover; cursor:pointer; }
        .foto-card.sel img { outline:3px solid var(--primary); outline-offset:-3px; }
        .foto-card .meta { padding:7px 9px; font-size:0.66rem; color:var(--text-muted); }
        .foto-card .meta b { color:#fff; }
        .compare { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:16px; }
        .compare img { width:100%; border-radius:10px; border:1px solid var(--border); }
        .compare figcaption { font-size:0.7rem; color:var(--text-muted); text-align:center; margin-top:6px; }
        .meta-item { border:1px solid var(--border); border-radius:12px; padding:14px; margin-bottom:10px; }
        .meta-item.ok { border-color:rgba(0,230,118,0.5); }
        .mi-head { display:flex; justify-content:space-between; gap:10px; }
        .mi-head .tit { font-weight:800; font-size:0.92rem; }
        .mi-head .tipo { font-size:0.62rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; margin-top:2px; }
        .badge-ok { font-size:0.6rem; font-weight:900; color:var(--green); background:rgba(0,230,118,0.12); border:1px solid rgba(0,230,118,0.4); padding:3px 9px; border-radius:20px; height:fit-content; }
        .bar { height:8px; background:rgba(255,255,255,0.08); border-radius:6px; overflow:hidden; margin:9px 0 5px; }
        .bar > span { display:block; height:100%; border-radius:6px; background:var(--primary); }
        .mi-foot { font-size:0.74rem; color:var(--text-muted); }
        .empty { color:var(--text-muted); text-align:center; padding:18px 0; font-size:0.85rem; }
        .hint { font-size:0.72rem; color:var(--text-muted); margin:8px 0; }
        @media (max-width:600px){ .top-bar{padding:15px 20px;} }
    </style>
</head>

<body>
    <div class="top-bar">
        <a href="{{ route('fichas-treino.aluno', $cliente->id) }}" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <span class="title"><i class="fas fa-chart-line"></i> Progresso do aluno</span>
    </div>

    <div class="container">
        <h1><i class="fas fa-chart-line"></i> {{ strtoupper($cliente->nome) }}</h1>

        @if(session('success'))
            <div class="alert-ok"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif

        {{-- MEDIDAS --}}
        <div class="panel">
            <div class="panel-title"><i class="fas fa-arrow-trend-up"></i> Evolução de medidas
                @if($medidas->count() > 0)
                <select id="campoSel" onchange="renderChart()" style="margin-left:auto; width:auto;">
                    @foreach(\App\Models\MedidaCorporal::CAMPOS as $campo => $label)
                        <option value="{{ $campo }}">{{ $label }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            @if($medidas->count() === 0)
                <div class="empty">O aluno ainda não registrou medidas.</div>
            @else
                <canvas id="chartMedidas" style="max-height:280px; margin-bottom:16px;"></canvas>
                <div class="scroll-x">
                    <table>
                        <thead><tr><th>Data</th><th>Peso</th><th>%G</th><th>Cint.</th><th>Quad.</th><th>Braço</th><th>Peito</th><th>Coxa</th></tr></thead>
                        <tbody>
                            @foreach($medidas->sortByDesc('data') as $m)
                            <tr><td>{{ $m->data->format('d/m/y') }}</td><td>{{ $m->peso }}</td><td>{{ $m->percentual_gordura }}</td><td>{{ $m->cintura }}</td><td>{{ $m->quadril }}</td><td>{{ $m->braco }}</td><td>{{ $m->peito }}</td><td>{{ $m->coxa }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- FOTOS --}}
        <div class="panel">
            <div class="panel-title"><i class="fas fa-camera"></i> Fotos de progresso</div>
            @if($fotos->count() === 0)
                <div class="empty">Sem fotos enviadas.</div>
            @else
                <p class="hint"><i class="fas fa-circle-info"></i> Toque em duas fotos para comparar lado a lado.</p>
                <div class="fotos-grid">
                    @foreach($fotos as $f)
                    <div class="foto-card" data-url="{{ asset('storage/'.$f->caminho) }}" data-cap="{{ $f->data->format('d/m/y') }}{{ $f->peso ? ' · '.$f->peso.'kg' : '' }}">
                        <img src="{{ asset('storage/'.$f->caminho) }}" onclick="toggleCompare(this.parentElement)">
                        <div class="meta"><b>{{ $f->data->format('d/m/y') }}</b> {{ $f->peso ? $f->peso.' kg' : '' }}</div>
                    </div>
                    @endforeach
                </div>
                <div id="compareBox" class="compare" style="display:none;"></div>
            @endif
        </div>

        {{-- METAS --}}
        <div class="panel">
            <div class="panel-title"><i class="fas fa-bullseye"></i> Metas do aluno</div>

            <form method="POST" action="{{ route('metas.criar-aluno', $cliente->id) }}" style="margin-bottom:18px;">
                @csrf
                <div class="row">
                    <div class="fg" style="flex:2;">
                        <label class="lbl">Tipo</label>
                        <select name="tipo" id="tipoSel" onchange="toggleCampos()">
                            <option value="treinos_mes">Treinos no mês</option>
                            <option value="carga">Carga em um exercício</option>
                            <option value="livre">Meta livre</option>
                        </select>
                    </div>
                    <div class="fg js-alvo"><label class="lbl">Alvo</label><input type="number" step="0.5" name="alvo" placeholder="ex: 60"></div>
                </div>
                <div class="row">
                    <div class="fg" style="flex:2;"><label class="lbl">Título</label><input type="text" name="titulo" placeholder="ex: Supino 60kg" required></div>
                    <div class="fg"><label class="lbl">Prazo (opcional)</label><input type="date" name="prazo"></div>
                </div>
                <div class="row js-exercicio" style="display:none;">
                    <div class="fg"><label class="lbl">Exercício</label>
                        <select name="exercicio">
                            <option value="">Selecione...</option>
                            @foreach($exercicios as $ex)<option value="{{ $ex }}">{{ $ex }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary"><i class="fas fa-bullseye"></i> Definir meta</button>
            </form>

            @if($metas->isEmpty())
                <div class="empty">Nenhuma meta ainda.</div>
            @else
                @foreach($metas as $m)
                    @php $p = $m->progresso; @endphp
                    <div class="meta-item {{ $p['atingida'] ? 'ok' : '' }}">
                        <div class="mi-head">
                            <div>
                                <div class="tit">{{ $m->titulo }}</div>
                                <div class="tipo">{{ $m->tipoLabel() }}{{ $m->exercicio ? ' · '.$m->exercicio : '' }}</div>
                            </div>
                            @if($p['atingida'])<span class="badge-ok"><i class="fas fa-check"></i> Atingida</span>@endif
                        </div>
                        @if($m->tipo !== 'livre')
                            <div class="bar"><span style="width:{{ $p['percent'] }}%; {{ $p['atingida'] ? 'background:var(--green);' : '' }}"></span></div>
                            <div class="mi-foot">{{ rtrim(rtrim(number_format($p['atual'],2,',','.'),'0'),',') }} / {{ rtrim(rtrim(number_format($p['alvo'],2,',','.'),'0'),',') }} ({{ $p['percent'] }}%){{ $m->prazo ? ' · até '.$m->prazo->format('d/m/Y') : '' }}</div>
                        @else
                            <div class="mi-foot">{{ $m->concluida ? 'Concluída ✅' : 'Em andamento' }}{{ $m->prazo ? ' · até '.$m->prazo->format('d/m/Y') : '' }}</div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        @if($medidas->count() > 0)
        const dadosMedidas = {
            labels: {!! json_encode($medidas->map(fn($m) => $m->data->format('d/m/y'))->values()) !!},
            campos: {!! json_encode(collect(\App\Models\MedidaCorporal::CAMPOS)->mapWithKeys(fn($label,$campo) => [$campo => $medidas->map(fn($m) => $m->$campo !== null ? (float)$m->$campo : null)->values()])) !!},
            rotulos: {!! json_encode(\App\Models\MedidaCorporal::CAMPOS) !!}
        };
        let chart;
        function renderChart() {
            const campo = document.getElementById('campoSel').value;
            const ctx = document.getElementById('chartMedidas').getContext('2d');
            const g = ctx.createLinearGradient(0,0,0,280);
            g.addColorStop(0,'rgba(244,190,22,0.35)'); g.addColorStop(1,'rgba(244,190,22,0)');
            const data = { labels: dadosMedidas.labels, datasets:[{ label:dadosMedidas.rotulos[campo], data:dadosMedidas.campos[campo], borderColor:'#F4BE16', backgroundColor:g, borderWidth:3, pointBackgroundColor:'#F4BE16', pointRadius:4, tension:0.3, fill:true, spanGaps:true }] };
            const opts = { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ticks:{color:'#9a9a9a'},grid:{color:'rgba(255,255,255,0.06)'}}, x:{ticks:{color:'#9a9a9a',maxTicksLimit:8},grid:{color:'rgba(255,255,255,0.04)'}} } };
            if (chart) { chart.data = data; chart.options = opts; chart.update(); } else chart = new Chart(ctx, { type:'line', data, options:opts });
        }
        renderChart();
        @endif

        let selecionadas = [];
        function toggleCompare(card) {
            const i = selecionadas.indexOf(card);
            if (i >= 0) { selecionadas.splice(i,1); card.classList.remove('sel'); }
            else { selecionadas.push(card); card.classList.add('sel'); if (selecionadas.length > 2) { selecionadas.shift().classList.remove('sel'); } }
            const box = document.getElementById('compareBox'); if (!box) return;
            if (selecionadas.length === 2) { box.style.display='grid'; box.innerHTML = selecionadas.map(c => `<figure><img src="${c.dataset.url}"><figcaption>${c.dataset.cap}</figcaption></figure>`).join(''); }
            else { box.style.display='none'; box.innerHTML=''; }
        }
        function toggleCampos() {
            const tipo = document.getElementById('tipoSel').value;
            document.querySelectorAll('.js-exercicio').forEach(e => e.style.display = tipo === 'carga' ? 'flex' : 'none');
            document.querySelectorAll('.js-alvo').forEach(e => e.style.display = tipo === 'livre' ? 'none' : 'flex');
        }
        toggleCampos();
    </script>
</body>

</html>
