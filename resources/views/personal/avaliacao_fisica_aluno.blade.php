<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliação Física — {{ $cliente->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .btn-snrfit { background: linear-gradient(135deg, #d4ff00, #a0cc00); color: #000; border: none; padding: 12px 22px; border-radius: 10px; font-weight: 900; font-size: 0.8rem; cursor: pointer; text-transform: uppercase; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-snrfit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(212,255,0,0.35); }
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
        .tipo-tab.snrfit-tab { border-color: rgba(212,255,0,0.3); }
        .tipo-tab.snrfit-tab.active { background: rgba(212,255,0,0.15); }

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
        .modal-content { background: var(--card-bg); border-radius: 24px; border: 1px solid var(--border); padding: 30px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal-content h2 { color: var(--primary); font-size: 1.2rem; font-weight: 900; margin: 0 0 20px; }
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

        /* Modal SNR Fit Tech grande */
        .modal-snrfit .modal-content { max-width: 900px; padding: 36px; }
        .modal-snrfit .modal-content h2 { font-size: 1.4rem; border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 24px; }

        /* Accordion (details/summary) */
        .snr-section { margin-bottom: 12px; border: 1px solid var(--border); border-radius: 16px; overflow: hidden; transition: border-color 0.2s; }
        .snr-section[open] { border-color: rgba(212,255,0,0.35); }
        .snr-section summary { cursor: pointer; list-style: none; padding: 16px 20px; background: rgba(255,255,255,0.03); display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 0.85rem; user-select: none; }
        .snr-section summary::-webkit-details-marker { display: none; }
        .snr-section summary .sec-num { background: rgba(212,255,0,0.12); color: var(--primary); border-radius: 8px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 900; flex-shrink: 0; }
        .snr-section summary .sec-arrow { margin-left: auto; color: var(--text-muted); font-size: 0.75rem; transition: transform 0.2s; }
        .snr-section[open] summary .sec-arrow { transform: rotate(180deg); }
        .snr-section-body { padding: 20px; border-top: 1px solid var(--border); background: rgba(0,0,0,0.15); }

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

        /* Card SNR Fit Tech no listing */
        .snrfit-card { border-color: rgba(212,255,0,0.25); }
        .snrfit-card:hover { border-color: rgba(212,255,0,0.5); }
        .snrfit-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .snrfit-badge { padding: 5px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; background: rgba(212,255,0,0.15); color: var(--primary); border: 1px solid rgba(212,255,0,0.4); }
        .modulos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; margin-top: 12px; }
        .modulo-chip { padding: 6px 12px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 8px; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .modulo-chip i { color: var(--primary); }
        .snrfit-expand { background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 12px; padding: 12px 16px; margin-top: 12px; font-size: 0.8rem; }
        .snrfit-expand summary { cursor: pointer; font-weight: 800; color: var(--text-muted); list-style: none; display: flex; align-items: center; gap: 8px; }
        .snrfit-expand summary::-webkit-details-marker { display: none; }
        .snrfit-expand[open] summary { color: var(--primary); }
        .snrfit-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-top: 12px; }

        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.98); z-index: 99999; justify-content: center; align-items: center; cursor: zoom-out; }
        #lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 12px; object-fit: contain; }

        @media (max-width: 700px) {
            .tipo-tabs { grid-template-columns: repeat(2, 1fr); }
            .top-bar { padding: 15px 20px; }
            .form-row { grid-template-columns: 1fr; }
            .form-row-3 { grid-template-columns: 1fr 1fr; }
            .postural-foto-grid { grid-template-columns: 1fr; }
            .modal-snrfit .modal-content { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('personal.avaliacao-fisica') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="{{ $personal->foto ? asset('storage/'.$personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
        <span style="font-weight:700; font-size:0.9rem;">{{ $personal->nome }}</span>
    </div>
</div>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:15px; flex-wrap:wrap; margin-bottom:6px;">
        <div>
            <h1 class="page-title"><i class="fas fa-heart-pulse" style="margin-right:10px;"></i>{{ $cliente->nome }}</h1>
            <p class="page-sub">Registros de avaliação física do aluno.</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn-primary" onclick="abrirModalRegistro()"><i class="fas fa-plus"></i> Novo Registro</button>
            <button class="btn-snrfit" onclick="abrirModalSNR()"><i class="fas fa-clipboard-list"></i> SNR Fit Tech</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    @php
        $tipos = [
            'antes_depois'     => ['label' => 'Antes e Depois',   'icon' => 'fa-camera-retro'],
            'dinamometro'      => ['label' => 'Dinamômetro',      'icon' => 'fa-hand-fist'],
            'oximetro'         => ['label' => 'Oxímetro',         'icon' => 'fa-lungs'],
            'pressao_arterial' => ['label' => 'Pressão Arterial', 'icon' => 'fa-heart-pulse'],
            'bioimpedancia'    => ['label' => 'Bioimpedância',    'icon' => 'fa-weight-scale'],
            'completa'         => ['label' => 'SNR Fit Tech',     'icon' => 'fa-clipboard-list'],
        ];
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
            <i class="fas fa-list"></i> Todos
        </a>
        @foreach($tipos as $key => $t)
        <a href="{{ route('personal.avaliacao-fisica.aluno', $cliente->id) }}?tipo={{ $key }}{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $key === 'completa' ? 'snrfit-tab' : '' }} {{ $tipoFiltro === $key ? 'active' : '' }}">
            <i class="fas {{ $t['icon'] }}"></i> {{ $t['label'] }}
        </a>
        @endforeach
        <a href="{{ route('personal.avaliacao-fisica.aluno', $cliente->id) }}?tipo=resumo{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $tipoFiltro === 'resumo' ? 'active' : '' }}">
            <i class="fas fa-chart-simple"></i> Resumo
        </a>
    </div>

    <div class="filtro-bar">
        <label style="color:var(--text-muted); font-size:0.7rem; font-weight:800; text-transform:uppercase;"><i class="fas fa-filter"></i> Filtrar por mês:</label>
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
                <i class="fas fa-chart-simple"></i>
                <p>Nenhum dado para montar o resumo{{ $mesFiltro ? ' nesse mês' : '' }}.<br>Adicione registros nas categorias para visualizar tudo aqui.</p>
            </div>
        @else
            <p style="color:var(--text-muted); font-size:0.8rem; margin:0 0 16px;"><i class="fas fa-info-circle"></i> Resumo com base no registro mais recente de cada categoria{{ $mesFiltro ? ' no mês selecionado' : '' }}.</p>
            <div class="resumo-grid">
                @foreach($resumo as $key => $item)
                <div class="resumo-card">
                    <div class="resumo-icon"><i class="fas {{ $tipos[$key]['icon'] ?? 'fa-clipboard' }}"></i></div>
                    <h3>{{ $tipos[$key]['label'] ?? $key }}</h3>
                    <div class="resumo-valor">{{ $item['valor'] }}</div>
                    <div class="resumo-data"><i class="far fa-calendar-alt"></i> {{ $item['registro']->data_avaliacao->format('d/m/Y') }}</div>

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
                            <a href="{{ asset('storage/'.$item['registro']->arquivo) }}" target="_blank" class="btn-pdf"><i class="fas fa-file-pdf"></i> Abrir PDF</a>
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

    @elseif($tipoFiltro === 'completa')
        {{-- LISTAGEM SNR FIT TECH --}}
        @if($registros->isEmpty())
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>Nenhuma avaliação SNR Fit Tech encontrada{{ $mesFiltro ? ' nesse mês' : '' }}.<br>Clique em "SNR Fit Tech" para criar a primeira.</p>
            </div>
        @else
            @foreach($registros as $r)
            <div class="card snrfit-card">
                <div class="registro-head">
                    <div class="snrfit-header">
                        <span class="snrfit-badge"><i class="fas fa-clipboard-list"></i> SNR Fit Tech</span>
                        <span class="data-registro"><i class="far fa-calendar-alt"></i> {{ $r->data_avaliacao->format('d/m/Y') }}</span>
                    </div>
                    <form action="{{ route('personal.avaliacao-fisica.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Excluir este registro?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
                    </form>
                </div>

                {{-- Módulos preenchidos --}}
                @php
                    $modulosPresentes = [];
                    if ($r->objetivo_principal || $r->historico_atividade || $r->lesoes || $r->restricoes_medicas || $r->nivel_estresse !== null)
                        $modulosPresentes[] = ['icon' => 'fa-file-medical', 'label' => 'Anamnese'];
                    if ($r->peso || $r->altura || $r->circ_cintura || $r->circ_quadril)
                        $modulosPresentes[] = ['icon' => 'fa-ruler', 'label' => 'Antropométrica'];
                    if ($r->protocolo_dobras || $r->percentual_gordura)
                        $modulosPresentes[] = ['icon' => 'fa-compress-arrows-alt', 'label' => 'Dobras Cutâneas'];
                    if ($r->foto_anterior || $r->foto_posterior || $r->postural_checklist)
                        $modulosPresentes[] = ['icon' => 'fa-person', 'label' => 'Postural'];
                    if ($r->equil_unipodal || $r->mob_ombro || $r->agach_profundidade)
                        $modulosPresentes[] = ['icon' => 'fa-brain', 'label' => 'Neuromotora'];
                    if ($r->flex_sentar_alcancar !== null || $r->flex_ombros)
                        $modulosPresentes[] = ['icon' => 'fa-person-walking', 'label' => 'Flexibilidade'];
                    if ($r->bpm || $r->teste_cooper_dist || $r->vo2max_estimado)
                        $modulosPresentes[] = ['icon' => 'fa-heart-pulse', 'label' => 'Cardiorrespiratória'];
                    if ($r->flexao_braco_reps || $r->prancha_tempo || $r->forca)
                        $modulosPresentes[] = ['icon' => 'fa-dumbbell', 'label' => 'Força'];
                    if ($r->func_agachamento || $r->func_avanco || $r->func_prancha)
                        $modulosPresentes[] = ['icon' => 'fa-running', 'label' => 'Funcional'];
                    if ($r->dor_lombar !== null || $r->dor_ombro !== null || $r->dor_joelho !== null)
                        $modulosPresentes[] = ['icon' => 'fa-triangle-exclamation', 'label' => 'Dor'];
                @endphp

                @if(!empty($modulosPresentes))
                <div class="modulos-grid">
                    @foreach($modulosPresentes as $mod)
                    <div class="modulo-chip"><i class="fas {{ $mod['icon'] }}"></i> {{ $mod['label'] }}</div>
                    @endforeach
                </div>
                @endif

                {{-- Destaques rápidos --}}
                <div class="dados-grid" style="margin-top:14px;">
                    @if($r->peso)
                    <div class="dado-item"><label>Peso</label><span>{{ number_format($r->peso, 1, ',', '.') }} kg</span></div>
                    @endif
                    @if($r->imc)
                    <div class="dado-item"><label>IMC</label><span>{{ number_format($r->imc, 1, ',', '.') }}</span></div>
                    @endif
                    @if($r->percentual_gordura)
                    <div class="dado-item"><label>% Gordura</label><span>{{ number_format($r->percentual_gordura, 1, ',', '.') }}%</span></div>
                    @endif
                    @if($r->massa_magra)
                    <div class="dado-item"><label>Massa Magra</label><span>{{ number_format($r->massa_magra, 1, ',', '.') }} kg</span></div>
                    @endif
                    @if($r->vo2max_estimado)
                    <div class="dado-item"><label>VO2max</label><span>{{ number_format($r->vo2max_estimado, 1, ',', '.') }}</span></div>
                    @endif
                    @if($r->bpm)
                    <div class="dado-item"><label>FC Repouso</label><span>{{ $r->bpm }} bpm</span></div>
                    @endif
                </div>

                {{-- Fotos posturais --}}
                @if($r->foto_anterior || $r->foto_posterior || $r->foto_lateral_direita || $r->foto_lateral_esquerda)
                <div style="margin-top:14px;">
                    <p style="font-size:0.65rem; text-transform:uppercase; font-weight:800; color:var(--text-muted); margin:0 0 8px;">Fotos Posturais</p>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        @foreach(['foto_anterior'=>'Anterior','foto_posterior'=>'Posterior','foto_lateral_direita'=>'Lat. Direita','foto_lateral_esquerda'=>'Lat. Esquerda'] as $campo => $label)
                            @if($r->$campo)
                            <div style="text-align:center;">
                                <img src="{{ asset('storage/'.$r->$campo) }}" class="foto-thumb" style="width:80px;height:80px;" onclick="abrirLightbox('{{ asset('storage/'.$r->$campo) }}')">
                                <div style="font-size:0.6rem; color:var(--text-muted); margin-top:4px;">{{ $label }}</div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Checklist postural --}}
                @if($r->postural_checklist && count($r->postural_checklist) > 0)
                <div style="margin-top:12px;">
                    <p style="font-size:0.65rem; text-transform:uppercase; font-weight:800; color:var(--text-muted); margin:0 0 6px;">Checklist Postural</p>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        @foreach($r->postural_checklist as $item)
                        <span style="background:rgba(212,255,0,0.08); border:1px solid rgba(212,255,0,0.25); color:var(--primary); padding:3px 10px; border-radius:20px; font-size:0.65rem; font-weight:800;">{{ $item }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Expandir detalhes completos --}}
                <details class="snrfit-expand" style="margin-top:14px;">
                    <summary><i class="fas fa-chevron-down"></i> Ver todos os detalhes</summary>

                    @if($r->objetivo_principal || $r->historico_atividade || $r->lesoes || $r->cirurgias || $r->medicamentos || $r->restricoes_medicas || $r->habitos_sono || $r->nivel_estresse !== null || $r->alimentacao)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-file-medical"></i> Anamnese</p>
                        <div class="snrfit-detail-grid">
                            @if($r->objetivo_principal)<div class="dado-item texto"><label>Objetivo</label><span>{{ $r->objetivo_principal }}</span></div>@endif
                            @if($r->historico_atividade)<div class="dado-item texto"><label>Histórico de Atividade</label><span>{{ $r->historico_atividade }}</span></div>@endif
                            @if($r->lesoes)<div class="dado-item texto"><label>Lesões</label><span>{{ $r->lesoes }}</span></div>@endif
                            @if($r->cirurgias)<div class="dado-item texto"><label>Cirurgias</label><span>{{ $r->cirurgias }}</span></div>@endif
                            @if($r->medicamentos)<div class="dado-item texto"><label>Medicamentos</label><span>{{ $r->medicamentos }}</span></div>@endif
                            @if($r->restricoes_medicas)<div class="dado-item texto"><label>Restrições Médicas</label><span>{{ $r->restricoes_medicas }}</span></div>@endif
                            @if($r->habitos_sono)<div class="dado-item texto"><label>Hábitos de Sono</label><span>{{ $r->habitos_sono }}</span></div>@endif
                            @if($r->nivel_estresse !== null)<div class="dado-item"><label>Nível de Estresse</label><span>{{ $r->nivel_estresse }}/10</span></div>@endif
                            @if($r->alimentacao)<div class="dado-item texto"><label>Alimentação</label><span>{{ $r->alimentacao }}</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->altura || $r->circ_cintura || $r->circ_abdomen || $r->circ_quadril || $r->circ_torax || $r->circ_braco || $r->circ_coxa || $r->circ_panturrilha)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-ruler"></i> Antropométrica</p>
                        <div class="snrfit-detail-grid">
                            @if($r->altura)<div class="dado-item"><label>Altura</label><span>{{ number_format($r->altura, 0) }} cm</span></div>@endif
                            @if($r->circ_cintura)<div class="dado-item"><label>Cintura</label><span>{{ number_format($r->circ_cintura, 1, ',', '.') }} cm</span></div>@endif
                            @if($r->circ_abdomen)<div class="dado-item"><label>Abdômen</label><span>{{ number_format($r->circ_abdomen, 1, ',', '.') }} cm</span></div>@endif
                            @if($r->circ_quadril)<div class="dado-item"><label>Quadril</label><span>{{ number_format($r->circ_quadril, 1, ',', '.') }} cm</span></div>@endif
                            @if($r->circ_torax)<div class="dado-item"><label>Tórax</label><span>{{ number_format($r->circ_torax, 1, ',', '.') }} cm</span></div>@endif
                            @if($r->circ_braco)<div class="dado-item"><label>Braço</label><span>{{ number_format($r->circ_braco, 1, ',', '.') }} cm</span></div>@endif
                            @if($r->circ_coxa)<div class="dado-item"><label>Coxa</label><span>{{ number_format($r->circ_coxa, 1, ',', '.') }} cm</span></div>@endif
                            @if($r->circ_panturrilha)<div class="dado-item"><label>Panturrilha</label><span>{{ number_format($r->circ_panturrilha, 1, ',', '.') }} cm</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->protocolo_dobras || $r->dobra_triceps || $r->dobra_peitoral)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-compress-arrows-alt"></i> Dobras Cutâneas</p>
                        @if($r->protocolo_dobras)<p style="font-size:0.75rem; color:var(--text-muted); margin:0 0 8px;">Protocolo: <strong style="color:#fff;">{{ $r->protocolo_dobras }}</strong></p>@endif
                        <div class="snrfit-detail-grid">
                            @if($r->dobra_triceps)<div class="dado-item"><label>Tríceps</label><span>{{ number_format($r->dobra_triceps, 1, ',', '.') }} mm</span></div>@endif
                            @if($r->dobra_biceps)<div class="dado-item"><label>Bíceps</label><span>{{ number_format($r->dobra_biceps, 1, ',', '.') }} mm</span></div>@endif
                            @if($r->dobra_subescapular)<div class="dado-item"><label>Subescapular</label><span>{{ number_format($r->dobra_subescapular, 1, ',', '.') }} mm</span></div>@endif
                            @if($r->dobra_suprailiaca)<div class="dado-item"><label>Suprailíaca</label><span>{{ number_format($r->dobra_suprailiaca, 1, ',', '.') }} mm</span></div>@endif
                            @if($r->dobra_abdominal)<div class="dado-item"><label>Abdominal</label><span>{{ number_format($r->dobra_abdominal, 1, ',', '.') }} mm</span></div>@endif
                            @if($r->dobra_coxa_dc)<div class="dado-item"><label>Coxa</label><span>{{ number_format($r->dobra_coxa_dc, 1, ',', '.') }} mm</span></div>@endif
                            @if($r->dobra_peitoral)<div class="dado-item"><label>Peitoral</label><span>{{ number_format($r->dobra_peitoral, 1, ',', '.') }} mm</span></div>@endif
                            @if($r->dobra_axilar_media)<div class="dado-item"><label>Axilar Média</label><span>{{ number_format($r->dobra_axilar_media, 1, ',', '.') }} mm</span></div>@endif
                            @if($r->percentual_gordura)<div class="dado-item"><label>% Gordura</label><span>{{ number_format($r->percentual_gordura, 2, ',', '.') }}%</span></div>@endif
                            @if($r->massa_gorda)<div class="dado-item"><label>Massa Gorda</label><span>{{ number_format($r->massa_gorda, 1, ',', '.') }} kg</span></div>@endif
                            @if($r->massa_magra)<div class="dado-item"><label>Massa Magra</label><span>{{ number_format($r->massa_magra, 1, ',', '.') }} kg</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->equil_unipodal || $r->coordenacao_motora || $r->mob_ombro || $r->agach_profundidade)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-brain"></i> Neuromotora</p>
                        <div class="snrfit-detail-grid">
                            @if($r->equil_unipodal)<div class="dado-item texto"><label>Equilíbrio Unipodal</label><span>{{ $r->equil_unipodal }}</span></div>@endif
                            @if($r->coordenacao_motora)<div class="dado-item texto"><label>Coordenação Motora</label><span>{{ $r->coordenacao_motora }}</span></div>@endif
                            @if($r->mob_ombro)<div class="dado-item texto"><label>Mob. Ombro</label><span>{{ $r->mob_ombro }}</span></div>@endif
                            @if($r->mob_quadril)<div class="dado-item texto"><label>Mob. Quadril</label><span>{{ $r->mob_quadril }}</span></div>@endif
                            @if($r->mob_tornozelo)<div class="dado-item texto"><label>Mob. Tornozelo</label><span>{{ $r->mob_tornozelo }}</span></div>@endif
                            @if($r->agach_profundidade)<div class="dado-item texto"><label>Agach. Profundidade</label><span>{{ $r->agach_profundidade }}</span></div>@endif
                            @if($r->agach_estabilidade)<div class="dado-item texto"><label>Agach. Estabilidade</label><span>{{ $r->agach_estabilidade }}</span></div>@endif
                            @if($r->agach_simetria)<div class="dado-item texto"><label>Agach. Simetria</label><span>{{ $r->agach_simetria }}</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->flex_sentar_alcancar !== null || $r->flex_ombros || $r->flex_quadril !== null)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-person-walking"></i> Flexibilidade</p>
                        <div class="snrfit-detail-grid">
                            @if($r->flex_sentar_alcancar !== null)<div class="dado-item"><label>Sentar e Alcançar</label><span>{{ number_format($r->flex_sentar_alcancar, 1, ',', '.') }} cm</span></div>@endif
                            @if($r->flex_ombros)<div class="dado-item texto"><label>Flex. Ombros</label><span>{{ $r->flex_ombros }}</span></div>@endif
                            @if($r->flex_quadril !== null)<div class="dado-item"><label>Flex. Quadril</label><span>{{ number_format($r->flex_quadril, 1, ',', '.') }}</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->bpm || $r->pressao_sistolica || $r->teste_caminhada_dist || $r->teste_cooper_dist || $r->vo2max_estimado)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-heart-pulse"></i> Cardiorrespiratória</p>
                        <div class="snrfit-detail-grid">
                            @if($r->bpm)<div class="dado-item"><label>FC Repouso</label><span>{{ $r->bpm }} bpm</span></div>@endif
                            @if($r->pressao_sistolica)<div class="dado-item"><label>Pressão Arterial</label><span>{{ $r->pressao_sistolica }}/{{ $r->pressao_diastolica }} mmHg</span></div>@endif
                            @if($r->teste_caminhada_dist)<div class="dado-item"><label>Caminhada 6 min</label><span>{{ number_format($r->teste_caminhada_dist, 0) }} m</span></div>@endif
                            @if($r->teste_cooper_dist)<div class="dado-item"><label>Cooper 12 min</label><span>{{ number_format($r->teste_cooper_dist, 0) }} m</span></div>@endif
                            @if($r->teste_rockport_tempo)<div class="dado-item"><label>Rockport</label><span>{{ number_format($r->teste_rockport_tempo, 2, ',', '.') }} min</span></div>@endif
                            @if($r->vo2max_estimado)<div class="dado-item"><label>VO2max</label><span>{{ number_format($r->vo2max_estimado, 1, ',', '.') }} ml/kg/min</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->flexao_braco_reps || $r->prancha_tempo || $r->forca || $r->testes_submax)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-dumbbell"></i> Força</p>
                        <div class="snrfit-detail-grid">
                            @if($r->flexao_braco_reps)<div class="dado-item"><label>Flexão de Braço</label><span>{{ $r->flexao_braco_reps }} reps</span></div>@endif
                            @if($r->prancha_tempo)<div class="dado-item"><label>Prancha</label><span>{{ $r->prancha_tempo }}s</span></div>@endif
                            @if($r->forca)<div class="dado-item"><label>Dinamometria</label><span>{{ number_format($r->forca, 1, ',', '.') }} kgf</span></div>@endif
                            @if($r->testes_submax)<div class="dado-item texto" style="grid-column:1/-1;"><label>Testes Submáximos</label><span>{{ $r->testes_submax }}</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->func_agachamento || $r->func_avanco || $r->func_stepup || $r->func_prancha || $r->func_mob_toracica)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-running"></i> Funcional</p>
                        <div class="snrfit-detail-grid">
                            @if($r->func_agachamento)<div class="dado-item texto"><label>Agachamento</label><span>{{ $r->func_agachamento }}</span></div>@endif
                            @if($r->func_avanco)<div class="dado-item texto"><label>Avanço</label><span>{{ $r->func_avanco }}</span></div>@endif
                            @if($r->func_stepup)<div class="dado-item texto"><label>Step-up</label><span>{{ $r->func_stepup }}</span></div>@endif
                            @if($r->func_prancha)<div class="dado-item texto"><label>Prancha</label><span>{{ $r->func_prancha }}</span></div>@endif
                            @if($r->func_mob_toracica)<div class="dado-item texto"><label>Mob. Torácica</label><span>{{ $r->func_mob_toracica }}</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->dor_lombar !== null || $r->dor_ombro !== null || $r->dor_joelho !== null || $r->dor_quadril !== null || $r->dor_cervical !== null)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-triangle-exclamation"></i> Dor</p>
                        <div class="snrfit-detail-grid">
                            @foreach(['dor_lombar'=>'Lombar','dor_ombro'=>'Ombro','dor_joelho'=>'Joelho','dor_quadril'=>'Quadril','dor_cervical'=>'Cervical'] as $campo => $label)
                                @if($r->$campo !== null)
                                @php $dv = $r->$campo; @endphp
                                <div class="dado-item">
                                    <label>{{ $label }}</label>
                                    <span class="{{ $dv <= 2 ? 'dor-0' : ($dv <= 5 ? 'dor-3' : ($dv <= 8 ? 'dor-6' : 'dor-9')) }}">{{ $dv }}/10</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($r->observacoes)
                    <div style="margin-top:14px;" class="dado-item texto">
                        <label>Observações Gerais</label>
                        <span>{{ $r->observacoes }}</span>
                    </div>
                    @endif
                </details>
            </div>
            @endforeach
        @endif

    @elseif($registros->isEmpty())
        <div class="empty-state">
            <i class="fas fa-clipboard"></i>
            <p>Nenhum registro encontrado{{ $tipoFiltro || $mesFiltro ? ' para esse filtro' : '' }}.<br>Clique em "Novo Registro" para começar.</p>
        </div>
    @else
        @foreach($registros as $r)
        <div class="card">
            <div class="registro-head">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span class="tipo-badge"><i class="fas {{ $tipos[$r->tipo]['icon'] ?? 'fa-clipboard' }}"></i> {{ $tipos[$r->tipo]['label'] ?? $r->tipo }}</span>
                    <span class="data-registro"><i class="far fa-calendar-alt"></i> {{ $r->data_avaliacao->format('d/m/Y') }}</span>
                </div>
                <form action="{{ route('personal.avaliacao-fisica.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Excluir este registro?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i></button>
                </form>
            </div>

            <div style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
                @if($r->tipo === 'antes_depois' && $r->foto)
                    <img src="{{ asset('storage/'.$r->foto) }}" class="foto-thumb" onclick="abrirLightbox('{{ asset('storage/'.$r->foto) }}')">
                @endif

                <div class="dados-grid" style="flex:1; min-width:200px;">
                    @if($r->tipo === 'antes_depois')
                        @if(!is_null($r->peso))
                        <div class="dado-item"><label>Peso</label><span>{{ rtrim(rtrim(number_format($r->peso, 2, ',', '.'), '0'), ',') }} kg</span></div>
                        @endif
                        @if($r->medidas)
                        <div class="dado-item texto" style="grid-column:1/-1;"><label>Medidas</label><span>{{ $r->medidas }}</span></div>
                        @endif
                    @elseif($r->tipo === 'dinamometro')
                        <div class="dado-item"><label>Força</label><span>{{ rtrim(rtrim(number_format($r->forca, 2, ',', '.'), '0'), ',') }} kgf</span></div>
                    @elseif($r->tipo === 'oximetro')
                        <div class="dado-item"><label>SpO2</label><span>{{ $r->spo2 }}%</span></div>
                        @if(!is_null($r->bpm))
                        <div class="dado-item"><label>Batimentos</label><span>{{ $r->bpm }} bpm</span></div>
                        @endif
                    @elseif($r->tipo === 'pressao_arterial')
                        <div class="dado-item"><label>Pressão Arterial</label><span>{{ $r->pressao_sistolica }}/{{ $r->pressao_diastolica }} mmHg</span></div>
                    @elseif($r->tipo === 'bioimpedancia')
                        <div class="dado-item" style="display:flex; flex-direction:column; gap:8px; align-items:flex-start;">
                            <label>Relatório de Bioimpedância</label>
                            @if($r->arquivo)
                                <a href="{{ asset('storage/'.$r->arquivo) }}" target="_blank" class="btn-pdf"><i class="fas fa-file-pdf"></i> Abrir PDF</a>
                            @else
                                <span style="color:var(--text-muted); font-size:0.8rem; font-weight:500;">Sem arquivo anexado</span>
                            @endif
                        </div>
                    @endif

                    @if($r->observacoes)
                    <div class="dado-item texto" style="grid-column:1/-1;"><label>Observações</label><span>{{ $r->observacoes }}</span></div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    @endif
</div>

{{-- MODAL NOVO REGISTRO --}}
<div id="modalRegistro" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close" onclick="fecharModalRegistro()"><i class="fas fa-times"></i></button>
        <h2><i class="fas fa-plus-circle"></i> Novo Registro</h2>

        <form action="{{ route('personal.avaliacao-fisica.store', $cliente->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de Avaliação</label>
                    <select name="tipo" id="selectTipo" onchange="trocarCampos()" required>
                        <option value="antes_depois">Antes e Depois</option>
                        <option value="dinamometro">Dinamômetro</option>
                        <option value="oximetro">Oxímetro</option>
                        <option value="pressao_arterial">Pressão Arterial</option>
                        <option value="bioimpedancia">Bioimpedância</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data da Avaliação</label>
                    <input type="date" name="data_avaliacao" value="{{ now()->format('Y-m-d') }}" required>
                </div>
            </div>

            <div id="campos-antes_depois">
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

            <div id="campos-dinamometro" style="display:none;">
                <div class="form-group">
                    <label>Força Medida (kgf)</label>
                    <input type="number" step="0.01" min="0" name="forca" placeholder="Ex: 42.5">
                </div>
            </div>

            <div id="campos-oximetro" style="display:none;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Saturação SpO2 (%)</label>
                        <input type="number" min="0" max="100" name="spo2" placeholder="Ex: 98">
                    </div>
                    <div class="form-group">
                        <label>Batimentos (bpm)</label>
                        <input type="number" min="0" max="300" name="bpm" placeholder="Ex: 72">
                    </div>
                </div>
            </div>

            <div id="campos-bioimpedancia" style="display:none;">
                <div class="form-group">
                    <label>Relatório de Bioimpedância (PDF)</label>
                    <input type="file" name="arquivo" accept="application/pdf">
                </div>
            </div>

            <div id="campos-pressao_arterial" style="display:none;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Sistólica (mmHg)</label>
                        <input type="number" min="0" max="400" name="pressao_sistolica" placeholder="Ex: 120">
                    </div>
                    <div class="form-group">
                        <label>Diastólica (mmHg)</label>
                        <input type="number" min="0" max="300" name="pressao_diastolica" placeholder="Ex: 80">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Observações</label>
                <textarea name="observacoes" rows="2" placeholder="Anotações sobre a avaliação (opcional)"></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; justify-content:center;"><i class="fas fa-save"></i> Salvar Registro</button>
        </form>
    </div>
</div>

{{-- MODAL SNR FIT TECH --}}
<div id="modalSNR" class="modal-overlay modal-snrfit">
    <div class="modal-content">
        <button class="modal-close" onclick="fecharModalSNR()"><i class="fas fa-times"></i></button>
        <h2><i class="fas fa-clipboard-list"></i> Avaliação SNR Fit Tech</h2>

        <form action="{{ route('personal.avaliacao-fisica.store', $cliente->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="tipo" value="completa">

            {{-- SEÇÃO 1: Anamnese --}}
            <details class="snr-section" open>
                <summary>
                    <span class="sec-num">1</span>
                    <i class="fas fa-file-medical" style="color:var(--primary);"></i>
                    Anamnese Inicial
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
                    <div class="form-group">
                        <label>Data da Avaliação *</label>
                        <input type="date" name="data_avaliacao" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Objetivo Principal</label>
                        <textarea name="objetivo_principal" rows="2" placeholder="Ex: Perda de gordura, ganho de massa muscular, condicionamento..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Histórico de Atividade Física</label>
                        <textarea name="historico_atividade" rows="2" placeholder="Ex: Pratica musculação há 2 anos, parou por 6 meses..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Lesões</label>
                            <textarea name="lesoes" rows="2" placeholder="Ex: Lesão no joelho direito (2020)..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Cirurgias</label>
                            <textarea name="cirurgias" rows="2" placeholder="Ex: Meniscectomia (2021)..."></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Medicamentos em Uso</label>
                            <textarea name="medicamentos" rows="2" placeholder="Medicamentos relevantes..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Restrições Médicas</label>
                            <textarea name="restricoes_medicas" rows="2" placeholder="Ex: Não pode fazer impacto..."></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Hábitos de Sono</label>
                            <input type="text" name="habitos_sono" placeholder="Ex: Dorme 7h, acorda cansado">
                        </div>
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
            </details>

            {{-- SEÇÃO 2: Antropométrica --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">2</span>
                    <i class="fas fa-ruler" style="color:var(--primary);"></i>
                    Avaliação Antropométrica
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
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
                    <p style="font-size:0.65rem; text-transform:uppercase; font-weight:800; color:var(--text-muted); margin:8px 0;">Circunferências (cm)</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cintura</label>
                            <input type="number" step="0.1" min="0" max="300" name="circ_cintura" placeholder="cm">
                        </div>
                        <div class="form-group">
                            <label>Abdômen</label>
                            <input type="number" step="0.1" min="0" max="300" name="circ_abdomen" placeholder="cm">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Quadril</label>
                            <input type="number" step="0.1" min="0" max="300" name="circ_quadril" placeholder="cm">
                        </div>
                        <div class="form-group">
                            <label>Tórax</label>
                            <input type="number" step="0.1" min="0" max="300" name="circ_torax" placeholder="cm">
                        </div>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Braço</label>
                            <input type="number" step="0.1" min="0" max="200" name="circ_braco" placeholder="cm">
                        </div>
                        <div class="form-group">
                            <label>Coxa</label>
                            <input type="number" step="0.1" min="0" max="200" name="circ_coxa" placeholder="cm">
                        </div>
                        <div class="form-group">
                            <label>Panturrilha</label>
                            <input type="number" step="0.1" min="0" max="200" name="circ_panturrilha" placeholder="cm">
                        </div>
                    </div>
                </div>
            </details>

            {{-- SEÇÃO 3: Dobras Cutâneas --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">3</span>
                    <i class="fas fa-compress-arrows-alt" style="color:var(--primary);"></i>
                    Dobras Cutâneas
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
                    <div class="form-group">
                        <label>Protocolo</label>
                        <select name="protocolo_dobras" id="snr-protocolo" onchange="calcPollock()">
                            <option value="">Selecione...</option>
                            <option value="pollock3">Pollock 3 dobras</option>
                            <option value="pollock7">Pollock 7 dobras</option>
                            <option value="jackson_pollock">Jackson & Pollock</option>
                            <option value="outro">Outro protocolo</option>
                        </select>
                    </div>
                    <p style="font-size:0.65rem; text-transform:uppercase; font-weight:800; color:var(--text-muted); margin:8px 0;">Dobras (mm)</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tríceps</label>
                            <input type="number" step="0.1" min="0" max="200" name="dobra_triceps" id="snr-d-triceps" placeholder="mm" oninput="calcPollock()">
                        </div>
                        <div class="form-group">
                            <label>Bíceps</label>
                            <input type="number" step="0.1" min="0" max="200" name="dobra_biceps" placeholder="mm">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Subescapular</label>
                            <input type="number" step="0.1" min="0" max="200" name="dobra_subescapular" id="snr-d-sub" placeholder="mm" oninput="calcPollock()">
                        </div>
                        <div class="form-group">
                            <label>Suprailíaca</label>
                            <input type="number" step="0.1" min="0" max="200" name="dobra_suprailiaca" id="snr-d-supra" placeholder="mm" oninput="calcPollock()">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Abdominal</label>
                            <input type="number" step="0.1" min="0" max="200" name="dobra_abdominal" id="snr-d-abd" placeholder="mm" oninput="calcPollock()">
                        </div>
                        <div class="form-group">
                            <label>Coxa</label>
                            <input type="number" step="0.1" min="0" max="200" name="dobra_coxa_dc" id="snr-d-coxa" placeholder="mm" oninput="calcPollock()">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Peitoral</label>
                            <input type="number" step="0.1" min="0" max="200" name="dobra_peitoral" id="snr-d-peit" placeholder="mm" oninput="calcPollock()">
                        </div>
                        <div class="form-group">
                            <label>Axilar Média</label>
                            <input type="number" step="0.1" min="0" max="200" name="dobra_axilar_media" id="snr-d-axilar" placeholder="mm" oninput="calcPollock()">
                        </div>
                    </div>
                    <div class="form-row-3" style="margin-top:8px;">
                        <div class="form-group">
                            <label>% Gordura (calc.)</label>
                            <input type="number" step="0.01" name="percentual_gordura" id="snr-pg" placeholder="Auto" readonly>
                        </div>
                        <div class="form-group">
                            <label>Massa Gorda (kg)</label>
                            <input type="number" step="0.01" name="massa_gorda" id="snr-mg" placeholder="Auto" readonly>
                        </div>
                        <div class="form-group">
                            <label>Massa Magra (kg)</label>
                            <input type="number" step="0.01" name="massa_magra" id="snr-mm" placeholder="Auto" readonly>
                        </div>
                    </div>
                </div>
            </details>

            {{-- SEÇÃO 4: Postural --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">4</span>
                    <i class="fas fa-person" style="color:var(--primary);"></i>
                    Avaliação Postural
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
                    <div class="postural-foto-grid">
                        <div class="form-group">
                            <label><i class="fas fa-arrow-up"></i> Foto Anterior</label>
                            <input type="file" name="foto_anterior" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-arrow-down"></i> Foto Posterior</label>
                            <input type="file" name="foto_posterior" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-arrow-right"></i> Lateral Direita</label>
                            <input type="file" name="foto_lateral_direita" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-arrow-left"></i> Lateral Esquerda</label>
                            <input type="file" name="foto_lateral_esquerda" accept="image/*">
                        </div>
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
            </details>

            {{-- SEÇÃO 5: Neuromotora --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">5</span>
                    <i class="fas fa-brain" style="color:var(--primary);"></i>
                    Avaliação Neuromotora
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Equilíbrio Unipodal</label>
                            <input type="text" name="equil_unipodal" placeholder="Ex: 30s D / 25s E">
                        </div>
                        <div class="form-group">
                            <label>Coordenação Motora</label>
                            <select name="coordenacao_motora">
                                <option value="">Selecione...</option>
                                <option value="Boa">Boa</option>
                                <option value="Regular">Regular</option>
                                <option value="Ruim">Ruim</option>
                            </select>
                        </div>
                    </div>
                    <p style="font-size:0.65rem; text-transform:uppercase; font-weight:800; color:var(--text-muted); margin:6px 0;">Mobilidade</p>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Ombro</label>
                            <select name="mob_ombro">
                                <option value="">—</option>
                                <option value="Boa">Boa</option>
                                <option value="Regular">Regular</option>
                                <option value="Ruim">Ruim</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quadril</label>
                            <select name="mob_quadril">
                                <option value="">—</option>
                                <option value="Boa">Boa</option>
                                <option value="Regular">Regular</option>
                                <option value="Ruim">Ruim</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tornozelo</label>
                            <select name="mob_tornozelo">
                                <option value="">—</option>
                                <option value="Boa">Boa</option>
                                <option value="Regular">Regular</option>
                                <option value="Ruim">Ruim</option>
                            </select>
                        </div>
                    </div>
                    <p style="font-size:0.65rem; text-transform:uppercase; font-weight:800; color:var(--text-muted); margin:6px 0;">Agachamento</p>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Profundidade</label>
                            <select name="agach_profundidade">
                                <option value="">—</option>
                                <option value="Completo">Completo</option>
                                <option value="Parcial">Parcial</option>
                                <option value="Limitado">Limitado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estabilidade</label>
                            <select name="agach_estabilidade">
                                <option value="">—</option>
                                <option value="Boa">Boa</option>
                                <option value="Regular">Regular</option>
                                <option value="Ruim">Ruim</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Simetria</label>
                            <select name="agach_simetria">
                                <option value="">—</option>
                                <option value="Simétrico">Simétrico</option>
                                <option value="Assimétrico">Assimétrico</option>
                            </select>
                        </div>
                    </div>
                </div>
            </details>

            {{-- SEÇÃO 6: Flexibilidade --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">6</span>
                    <i class="fas fa-person-walking" style="color:var(--primary);"></i>
                    Avaliação de Flexibilidade
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Sentar e Alcançar (cm)</label>
                            <input type="number" step="0.1" min="-50" max="100" name="flex_sentar_alcancar" placeholder="Ex: 18.5">
                        </div>
                        <div class="form-group">
                            <label>Flexibilidade Ombros</label>
                            <select name="flex_ombros">
                                <option value="">—</option>
                                <option value="Boa">Boa</option>
                                <option value="Regular">Regular</option>
                                <option value="Ruim">Ruim</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Flexibilidade Quadril</label>
                            <input type="number" step="0.1" min="0" max="200" name="flex_quadril" placeholder="Graus ou cm">
                        </div>
                    </div>
                </div>
            </details>

            {{-- SEÇÃO 7: Cardiorrespiratória --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">7</span>
                    <i class="fas fa-heart-pulse" style="color:var(--primary);"></i>
                    Avaliação Cardiorrespiratória
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>FC Repouso (bpm)</label>
                            <input type="number" min="0" max="300" name="bpm" placeholder="Ex: 68">
                        </div>
                        <div class="form-group">
                            <label>PA Sistólica (mmHg)</label>
                            <input type="number" min="0" max="400" name="pressao_sistolica" placeholder="Ex: 120">
                        </div>
                        <div class="form-group">
                            <label>PA Diastólica (mmHg)</label>
                            <input type="number" min="0" max="300" name="pressao_diastolica" placeholder="Ex: 80">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Caminhada 6 min (metros)</label>
                            <input type="number" step="0.1" min="0" max="9999" name="teste_caminhada_dist" placeholder="Ex: 540">
                        </div>
                        <div class="form-group">
                            <label>Cooper 12 min (metros)</label>
                            <input type="number" step="0.1" min="0" max="9999" name="teste_cooper_dist" id="snr-cooper" placeholder="Ex: 2400" oninput="calcVO2()">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Rockport — Tempo (min)</label>
                            <input type="number" step="0.01" min="0" max="999" name="teste_rockport_tempo" placeholder="Ex: 14.5">
                        </div>
                        <div class="form-group">
                            <label>VO2max estimado (ml/kg/min)</label>
                            <input type="number" step="0.01" name="vo2max_estimado" id="snr-vo2" placeholder="Auto via Cooper" readonly>
                        </div>
                    </div>
                </div>
            </details>

            {{-- SEÇÃO 8: Força --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">8</span>
                    <i class="fas fa-dumbbell" style="color:var(--primary);"></i>
                    Avaliação de Força
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Flexão de Braço (reps)</label>
                            <input type="number" min="0" max="9999" name="flexao_braco_reps" placeholder="Ex: 30">
                        </div>
                        <div class="form-group">
                            <label>Prancha (segundos)</label>
                            <input type="number" min="0" max="9999" name="prancha_tempo" placeholder="Ex: 90">
                        </div>
                        <div class="form-group">
                            <label>Dinamometria (kgf)</label>
                            <input type="number" step="0.01" min="0" max="500" name="forca" placeholder="Ex: 42.5">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Testes Submáximos</label>
                        <textarea name="testes_submax" rows="2" placeholder="Descrição de testes submáximos realizados..."></textarea>
                    </div>
                </div>
            </details>

            {{-- SEÇÃO 9: Funcional --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">9</span>
                    <i class="fas fa-running" style="color:var(--primary);"></i>
                    Avaliação Funcional
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
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
            </details>

            {{-- SEÇÃO 10: Dor --}}
            <details class="snr-section">
                <summary>
                    <span class="sec-num">10</span>
                    <i class="fas fa-triangle-exclamation" style="color:var(--primary);"></i>
                    Avaliação de Dor
                    <i class="fas fa-chevron-down sec-arrow"></i>
                </summary>
                <div class="snr-section-body">
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
                            <input type="range" name="{{ $dor['name'] }}" min="0" max="10" value="0"
                                oninput="atualizarDor('{{ $dor['id'] }}', this.value)">
                            <span class="range-val" id="{{ $dor['id'] }}-val">0</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </details>

            {{-- Observações gerais --}}
            <div class="form-group" style="margin-top:16px;">
                <label>Observações Gerais</label>
                <textarea name="observacoes" rows="2" placeholder="Anotações adicionais sobre a avaliação..."></textarea>
            </div>

            <button type="submit" class="btn-snrfit" style="width:100%; justify-content:center; margin-top:8px;"><i class="fas fa-save"></i> Salvar Avaliação SNR Fit Tech</button>
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
        @if($tipoFiltro && $tipoFiltro !== 'resumo' && $tipoFiltro !== 'completa')
        document.getElementById('selectTipo').value = '{{ $tipoFiltro }}';
        @endif
        trocarCampos();
        document.getElementById('modalRegistro').style.display = 'flex';
    }
    function fecharModalRegistro() {
        document.getElementById('modalRegistro').style.display = 'none';
    }
    function abrirModalSNR() {
        document.getElementById('modalSNR').style.display = 'flex';
    }
    function fecharModalSNR() {
        document.getElementById('modalSNR').style.display = 'none';
    }

    function trocarCampos() {
        const tipo = document.getElementById('selectTipo').value;
        ['antes_depois', 'dinamometro', 'oximetro', 'pressao_arterial', 'bioimpedancia'].forEach(t => {
            document.getElementById('campos-' + t).style.display = (t === tipo) ? 'block' : 'none';
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

    // Calcular IMC automaticamente
    function calcIMC() {
        const peso   = parseFloat(document.getElementById('snr-peso').value);
        const altura = parseFloat(document.getElementById('snr-altura').value);
        const imcEl  = document.getElementById('snr-imc');
        if (peso > 0 && altura > 0) {
            const altM = altura / 100;
            const imc  = peso / (altM * altM);
            imcEl.value = imc.toFixed(2);
        } else {
            imcEl.value = '';
        }
        calcPollock(); // recalc massa gorda/magra com novo peso
    }

    // Calcular % gordura via Pollock
    function calcPollock() {
        const protocolo = document.getElementById('snr-protocolo').value;
        const pgEl = document.getElementById('snr-pg');
        const mgEl = document.getElementById('snr-mg');
        const mmEl = document.getElementById('snr-mm');
        const pesoVal = parseFloat(document.getElementById('snr-peso').value);
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
        } else {
            if (!pgEl.value || protocolo) {
                // only clear if protocol is set but D failed
            }
        }
    }

    // Calcular VO2max via Cooper
    function calcVO2() {
        const dist = parseFloat(document.getElementById('snr-cooper').value);
        const vo2El = document.getElementById('snr-vo2');
        if (dist > 0) {
            const vo2 = (dist - 504.9) / 44.73;
            vo2El.value = Math.max(0, vo2).toFixed(2);
        } else {
            vo2El.value = '';
        }
    }

    // Atualizar display de dor com cor
    function atualizarDor(spanId, val) {
        const el = document.getElementById(spanId);
        const n = parseInt(val);
        el.textContent = n;
        el.className = n <= 2 ? 'dor-0' : (n <= 5 ? 'dor-3' : (n <= 8 ? 'dor-6' : 'dor-9'));
        document.getElementById(spanId + '-val').textContent = n;
    }

    // Fechar modais ao clicar fora
    document.getElementById('modalRegistro').addEventListener('click', function(e) {
        if (e.target === this) fecharModalRegistro();
    });
    document.getElementById('modalSNR').addEventListener('click', function(e) {
        if (e.target === this) fecharModalSNR();
    });

    // Abrir modal SNR se o filtro ativo for 'completa' ao clicar em Novo Registro
    // (botão SNR está separado, mas manter comportamento do botão "Novo Registro" padrão)
</script>

</body>
</html>
