<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periodização · {{ $cliente->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">
    <style>
        :root {
            --primary: var(--snr-lime); --bg-dark: var(--snr-bg); --card-bg: var(--snr-surface); --field: var(--snr-surface-2);
            --text-main: var(--snr-text); --text-muted: var(--snr-dim); --green: var(--snr-success); --red: var(--snr-error);
            --border: var(--snr-border);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-dark); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: var(--text-main); min-height: 100vh; background-image: radial-gradient(circle at 12% -10%, rgba(212, 255, 0, 0.10), transparent 45%); }
        a { color: inherit; text-decoration: none; }
        .top-bar { display: flex; align-items: center; gap: 15px; padding: 15px 40px; background: rgba(0,0,0,0.6); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .back-btn { background: var(--card-bg); border: 1px solid var(--border); color: var(--primary); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: 0.3s; }
        .back-btn:hover { background: var(--primary); color: #000; }
        .top-bar .title { font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .top-bar .title i { color: var(--primary); }
        .top-bar .spacer { flex: 1; }
        .btn-top { background: transparent; border: 1px solid var(--border); color: var(--text-main); padding: 9px 16px; border-radius: 8px; font-weight: 700; font-size: 0.78rem; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }
        .container { max-width: 900px; margin: 28px auto; padding: 0 20px; }
        h1 { font-size: 1.6rem; font-weight: 900; color: var(--primary); margin-bottom: 22px; display: flex; align-items: center; gap: 10px; }

        .alert { padding: 14px; border-radius: 12px; margin-bottom: 18px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(0,230,118,0.1); color: var(--green); border: 1px solid var(--green); }
        .alert-error { background: rgba(255,82,82,0.12); color: var(--red); border: 1px solid var(--red); }

        .panel { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .panel > .panel-title { font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary); font-weight: 900; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

        label { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 900; display: block; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 11px; background: var(--field); border: 1px solid rgba(255,255,255,0.1); color: var(--text-main); border-radius: 9px; font-size: 0.92rem; font-family: inherit; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); }
        .row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .row > .fg { flex: 1; min-width: 140px; }
        .radio-row { display: flex; gap: 10px; margin-bottom: 14px; }
        .radio-row label.opt { flex: 1; display: flex; align-items: center; gap: 8px; background: var(--field); border: 1px solid rgba(255,255,255,0.1); border-radius: 9px; padding: 11px; cursor: pointer; text-transform: none; letter-spacing: 0; font-weight: 700; color: var(--text-main); font-size: 0.85rem; margin: 0; }
        .radio-row input { width: auto; accent-color: var(--primary); }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border: none; border-radius: 10px; font-weight: 900; font-size: 0.82rem; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: var(--primary); color: #000; }
        .btn-primary:hover { filter: brightness(1.1); }
        .btn-ghost { background: var(--field); color: var(--text-main); border: 1px solid var(--border); }
        .btn-ghost:hover { background: rgba(255,255,255,0.06); }
        .btn-danger { background: rgba(255,82,82,0.12); color: var(--red); border: 1px solid var(--red); }
        .btn-sm { padding: 8px 12px; font-size: 0.72rem; }

        .meso { border: 1px solid var(--border); border-radius: 16px; margin-bottom: 18px; overflow: hidden; }
        .meso.venc { border-color: rgba(255,82,82,0.5); }
        .meso-head { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 16px 18px; background: rgba(255,255,255,0.02); }
        .meso-head .nome { font-weight: 900; font-size: 1.05rem; }
        .meso-head .meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
        .badge { font-size: 0.66rem; font-weight: 800; padding: 3px 9px; border-radius: 20px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text-muted); }
        .badge.ativo { background: rgba(0,230,118,0.12); color: var(--green); border-color: rgba(0,230,118,0.4); }
        .badge.venc { background: rgba(255,82,82,0.15); color: var(--red); border-color: var(--red); }
        .badge.ok { background: rgba(212,255,0,0.12); color: var(--primary); border-color: rgba(212,255,0,0.4); }
        .meso-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .renovar-form { display: none; padding: 14px 18px; border-top: 1px dashed var(--border); background: rgba(0,0,0,0.2); }
        .renovar-form.open { display: block; }

        .treino { border-top: 1px solid var(--border); padding: 16px 18px; }
        .treino-head { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .treino-head .letra { width: 38px; height: 38px; border-radius: 10px; background: radial-gradient(circle at 35% 30%, #ffe27a, var(--primary)); color: #000; display: flex; align-items: center; justify-content: center; font-weight: 900; }
        .treino-head .tn { font-weight: 800; }
        table.ex { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-bottom: 12px; }
        table.ex th { text-align: left; padding: 8px 6px; color: var(--primary); font-size: 0.68rem; text-transform: uppercase; font-weight: 900; }
        table.ex td { padding: 9px 6px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        table.ex td.c { text-align: center; }
        .ex-empty { color: var(--text-muted); font-size: 0.82rem; padding: 6px 0 12px; }
        .add-ex { display: none; margin-top: 8px; padding: 14px; background: rgba(0,0,0,0.25); border: 1px dashed var(--border); border-radius: 10px; }
        .add-ex.open { display: block; }

        .empty { text-align: center; padding: 50px 20px; color: var(--text-muted); }
        .empty i { font-size: 2.5rem; color: var(--primary); margin-bottom: 12px; display: block; opacity: 0.8; }

        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('academia.alunos') }}" class="back-btn" title="Voltar aos alunos"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-lightning"></i> Periodização</span>
        <span class="spacer"></span>
        <a href="{{ route('academia.aluno-fichas', $cliente->id) }}" class="btn-top"><i class="ph ph-clipboard-text"></i> Fichas da semana</a>
    </div>

    <div class="container">
        <div class="ed-eyebrow"><i class="ph ph-lightning"></i> Periodização</div>
        <h1 class="ed-h">{{ strtoupper($cliente->nome) }}</h1>
        <p style="color:var(--text-muted); font-size:0.86rem; margin:-12px 0 22px;">Monte treinos A/B/C/D que se alternam automaticamente. O aluno vê em "Treino do dia".</p>

        @if(session('success'))
            <div class="alert alert-success"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error"><i class="ph ph-warning-circle"></i> {{ $errors->first() }}</div>
        @endif

        {{-- NOVO MESOCICLO --}}
        <div class="panel">
            <div class="panel-title"><i class="ph ph-plus"></i> Novo mesociclo</div>
            <form method="POST" action="{{ route('academia.periodizacao.criar', $cliente->id) }}">
                @csrf
                <div class="row">
                    <div class="fg" style="flex: 2;">
                        <label>Nome do ciclo</label>
                        <input type="text" name="nome" placeholder="Ex: Hipertrofia — Bloco 1" required>
                    </div>
                    <div class="fg">
                        <label>Início</label>
                        <input type="date" name="data_inicio" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="fg">
                        <label>Qtd. de treinos</label>
                        <select name="qtd_treinos">
                            @for($i = 2; $i <= 6; $i++)
                                <option value="{{ $i }}" {{ $i === 3 ? 'selected' : '' }}>{{ $i }} (A–{{ chr(64 + $i) }})</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <label>Validade do mesociclo</label>
                <div class="radio-row">
                    <label class="opt"><input type="radio" name="modo_validade" value="semanas" checked onchange="toggleValidade(this.form)"> Por semanas</label>
                    <label class="opt"><input type="radio" name="modo_validade" value="data" onchange="toggleValidade(this.form)"> Por data</label>
                </div>
                <div class="row">
                    <div class="fg js-semanas">
                        <label>Duração (semanas)</label>
                        <input type="number" name="duracao_semanas" min="1" max="52" value="6">
                    </div>
                    <div class="fg js-data" style="display:none;">
                        <label>Vence em</label>
                        <input type="date" name="data_fim">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="ph ph-lightning"></i> Criar mesociclo</button>
            </form>
        </div>

        {{-- MESOCICLOS EXISTENTES --}}
        @if($mesociclos->isEmpty())
            <div class="empty"><i class="ph ph-stack"></i> Nenhum mesociclo criado para este aluno.</div>
        @else
            @foreach($mesociclos as $m)
                @php $venc = $m->estaVencido(); $dias = $m->diasRestantes(); $fim = $m->dataFim(); @endphp
                <div class="meso {{ $venc ? 'venc' : '' }}">
                    <div class="meso-head">
                        <div>
                            <div class="nome">{{ $m->nome }}</div>
                            <div class="meta">
                                <span class="badge {{ $m->ativo ? 'ativo' : '' }}">{{ $m->ativo ? 'Ativo' : 'Inativo' }}</span>
                                <span class="badge">Início {{ $m->data_inicio?->format('d/m/Y') }}</span>
                                @if($venc)
                                    <span class="badge venc"><i class="ph ph-warning"></i> Vencido {{ $fim?->format('d/m/Y') }}</span>
                                @elseif($dias !== null)
                                    <span class="badge ok">Vence em {{ $fim?->format('d/m/Y') }} ({{ $dias }}d)</span>
                                @else
                                    <span class="badge">Sem prazo</span>
                                @endif
                            </div>
                        </div>
                        <div class="meso-actions">
                            @if(!$m->ativo)
                                <form method="POST" action="{{ route('academia.periodizacao.ativar', $m->id) }}">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm"><i class="ph ph-play"></i> Ativar</button>
                                </form>
                            @endif
                            <button class="btn btn-ghost btn-sm" onclick="toggleRenovar({{ $m->id }})"><i class="ph ph-arrows-clockwise"></i> Renovar</button>
                            <form method="POST" action="{{ route('academia.periodizacao.deletar', $m->id) }}" onsubmit="return confirm('Excluir este mesociclo?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button>
                            </form>
                        </div>
                    </div>

                    {{-- RENOVAR --}}
                    <div class="renovar-form" id="renovar-{{ $m->id }}">
                        <form method="POST" action="{{ route('academia.periodizacao.renovar', $m->id) }}">
                            @csrf
                            <div class="radio-row">
                                <label class="opt"><input type="radio" name="modo_validade" value="semanas" checked onchange="toggleValidade(this.form)"> Por semanas</label>
                                <label class="opt"><input type="radio" name="modo_validade" value="data" onchange="toggleValidade(this.form)"> Por data</label>
                            </div>
                            <div class="row">
                                <div class="fg js-semanas">
                                    <label>Duração (semanas)</label>
                                    <input type="number" name="duracao_semanas" min="1" max="52" value="6">
                                </div>
                                <div class="fg js-data" style="display:none;">
                                    <label>Vence em</label>
                                    <input type="date" name="data_fim">
                                </div>
                                <div class="fg" style="display:flex; align-items:flex-end;">
                                    <button class="btn btn-primary btn-sm" style="width:100%;"><i class="ph ph-arrows-clockwise"></i> Renovar a partir de hoje</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- TREINOS --}}
                    @foreach($m->treinos as $treino)
                        <div class="treino">
                            <div class="treino-head">
                                <div class="letra">{{ $treino->letra }}</div>
                                <div class="tn">{{ $treino->nome_treino }}</div>
                            </div>

                            @if($treino->exercicios->isEmpty())
                                <div class="ex-empty">Nenhum exercício neste treino.</div>
                            @else
                                <table class="ex">
                                    <thead>
                                        <tr><th>Exercício</th><th class="c">Séries</th><th class="c">Reps</th><th class="c">Peso</th><th></th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($treino->exercicios as $ex)
                                            <tr>
                                                <td>{{ $ex->nome_exercicio }}</td>
                                                <td class="c">{{ $ex->series }}</td>
                                                <td class="c">{{ $ex->repeticoes }}</td>
                                                <td class="c">{{ $ex->peso ? $ex->peso.' kg' : '-' }}</td>
                                                <td class="c">
                                                    <form method="POST" action="{{ route('periodizacao.exercicio.del', $ex->id) }}" onsubmit="return confirm('Remover exercício?')">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" style="padding:5px 9px;"><i class="ph ph-x"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            <button class="btn btn-ghost btn-sm" onclick="toggleAddEx({{ $treino->id }})"><i class="ph ph-plus"></i> Adicionar exercício</button>
                            <div class="add-ex" id="addex-{{ $treino->id }}">
                                <form method="POST" action="{{ route('periodizacao.exercicio.add', $treino->id) }}">
                                    @csrf
                                    <div class="row">
                                        <div class="fg" style="flex:2;">
                                            <label>Exercício</label>
                                            <input type="text" name="nome_exercicio" list="catalogoExercicios" required oninput="onExercicioInput(this)">
                                            <div class="preview-video-catalogo" style="display:none; margin-top:6px;">
                                                <video muted loop playsinline onclick="abrirVideo(this.dataset.full)" style="width:80px; height:80px; object-fit:cover; border-radius:8px; background:#000; cursor:pointer; border:1px solid var(--border);" title="Vídeo demonstrativo — clique para ampliar"></video>
                                            </div>
                                        </div>
                                        <div class="fg"><label>Séries</label><input type="number" name="series" min="1" value="3" required></div>
                                        <div class="fg"><label>Reps</label><input type="number" name="repeticoes" min="1" value="10" required></div>
                                        <div class="fg"><label>Peso (kg)</label><input type="number" step="0.5" min="0" name="peso"></div>
                                    </div>
                                    <div class="row">
                                        <div class="fg" style="flex:3;"><label>Observações</label><input type="text" name="observacoes" placeholder="Técnica, cadência..."></div>
                                        <div class="fg" style="display:flex; align-items:flex-end;"><button class="btn btn-primary btn-sm" style="width:100%;"><i class="ph ph-check"></i> Salvar</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>

    {{-- CATÁLOGO DE EXERCÍCIOS (para o datalist do input) --}}
    <datalist id="catalogoExercicios">
        @foreach($exerciciosData as $ex)
            <option value="{{ $ex['nome'] }}">{{ $ex['grupo'] }}</option>
        @endforeach
    </datalist>

    {{-- MODAL VÍDEO DEMONSTRATIVO --}}
    <div id="videoModal" onclick="if(event.target===this)fecharVideo()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:99999; justify-content:center; align-items:center; padding:20px;">
        <div style="position:relative; max-width:520px; width:100%;">
            <button type="button" onclick="fecharVideo()" style="position:absolute; top:-38px; right:0; background:none; border:none; color:#fff; font-size:1.4rem; cursor:pointer;">✕</button>
            <video id="videoPlayer" controls playsinline autoplay style="width:100%; border-radius:14px; background:#000;"></video>
        </div>
    </div>

    <script>
        // Mapa nome do exercício -> caminho do vídeo demonstrativo (biblioteca SNR).
        const VIDEOS_EXERCICIOS = {!! json_encode(collect($exerciciosData)->filter(fn($e) => ! empty($e['video']))->pluck('video', 'nome'), (JSON_UNESCAPED_UNICODE)|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!};
        const STORAGE_BASE = "{{ asset('storage') }}";

        // Ao escolher um exercício do catálogo, mostra a prévia do vídeo demonstrativo ao lado.
        function onExercicioInput(input) {
            const form = input.closest('form');
            const wrap = form ? form.querySelector('.preview-video-catalogo') : null;
            if (!wrap) return;
            const video = wrap.querySelector('video');
            const path  = VIDEOS_EXERCICIOS[input.value.trim()];
            if (path) {
                const url = STORAGE_BASE + '/' + path;
                video.src = url;
                video.dataset.full = url;
                video.load();
                video.play().catch(() => {});
                wrap.style.display = 'block';
            } else {
                video.pause();
                video.removeAttribute('src');
                video.dataset.full = '';
                wrap.style.display = 'none';
            }
        }

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

        function toggleValidade(form) {
            const modo = form.querySelector('input[name="modo_validade"]:checked').value;
            form.querySelectorAll('.js-semanas').forEach(e => e.style.display = modo === 'semanas' ? '' : 'none');
            form.querySelectorAll('.js-data').forEach(e => e.style.display = modo === 'data' ? '' : 'none');
        }
        function toggleRenovar(id) {
            document.getElementById('renovar-' + id).classList.toggle('open');
        }
        function toggleAddEx(id) {
            document.getElementById('addex-' + id).classList.toggle('open');
        }
    </script>
</body>

</html>
