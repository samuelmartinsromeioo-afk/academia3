<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliação Física — {{ $cliente->nome }}</title>
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
        .btn-primary { background: var(--primary); color: #000; border: none; padding: 12px 22px; border-radius: 10px; font-weight: 900; font-size: 0.8rem; cursor: pointer; text-transform: uppercase; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(212,255,0,0.2); }
        .btn-back { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 3rem; color: var(--text-muted); margin-bottom: 16px; display: block; }
        .empty-state p { color: var(--text-muted); font-size: 0.9rem; }
        .alert-success { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.3); color: var(--success); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }
        .alert-error { background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.3); color: var(--error); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }

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
        .dados-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
        .dado-item { background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
        .dado-item label { display: block; color: var(--text-muted); font-size: 0.6rem; text-transform: uppercase; font-weight: 800; margin-bottom: 4px; }
        .dado-item span { font-size: 1rem; font-weight: 900; color: var(--primary); }
        .dado-item.texto span { font-size: 0.82rem; font-weight: 500; color: var(--text-main); line-height: 1.5; white-space: pre-line; }
        .foto-thumb { width: 110px; height: 110px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border); cursor: zoom-in; transition: 0.2s; }
        .foto-thumb:hover { border-color: var(--primary); }
        .btn-delete { background: transparent; border: 1px solid rgba(255,68,68,0.4); color: var(--error); padding: 7px 13px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; cursor: pointer; transition: 0.2s; }
        .btn-delete:hover { background: rgba(255,68,68,0.12); }
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

        /* Modal base */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(6px); padding: 20px; }
        .modal-content { background: var(--card-bg); border-radius: 24px; border: 1px solid var(--border); padding: 30px; width: 100%; max-width: 720px; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal-content h2 { color: var(--primary); font-size: 1.3rem; font-weight: 900; margin: 0 0 4px; }
        .modal-content .modal-sub { color: var(--text-muted); font-size: 0.8rem; margin: 0 0 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: var(--text-muted); font-size: 0.65rem; text-transform: uppercase; font-weight: 800; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid var(--border); color: #fff; padding: 12px 14px; border-radius: 10px; font-size: 0.9rem; outline: none; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary); }
        .form-group select option { background: var(--card-bg); }
        .form-group input[readonly] { color: var(--primary); font-weight: 900; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        .modal-close { position: absolute; top: 18px; right: 22px; cursor: pointer; color: var(--text-muted); font-size: 1.2rem; background: none; border: none; }
        .modal-close:hover { color: var(--error); }
        .sub-label { font-size: 0.65rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted); margin: 8px 0; }

        /* Range inputs */
        .range-wrapper { display: flex; align-items: center; gap: 12px; }
        .range-wrapper input[type=range] { flex: 1; accent-color: var(--primary); }
        .range-val { min-width: 32px; text-align: center; font-weight: 900; color: var(--primary); font-size: 1rem; }

        /* Dor colorida */
        .dor-0, .dor-1, .dor-2 { color: #00ff88; }
        .dor-3, .dor-4, .dor-5 { color: #ffdd00; }
        .dor-6, .dor-7, .dor-8 { color: #ff8800; }
        .dor-9, .dor-10 { color: #ff4444; }

        /* Fotos posturais grid */
        .postural-foto-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }

        /* Checklist */
        .check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; }
        .check-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; cursor: pointer; font-size: 0.8rem; }
        .check-item input[type=checkbox] { accent-color: var(--primary); width: 16px; height: 16px; }

        .snrfit-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-top: 4px; }

        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.98); z-index: 99999; justify-content: center; align-items: center; cursor: zoom-out; }
        #lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 12px; object-fit: contain; }

        @media (max-width: 700px) {
            .tipo-tabs { grid-template-columns: repeat(2, 1fr); }
            .top-bar { padding: 15px 20px; }
            .form-row { grid-template-columns: 1fr; }
            .form-row-3 { grid-template-columns: 1fr 1fr; }
            .postural-foto-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('personal.avaliacao-fisica') }}" class="btn-back"><i class="ph ph-arrow-left"></i> Voltar</a>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="{{ $personal->foto ? asset('storage/'.$personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
        <span style="font-weight:700; font-size:0.9rem;">{{ $personal->nome }}</span>
    </div>
</div>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:15px; flex-wrap:wrap; margin-bottom:6px;">
        <div>
            <div class="ed-eyebrow"><i class="ph ph-heartbeat"></i> AvaliaÃ§Ã£o do aluno</div><h1 class="ed-h">{{ $cliente->nome }}</h1>
            <p class="page-sub">Registros de avaliação física do aluno. Cada avaliação é salva separadamente.</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn-primary" onclick="abrirModalRegistro()"><i class="ph ph-plus"></i> Novo Registro</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-error"><i class="ph ph-warning-circle"></i> {{ $errors->first() }}</div>
    @endif

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
        <a href="{{ route('personal.avaliacao-fisica.aluno', $cliente->id) }}{{ $mesFiltro ? '?mes='.$mesFiltro : '' }}" class="tipo-tab {{ !$tipoFiltro ? 'active' : '' }}">
            <i class="ph ph-list"></i> Todos
        </a>
        @foreach($tiposCriaveis as $key)
        <a href="{{ route('personal.avaliacao-fisica.aluno', $cliente->id) }}?tipo={{ $key }}{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $tipoFiltro === $key ? 'active' : '' }}">
            <i class="ph {{ $tipos[$key]['icon'] }}"></i> {{ $tipos[$key]['label'] }}
        </a>
        @endforeach
        <a href="{{ route('personal.avaliacao-fisica.aluno', $cliente->id) }}?tipo=resumo{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $tipoFiltro === 'resumo' ? 'active' : '' }}">
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
                <p>Nenhum dado para montar o resumo{{ $mesFiltro ? ' nesse mês' : '' }}.<br>Adicione registros nas categorias para visualizar tudo aqui.</p>
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
            <p>Nenhum registro encontrado{{ $tipoFiltro || $mesFiltro ? ' para esse filtro' : '' }}.<br>Clique em "Novo Registro" para começar.</p>
        </div>
    @else
        @foreach($registros as $r)
        <div class="card">
            <div class="registro-head">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span class="tipo-badge"><i class="ph {{ $tipos[$r->tipo]['icon'] ?? 'ph-clipboard' }}"></i> {{ $tipos[$r->tipo]['label'] ?? $r->tipo }}</span>
                    <span class="data-registro"><i class="ph ph-calendar"></i> {{ $r->data_avaliacao->format('d/m/Y') }}</span>
                </div>
                <form action="{{ route('personal.avaliacao-fisica.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Excluir este registro?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-delete"><i class="ph ph-trash"></i></button>
                </form>
            </div>

            @include('avaliacoes._dados', ['r' => $r])
        </div>
        @endforeach
    @endif
</div>

{{-- MODAL NOVO REGISTRO --}}
<div id="modalRegistro" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close" onclick="fecharModalRegistro()"><i class="ph ph-x"></i></button>
        <h2><i class="ph ph-plus-circle"></i> Novo Registro</h2>
        <p class="modal-sub">Escolha o tipo de avaliação e preencha apenas o que for medir — cada registro é salvo de forma independente.</p>

        <form action="{{ route('personal.avaliacao-fisica.store', $cliente->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de Avaliação</label>
                    <select name="tipo" id="selectTipo" onchange="trocarCampos()" required>
                        @foreach($tiposCriaveis as $key)
                        <option value="{{ $key }}">{{ $tipos[$key]['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Data da Avaliação</label>
                    <input type="date" name="data_avaliacao" value="{{ now()->format('Y-m-d') }}" required>
                </div>
            </div>

            {{-- ANTES E DEPOIS --}}
            <div class="campos-tipo" data-tipo="antes_depois">
                <div class="form-group">
                    <label>Foto (antes/depois)</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" step="0.01" min="0" name="peso" placeholder="Ex: 78.5">
                </div>
                <div class="form-group">
                    <label>Medidas Corporais</label>
                    <textarea name="medidas" rows="3" placeholder="Ex: Cintura: 85cm&#10;Braço: 38cm&#10;Coxa: 60cm"></textarea>
                </div>
            </div>

            {{-- ANTROPOMÉTRICA --}}
            <div class="campos-tipo" data-tipo="antropometrica" style="display:none;">
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Peso (kg)</label>
                        <input type="number" step="0.01" min="0" max="500" name="peso" id="snr-peso" placeholder="Ex: 78.5" oninput="calcIMC()">
                    </div>
                    <div class="form-group">
                        <label>Altura (cm)</label>
                        <input type="number" step="0.1" min="0" max="300" name="altura" id="snr-altura" placeholder="Ex: 175" oninput="calcIMC()">
                    </div>
                    <div class="form-group">
                        <label>IMC (calculado)</label>
                        <input type="number" step="0.01" name="imc" id="snr-imc" placeholder="Auto" readonly>
                    </div>
                </div>
                <p class="sub-label">Circunferências (cm)</p>
                <div class="form-row">
                    <div class="form-group"><label>Cintura</label><input type="number" step="0.1" min="0" max="300" name="circ_cintura" placeholder="cm"></div>
                    <div class="form-group"><label>Abdômen</label><input type="number" step="0.1" min="0" max="300" name="circ_abdomen" placeholder="cm"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Quadril</label><input type="number" step="0.1" min="0" max="300" name="circ_quadril" placeholder="cm"></div>
                    <div class="form-group"><label>Tórax</label><input type="number" step="0.1" min="0" max="300" name="circ_torax" placeholder="cm"></div>
                </div>
                <div class="form-row-3">
                    <div class="form-group"><label>Braço</label><input type="number" step="0.1" min="0" max="200" name="circ_braco" placeholder="cm"></div>
                    <div class="form-group"><label>Coxa</label><input type="number" step="0.1" min="0" max="200" name="circ_coxa" placeholder="cm"></div>
                    <div class="form-group"><label>Panturrilha</label><input type="number" step="0.1" min="0" max="200" name="circ_panturrilha" placeholder="cm"></div>
                </div>
            </div>

            {{-- DOBRAS CUTÂNEAS --}}
            <div class="campos-tipo" data-tipo="dobras" style="display:none;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Peso (kg) — p/ massa gorda/magra</label>
                        <input type="number" step="0.01" min="0" max="500" name="peso" id="dobras-peso" placeholder="Ex: 78.5" oninput="calcPollock()">
                    </div>
                    <div class="form-group">
                        <label>Protocolo</label>
                        <select name="protocolo_dobras" id="snr-protocolo" onchange="calcPollock()">
                            <option value="">Selecione...</option>
                            <option value="pollock3">Pollock 3 dobras</option>
                            <option value="pollock7">Pollock 7 dobras</option>
                            <option value="jackson_pollock">Jackson &amp; Pollock</option>
                            <option value="outro">Outro protocolo</option>
                        </select>
                    </div>
                </div>
                <p class="sub-label">Dobras (mm)</p>
                <div class="form-row">
                    <div class="form-group"><label>Tríceps</label><input type="number" step="0.1" min="0" max="200" name="dobra_triceps" id="snr-d-triceps" placeholder="mm" oninput="calcPollock()"></div>
                    <div class="form-group"><label>Bíceps</label><input type="number" step="0.1" min="0" max="200" name="dobra_biceps" placeholder="mm"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Subescapular</label><input type="number" step="0.1" min="0" max="200" name="dobra_subescapular" id="snr-d-sub" placeholder="mm" oninput="calcPollock()"></div>
                    <div class="form-group"><label>Suprailíaca</label><input type="number" step="0.1" min="0" max="200" name="dobra_suprailiaca" id="snr-d-supra" placeholder="mm" oninput="calcPollock()"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Abdominal</label><input type="number" step="0.1" min="0" max="200" name="dobra_abdominal" id="snr-d-abd" placeholder="mm" oninput="calcPollock()"></div>
                    <div class="form-group"><label>Coxa</label><input type="number" step="0.1" min="0" max="200" name="dobra_coxa_dc" id="snr-d-coxa" placeholder="mm" oninput="calcPollock()"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Peitoral</label><input type="number" step="0.1" min="0" max="200" name="dobra_peitoral" id="snr-d-peit" placeholder="mm" oninput="calcPollock()"></div>
                    <div class="form-group"><label>Axilar Média</label><input type="number" step="0.1" min="0" max="200" name="dobra_axilar_media" id="snr-d-axilar" placeholder="mm" oninput="calcPollock()"></div>
                </div>
                <div class="form-row-3" style="margin-top:8px;">
                    <div class="form-group"><label>% Gordura (calc.)</label><input type="number" step="0.01" name="percentual_gordura" id="snr-pg" placeholder="Auto" readonly></div>
                    <div class="form-group"><label>Massa Gorda (kg)</label><input type="number" step="0.01" name="massa_gorda" id="snr-mg" placeholder="Auto" readonly></div>
                    <div class="form-group"><label>Massa Magra (kg)</label><input type="number" step="0.01" name="massa_magra" id="snr-mm" placeholder="Auto" readonly></div>
                </div>
            </div>

            {{-- BIOIMPEDÂNCIA --}}
            <div class="campos-tipo" data-tipo="bioimpedancia" style="display:none;">
                <div class="form-group">
                    <label>Relatório de Bioimpedância (PDF)</label>
                    <input type="file" name="arquivo" accept="application/pdf">
                </div>
            </div>

            {{-- DINAMÔMETRO --}}
            <div class="campos-tipo" data-tipo="dinamometro" style="display:none;">
                <div class="form-group">
                    <label>Força Medida (kgf)</label>
                    <input type="number" step="0.01" min="0" name="forca" placeholder="Ex: 42.5">
                </div>
            </div>

            {{-- FORÇA --}}
            <div class="campos-tipo" data-tipo="forca" style="display:none;">
                <div class="form-row-3">
                    <div class="form-group"><label>Flexão de Braço (reps)</label><input type="number" min="0" max="9999" name="flexao_braco_reps" placeholder="Ex: 30"></div>
                    <div class="form-group"><label>Prancha (segundos)</label><input type="number" min="0" max="9999" name="prancha_tempo" placeholder="Ex: 90"></div>
                    <div class="form-group"><label>Dinamometria (kgf)</label><input type="number" step="0.01" min="0" max="500" name="forca" placeholder="Ex: 42.5"></div>
                </div>
                <div class="form-group">
                    <label>Testes Submáximos</label>
                    <textarea name="testes_submax" rows="2" placeholder="Descrição de testes submáximos realizados..."></textarea>
                </div>
            </div>

            {{-- FLEXIBILIDADE --}}
            <div class="campos-tipo" data-tipo="flexibilidade" style="display:none;">
                <div class="form-row-3">
                    <div class="form-group"><label>Sentar e Alcançar (cm)</label><input type="number" step="0.1" min="-50" max="100" name="flex_sentar_alcancar" placeholder="Ex: 18.5"></div>
                    <div class="form-group">
                        <label>Flexibilidade Ombros</label>
                        <select name="flex_ombros">
                            <option value="">—</option><option value="Boa">Boa</option><option value="Regular">Regular</option><option value="Ruim">Ruim</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Flexibilidade Quadril</label><input type="number" step="0.1" min="0" max="200" name="flex_quadril" placeholder="Graus ou cm"></div>
                </div>
            </div>

            {{-- NEUROMOTORA --}}
            <div class="campos-tipo" data-tipo="neuromotora" style="display:none;">
                <div class="form-row">
                    <div class="form-group"><label>Equilíbrio Unipodal</label><input type="text" name="equil_unipodal" placeholder="Ex: 30s D / 25s E"></div>
                    <div class="form-group">
                        <label>Coordenação Motora</label>
                        <select name="coordenacao_motora">
                            <option value="">Selecione...</option><option value="Boa">Boa</option><option value="Regular">Regular</option><option value="Ruim">Ruim</option>
                        </select>
                    </div>
                </div>
                <p class="sub-label">Mobilidade</p>
                <div class="form-row-3">
                    <div class="form-group"><label>Ombro</label><select name="mob_ombro"><option value="">—</option><option value="Boa">Boa</option><option value="Regular">Regular</option><option value="Ruim">Ruim</option></select></div>
                    <div class="form-group"><label>Quadril</label><select name="mob_quadril"><option value="">—</option><option value="Boa">Boa</option><option value="Regular">Regular</option><option value="Ruim">Ruim</option></select></div>
                    <div class="form-group"><label>Tornozelo</label><select name="mob_tornozelo"><option value="">—</option><option value="Boa">Boa</option><option value="Regular">Regular</option><option value="Ruim">Ruim</option></select></div>
                </div>
                <p class="sub-label">Agachamento</p>
                <div class="form-row-3">
                    <div class="form-group"><label>Profundidade</label><select name="agach_profundidade"><option value="">—</option><option value="Completo">Completo</option><option value="Parcial">Parcial</option><option value="Limitado">Limitado</option></select></div>
                    <div class="form-group"><label>Estabilidade</label><select name="agach_estabilidade"><option value="">—</option><option value="Boa">Boa</option><option value="Regular">Regular</option><option value="Ruim">Ruim</option></select></div>
                    <div class="form-group"><label>Simetria</label><select name="agach_simetria"><option value="">—</option><option value="Simétrico">Simétrico</option><option value="Assimétrico">Assimétrico</option></select></div>
                </div>
            </div>

            {{-- FUNCIONAL --}}
            <div class="campos-tipo" data-tipo="funcional" style="display:none;">
                @foreach([
                    ['name'=>'func_agachamento','label'=>'Agachamento'],
                    ['name'=>'func_avanco','label'=>'Avanço'],
                    ['name'=>'func_stepup','label'=>'Step-up'],
                    ['name'=>'func_prancha','label'=>'Prancha'],
                    ['name'=>'func_mob_toracica','label'=>'Mobilidade Torácica'],
                ] as $func)
                <div class="form-group">
                    <label>{{ $func['label'] }}</label>
                    <input type="text" name="{{ $func['name'] }}" placeholder="Ex: Bom — joelhos alinhados, profundidade completa">
                </div>
                @endforeach
            </div>

            {{-- CARDIORRESPIRATÓRIA --}}
            <div class="campos-tipo" data-tipo="cardio" style="display:none;">
                <div class="form-row-3">
                    <div class="form-group"><label>FC Repouso (bpm)</label><input type="number" min="0" max="300" name="bpm" placeholder="Ex: 68"></div>
                    <div class="form-group"><label>PA Sistólica (mmHg)</label><input type="number" min="0" max="400" name="pressao_sistolica" placeholder="Ex: 120"></div>
                    <div class="form-group"><label>PA Diastólica (mmHg)</label><input type="number" min="0" max="300" name="pressao_diastolica" placeholder="Ex: 80"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Caminhada 6 min (metros)</label><input type="number" step="0.1" min="0" max="9999" name="teste_caminhada_dist" placeholder="Ex: 540"></div>
                    <div class="form-group"><label>Cooper 12 min (metros)</label><input type="number" step="0.1" min="0" max="9999" name="teste_cooper_dist" id="snr-cooper" placeholder="Ex: 2400" oninput="calcVO2()"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Rockport — Tempo (min)</label><input type="number" step="0.01" min="0" max="999" name="teste_rockport_tempo" placeholder="Ex: 14.5"></div>
                    <div class="form-group"><label>VO2max estimado (ml/kg/min)</label><input type="number" step="0.01" name="vo2max_estimado" id="snr-vo2" placeholder="Auto via Cooper" readonly></div>
                </div>
            </div>

            {{-- OXÍMETRO --}}
            <div class="campos-tipo" data-tipo="oximetro" style="display:none;">
                <div class="form-row">
                    <div class="form-group"><label>Saturação SpO2 (%)</label><input type="number" min="0" max="100" name="spo2" placeholder="Ex: 98"></div>
                    <div class="form-group"><label>Batimentos (bpm)</label><input type="number" min="0" max="300" name="bpm" placeholder="Ex: 72"></div>
                </div>
            </div>

            {{-- PRESSÃO ARTERIAL --}}
            <div class="campos-tipo" data-tipo="pressao_arterial" style="display:none;">
                <div class="form-row">
                    <div class="form-group"><label>Sistólica (mmHg)</label><input type="number" min="0" max="400" name="pressao_sistolica" placeholder="Ex: 120"></div>
                    <div class="form-group"><label>Diastólica (mmHg)</label><input type="number" min="0" max="300" name="pressao_diastolica" placeholder="Ex: 80"></div>
                </div>
            </div>

            {{-- POSTURAL --}}
            <div class="campos-tipo" data-tipo="postural" style="display:none;">
                <div class="postural-foto-grid">
                    <div class="form-group"><label><i class="ph ph-arrow-up"></i> Foto Anterior</label><input type="file" name="foto_anterior" accept="image/*"></div>
                    <div class="form-group"><label><i class="ph ph-arrow-down"></i> Foto Posterior</label><input type="file" name="foto_posterior" accept="image/*"></div>
                    <div class="form-group"><label><i class="ph ph-arrow-right"></i> Lateral Direita</label><input type="file" name="foto_lateral_direita" accept="image/*"></div>
                    <div class="form-group"><label><i class="ph ph-arrow-left"></i> Lateral Esquerda</label><input type="file" name="foto_lateral_esquerda" accept="image/*"></div>
                </div>
                <div class="form-group" style="margin-top:8px;">
                    <label>Checklist Postural (marque os que se aplicam)</label>
                    <div class="check-grid" style="margin-top:8px;">
                        @foreach(['Cabeça anteriorizada','Ombros elevados','Escoliose aparente','Hiperlordose','Hipercifose','Joelho valgo','Joelho varo','Pé plano'] as $checkItem)
                        <label class="check-item">
                            <input type="checkbox" name="postural_checklist[]" value="{{ $checkItem }}">
                            {{ $checkItem }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- DOR --}}
            <div class="campos-tipo" data-tipo="dor" style="display:none;">
                <p style="font-size:0.75rem; color:var(--text-muted); margin:0 0 14px;">Escala EVA — 0 = Sem dor, 10 = Dor máxima</p>
                @foreach([
                    ['name'=>'dor_lombar','label'=>'Lombar','id'=>'dv-lombar'],
                    ['name'=>'dor_ombro','label'=>'Ombro','id'=>'dv-ombro'],
                    ['name'=>'dor_joelho','label'=>'Joelho','id'=>'dv-joelho'],
                    ['name'=>'dor_quadril','label'=>'Quadril','id'=>'dv-quadril'],
                    ['name'=>'dor_cervical','label'=>'Cervical','id'=>'dv-cervical'],
                ] as $dor)
                <div class="form-group">
                    <label>{{ $dor['label'] }}: <span id="{{ $dor['id'] }}" class="dor-0" style="font-weight:900; font-size:1rem;">0</span>/10</label>
                    <div class="range-wrapper">
                        <input type="range" name="{{ $dor['name'] }}" min="0" max="10" value="0" oninput="atualizarDor('{{ $dor['id'] }}', this.value)">
                        <span class="range-val" id="{{ $dor['id'] }}-val">0</span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ANAMNESE --}}
            <div class="campos-tipo" data-tipo="anamnese" style="display:none;">
                <div class="form-group">
                    <label>Objetivo Principal</label>
                    <textarea name="objetivo_principal" rows="2" placeholder="Ex: Perda de gordura, ganho de massa muscular, condicionamento..."></textarea>
                </div>
                <div class="form-group">
                    <label>Histórico de Atividade Física</label>
                    <textarea name="historico_atividade" rows="2" placeholder="Ex: Pratica musculação há 2 anos, parou por 6 meses..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Lesões</label><textarea name="lesoes" rows="2" placeholder="Ex: Lesão no joelho direito (2020)..."></textarea></div>
                    <div class="form-group"><label>Cirurgias</label><textarea name="cirurgias" rows="2" placeholder="Ex: Meniscectomia (2021)..."></textarea></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Medicamentos em Uso</label><textarea name="medicamentos" rows="2" placeholder="Medicamentos relevantes..."></textarea></div>
                    <div class="form-group"><label>Restrições Médicas</label><textarea name="restricoes_medicas" rows="2" placeholder="Ex: Não pode fazer impacto..."></textarea></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Hábitos de Sono</label><input type="text" name="habitos_sono" placeholder="Ex: Dorme 7h, acorda cansado"></div>
                    <div class="form-group">
                        <label>Nível de Estresse: <span id="val-estresse" style="color:var(--primary); font-weight:900;">5</span>/10</label>
                        <div class="range-wrapper">
                            <input type="range" name="nivel_estresse" min="0" max="10" value="5" oninput="document.getElementById('val-estresse').textContent=this.value">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Hábitos Alimentares</label>
                    <textarea name="alimentacao" rows="2" placeholder="Ex: Come bem, faz dieta low-carb..."></textarea>
                </div>
            </div>

            {{-- Observações (todos os tipos) --}}
            <div class="form-group" style="margin-top:8px;">
                <label>Observações</label>
                <textarea name="observacoes" rows="2" placeholder="Anotações sobre a avaliação (opcional)"></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; justify-content:center;"><i class="ph ph-floppy-disk"></i> Salvar Registro</button>
        </form>
    </div>
</div>

{{-- LIGHTBOX --}}
<div id="lightbox" onclick="fecharLightbox()">
    <img id="lightboxImg">
</div>

<script>
    const clienteIdade = {{ $clienteIdade ?? 30 }};
    const clienteSexo  = '{{ $cliente->sexo ?? "masculino" }}';

    function abrirModalRegistro() {
        @if($tipoFiltro && $tipoFiltro !== 'resumo' && in_array($tipoFiltro, $tiposCriaveis))
        document.getElementById('selectTipo').value = '{{ $tipoFiltro }}';
        @endif
        trocarCampos();
        document.getElementById('modalRegistro').style.display = 'flex';
    }
    function fecharModalRegistro() {
        document.getElementById('modalRegistro').style.display = 'none';
    }

    // Mostra a seção do tipo selecionado e DESABILITA os campos das demais,
    // para que campos de mesmo nome (ex: bpm, pressao_*) não sejam enviados em branco.
    function trocarCampos() {
        const tipo = document.getElementById('selectTipo').value;
        document.querySelectorAll('.campos-tipo').forEach(sec => {
            const ativo = sec.dataset.tipo === tipo;
            sec.style.display = ativo ? 'block' : 'none';
            sec.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = !ativo; });
        });
    }

    function filtrarMes(mes) {
        const params = new URLSearchParams();
        @if($tipoFiltro)
        params.set('tipo', '{{ $tipoFiltro }}');
        @endif
        if (mes) params.set('mes', mes);
        const qs = params.toString();
        window.location.href = '{{ route('personal.avaliacao-fisica.aluno', $cliente->id) }}' + (qs ? '?' + qs : '');
    }

    function abrirLightbox(url) {
        document.getElementById('lightboxImg').src = url;
        document.getElementById('lightbox').style.display = 'flex';
    }
    function fecharLightbox() {
        document.getElementById('lightbox').style.display = 'none';
    }

    // IMC (antropométrica)
    function calcIMC() {
        const peso   = parseFloat(document.getElementById('snr-peso').value);
        const altura = parseFloat(document.getElementById('snr-altura').value);
        const imcEl  = document.getElementById('snr-imc');
        if (peso > 0 && altura > 0) {
            const altM = altura / 100;
            imcEl.value = (peso / (altM * altM)).toFixed(2);
        } else {
            imcEl.value = '';
        }
    }

    // % gordura via Pollock (dobras cutâneas)
    function calcPollock() {
        const protocolo = document.getElementById('snr-protocolo').value;
        const pgEl = document.getElementById('snr-pg');
        const mgEl = document.getElementById('snr-mg');
        const mmEl = document.getElementById('snr-mm');
        const pesoVal = parseFloat(document.getElementById('dobras-peso').value);
        const sexo  = clienteSexo;
        const idade = clienteIdade || 30;

        function v(id) { return parseFloat(document.getElementById(id)?.value) || null; }

        let D = null;

        if (protocolo === 'pollock3') {
            const peit = v('snr-d-peit'), abd = v('snr-d-abd'), coxa = v('snr-d-coxa');
            const tric = v('snr-d-triceps'), supra = v('snr-d-supra');
            if (sexo === 'masculino' && peit !== null && abd !== null && coxa !== null) {
                const S = peit + abd + coxa;
                D = 1.10938 - (0.0008267 * S) + (0.0000016 * S * S) - (0.0002574 * idade);
            } else if (sexo !== 'masculino' && tric !== null && supra !== null && coxa !== null) {
                const S = tric + supra + coxa;
                D = 1.0994921 - (0.0009929 * S) + (0.0000023 * S * S) - (0.0001392 * idade);
            }
        } else if (protocolo === 'pollock7') {
            const peit = v('snr-d-peit'), abd = v('snr-d-abd'), coxa = v('snr-d-coxa');
            const tric = v('snr-d-triceps'), supra = v('snr-d-supra');
            const sub  = v('snr-d-sub'),  ax = v('snr-d-axilar');
            if (peit !== null && abd !== null && coxa !== null && tric !== null && supra !== null && sub !== null && ax !== null) {
                const S = peit + abd + coxa + tric + supra + sub + ax;
                if (sexo === 'masculino') {
                    D = 1.112 - (0.00043499 * S) + (0.00000055 * S * S) - (0.00028826 * idade);
                } else {
                    D = 1.097 - (0.00046971 * S) + (0.00000056 * S * S) - (0.00012828 * idade);
                }
            }
        }

        if (D !== null && D > 0) {
            const pg = Math.max(0, Math.min(100, ((4.95 / D) - 4.50) * 100));
            pgEl.value = pg.toFixed(2);
            if (pesoVal > 0) {
                const mg = pesoVal * pg / 100;
                mgEl.value = mg.toFixed(2);
                mmEl.value = (pesoVal - mg).toFixed(2);
            }
        }
    }

    // VO2max via Cooper (cardiorrespiratória)
    function calcVO2() {
        const dist = parseFloat(document.getElementById('snr-cooper').value);
        const vo2El = document.getElementById('snr-vo2');
        if (dist > 0) {
            vo2El.value = Math.max(0, (dist - 504.9) / 44.73).toFixed(2);
        } else {
            vo2El.value = '';
        }
    }

    // Display de dor com cor
    function atualizarDor(spanId, val) {
        const el = document.getElementById(spanId);
        const n = parseInt(val);
        el.textContent = n;
        el.className = n <= 2 ? 'dor-0' : (n <= 5 ? 'dor-3' : (n <= 8 ? 'dor-6' : 'dor-9'));
        document.getElementById(spanId + '-val').textContent = n;
    }

    // Fechar modal ao clicar fora
    document.getElementById('modalRegistro').addEventListener('click', function(e) {
        if (e.target === this) fecharModalRegistro();
    });

    // Estado inicial: garante que só os campos do tipo selecionado fiquem ativos.
    trocarCampos();
</script>

</body>
</html>
