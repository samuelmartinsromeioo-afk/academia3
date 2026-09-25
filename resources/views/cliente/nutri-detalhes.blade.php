<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $nutri->nome }} — Nutricionista | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.meta-pixel', ['fbEvents' => isset($fbEvent) ? [$fbEvent] : []])
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root { --primary:#7cff00; --bg-dark:#0a0b0d; --card-bg:#16181d; --text-main:#fff; --text-muted:#a0a0a0; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:linear-gradient(135deg,var(--bg-dark) 0%,#0f1217 100%); font-family:'Inter',sans-serif; color:var(--text-main); min-height:100vh; }
        a { text-decoration:none; color:inherit; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; padding:18px 40px; background:rgba(0,0,0,0.3); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .logo { font-family:'Syncopate',sans-serif; font-size:1.1rem; letter-spacing:3px; }
        .logo, .logo span { color:var(--primary); }
        .btn-top { background:transparent; border:1px solid var(--border); color:var(--text-main); padding:9px 16px; border-radius:8px; font-weight:700; font-size:0.78rem; display:inline-flex; align-items:center; gap:6px; transition:.2s; }
        .btn-top:hover { border-color:var(--primary); color:var(--primary); }
        .container { max-width:820px; margin:0 auto; padding:32px 20px; }
        .hero { display:flex; gap:22px; align-items:center; background:var(--card-bg); border:1px solid var(--border); border-radius:20px; padding:24px; margin-bottom:20px; }
        .avatar { width:120px; height:120px; border-radius:18px; object-fit:cover; flex:0 0 auto; background:linear-gradient(135deg,rgba(124,255,0,0.18),rgba(124,255,0,0.03)); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:3rem; }
        .hero h1 { font-size:1.5rem; font-weight:900; }
        .badge-tipo { display:inline-flex; align-items:center; gap:6px; background:rgba(124,255,0,0.1); color:var(--primary); border:1px solid rgba(124,255,0,0.3); font-size:0.72rem; font-weight:800; text-transform:uppercase; padding:4px 12px; border-radius:20px; margin-bottom:8px; }
        .meta { color:var(--text-muted); font-size:0.85rem; margin-top:6px; display:flex; flex-wrap:wrap; gap:14px; }
        .meta i { color:var(--primary); }
        .rating { color:#ffc107; font-size:0.9rem; margin-top:8px; }
        .rating .num { color:var(--text-muted); }
        .card { background:var(--card-bg); border:1px solid var(--border); border-radius:18px; padding:22px; margin-bottom:18px; }
        .card h3 { font-size:0.95rem; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .card h3 i { color:var(--primary); }
        .chips { display:flex; flex-wrap:wrap; gap:8px; }
        .chip { font-size:0.78rem; background:rgba(124,255,0,0.08); color:var(--primary); border:1px solid rgba(124,255,0,0.2); padding:5px 12px; border-radius:20px; }
        .bio { color:var(--text-muted); font-size:0.92rem; line-height:1.6; white-space:pre-line; }
        .cta { position:sticky; bottom:0; padding:16px 0; }
        .btn-wpp { display:flex; align-items:center; justify-content:center; gap:10px; width:100%; background:#25D366; color:#000; font-weight:800; font-size:0.95rem; padding:16px; border-radius:14px; transition:.2s; }
        .btn-wpp:hover { filter:brightness(1.05); transform:translateY(-1px); }
        .btn-pagar { display:flex; align-items:center; justify-content:center; gap:10px; width:100%; background:var(--primary); color:#000; font-weight:800; font-size:0.95rem; padding:16px; border-radius:14px; border:none; cursor:pointer; font-family:inherit; transition:.2s; }
        .btn-pagar:hover { filter:brightness(1.05); transform:translateY(-1px); box-shadow:0 8px 22px rgba(124,255,0,0.25); }
        .aval { padding:12px 0; border-bottom:1px solid var(--border); }
        .aval:last-child { border-bottom:none; }
        .aval .estrelas { color:#ffc107; font-size:0.8rem; }
        .aval .txt { color:var(--text-muted); font-size:0.88rem; margin-top:4px; }
        .empty { color:var(--text-muted); font-size:0.88rem; }
        @media (max-width:600px) { .top-bar { padding:14px 20px; } .hero { flex-direction:column; text-align:center; } .meta { justify-content:center; } }
    </style>
    @include('partials.brand-head')
</head>
<body class="ed-page">

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span></div>
    <a href="{{ route('personais.explorar') }}?tipo=nutricionistas" class="btn-top"><i class="ph ph-arrow-left"></i> Voltar</a>
</div>

<div class="container">
    <div class="hero">
        @if ($nutri->foto)
            <img class="avatar" src="{{ asset('storage/' . $nutri->foto) }}" alt="{{ $nutri->nome }}">
        @elseif ($nutri->fotos->isNotEmpty())
            <img class="avatar" src="{{ asset('storage/' . $nutri->fotos->first()->path) }}" alt="{{ $nutri->nome }}">
        @else
            <div class="avatar"><i class="ph ph-carrot"></i></div>
        @endif
        <div>
            <span class="badge-tipo"><i class="ph ph-carrot"></i> Nutricionista</span>
            <h1 style="display:flex; align-items:center; gap:7px;">
                {{ $nutri->nome }}
                @if ($nutri->eh_pioneiro)
                    @include('partials.badge-pioneiro', ['posicao' => $nutri->pioneiro_posicao, 'estado' => $nutri->estado, 'tipo' => 'nutricionista', 'tamanho' => 20])
                @endif
            </h1>
            <div class="meta">
                @if ($nutri->cidade)<span><i class="ph ph-map-pin"></i> {{ $nutri->cidade }}{{ $nutri->estado ? ' - ' . $nutri->estado : '' }}</span>@endif
                @if ($nutri->crn)<span><i class="ph ph-identification-badge"></i> CRN {{ $nutri->crn }}</span>@endif
                @if ($nutri->modalidade)<span><i class="ph ph-monitor"></i> {{ $nutri->modalidade }}</span>@endif
            </div>
            @if ($nutri->avaliacoes->count())
                <div class="rating">
                    @php $media = (float) $nutri->media_avaliacao; @endphp
                    @for ($i = 1; $i <= 5; $i++)<i class="ph-star {{ $i <= round($media) ? 'ph-fill' : 'ph' }}"></i>@endfor
                    <span class="num">{{ $nutri->media_avaliacao }} ({{ $nutri->avaliacoes->count() }} avaliações)</span>
                </div>
            @else
                <div class="rating"><span class="num" style="color:var(--primary);"><i class="ph ph-plant"></i> Novo profissional</span></div>
            @endif
        </div>
    </div>

    @if (!empty($nutri->especialidades))
        <div class="card">
            <h3><i class="ph ph-target"></i> Especialidades</h3>
            <div class="chips">
                @foreach ((array) $nutri->especialidades as $esp)
                    <span class="chip">{{ $esp }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($nutri->bio)
        <div class="card">
            <h3><i class="ph ph-user"></i> Sobre</h3>
            <div class="bio">{{ $nutri->bio }}</div>
        </div>
    @endif

    @if ($nutri->avaliacoes->count())
        <div class="card">
            <h3><i class="ph ph-star"></i> Avaliações</h3>
            @foreach ($nutri->avaliacoes->take(10) as $av)
                <div class="aval">
                    <div class="estrelas">
                        @for ($i = 1; $i <= 5; $i++)<i class="ph-star {{ $i <= (int) $av->nota ? 'ph-fill' : 'ph' }}"></i>@endfor
                    </div>
                    @if ($av->comentario)<div class="txt">{{ $av->comentario }}</div>@endif
                </div>
            @endforeach
        </div>
    @endif

    @php
        $tel = preg_replace('/\D/', '', (string) $nutri->whatsapp);
        if ($tel && ! str_starts_with($tel, '55')) { $tel = '55' . $tel; }
        $msg = rawurlencode('Olá, ' . $nutri->nome . '! Vi seu perfil no SnrFit e gostaria de saber sobre acompanhamento nutricional.');
    @endphp
    @if (session('error'))
        <div class="card" style="border-color:rgba(255,68,68,0.35); background:rgba(255,68,68,0.06); color:#ff6b6b;">
            <i class="ph ph-warning-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="cta" style="display:flex; flex-direction:column; gap:10px;">
        @if ($nutri->valor_consulta > 0)
            <form method="POST" action="{{ route('nutricionistas.pagar', $nutri->id) }}">
                @csrf
                <button type="submit" class="btn-pagar">
                    <i class="ph-fill ph-credit-card" style="font-size:1.3rem;"></i>
                    Pagar consulta — R$ {{ number_format($nutri->valor_consulta, 2, ',', '.') }}
                </button>
            </form>
            <div style="text-align:center; color:var(--text-muted); font-size:0.72rem;">Pix, cartão ou boleto · pagamento seguro via Asaas</div>
        @endif

        @if ($tel)
            <a class="btn-wpp" href="https://wa.me/{{ $tel }}?text={{ $msg }}" target="_blank" rel="noopener">
                <i class="ph-fill ph-whatsapp-logo" style="font-size:1.3rem;"></i> Falar no WhatsApp
            </a>
        @elseif (!($nutri->valor_consulta > 0))
            <div class="card" style="text-align:center; margin:0;"><span class="empty">Este nutricionista ainda não cadastrou uma forma de contato.</span></div>
        @endif
    </div>
</div>
</body>
</html>
