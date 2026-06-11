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

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(6px); padding: 20px; }
        .modal-content { background: var(--card-bg); border-radius: 24px; border: 1px solid var(--border); padding: 30px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal-content h2 { color: var(--primary); font-size: 1.2rem; font-weight: 900; margin: 0 0 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: var(--text-muted); font-size: 0.65rem; text-transform: uppercase; font-weight: 800; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid var(--border); color: #fff; padding: 12px 14px; border-radius: 10px; font-size: 0.9rem; outline: none; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary); }
        .form-group select option { background: var(--card-bg); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .modal-close { position: absolute; top: 18px; right: 22px; cursor: pointer; color: var(--text-muted); font-size: 1.2rem; background: none; border: none; }
        .modal-close:hover { color: var(--error); }

        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.98); z-index: 99999; justify-content: center; align-items: center; cursor: zoom-out; }
        #lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 12px; object-fit: contain; }

        @media (max-width: 700px) {
            .tipo-tabs { grid-template-columns: repeat(2, 1fr); }
            .top-bar { padding: 15px 20px; }
            .form-row { grid-template-columns: 1fr; }
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
        <button class="btn-primary" onclick="abrirModalRegistro()"><i class="fas fa-plus"></i> Novo Registro</button>
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
        <a href="{{ route('personal.avaliacao-fisica.aluno', $cliente->id) }}?tipo={{ $key }}{{ $mesFiltro ? '&mes='.$mesFiltro : '' }}" class="tipo-tab {{ $tipoFiltro === $key ? 'active' : '' }}">
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

{{-- LIGHTBOX --}}
<div id="lightbox" onclick="fecharLightbox()">
    <img id="lightboxImg">
</div>

<script>
    function abrirModalRegistro() {
        @if($tipoFiltro && $tipoFiltro !== 'resumo')
        document.getElementById('selectTipo').value = '{{ $tipoFiltro }}';
        @endif
        trocarCampos();
        document.getElementById('modalRegistro').style.display = 'flex';
    }

    function fecharModalRegistro() {
        document.getElementById('modalRegistro').style.display = 'none';
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

    document.getElementById('modalRegistro').addEventListener('click', function(e) {
        if (e.target === this) fecharModalRegistro();
    });
</script>

</body>
</html>
