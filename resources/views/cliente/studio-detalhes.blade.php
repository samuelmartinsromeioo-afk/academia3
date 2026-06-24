<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $studio->nome }} | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
            --studio-color: #b14cff;
            --success: #28a745;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 40px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .logo { font-family: 'Syncopate', sans-serif; font-size: 1.1rem; letter-spacing: 3px; }
        .logo span { color: var(--primary); }

        .btn-top {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 9px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.78rem;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 1100px; margin: 0 auto; padding: 36px 20px; }

        /* HEADER */
        .header-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 28px;
            display: flex;
            gap: 24px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .header-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: rgba(177,76,255,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--studio-color);
            font-size: 2rem;
            flex-shrink: 0;
        }

        .header-info { flex: 1; min-width: 240px; }
        .header-info h1 { font-size: 1.5rem; font-weight: 900; margin-bottom: 4px; }
        .header-info .modalidades { color: var(--studio-color); font-weight: 700; font-size: 0.85rem; margin-bottom: 10px; }
        .header-info .meta { color: var(--text-muted); font-size: 0.83rem; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
        .header-info .meta i { color: var(--primary); width: 15px; text-align: center; }

        .rating-box { text-align: center; }
        .rating-box .nota { font-size: 2rem; font-weight: 900; color: var(--primary); }
        .rating-box .stars { color: #ffc107; font-size: 0.85rem; margin: 4px 0; }
        .rating-box .count { color: var(--text-muted); font-size: 0.72rem; }

        .descricao { color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-top: 10px; }

        /* SECTIONS */
        .section {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: var(--primary); }

        .empty { color: var(--text-muted); font-size: 0.88rem; }

        /* GALERIA */
        .galeria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 12px;
        }
        .galeria-grid img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: 0.2s;
        }
        .galeria-grid img:hover { transform: scale(1.03); border-color: var(--studio-color); }

        /* FUNCIONAMENTO */
        .horarios-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .horario-chip {
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.83rem;
        }
        .horario-chip strong { display: block; margin-bottom: 3px; }
        .horario-chip span { color: var(--text-muted); }

        /* PLANOS */
        .planos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
        .plano-card {
            background: var(--input-bg);
            border: 1px solid rgba(212,255,0,0.2);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        .plano-card h4 { color: var(--primary); font-size: 1rem; font-weight: 800; margin-bottom: 6px; }
        .plano-card .valor { font-size: 1.3rem; font-weight: 900; margin-bottom: 4px; }
        .plano-card .valor small { color: var(--text-muted); font-weight: 400; font-size: 0.7rem; }
        .plano-card .desc { color: var(--text-muted); font-size: 0.78rem; margin-bottom: 14px; line-height: 1.5; flex: 1; }

        .botoes-pagamento { display: flex; gap: 8px; }
        .btn-pix, .btn-cartao {
            flex: 1;
            border: none;
            border-radius: 9px;
            padding: 10px;
            font-weight: 900;
            font-size: 0.78rem;
            cursor: pointer;
            transition: 0.2s;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-pix { background: var(--primary); color: #000; }
        .btn-pix:hover { background: #e8ff40; }
        .btn-cartao { background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .btn-cartao:hover { background: rgba(255,255,255,0.15); }

        /* AULA AVULSA */
        .aula-header { display: flex; gap: 14px; align-items: end; flex-wrap: wrap; margin-bottom: 20px; }
        label { display: block; font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; }
        input[type="date"] {
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 13px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.88rem;
            outline: none;
            color-scheme: dark;
        }
        input[type="date"]:focus { border-color: var(--primary); }

        .valor-aula-tag {
            background: rgba(212,255,0,0.08);
            border: 1px solid rgba(212,255,0,0.25);
            color: var(--primary);
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 12px;
        }

        .slot-card {
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: 0.2s;
            text-align: center;
        }
        .slot-card:hover { border-color: var(--studio-color); }
        .slot-card.selected { border-color: var(--primary); background: rgba(212,255,0,0.06); }
        .slot-card.lotado { opacity: 0.45; cursor: not-allowed; }

        .slot-card .hora { font-weight: 800; font-size: 0.92rem; margin-bottom: 4px; }
        .slot-card .vagas { font-size: 0.74rem; color: var(--text-muted); }
        .slot-card .vagas strong { color: var(--primary); }
        .slot-card.lotado .vagas strong { color: #ff6b6b; }

        .slot-acao {
            margin-top: 20px;
            background: rgba(212,255,0,0.05);
            border: 1px solid rgba(212,255,0,0.2);
            border-radius: 14px;
            padding: 18px;
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .slot-acao.visivel { display: flex; }
        .slot-acao .info { font-size: 0.9rem; font-weight: 700; }
        .slot-acao .info span { color: var(--primary); }
        .slot-acao .botoes-pagamento { min-width: 240px; }

        /* AVALIAÇÕES */
        .avaliacao-item {
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
        }
        .avaliacao-item:last-child { border-bottom: none; }
        .avaliacao-item .topo { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .avaliacao-item .nome { font-weight: 700; font-size: 0.88rem; }
        .avaliacao-item .stars { color: #ffc107; font-size: 0.75rem; }
        .avaliacao-item .comentario { color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; }
        .avaliacao-item .data { color: var(--text-muted); font-size: 0.7rem; margin-top: 4px; }

        /* MODAIS */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.88);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(6px);
            padding: 20px;
            overflow-y: auto;
        }
        .modal-overlay.aberto { display: flex; }

        .modal-box {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            max-width: 460px;
            width: 100%;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-fechar {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
        }

        .modal-box h3 { font-size: 1.1rem; font-weight: 900; margin-bottom: 4px; }
        .modal-box h3 i { color: var(--primary); }
        .modal-box .modal-sub { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 4px; }
        .modal-box .modal-valor { font-size: 1.4rem; font-weight: 700; margin-bottom: 20px; }

        .pix-qr { display: block; margin: 0 auto 16px; width: 220px; height: 220px; border-radius: 12px; background: #fff; }
        .pix-copia-wrap { display: flex; gap: 8px; margin-bottom: 14px; }
        .pix-copia-wrap input {
            flex: 1;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            color: var(--text-muted);
            font-size: 0.72rem;
            outline: none;
        }
        .pix-copia-wrap button {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            border-radius: 10px;
            padding: 0 16px;
            cursor: pointer;
            font-family: inherit;
        }

        .pix-status { display: none; text-align: center; font-weight: 800; font-size: 0.9rem; padding: 10px 0; }

        .form-cartao input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            font-family: inherit;
        }
        .form-cartao .campo { margin-bottom: 14px; }
        .form-cartao .linha { display: flex; gap: 12px; }
        .form-cartao .linha > div { flex: 1; }

        .erro-msg {
            display: none;
            color: #ff6b6b;
            font-size: 0.82rem;
            background: rgba(255,107,107,0.1);
            border: 1px solid rgba(255,107,107,0.3);
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .ok-msg { display: none; color: #4caf50; font-size: 0.9rem; font-weight: 700; text-align: center; padding: 10px 0; }

        .btn-confirmar {
            width: 100%;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 900;
            font-size: 0.95rem;
            cursor: pointer;
            font-family: inherit;
        }

        @media (max-width: 600px) {
            .top-bar { padding: 14px 20px; }
            .section, .header-card { padding: 20px; }
        }
    </style>
</head>
<body>

@php
    $diasSemana = [0 => 'Domingo', 1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];
    $mediaNota = (float) $studio->media_avaliacao;
@endphp

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span></div>
    <a href="{{ route('studios.explorar') }}" class="btn-top"><i class="fas fa-arrow-left"></i> Voltar aos studios</a>
</div>

<div class="container">

    @if (session('sucesso'))
        <div style="background: rgba(212,255,0,0.1); border: 1px solid rgba(212,255,0,0.35); color: var(--primary); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.88rem;">
            <i class="fas fa-check-circle"></i> {{ session('sucesso') }}
        </div>
    @endif
    @if (session('error'))
        <div style="background: rgba(255,77,77,0.1); border: 1px solid rgba(255,77,77,0.35); color: #ff6b6b; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.88rem;">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <!-- HEADER -->
    <div class="header-card">
        <div class="header-icon"><i class="fas fa-spa"></i></div>
        <div class="header-info">
            <h1>{{ $studio->nome }}</h1>
            @if ($studio->modalidades)
                <div class="modalidades"><i class="fas fa-dumbbell"></i> {{ $studio->modalidades }}</div>
            @endif
            <div class="meta"><i class="fas fa-map-marker-alt"></i> {{ $studio->endereco ?? ($studio->cidade . ' - ' . $studio->estado) }}</div>
            @if ($studio->whatsapp)
                <div class="meta"><i class="fab fa-whatsapp"></i> {{ $studio->whatsapp }}</div>
            @endif
            <div class="meta"><i class="fas fa-tag"></i> Aula avulsa: <strong style="color:#fff;">R$ {{ number_format($studio->valor_aula ?? 0, 2, ',', '.') }}</strong></div>
            @if ($studio->descricao)
                <p class="descricao">{{ $studio->descricao }}</p>
            @endif
        </div>
        <div class="rating-box">
            <div class="nota">{{ $studio->media_avaliacao }}</div>
            <div class="stars">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="fa-star {{ $i <= round($mediaNota) ? 'fas' : 'far' }}"></i>
                @endfor
            </div>
            <div class="count">{{ $studio->avaliacoes->count() }} avaliações</div>
        </div>
    </div>

    <!-- GALERIA -->
    @if ($studio->fotos->isNotEmpty())
        <div class="section">
            <div class="section-title"><i class="fas fa-images"></i> Galeria</div>
            <div class="galeria-grid">
                @foreach ($studio->fotos as $foto)
                    <img src="{{ asset('storage/' . $foto->path) }}" alt="{{ $foto->legenda ?? $studio->nome }}" title="{{ $foto->legenda }}" onclick="window.open(this.src, '_blank')">
                @endforeach
            </div>
        </div>
    @endif

    <!-- FUNCIONAMENTO -->
    <div class="section">
        <div class="section-title"><i class="fas fa-clock"></i> Horário de funcionamento</div>
        @if ($studio->horarios->isEmpty())
            <p class="empty">O studio ainda não divulgou seus horários.</p>
        @else
            <div class="horarios-grid">
                @foreach ($studio->horarios as $h)
                    <div class="horario-chip">
                        <strong>{{ $diasSemana[$h->dia_semana] ?? $h->dia_semana }}</strong>
                        <span>{{ substr($h->hora_abertura, 0, 5) }} às {{ substr($h->hora_fechamento, 0, 5) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- PLANOS -->
    <div class="section">
        <div class="section-title"><i class="fas fa-id-card"></i> Planos mensais</div>
        @if ($studio->planos->isEmpty())
            <p class="empty">Este studio ainda não oferece planos mensais. Você pode agendar aulas avulsas abaixo.</p>
        @else
            <div class="planos-grid">
                @foreach ($studio->planos as $plano)
                    <div class="plano-card">
                        <h4>{{ $plano->nome }}</h4>
                        <div class="valor">R$ {{ number_format($plano->valor, 2, ',', '.') }} <small>/mês · {{ $plano->duracao_meses }} {{ $plano->duracao_meses == 1 ? 'mês' : 'meses' }}</small></div>
                        @if ($plano->descricao)
                            <p class="desc">{{ $plano->descricao }}</p>
                        @else
                            <p class="desc"></p>
                        @endif
                        <div class="botoes-pagamento">
                            <button class="btn-pix" onclick="pagarPlanoPix({{ $plano->id }}, {!! json_encode($plano->nome) !!}, {{ $plano->valor }})"><i class="fas fa-qrcode"></i> PIX</button>
                            <button class="btn-cartao" onclick="pagarPlanoCartao({{ $plano->id }}, {!! json_encode($plano->nome) !!}, {{ $plano->valor }})"><i class="fas fa-credit-card"></i> Cartão</button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- AULA AVULSA -->
    <div class="section">
        <div class="section-title"><i class="fas fa-calendar-plus"></i> Agendar aula avulsa</div>
        <div class="aula-header">
            <div>
                <label for="dataAula">Escolha a data</label>
                <input type="date" id="dataAula" min="{{ today()->format('Y-m-d') }}" value="{{ today()->format('Y-m-d') }}">
            </div>
            <div class="valor-aula-tag"><i class="fas fa-tag"></i> R$ {{ number_format($studio->valor_aula ?? 0, 2, ',', '.') }} por aula</div>
        </div>

        <div id="slotsContainer">
            <p class="empty"><i class="fas fa-circle-notch fa-spin"></i> Carregando horários...</p>
        </div>

        <div class="slot-acao" id="slotAcao">
            <div class="info">Horário selecionado: <span id="slotSelecionadoLabel"></span></div>
            <div class="botoes-pagamento">
                <button class="btn-pix" onclick="pagarAulaPix()"><i class="fas fa-qrcode"></i> Pagar com PIX</button>
                <button class="btn-cartao" onclick="pagarAulaCartao()"><i class="fas fa-credit-card"></i> Cartão</button>
            </div>
        </div>
    </div>

    <!-- AVALIAÇÕES -->
    <div class="section">
        <div class="section-title"><i class="fas fa-star"></i> Avaliações</div>

        <form action="{{ route('avaliar.store') }}" method="POST" style="margin-bottom: 26px; padding-bottom: 22px; border-bottom: 1px solid var(--border);">
            @csrf
            <input type="hidden" name="studio_id" value="{{ $studio->id }}">
            <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 12px;">Já treinou aqui? Deixe a sua avaliação.</p>
            <div class="star-rating" style="display: inline-flex; gap: 8px; font-size: 1.8rem; color: #444; cursor: pointer; flex-direction: row-reverse; margin-bottom: 14px;">
                <input type="radio" name="nota" value="5" id="star5" style="display:none;" required>
                <label for="star5"><i class="fas fa-star"></i></label>
                <input type="radio" name="nota" value="4" id="star4" style="display:none;">
                <label for="star4"><i class="fas fa-star"></i></label>
                <input type="radio" name="nota" value="3" id="star3" style="display:none;">
                <label for="star3"><i class="fas fa-star"></i></label>
                <input type="radio" name="nota" value="2" id="star2" style="display:none;">
                <label for="star2"><i class="fas fa-star"></i></label>
                <input type="radio" name="nota" value="1" id="star1" style="display:none;">
                <label for="star1"><i class="fas fa-star"></i></label>
            </div>
            <textarea name="comentario" placeholder="Conte como foi a sua experiência (opcional)..." maxlength="500" style="width: 100%; background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 12px; color: #fff; padding: 13px 14px; min-height: 80px; resize: vertical; outline: none; font-family: inherit; font-size: 0.85rem; margin-bottom: 12px;"></textarea>
            <button type="submit" style="background: var(--studio-color); color: #fff; border: none; border-radius: 10px; padding: 11px 22px; font-weight: 800; font-size: 0.8rem; cursor: pointer; font-family: inherit;">
                <i class="fas fa-paper-plane"></i> Enviar avaliação
            </button>
        </form>
        <style>
            .star-rating label { transition: color 0.2s; }
            .star-rating label:hover,
            .star-rating label:hover ~ label,
            .star-rating input:checked ~ label { color: gold; }
        </style>

        @if ($studio->avaliacoes->isEmpty())
            <p class="empty">Ainda não há avaliações para este studio.</p>
        @else
            @foreach ($studio->avaliacoes as $av)
                <div class="avaliacao-item">
                    <div class="topo">
                        <span class="nome"><i class="fas fa-user-circle" style="color: var(--studio-color);"></i> {{ $av->cliente?->nome ?? 'Aluno' }}</span>
                        <span class="stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fa-star {{ $i <= $av->nota ? 'fas' : 'far' }}"></i>
                            @endfor
                        </span>
                    </div>
                    @if ($av->comentario)
                        <p class="comentario">{{ $av->comentario }}</p>
                    @endif
                    <div class="data">{{ $av->created_at->format('d/m/Y') }}</div>
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- MODAL PROFISSIONAL -->
<div class="modal-overlay" id="profModal">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharProfModal()">✕</button>
        <h3><i class="fas fa-user-tie"></i> <span id="profModalNome">Profissional</span></h3>
        <p id="profModalResumo" style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; white-space: pre-line; margin-top: 12px;"></p>
    </div>
</div>

<!-- MODAL PIX -->
<div class="modal-overlay" id="modalPix">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharModalPix()">✕</button>
        <h3><i class="fas fa-qrcode"></i> PAGAMENTO PIX</h3>
        <p class="modal-sub" id="pixDescricao"></p>
        <p class="modal-valor" id="pixValor">Gerando QR Code...</p>
        <p id="pixRecorrenteNota" style="display:none; color:#d4ff00; font-size:0.78rem; font-weight:700; margin:0 0 12px; background:rgba(212,255,0,0.08); border:1px solid rgba(212,255,0,0.25); border-radius:10px; padding:8px 12px;">
            🔁 Assinatura mensal — uma nova cobrança PIX é gerada todo mês.
        </p>
        <img class="pix-qr" id="pixQr" src="" alt="QR Code PIX">
        <div class="pix-copia-wrap">
            <input type="text" id="pixCopia" readonly>
            <button onclick="copiarPix()"><i class="fas fa-copy"></i></button>
        </div>
        <p style="color: var(--text-muted); font-size: 0.75rem; text-align: center;">Escaneie o QR Code ou use o copia e cola. A confirmação é automática.</p>
        <p class="pix-status" id="pixStatus"></p>
    </div>
</div>

<!-- MODAL CARTÃO -->
<div class="modal-overlay" id="modalCartao">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharModalCartao()">✕</button>
        <h3><i class="fas fa-credit-card"></i> PAGAMENTO COM CARTÃO</h3>
        <p class="modal-sub" id="cartaoDescricao"></p>
        <p class="modal-valor" id="cartaoValor"></p>

        <form class="form-cartao" id="formCartao" onsubmit="submeterCartao(event)" autocomplete="off">
            <div class="campo">
                <label>Número do cartão</label>
                <input id="cartaoNumero" type="text" inputmode="numeric" maxlength="19" placeholder="0000 0000 0000 0000" oninput="formatarNumeroCartao(this)" required>
            </div>
            <div class="campo">
                <label>Nome impresso no cartão</label>
                <input id="cartaoNomeTitular" type="text" placeholder="NOME SOBRENOME" maxlength="100" oninput="this.value=this.value.toUpperCase()" required>
            </div>
            <div class="linha campo">
                <div>
                    <label>Validade (MM/AA)</label>
                    <input id="cartaoValidade" type="text" inputmode="numeric" maxlength="5" placeholder="MM/AA" oninput="formatarValidade(this)" required>
                </div>
                <div>
                    <label>CVV</label>
                    <input id="cartaoCCV" type="text" inputmode="numeric" maxlength="4" placeholder="123" oninput="this.value=this.value.replace(/\D/g,'')" required>
                </div>
            </div>

            <hr style="border:none; border-top:1px solid var(--border); margin:4px 0 16px;">
            <p style="color: var(--text-muted); font-size: 0.72rem; margin-bottom: 12px;">Dados do titular (necessários para antifraude)</p>

            <div class="campo">
                <label>CPF do titular</label>
                <input id="cartaoCPF" type="text" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" oninput="formatarCPF(this)" required>
            </div>
            <div class="linha campo">
                <div style="flex:2;">
                    <label>CEP</label>
                    <input id="cartaoCEP" type="text" inputmode="numeric" maxlength="9" placeholder="00000-000" oninput="formatarCEP(this)" required>
                </div>
                <div>
                    <label>Número</label>
                    <input id="cartaoNumeroEnd" type="text" maxlength="20" placeholder="277" required>
                </div>
            </div>
            <div class="campo">
                <label>Telefone / WhatsApp</label>
                <input id="cartaoTelefone" type="text" inputmode="numeric" maxlength="15" placeholder="(11) 99999-9999" oninput="formatarTelefone(this)" required>
            </div>

            <p class="erro-msg" id="cartaoErro"></p>
            <p class="ok-msg" id="cartaoSucesso">✅ Pagamento aprovado! Atualizando...</p>

            <button type="submit" class="btn-confirmar" id="btnSubmeterCartao"><i class="fas fa-lock"></i> Pagar com Segurança</button>
        </form>
    </div>
</div>

<script>
    const STUDIO_ID    = {!! json_encode($studio->id) !!};
    const VALOR_AULA   = {!! json_encode((float) ($studio->valor_aula ?? 0)) !!};
    const STUDIO_NOME  = {!! json_encode($studio->nome) !!};
    const CSRF         = '{{ csrf_token() }}';
    const CLIENTE_TEL  = {!! json_encode($cliente->whatsapp ?? '') !!};
    const CLIENTE_CEP  = {!! json_encode($cliente->cep ?? '') !!};

    // ============ SLOTS DE AULA AVULSA ============
    let slotSelecionado = null;
    let slotsCarregados = [];

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    async function carregarSlots() {
        const data = document.getElementById('dataAula').value;
        const container = document.getElementById('slotsContainer');
        slotSelecionado = null;
        slotsCarregados = [];
        document.getElementById('slotAcao').classList.remove('visivel');

        if (!data) return;

        container.innerHTML = '<p class="empty"><i class="fas fa-circle-notch fa-spin"></i> Carregando horários...</p>';

        try {
            const res = await fetch(`/studio-horarios-disponiveis/${STUDIO_ID}/${data}`, {
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
            });
            const slots = await res.json();

            if (!res.ok) throw new Error(slots.erro || 'Erro ao carregar horários.');

            if (!Array.isArray(slots) || slots.length === 0) {
                container.innerHTML = '<p class="empty"><i class="fas fa-calendar-times"></i> O studio não funciona nesta data ou todos os horários estão indisponíveis.</p>';
                return;
            }

            slotsCarregados = slots;
            container.innerHTML = '<div class="slots-grid">' + slots.map((s, i) => `
                <div class="slot-card ${s.vagas === 0 ? 'lotado' : ''}" id="slot-${i}"
                     ${s.vagas === 0 ? '' : `onclick="selecionarSlot(${i})"`}>
                    <div class="hora">${escHtml(s.label)}${s.duracao ? ` <span style="color:var(--text-muted); font-weight:600; font-size:0.74rem;">· ${s.duracao}min</span>` : ''}</div>
                    ${s.profissional ? `<div class="vagas" style="margin-bottom:4px;"><i class="fas fa-chalkboard-teacher" style="color:var(--studio-color);"></i> ${s.professor_resumo ? `<a href="#" onclick="event.stopPropagation(); verProfessor(${i}); return false;" style="color:var(--studio-color); text-decoration:underline; font-weight:700;">${escHtml(s.profissional)}</a>` : escHtml(s.profissional)}</div>` : ''}
                    <div class="vagas"><strong>${s.vagas}</strong>/${s.capacidade} vagas</div>
                </div>
            `).join('') + '</div>';
        } catch (err) {
            container.innerHTML = `<p class="empty" style="color:#ff6b6b;"><i class="fas fa-exclamation-triangle"></i> ${err.message}</p>`;
        }
    }

    function selecionarSlot(index) {
        const s = slotsCarregados[index];
        if (!s) return;
        slotSelecionado = { inicio: s.inicio, fim: s.fim, label: s.label, profissional: s.profissional || '' };
        document.querySelectorAll('.slot-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('slot-' + index).classList.add('selected');
        const dataFmt = document.getElementById('dataAula').value.split('-').reverse().join('/');
        const profTxt = s.profissional ? ' · ' + s.profissional : '';
        document.getElementById('slotSelecionadoLabel').textContent = s.label + profTxt + ' em ' + dataFmt;
        document.getElementById('slotAcao').classList.add('visivel');
    }

    function verProfessor(index) {
        const s = slotsCarregados[index];
        if (!s) return;
        document.getElementById('profModalNome').textContent = s.profissional || 'Profissional';
        document.getElementById('profModalResumo').textContent = s.professor_resumo || 'Sem resumo disponível.';
        document.getElementById('profModal').classList.add('aberto');
    }
    function fecharProfModal() {
        document.getElementById('profModal').classList.remove('aberto');
    }

    document.getElementById('dataAula').addEventListener('change', carregarSlots);
    carregarSlots();

    // ============ PIX ============
    let pixPolling = null;

    function abrirModalPix(descricao) {
        document.getElementById('pixDescricao').textContent = descricao;
        document.getElementById('pixValor').textContent = 'Gerando QR Code...';
        document.getElementById('pixQr').src = '';
        document.getElementById('pixCopia').value = '';
        document.getElementById('pixStatus').style.display = 'none';
        document.getElementById('modalPix').classList.add('aberto');
    }

    function fecharModalPix() {
        document.getElementById('modalPix').classList.remove('aberto');
        if (pixPolling) { clearInterval(pixPolling); pixPolling = null; }
    }

    function copiarPix() {
        const input = document.getElementById('pixCopia');
        input.select();
        document.execCommand('copy');
    }

    async function iniciarPix(endpoint, payload, descricao, msgSucesso) {
        abrirModalPix(descricao);
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro ao gerar pagamento.');

            document.getElementById('pixQr').src = 'data:image/png;base64,' + data.pixQrCode;
            document.getElementById('pixCopia').value = data.pixPayload;
            document.getElementById('pixValor').textContent = 'R$ ' + parseFloat(data.amount).toFixed(2).replace('.', ',');
            document.getElementById('pixRecorrenteNota').style.display = data.recorrente ? 'block' : 'none';

            pixPolling = setInterval(async () => {
                try {
                    const sr = await fetch('/api/pagamento/status/' + data.asaasPaymentId, { headers: { 'X-CSRF-TOKEN': CSRF } });
                    const sd = await sr.json();
                    if (sd.confirmed) {
                        clearInterval(pixPolling);
                        const msg = document.getElementById('pixStatus');
                        msg.textContent = msgSucesso;
                        msg.style.color = 'var(--success)';
                        msg.style.display = 'block';
                        setTimeout(() => window.location.reload(), 2500);
                    }
                } catch (_) {}
            }, 4000);
        } catch (err) {
            alert('Erro ao iniciar pagamento: ' + err.message);
            fecharModalPix();
        }
    }

    function pagarPlanoPix(planoId, planoNome, valor) {
        iniciarPix(
            '/api/criar-pagamento-studio-plano',
            { studio_id: STUDIO_ID, plano_id: planoId },
            `Plano ${planoNome} — ${STUDIO_NOME}`,
            '✅ Pagamento confirmado! Bem-vindo ao studio!'
        );
    }

    function pagarAulaPix() {
        if (!slotSelecionado) return;
        iniciarPix(
            '/api/criar-pagamento-aula-studio',
            { studio_id: STUDIO_ID, data: document.getElementById('dataAula').value, hora_inicio: slotSelecionado.inicio, hora_fim: slotSelecionado.fim },
            `Aula ${slotSelecionado.label}${slotSelecionado.profissional ? ' com ' + slotSelecionado.profissional : ''} — ${STUDIO_NOME}`,
            '✅ Pagamento confirmado! Sua vaga está garantida!'
        );
    }

    // ============ CARTÃO ============
    let cartaoCtx = null;

    function abrirModalCartao(endpoint, payload, descricao, valor) {
        cartaoCtx = { endpoint, payload };
        document.getElementById('cartaoDescricao').textContent = descricao;
        document.getElementById('cartaoValor').textContent = 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
        document.getElementById('formCartao').reset();
        document.getElementById('cartaoErro').style.display = 'none';
        document.getElementById('cartaoSucesso').style.display = 'none';
        const btn = document.getElementById('btnSubmeterCartao');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock"></i> Pagar com Segurança';
        document.getElementById('cartaoTelefone').value = CLIENTE_TEL || '';
        document.getElementById('cartaoCEP').value = CLIENTE_CEP || '';
        document.getElementById('modalCartao').classList.add('aberto');
    }

    function fecharModalCartao() {
        document.getElementById('modalCartao').classList.remove('aberto');
        cartaoCtx = null;
    }

    function pagarPlanoCartao(planoId, planoNome, valor) {
        abrirModalCartao(
            '/api/criar-pagamento-cartao-studio-plano',
            { studio_id: STUDIO_ID, plano_id: planoId },
            `Plano ${planoNome} — ${STUDIO_NOME}`,
            valor
        );
    }

    function pagarAulaCartao() {
        if (!slotSelecionado) return;
        abrirModalCartao(
            '/api/criar-pagamento-cartao-aula-studio',
            { studio_id: STUDIO_ID, data: document.getElementById('dataAula').value, hora_inicio: slotSelecionado.inicio, hora_fim: slotSelecionado.fim },
            `Aula ${slotSelecionado.label}${slotSelecionado.profissional ? ' com ' + slotSelecionado.profissional : ''} — ${STUDIO_NOME}`,
            VALOR_AULA
        );
    }

    async function submeterCartao(e) {
        e.preventDefault();
        if (!cartaoCtx) return;

        const validade = document.getElementById('cartaoValidade').value.trim();
        const parts = validade.split('/');
        if (parts.length !== 2 || parts[0].length !== 2 || parts[1].length !== 2) {
            const erro = document.getElementById('cartaoErro');
            erro.textContent = 'Validade inválida. Use o formato MM/AA.';
            erro.style.display = 'block';
            return;
        }

        const cardData = {
            card_holder:       document.getElementById('cartaoNomeTitular').value.trim(),
            card_number:       document.getElementById('cartaoNumero').value.replace(/\s/g, ''),
            card_expiry_month: parts[0],
            card_expiry_year:  '20' + parts[1],
            card_ccv:          document.getElementById('cartaoCCV').value.trim(),
            cpf:               document.getElementById('cartaoCPF').value.replace(/\D/g, ''),
            cep:               document.getElementById('cartaoCEP').value.replace(/\D/g, ''),
            numero:            document.getElementById('cartaoNumeroEnd').value.trim(),
            telefone:          document.getElementById('cartaoTelefone').value.replace(/\D/g, ''),
        };

        const btn = document.getElementById('btnSubmeterCartao');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        document.getElementById('cartaoErro').style.display = 'none';

        try {
            const res = await fetch(cartaoCtx.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ ...cartaoCtx.payload, ...cardData }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro ao processar cartão.');

            if (data.confirmed) {
                document.getElementById('cartaoSucesso').style.display = 'block';
                setTimeout(() => { fecharModalCartao(); window.location.reload(); }, 2000);
            } else {
                throw new Error('Pagamento não confirmado. Verifique os dados e tente novamente.');
            }
        } catch (err) {
            document.getElementById('cartaoErro').textContent = err.message;
            document.getElementById('cartaoErro').style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> Pagar com Segurança';
        }
    }

    function formatarNumeroCartao(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 16);
        input.value = v.replace(/(.{4})/g, '$1 ').trim();
    }

    function formatarValidade(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 4);
        if (v.length >= 3) v = v.substring(0, 2) + '/' + v.substring(2);
        input.value = v;
    }

    function formatarCPF(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = v;
    }

    function formatarCEP(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 8);
        if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5);
        input.value = v;
    }

    function formatarTelefone(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 11);
        if (v.length > 2) v = '(' + v.substring(0, 2) + ') ' + v.substring(2);
        if (v.length > 10) v = v.substring(0, 10) + '-' + v.substring(10);
        input.value = v;
    }

    window.addEventListener('click', e => {
        if (e.target === document.getElementById('modalPix')) fecharModalPix();
        if (e.target === document.getElementById('modalCartao')) fecharModalCartao();
    });
</script>
</body>
</html>
