<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Avaliação Física — SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --success: #00ff88;
            --error: #ff4444;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; padding: 0; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .container { max-width: 950px; margin: 40px auto; padding: 0 20px; }
        .page-title { color: var(--primary); font-size: 1.4rem; font-weight: 900; margin: 0 0 6px; }
        .page-sub { color: var(--text-muted); font-size: 0.85rem; margin: 0 0 30px; }
        .card { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border); padding: 24px; margin-bottom: 16px; transition: 0.3s; }
        .card:hover { border-color: rgba(212,255,0,0.2); }
        .btn-back { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 3rem; color: var(--text-muted); margin-bottom: 16px; display: block; }
        .empty-state p { color: var(--text-muted); font-size: 0.9rem; }

        .tipo-tabs { display: grid; grid-template-columns: repeat(auto-fit, minmax(118px, 1fr)); gap: 10px; margin-bottom: 20px; }
        .tipo-tab { background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 14px 10px; text-align: center; text-decoration: none; color: var(--text-muted); font-size: 0.7rem; font-weight: 800; text-transform: uppercase; transition: 0.2s; }
        .tipo-tab i { display: block; font-size: 1.2rem; margin-bottom: 6px; }
        .tipo-tab:hover { border-color: var(--primary); color: var(--primary); }
        .tipo-tab.active { background: rgba(212,255,0,0.08); border-color: var(--primary); color: var(--primary); }

        .filtro-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 24px; }
        .filtro-bar select { background: var(--card-bg); border: 1px solid var(--border); color: #fff; padding: 11px 14px; border-radius: 10px; font-size: 0.85rem; outline: none; cursor: pointer; }
        .filtro-bar select:focus { border-color: var(--primary); }

        .registro-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
        .tipo-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; background: rgba(212,255,0,0.1); color: var(--primary); border: 1px solid rgba(212,255,0,0.3); }
        .data-registro { color: var(--text-muted); font-size: 0.75rem; font-weight: 700; }
        .personal-tag { color: var(--text-muted); font-size: 0.72rem; font-weight: 700; }
        .personal-tag i { color: var(--primary); }
        .dados-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
        .dado-item { background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
        .dado-item label { display: block; color: var(--text-muted); font-size: 0.6rem; text-transform: uppercase; font-weight: 800; margin-bottom: 4px; }
        .dado-item span { font-size: 1rem; font-weight: 900; color: var(--primary); }
        .dado-item.texto span { font-size: 0.82rem; font-weight: 500; color: var(--text-main); line-height: 1.5; white-space: pre-line; }
        .foto-thumb { width: 110px; height: 110px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border); cursor: zoom-in; transition: 0.2s; }
        .foto-thumb:hover { border-color: var(--primary); }
        .btn-pdf { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.35); color: #ff8888; padding: 10px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 800; text-decoration: none; transition: 0.2s; }
        .btn-pdf:hover { background: rgba(255,68,68,0.18); }

        .resumo-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
        .resumo-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 22px; transition: 0.3s; }
        .resumo-card:hover { border-color: rgba(212,255,0,0.25); }
        .resumo-card .resumo-icon { font-size: 1.3rem; color: var(--primary); margin-bottom: 10px; }
        .resumo-card h3 { margin: 0 0 4px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 800; }
        .resumo-card .resumo-valor { font-size: 1.3rem; font-weight: 900; color: #fff; margin-bottom: 6px; }
        .resumo-card .resumo-data { font-size: 0.7rem; color: var(--text-muted); }
        .resumo-card .resumo-detalhe { font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; }
        .class-badge { display: inline-block; margin-top: 10px; padding: 5px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }

        .snrfit-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-top: 4px; }

        /* Dor colors */
        .dor-0, .dor-1, .dor-2 { color: #00ff88; }
        .dor-3, .dor-4, .dor-5 { color: #ffdd00; }
        .dor-6, .dor-7, .dor-8 { color: #ff8800; }
        .dor-9, .dor-10 { color: #ff4444; }

        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.98); z-index: 99999; justify-content: center; align-items: center; cursor: zoom-out; }
        #lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 12px; object-fit: contain; }

        @media (max-width: 700px) {
            .tipo-tabs { grid-template-columns: repeat(2, 1fr); }
            .top-bar { padding: 15px 20px; }
        }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('cliente.index') }}" class="btn-back"><i class="ph ph-arrow-left"></i> Voltar</a>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        @if($cliente->foto)
            <img src="{{ asset('storage/'.$cliente->foto) }}" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
        @else
            <img src="https://ui-avatars.com/api/?name={{ urlencode($cliente->nome) }}&background=d4ff00&color=000" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary);">
        @endif
        <span style="font-weight:700; font-size:0.9rem;">{{ $cliente->nome }}</span>
    </div>
</div>

<div class="container">
    <div class="ed-eyebrow"><i class="ph ph-heartbeat"></i> Saúde</div><h1 class="ed-h">Minha Avaliação <span class="ed-mark">Física</span></h1>
    <p class="page-sub">Acompanhe os registros que seu personal fez sobre sua evolução.</p>

    @php
        $tipos = \App\Models\AvaliacaoFisica::META;
        $tiposCriaveis = \App\Models\AvaliacaoFisica::TIPOS;
        $classificacoes = [
            'otimo'   => ['label' => 'Ótimo',   'cor' => '#d4ff00'],
            'bom'     => ['label' => 'Bom',     'cor' => '#00ff88'],
            'normal'  => ['label' => 'Normal',  'cor' => '#4da6ff'],
            'ruim'    => ['label' => 'Ruim',    'cor' => '#ffaa00'],
            'pessimo' => ['label' => 'Péssimo', 'cor' => '#ff4444'],
        ];
    @endphp

    <div class="tipo-tabs">
        <a href="{{ route('cliente.avaliacao-fisica') }}{{ $mesFiltro ? '?mes='.$mesFiltro : '' }}" class="tipo-tab {{ !$tipoFiltro ? 'active' : '' }}">
            <i class="ph ph-list"></i> Todos
        </a>
        @foreach($tiposCriaveis as $key)
        <a href="{{ route('cliente.avaliacao-fisica') }}?tipo={{ $key }}{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $tipoFiltro === $key ? 'active' : '' }}">
            <i class="ph {{ $tipos[$key]['icon'] }}"></i> {{ $tipos[$key]['label'] }}
        </a>
        @endforeach
        <a href="{{ route('cliente.avaliacao-fisica') }}?tipo=resumo{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $tipoFiltro === 'resumo' ? 'active' : '' }}">
            <i class="ph ph-chart-bar"></i> Resumo
        </a>
    </div>

    <div class="filtro-bar">
        <label style="color:var(--text-muted); font-size:0.7rem; font-weight:800; text-transform:uppercase;"><i class="ph ph-funnel"></i> Filtrar por mês:</label>
        <select onchange="filtrarMes(this.value)">
            <option value="">Todos os meses</option>
            @foreach($mesesDisponiveis as $m)
                <option value="{{ $m }}" {{ $mesFiltro === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->translatedFormat('F/Y') }}</option>
            @endforeach
        </select>
    </div>

    @if($tipoFiltro === 'resumo')
        @if(empty($resumo))
            <div class="empty-state">
                <i class="ph ph-chart-bar"></i>
                <p>Nenhum dado para montar o resumo{{ $mesFiltro ? ' nesse mês' : '' }}.<br>Seu personal ainda não registrou avaliações nessas categorias.</p>
            </div>
        @else
            <p style="color:var(--text-muted); font-size:0.8rem; margin:0 0 16px;"><i class="ph ph-info"></i> Resumo com base no registro mais recente de cada categoria{{ $mesFiltro ? ' no mês selecionado' : '' }}.</p>
            <div class="resumo-grid">
                @foreach($resumo as $key => $item)
                <div class="resumo-card">
                    <div class="resumo-icon"><i class="ph {{ $tipos[$key]['icon'] ?? 'ph-clipboard' }}"></i></div>
                    <h3>{{ $tipos[$key]['label'] ?? $key }}</h3>
                    <div class="resumo-valor">{{ $item['valor'] }}</div>
                    <div class="resumo-data"><i class="ph ph-calendar"></i> {{ $item['registro']->data_avaliacao->format('d/m/Y') }}</div>

                    @if($item['detalhe'])
                        <div class="resumo-detalhe">{{ $item['detalhe'] }}</div>
                    @endif

                    @if($key === 'antes_depois' && $item['registro']->foto)
                        <div style="margin-top:12px;">
                            <img src="{{ asset('storage/'.$item['registro']->foto) }}" class="foto-thumb" style="width:80px; height:80px;" onclick="abrirLightbox('{{ asset('storage/'.$item['registro']->foto) }}')">
                        </div>
                    @endif

                    @if($key === 'bioimpedancia' && $item['registro']->arquivo)
                        <div style="margin-top:12px;">
                            <a href="{{ asset('storage/'.$item['registro']->arquivo) }}" target="_blank" class="btn-pdf"><i class="ph ph-file-pdf"></i> Abrir PDF</a>
                        </div>
                    @endif

                    @if($item['classificacao'])
                        @php $cl = $classificacoes[$item['classificacao']]; @endphp
                        <span class="class-badge" style="background: {{ $cl['cor'] }}1a; color: {{ $cl['cor'] }}; border: 1px solid {{ $cl['cor'] }}55;">{{ $cl['label'] }}</span>
                    @else
                        <span class="class-badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border);">Acompanhamento</span>
                    @endif
                </div>
                @endforeach
            </div>
        @endif

    @elseif($registros->isEmpty())
        <div class="empty-state">
            <i class="ph ph-clipboard"></i>
            <p>Nenhum registro encontrado{{ $tipoFiltro || $mesFiltro ? ' para esse filtro' : '' }}.<br>Seu personal ainda não adicionou avaliações aqui.</p>
        </div>
    @else
        @foreach($registros as $r)
        <div class="card">
            <div class="registro-head">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span class="tipo-badge"><i class="ph {{ $tipos[$r->tipo]['icon'] ?? 'ph-clipboard' }}"></i> {{ $tipos[$r->tipo]['label'] ?? $r->tipo }}</span>
                    <span class="data-registro"><i class="ph ph-calendar"></i> {{ $r->data_avaliacao->format('d/m/Y') }}</span>
                </div>
                @if($r->personal)
                    <span class="personal-tag"><i class="ph ph-user-list"></i> {{ $r->personal?->nome }}</span>
                @elseif($r->academia)
                    <span class="personal-tag"><i class="ph ph-barbell"></i> {{ $r->academia?->nome }}</span>
                @endif
            </div>

            @include('avaliacoes._dados', ['r' => $r])
        </div>
        @endforeach
    @endif
</div>

{{-- LIGHTBOX --}}
<div id="lightbox" onclick="fecharLightbox()">
    <img id="lightboxImg">
</div>

<script>
    function filtrarMes(mes) {
        const params = new URLSearchParams();
        @if($tipoFiltro)
        params.set('tipo', '{{ $tipoFiltro }}');
        @endif
        if (mes) params.set('mes', mes);
        const qs = params.toString();
        window.location.href = '{{ route('cliente.avaliacao-fisica') }}' + (qs ? '?' + qs : '');
    }

    function abrirLightbox(url) {
        document.getElementById('lightboxImg').src = url;
        document.getElementById('lightbox').style.display = 'flex';
    }
    function fecharLightbox() {
        document.getElementById('lightbox').style.display = 'none';
    }
</script>

</body>
</html>
