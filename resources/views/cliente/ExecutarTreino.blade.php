<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executar Treino</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root { --primary:#F4BE16; --bg-dark:#000; --card-bg:#111317; --field:#1a1d23; --text-main:#fff; --text-muted:#9a9a9a; --green:#00e676; --red:#ff5252; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); min-height:100vh; padding-bottom:120px; background-image:radial-gradient(circle at 50% -10%, rgba(244,190,22,0.12), transparent 50%); }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:14px 22px; background:rgba(0,0,0,0.7); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:50; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; }
        .top-bar .t { font-weight:800; font-size:0.95rem; } .top-bar .t small { display:block; color:var(--text-muted); font-size:0.7rem; font-weight:600; }
        .prog-wrap { padding:14px 22px 0; }
        .prog-info { display:flex; justify-content:space-between; font-size:0.74rem; color:var(--text-muted); margin-bottom:6px; font-weight:700; }
        .prog-bar { height:8px; background:rgba(255,255,255,0.08); border-radius:6px; overflow:hidden; }
        .prog-bar > span { display:block; height:100%; background:var(--primary); width:0; transition:width 0.3s; }
        .container { max-width:680px; margin:18px auto; padding:0 22px; }

        .rest-config { display:flex; align-items:center; gap:10px; background:var(--card-bg); border:1px solid var(--border); border-radius:12px; padding:12px 14px; margin-bottom:18px; font-size:0.82rem; }
        .rest-config select { margin-left:auto; background:var(--field); border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:8px; padding:7px 10px; font-family:inherit; }

        .ex { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:18px; margin-bottom:14px; }
        .ex.done { border-color:rgba(0,230,118,0.45); }
        .ex-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:12px; }
        .ex-nome { font-weight:800; font-size:1.05rem; }
        .ex-alvo { font-size:0.74rem; color:var(--text-muted); margin-top:3px; }
        .ex-inputs { display:flex; gap:10px; margin-bottom:14px; }
        .ex-inputs .fg { flex:1; display:flex; flex-direction:column; }
        .ex-inputs label { font-size:0.6rem; text-transform:uppercase; color:var(--text-muted); font-weight:900; margin-bottom:4px; }
        .ex-inputs input { width:100%; padding:9px; background:var(--field); border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:8px; text-align:center; font-size:0.95rem; }
        .ex-inputs input:focus { outline:none; border-color:var(--primary); }
        .sets { display:flex; gap:8px; flex-wrap:wrap; }
        .set-dot { width:44px; height:44px; border-radius:12px; border:1px solid rgba(255,255,255,0.15); background:var(--field); color:var(--text-muted); font-weight:900; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:0.15s; user-select:none; }
        .set-dot.done { background:var(--green); color:#000; border-color:var(--green); }
        .sets-label { font-size:0.6rem; text-transform:uppercase; color:var(--text-muted); font-weight:900; margin-bottom:7px; }

        /* feedback */
        .panel { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:18px; margin-bottom:14px; }
        .panel-title { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--primary); font-weight:900; margin-bottom:14px; }
        .fb-emojis { display:flex; gap:8px; margin-bottom:14px; }
        .fb-emojis label { flex:1; cursor:pointer; text-align:center; padding:8px 4px; border:1px solid rgba(255,255,255,0.1); border-radius:10px; background:var(--field); }
        .fb-emojis label:has(input:checked){ border-color:var(--primary); background:rgba(244,190,22,0.14); }
        .fb-emojis input { display:none; } .fb-emojis .emo { font-size:1.3rem; display:block; } .fb-emojis .cap { font-size:0.56rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; }
        .rpe-chips { display:flex; gap:5px; flex-wrap:wrap; }
        .rpe-chips label { cursor:pointer; width:32px; height:32px; display:flex; align-items:center; justify-content:center; border:1px solid rgba(255,255,255,0.1); border-radius:8px; background:var(--field); font-weight:800; font-size:0.78rem; }
        .rpe-chips label:has(input:checked){ border-color:var(--primary); background:var(--primary); color:#000; }
        .rpe-chips input { display:none; }

        /* barra de finalizar */
        .finish-bar { position:fixed; bottom:0; left:0; right:0; padding:14px 22px; background:rgba(0,0,0,0.85); border-top:1px solid var(--border); backdrop-filter:blur(10px); z-index:40; }
        .finish-bar button { width:100%; max-width:680px; margin:0 auto; display:flex; align-items:center; justify-content:center; gap:10px; padding:15px; border:none; border-radius:14px; background:var(--primary); color:#000; font-weight:900; font-size:1rem; cursor:pointer; }

        /* timer de descanso */
        .rest-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.92); backdrop-filter:blur(6px); z-index:200; display:none; flex-direction:column; align-items:center; justify-content:center; gap:24px; }
        .rest-overlay.on { display:flex; }
        .rest-overlay .lbl { color:var(--primary); font-weight:900; text-transform:uppercase; letter-spacing:1px; font-size:0.85rem; }
        .rest-ring { position:relative; width:220px; height:220px; }
        .rest-ring svg { transform:rotate(-90deg); }
        .rest-ring .num { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:3.5rem; font-weight:900; color:#fff; }
        .rest-actions { display:flex; gap:12px; }
        .rest-actions button { padding:12px 20px; border-radius:12px; border:1px solid var(--border); background:var(--card-bg); color:#fff; font-weight:800; cursor:pointer; font-size:0.9rem; }
        .rest-actions .skip { background:var(--primary); color:#000; border-color:var(--primary); }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('fichas-treino.minhas') }}" class="back-btn"><i class="ph ph-arrow-left"></i></a>
        <div class="t">{{ $ficha->nome_treino }}<small>Modo execução guiada</small></div>
    </div>

    <div class="prog-wrap">
        <div class="prog-info"><span id="progTxt">0 de 0 séries</span><span id="progPct">0%</span></div>
        <div class="prog-bar"><span id="progBar"></span></div>
    </div>

    <form method="POST" action="{{ route('fichas-treino.concluido', $ficha->id) }}" id="formExec">
        @csrf
        <input type="hidden" name="data_treino" value="{{ now()->format('Y-m-d') }}">

        <div class="container">
            <div class="rest-config">
                <i class="ph ph-hourglass-medium" style="color:var(--primary);"></i> Tempo de descanso
                <select onchange="rest_config(this.value)">
                    <option value="30">30s</option>
                    <option value="45">45s</option>
                    <option value="60" selected>60s</option>
                    <option value="90">90s</option>
                    <option value="120">120s</option>
                </select>
            </div>
            @forelse($ficha->exercicios as $ex)
                @php $series = max(1, (int) $ex->series); @endphp
                <div class="ex" id="ex-{{ $ex->id }}" data-series="{{ $series }}">
                    <div class="ex-head">
                        <div>
                            <div class="ex-nome">{{ $ex->nome_exercicio }}</div>
                            <div class="ex-alvo">Alvo: {{ $ex->series }}x{{ $ex->repeticoes }}{{ $ex->peso ? ' · '.$ex->peso.' kg' : '' }}</div>
                            @php $videoEx = $ex->videoResolvido(); @endphp
                            @if($videoEx)
                                <button type="button" onclick="abrirVideo('{{ asset('storage/' . $videoEx) }}')" style="margin-top:6px; background:none; border:none; color:var(--primary); font-size:0.78rem; font-weight:700; cursor:pointer; padding:0; display:inline-flex; align-items:center; gap:6px;"><i class="ph ph-play-circle"></i> Ver execução</button>
                            @endif
                        </div>
                    </div>

                    <div class="ex-inputs">
                        <div class="fg"><label>Peso (kg)</label><input type="number" step="0.5" name="registros[{{ $ex->id }}][peso]" value="{{ $ex->peso }}"></div>
                        <div class="fg"><label>Reps</label><input type="number" name="registros[{{ $ex->id }}][repeticoes]" value="{{ $ex->repeticoes }}"></div>
                    </div>

                    <div class="sets-label">Séries concluídas (toque ao terminar cada uma)</div>
                    <div class="sets">
                        @for($s = 1; $s <= $series; $s++)
                            <div class="set-dot" onclick="toggleSet(this, {{ $ex->id }})">{{ $s }}</div>
                        @endfor
                    </div>
                    <input type="hidden" name="registros[{{ $ex->id }}][series]" id="serie-count-{{ $ex->id }}" value="0">
                </div>
            @empty
                <div class="ex"><div class="ex-nome">Esta ficha não tem exercícios.</div></div>
            @endforelse

            {{-- FEEDBACK --}}
            <div class="panel">
                <div class="panel-title"><i class="ph ph-smiley"></i> Como foi o treino? (opcional)</div>
                <div class="fb-emojis">
                    <label><input type="radio" name="sensacao" value="otimo"><span class="emo">😀</span><span class="cap">Ótimo</span></label>
                    <label><input type="radio" name="sensacao" value="bem"><span class="emo">🙂</span><span class="cap">Bem</span></label>
                    <label><input type="radio" name="sensacao" value="cansado"><span class="emo">😓</span><span class="cap">Cansado</span></label>
                    <label><input type="radio" name="sensacao" value="exausto"><span class="emo">🥵</span><span class="cap">Exausto</span></label>
                    <label><input type="radio" name="sensacao" value="dor"><span class="emo">🤕</span><span class="cap">Dor</span></label>
                </div>
                <div class="panel-title" style="margin:4px 0 10px;"><i class="ph ph-gauge"></i> Esforço</div>
                <div class="rpe-chips">
                    @for($n = 1; $n <= 10; $n++)<label><input type="radio" name="rpe" value="{{ $n }}">{{ $n }}</label>@endfor
                </div>
            </div>
        </div>

        <div class="finish-bar">
            <button type="submit"><i class="ph ph-flag-checkered"></i> Finalizar treino</button>
        </div>
    </form>

    {{-- TIMER DE DESCANSO --}}
    <div class="rest-overlay" id="restOverlay">
        <div class="lbl">Descanso</div>
        <div class="rest-ring">
            <svg width="220" height="220">
                <circle cx="110" cy="110" r="100" stroke="rgba(255,255,255,0.1)" stroke-width="12" fill="none"></circle>
                <circle id="restCircle" cx="110" cy="110" r="100" stroke="#F4BE16" stroke-width="12" fill="none" stroke-linecap="round" stroke-dasharray="628" stroke-dashoffset="0"></circle>
            </svg>
            <div class="num" id="restNum">60</div>
        </div>
        <div class="rest-actions">
            <button type="button" onclick="addRest(15)">+15s</button>
            <button type="button" class="skip" onclick="skipRest()">Pular descanso</button>
        </div>
    </div>

    {{-- MODAL VÍDEO DEMONSTRATIVO --}}
    <div id="videoModal" onclick="if(event.target===this)fecharVideo()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:99999; justify-content:center; align-items:center; padding:20px;">
        <div style="position:relative; max-width:520px; width:100%;">
            <button type="button" onclick="fecharVideo()" style="position:absolute; top:-38px; right:0; background:none; border:none; color:#fff; font-size:1.4rem; cursor:pointer;">✕</button>
            <video id="videoPlayer" controls playsinline autoplay style="width:100%; border-radius:14px; background:#000;"></video>
        </div>
    </div>

    <script>
        function abrirVideo(url) {
            if (!url) return;
            const p = document.getElementById('videoPlayer');
            p.src = url;
            document.getElementById('videoModal').style.display = 'flex';
            p.play().catch(() => {});
        }
        function fecharVideo() {
            const p = document.getElementById('videoPlayer');
            p.pause(); p.src = '';
            document.getElementById('videoModal').style.display = 'none';
        }

        const CIRC = 628; // 2*pi*100
        let restDefault = 60, restLeft = 0, restTotal = 60, restTimer = null;

        document.addEventListener('DOMContentLoaded', updateProgress);

        function rest_config(v) { restDefault = parseInt(v); }

        function toggleSet(el, exId) {
            const marcando = !el.classList.contains('done');
            el.classList.toggle('done');
            const ex = document.getElementById('ex-' + exId);
            const feitas = ex.querySelectorAll('.set-dot.done').length;
            document.getElementById('serie-count-' + exId).value = feitas;
            ex.classList.toggle('done', feitas >= parseInt(ex.dataset.series));
            updateProgress();
            if (marcando) startRest();
        }

        function updateProgress() {
            let total = 0, done = 0;
            document.querySelectorAll('.ex[data-series]').forEach(ex => {
                total += parseInt(ex.dataset.series);
                done += ex.querySelectorAll('.set-dot.done').length;
            });
            const pct = total ? Math.round(done / total * 100) : 0;
            document.getElementById('progTxt').textContent = `${done} de ${total} séries`;
            document.getElementById('progPct').textContent = pct + '%';
            document.getElementById('progBar').style.width = pct + '%';
        }

        function startRest() {
            restTotal = restDefault; restLeft = restDefault;
            document.getElementById('restOverlay').classList.add('on');
            tickRest();
            clearInterval(restTimer);
            restTimer = setInterval(() => { restLeft--; tickRest(); if (restLeft <= 0) endRest(); }, 1000);
        }
        function tickRest() {
            document.getElementById('restNum').textContent = Math.max(0, restLeft);
            const off = CIRC * (1 - restLeft / restTotal);
            document.getElementById('restCircle').style.strokeDashoffset = off;
        }
        function addRest(s) { restLeft += s; restTotal = Math.max(restTotal, restLeft); tickRest(); }
        function skipRest() { endRest(); }
        function endRest() {
            clearInterval(restTimer);
            document.getElementById('restOverlay').classList.remove('on');
            if (navigator.vibrate) navigator.vibrate(200);
        }
    </script>
</body>

</html>
