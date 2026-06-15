<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Avaliação Física — SnrFit</title>
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
        .tipo-tab.snrfit-tab { border-color: rgba(212,255,0,0.3); }
        .tipo-tab.snrfit-tab.active { background: rgba(212,255,0,0.15); }

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

        /* SNR Fit Tech card styles */
        .snrfit-card { border-color: rgba(212,255,0,0.25); }
        .snrfit-card:hover { border-color: rgba(212,255,0,0.5); }
        .snrfit-badge { padding: 5px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; background: rgba(212,255,0,0.15); color: var(--primary); border: 1px solid rgba(212,255,0,0.4); }
        .modulos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; margin-top: 12px; }
        .modulo-chip { padding: 6px 12px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 8px; font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .modulo-chip i { color: var(--primary); }
        .snrfit-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-top: 10px; }
        .snrfit-expand { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; padding: 12px 16px; margin-top: 14px; font-size: 0.8rem; }
        .snrfit-expand summary { cursor: pointer; font-weight: 800; color: var(--text-muted); list-style: none; display: flex; align-items: center; gap: 8px; }
        .snrfit-expand summary::-webkit-details-marker { display: none; }
        .snrfit-expand[open] summary { color: var(--primary); }

        /* Dor colors */
        .dor-0 { color: #00ff88; }
        .dor-3 { color: #ffdd00; }
        .dor-6 { color: #ff8800; }
        .dor-9 { color: #ff4444; }

        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.98); z-index: 99999; justify-content: center; align-items: center; cursor: zoom-out; }
        #lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 12px; object-fit: contain; }

        @media (max-width: 700px) {
            .tipo-tabs { grid-template-columns: repeat(2, 1fr); }
            .top-bar { padding: 15px 20px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('cliente.index') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
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
    <h1 class="page-title"><i class="fas fa-heart-pulse" style="margin-right:10px;"></i>Minha Avaliação Física</h1>
    <p class="page-sub">Acompanhe os registros que seu personal fez sobre sua evolução.</p>

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
        <a href="{{ route('cliente.avaliacao-fisica') }}{{ $mesFiltro ? '?mes='.$mesFiltro : '' }}" class="tipo-tab {{ !$tipoFiltro ? 'active' : '' }}">
            <i class="fas fa-list"></i> Todos
        </a>
        @foreach($tipos as $key => $t)
        <a href="{{ route('cliente.avaliacao-fisica') }}?tipo={{ $key }}{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $key === 'completa' ? 'snrfit-tab' : '' }} {{ $tipoFiltro === $key ? 'active' : '' }}">
            <i class="fas {{ $t['icon'] }}"></i> {{ $t['label'] }}
        </a>
        @endforeach
        <a href="{{ route('cliente.avaliacao-fisica') }}?tipo=resumo{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $tipoFiltro === 'resumo' ? 'active' : '' }}">
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
                <p>Nenhum dado para montar o resumo{{ $mesFiltro ? ' nesse mês' : '' }}.<br>Seu personal ainda não registrou avaliações nessas categorias.</p>
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
        {{-- LISTAGEM SNR FIT TECH para o cliente (read-only) --}}
        @if($registros->isEmpty())
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>Nenhuma avaliação SNR Fit Tech encontrada{{ $mesFiltro ? ' nesse mês' : '' }}.<br>Seu personal ainda não realizou essa avaliação.</p>
            </div>
        @else
            <p style="color:var(--text-muted); font-size:0.8rem; margin:0 0 16px;"><i class="fas fa-info-circle"></i> Avaliação física completa realizada pelo seu personal.</p>
            @foreach($registros as $r)
            <div class="card snrfit-card">
                <div class="registro-head">
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="snrfit-badge"><i class="fas fa-clipboard-list"></i> SNR Fit Tech</span>
                        <span class="data-registro"><i class="far fa-calendar-alt"></i> {{ $r->data_avaliacao->format('d/m/Y') }}</span>
                    </div>
                    @if($r->personal)
                        <span class="personal-tag"><i class="fas fa-user-tie"></i> {{ $r->personal->nome }}</span>
                    @endif
                </div>

                {{-- Módulos presentes --}}
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
                <details class="snrfit-expand">
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

                    @if($r->protocolo_dobras || $r->dobra_triceps || $r->percentual_gordura)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-compress-arrows-alt"></i> Dobras Cutâneas</p>
                        @if($r->protocolo_dobras)<p style="font-size:0.75rem; color:var(--text-muted); margin:0 0 8px;">Protocolo: <strong style="color:#fff;">{{ $r->protocolo_dobras }}</strong></p>@endif
                        <div class="snrfit-detail-grid">
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
                            @if($r->coordenacao_motora)<div class="dado-item texto"><label>Coordenação</label><span>{{ $r->coordenacao_motora }}</span></div>@endif
                            @if($r->mob_ombro)<div class="dado-item texto"><label>Mob. Ombro</label><span>{{ $r->mob_ombro }}</span></div>@endif
                            @if($r->mob_quadril)<div class="dado-item texto"><label>Mob. Quadril</label><span>{{ $r->mob_quadril }}</span></div>@endif
                            @if($r->mob_tornozelo)<div class="dado-item texto"><label>Mob. Tornozelo</label><span>{{ $r->mob_tornozelo }}</span></div>@endif
                            @if($r->agach_profundidade)<div class="dado-item texto"><label>Agach. Profundidade</label><span>{{ $r->agach_profundidade }}</span></div>@endif
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

                    @if($r->bpm || $r->pressao_sistolica || $r->teste_cooper_dist || $r->vo2max_estimado)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-heart-pulse"></i> Cardiorrespiratória</p>
                        <div class="snrfit-detail-grid">
                            @if($r->bpm)<div class="dado-item"><label>FC Repouso</label><span>{{ $r->bpm }} bpm</span></div>@endif
                            @if($r->pressao_sistolica)<div class="dado-item"><label>Pressão Arterial</label><span>{{ $r->pressao_sistolica }}/{{ $r->pressao_diastolica }} mmHg</span></div>@endif
                            @if($r->teste_caminhada_dist)<div class="dado-item"><label>Caminhada 6min</label><span>{{ number_format($r->teste_caminhada_dist, 0) }} m</span></div>@endif
                            @if($r->teste_cooper_dist)<div class="dado-item"><label>Cooper 12min</label><span>{{ number_format($r->teste_cooper_dist, 0) }} m</span></div>@endif
                            @if($r->vo2max_estimado)<div class="dado-item"><label>VO2max</label><span>{{ number_format($r->vo2max_estimado, 1, ',', '.') }} ml/kg/min</span></div>@endif
                        </div>
                    </div>
                    @endif

                    @if($r->flexao_braco_reps || $r->prancha_tempo || $r->forca)
                    <div style="margin-top:14px;">
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-dumbbell"></i> Força</p>
                        <div class="snrfit-detail-grid">
                            @if($r->flexao_braco_reps)<div class="dado-item"><label>Flexão de Braço</label><span>{{ $r->flexao_braco_reps }} reps</span></div>@endif
                            @if($r->prancha_tempo)<div class="dado-item"><label>Prancha</label><span>{{ $r->prancha_tempo }}s</span></div>@endif
                            @if($r->forca)<div class="dado-item"><label>Dinamometria</label><span>{{ number_format($r->forca, 1, ',', '.') }} kgf</span></div>@endif
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
                        <p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:0 0 8px;"><i class="fas fa-triangle-exclamation"></i> Avaliação de Dor</p>
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
            <p>Nenhum registro encontrado{{ $tipoFiltro || $mesFiltro ? ' para esse filtro' : '' }}.<br>Seu personal ainda não adicionou avaliações aqui.</p>
        </div>
    @else
        @foreach($registros as $r)
        <div class="card">
            <div class="registro-head">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span class="tipo-badge"><i class="fas {{ $tipos[$r->tipo]['icon'] ?? 'fa-clipboard' }}"></i> {{ $tipos[$r->tipo]['label'] ?? $r->tipo }}</span>
                    <span class="data-registro"><i class="far fa-calendar-alt"></i> {{ $r->data_avaliacao->format('d/m/Y') }}</span>
                </div>
                @if($r->personal)
                    <span class="personal-tag"><i class="fas fa-user-tie"></i> {{ $r->personal->nome }}</span>
                @endif
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
