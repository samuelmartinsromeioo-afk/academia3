{{-- Modal de celebrações (tela cheia). Mostra os eventos não-vistos do usuário
     logado (primeiro login, sequência, perda de peso, progressão de carga) e os
     marca como vistos. Inclua em telas onde o usuário aterrissa (dashboards,
     "Minhas Fichas"). Requer Phosphor Icons + fontes da marca (snrfit-brand.css). --}}
@php
    $__mapaPapel = [
        'cliente_id'  => 'cliente',
        'personal_id' => 'personal',
        'academia_id' => 'academia',
        'studio_id'   => 'studio',
        'loja_id'     => 'loja',
    ];
    $__papel = null; $__uid = null;
    foreach ($__mapaPapel as $__k => $__p) {
        if (session($__k)) { $__papel = $__p; $__uid = (int) session($__k); break; }
    }

    $__celebs = collect();
    if ($__papel && $__uid && \Illuminate\Support\Facades\Schema::hasTable('celebracoes')) {
        $__celebs = \App\Models\Celebracao::where('papel', $__papel)
            ->where('usuario_id', $__uid)
            ->whereNull('visto_em')
            ->orderBy('id')
            ->limit(5)
            ->get();

        if ($__celebs->isNotEmpty()) {
            \App\Models\Celebracao::whereIn('id', $__celebs->pluck('id'))
                ->update(['visto_em' => now()]);
        }
    }

    // Payload simples para o JS (montado aqui para não usar closure dentro do @json).
    $__payload = $__celebs->map(function ($c) {
        return [
            'tipo'     => $c->tipo,
            'titulo'   => $c->titulo,
            'mensagem' => $c->mensagem,
            'icone'    => $c->icone ?: 'ph-trophy',
            'cor'      => $c->cor_medalha ?: '#d4ff00',
            'num'      => $c->dados_extras['sequencia_atual'] ?? null,
        ];
    })->values();
@endphp

@if($__celebs->isNotEmpty())
<style>
    #snrCelebOverlay {
        position: fixed; inset: 0; z-index: 9999; overflow: hidden;
        display: flex; align-items: center; justify-content: center; padding: 24px;
        background: radial-gradient(circle at 50% 42%, rgba(20,22,26,0.7), rgba(0,0,0,0.86));
        backdrop-filter: blur(9px);
        opacity: 0; transition: opacity .35s ease;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    #snrCelebOverlay.show { opacity: 1; }

    .snr-celeb-card {
        position: relative; width: 100%; max-width: 440px; text-align: center;
        background: linear-gradient(180deg, #16181d, #0a0b0d); /* fallback */
        background:
            radial-gradient(120% 80% at 50% -10%, color-mix(in srgb, var(--cor) 14%, transparent), transparent 60%),
            linear-gradient(180deg, #16181d, #0a0b0d);
        border: 1px solid rgba(255,255,255,0.10); /* fallback */
        border: 1px solid color-mix(in srgb, var(--cor) 35%, rgba(255,255,255,0.08));
        border-radius: 26px; padding: 46px 32px 26px;
        box-shadow: 0 30px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.03) inset;
        transform: scale(0.7) translateY(20px); opacity: 0;
    }
    #snrCelebOverlay.show .snr-celeb-card {
        animation: snrCelebPop .6s cubic-bezier(.16,1.05,.3,1.3) forwards;
    }
    @keyframes snrCelebPop { to { transform: scale(1) translateY(0); opacity: 1; } }

    .snr-celeb-close {
        position: absolute; top: 14px; right: 16px; z-index: 3;
        background: none; border: none; color: rgba(255,255,255,0.4);
        font-size: 1.3rem; cursor: pointer; line-height: 1; padding: 4px; transition: .2s;
    }
    .snr-celeb-close:hover { color: #fff; transform: rotate(90deg); }

    /* ---- Medalha ---- */
    .snr-celeb-medal {
        position: relative; width: 130px; height: 130px; margin: 4px auto 24px;
        display: flex; align-items: center; justify-content: center;
    }
    .snr-celeb-medal::before { /* raios girando */
        content: ""; position: absolute; inset: -30px; border-radius: 50%;
        background: conic-gradient(from 0deg, transparent 0 10deg, var(--cor) 10deg 15deg, transparent 15deg 30deg);
        opacity: .22; animation: snrSpin 10s linear infinite;
    }
    @keyframes snrSpin { to { transform: rotate(360deg); } }

    .snr-celeb-disc {
        position: relative; width: 100%; height: 100%; border-radius: 50%; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        background: radial-gradient(circle at 34% 28%, rgba(255,255,255,0.4), transparent 55%), var(--cor);
        box-shadow: 0 0 0 7px rgba(255,255,255,0.06), 0 12px 30px color-mix(in srgb, var(--cor) 45%, transparent);
        transform: scale(0);
    }
    #snrCelebOverlay.show .snr-celeb-disc { animation: snrStamp .55s cubic-bezier(.2,1.2,.3,1.4) .12s forwards, snrGlow 1.8s ease-in-out 1s infinite; }
    @keyframes snrStamp { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.12) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes snrGlow {
        0%,100% { box-shadow: 0 0 0 7px rgba(255,255,255,0.06), 0 0 24px 2px color-mix(in srgb, var(--cor) 60%, transparent); }
        50%     { box-shadow: 0 0 0 7px rgba(255,255,255,0.06), 0 0 52px 12px color-mix(in srgb, var(--cor) 75%, transparent); }
    }
    .snr-celeb-disc::after { /* brilho que varre */
        content: ""; position: absolute; top: 0; left: -120%; width: 60%; height: 100%;
        background: linear-gradient(105deg, transparent, rgba(255,255,255,0.85), transparent);
        transform: skewX(-20deg);
    }
    #snrCelebOverlay.show .snr-celeb-disc::after { animation: snrShine 2.6s ease-in-out .7s infinite; }
    @keyframes snrShine { 0% { left: -120%; } 35%,100% { left: 140%; } }

    .snr-celeb-disc i { font-size: 3.4rem; color: #0a0b0d; position: relative; z-index: 1; }
    .snr-celeb-disc .snr-celeb-discnum {
        font-family: 'Syncopate', sans-serif; font-weight: 700; color: #0a0b0d;
        font-size: 2.6rem; line-height: 1; position: relative; z-index: 1; letter-spacing: -1px;
    }

    /* ---- Texto ---- */
    .snr-celeb-kicker {
        display: inline-flex; align-items: center; gap: 7px;
        font-size: 0.64rem; font-weight: 800; letter-spacing: 3px; text-transform: uppercase;
        color: var(--cor); margin-bottom: 12px;
    }
    .snr-celeb-kicker::before, .snr-celeb-kicker::after {
        content: ""; width: 16px; height: 1px; background: color-mix(in srgb, var(--cor) 60%, transparent);
    }
    .snr-celeb-title {
        font-family: 'Syncopate', sans-serif; font-weight: 700; text-transform: uppercase;
        font-size: clamp(1.15rem, 4.6vw, 1.5rem); line-height: 1.18; letter-spacing: -0.5px;
        color: #fff; margin: 0 0 14px;
    }
    .snr-celeb-msg { font-size: 0.98rem; line-height: 1.6; color: #cfd3da; margin: 0 0 24px; }

    /* revelação escalonada */
    .snr-stagger { opacity: 0; }
    #snrCelebOverlay.show .snr-stagger { animation: snrUp .55s ease both; }
    #snrCelebOverlay.show .snr-d1 { animation-delay: .30s; }
    #snrCelebOverlay.show .snr-d2 { animation-delay: .40s; }
    #snrCelebOverlay.show .snr-d3 { animation-delay: .50s; }
    #snrCelebOverlay.show .snr-d4 { animation-delay: .62s; }
    @keyframes snrUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

    .snr-celeb-btn {
        width: 100%; padding: 15px; border: none; border-radius: 14px; cursor: pointer;
        background: var(--cor); color: #0a0b0d; font-family: inherit; font-weight: 800; font-size: 0.95rem;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .snr-celeb-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 28px color-mix(in srgb, var(--cor) 35%, transparent); }

    .snr-celeb-sign {
        margin-top: 18px; display: flex; align-items: center; justify-content: center; gap: 8px;
        font-size: 0.62rem; color: rgba(255,255,255,0.4); letter-spacing: 0.3px;
    }
    .snr-celeb-sign .mark { font-family: 'Syncopate', sans-serif; font-weight: 700; letter-spacing: 2px; color: rgba(255,255,255,0.65); }
    .snr-celeb-sign .mark b { color: var(--cor); }
    .snr-celeb-count { margin-top: 12px; font-size: 0.7rem; color: rgba(255,255,255,0.35); }

    /* ---- Confete ---- */
    .snr-confetti {
        position: absolute; top: 46%; left: 50%; width: 9px; height: 14px; border-radius: 2px;
        pointer-events: none; will-change: transform, opacity;
        animation: snrConfetti 1.25s cubic-bezier(.15,.6,.3,1) forwards;
    }
    @keyframes snrConfetti {
        0%   { transform: translate(0,0) rotate(0); opacity: 1; }
        100% { transform: translate(var(--dx), var(--dy)) rotate(var(--rot)); opacity: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        #snrCelebOverlay.show .snr-celeb-card,
        #snrCelebOverlay.show .snr-celeb-disc,
        #snrCelebOverlay.show .snr-stagger { animation: none; opacity: 1; transform: none; }
        .snr-celeb-medal::before, .snr-celeb-disc::after, .snr-confetti { display: none; }
    }
</style>

<div id="snrCelebOverlay" role="dialog" aria-modal="true" style="--cor:#d4ff00;">
    <div class="snr-celeb-card">
        <button type="button" class="snr-celeb-close" aria-label="Fechar" onclick="snrCelebFechar()">
            <i class="ph ph-x"></i>
        </button>
        <div class="snr-celeb-medal">
            <div class="snr-celeb-disc">
                <i class="ph-fill ph-trophy" id="snrCelebIcon"></i>
                <span class="snr-celeb-discnum" id="snrCelebNum" style="display:none;">0</span>
            </div>
        </div>
        <div class="snr-celeb-kicker snr-stagger snr-d1" id="snrCelebKicker"><span>Conquista</span></div>
        <h2 class="snr-celeb-title snr-stagger snr-d2" id="snrCelebTitle"></h2>
        <p class="snr-celeb-msg snr-stagger snr-d3" id="snrCelebMsg"></p>
        <button type="button" class="snr-celeb-btn snr-stagger snr-d4" id="snrCelebBtn" onclick="snrCelebProximo()">Continuar</button>
        <div class="snr-celeb-sign snr-stagger snr-d4">
            <span class="mark">SNR<b>·</b>FIT</span>
            <span>·</span>
            <span id="snrCelebVoice">Bora pra cima.</span>
        </div>
        <div class="snr-celeb-count" id="snrCelebCount"></div>
    </div>
</div>

<script>
(function () {
    const CELEBS = @json($__payload);

    const KICKERS = {
        primeiro_login:   'Bem-vindo',
        sequencia_dias:   'Sequência',
        perda_peso:       'Evolução',
        progressao_carga: 'Progressão',
    };
    const VOICES = {
        primeiro_login:   'Bem-vindo à família.',
        sequencia_dias:   'Bora pra cima.',
        perda_peso:       'Treino que vira história.',
        progressao_carga: 'Cada vez mais forte.',
    };

    const overlay = document.getElementById('snrCelebOverlay');
    const card    = overlay.querySelector('.snr-celeb-card');
    const icon    = document.getElementById('snrCelebIcon');
    const numEl   = document.getElementById('snrCelebNum');
    const kicker  = document.querySelector('#snrCelebKicker span');
    const title   = document.getElementById('snrCelebTitle');
    const msg     = document.getElementById('snrCelebMsg');
    const btn     = document.getElementById('snrCelebBtn');
    const voice   = document.getElementById('snrCelebVoice');
    const count   = document.getElementById('snrCelebCount');
    const reduz   = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let i = 0;

    function countUp(el, to) {
        if (reduz || !to) { el.textContent = to || 0; return; }
        const dur = 750, ini = performance.now();
        (function step(t) {
            const p = Math.min(1, (t - ini) / dur);
            el.textContent = Math.round((1 - Math.pow(1 - p, 3)) * to);
            if (p < 1) requestAnimationFrame(step);
        })(ini);
    }

    function confete(cor) {
        if (reduz) return;
        const cores = ['#d4ff00', cor, '#ffffff', '#FFD700'];
        for (let k = 0; k < 48; k++) {
            const p = document.createElement('span');
            p.className = 'snr-confetti';
            p.style.background = cores[k % cores.length];
            const ang = Math.random() * Math.PI * 2, dist = 120 + Math.random() * 230;
            p.style.setProperty('--dx', (Math.cos(ang) * dist) + 'px');
            p.style.setProperty('--dy', (Math.sin(ang) * dist - 40) + 'px');
            p.style.setProperty('--rot', (Math.random() * 720 - 360) + 'deg');
            p.style.animationDelay = (Math.random() * 0.1) + 's';
            p.style.height = (10 + Math.random() * 8) + 'px';
            overlay.appendChild(p);
            setTimeout(() => p.remove(), 1500);
        }
    }

    function pintar(n) {
        const c = CELEBS[n];
        overlay.style.setProperty('--cor', c.cor);
        // medalha: número (sequência) ou ícone
        if (c.num) {
            icon.style.display = 'none'; numEl.style.display = '';
            countUp(numEl, c.num);
        } else {
            numEl.style.display = 'none'; icon.style.display = '';
            icon.className = 'ph-fill ' + c.icone;
        }
        kicker.textContent = KICKERS[c.tipo] || 'Conquista';
        title.textContent = c.titulo;
        msg.textContent = c.mensagem;
        voice.textContent = VOICES[c.tipo] || 'Bora pra cima.';
        btn.textContent = (n >= CELEBS.length - 1) ? 'Fechar' : 'Continuar';
        count.textContent = CELEBS.length > 1 ? (n + 1) + ' de ' + CELEBS.length : '';
        confete(c.cor);
        if (navigator.vibrate) { try { navigator.vibrate([0, 35, 25, 45]); } catch (e) {} }
    }

    function reanimar() {
        // reinicia as animações de entrada (card + medalha + stagger)
        ['snr-celeb-card', 'snr-celeb-disc'].forEach(cls => {
            const el = overlay.querySelector('.' + cls);
            el.style.animation = 'none'; void el.offsetWidth; el.style.animation = '';
        });
        overlay.querySelectorAll('.snr-stagger').forEach(el => {
            el.style.animation = 'none'; void el.offsetWidth; el.style.animation = '';
        });
    }

    window.snrCelebProximo = function () {
        if (i >= CELEBS.length - 1) { snrCelebFechar(); return; }
        i++;
        reanimar();
        pintar(i);
    };

    window.snrCelebFechar = function () {
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 360);
    };

    overlay.addEventListener('click', (e) => { if (e.target === overlay) snrCelebFechar(); });
    document.addEventListener('keydown', function esc(e) {
        if (e.key === 'Escape') { snrCelebFechar(); document.removeEventListener('keydown', esc); }
    });

    pintar(0);
    requestAnimationFrame(() => overlay.classList.add('show'));
})();
</script>
@endif
