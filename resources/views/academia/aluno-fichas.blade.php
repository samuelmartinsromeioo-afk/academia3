<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de {{ $cliente->nome }} | SnrFit</title>
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
            --error: #ff4444;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); }

        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .logo { font-weight: 900; letter-spacing: 2px; }
        .logo span { color: var(--primary); }
        .btn-top { background: transparent; border: 1px solid var(--border); color: var(--text-main); padding: 9px 16px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.78rem; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        header.page-head { margin-bottom: 24px; display: flex; align-items: center; gap: 16px; }
        header.page-head img { width: 56px; height: 56px; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; }
        header.page-head h1 { font-size: 1.6rem; font-weight: 900; }
        header.page-head p { color: var(--text-muted); font-size: 0.85rem; margin-top: 3px; }

        .alert { padding: 13px 18px; border-radius: 12px; margin-bottom: 18px; font-size: 0.85rem; }
        .alert-ok { background: rgba(212,255,0,0.1); border: 1px solid rgba(212,255,0,0.35); color: var(--primary); }
        .alert-err { background: rgba(255,68,68,0.1); border: 1px solid rgba(255,68,68,0.35); color: #ff6b6b; }

        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 22px; margin-bottom: 20px; }
        .card h2 { font-size: 1rem; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .card h2 i { color: var(--primary); }

        label { display: block; font-size: 0.65rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted); margin-bottom: 6px; letter-spacing: 0.5px; }
        input, select, textarea { width: 100%; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px; padding: 11px 13px; color: #fff; font-family: inherit; font-size: 0.88rem; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); }
        textarea { resize: vertical; min-height: 60px; }
        select option { background: var(--card-bg); }

        .grid { display: grid; gap: 14px; }
        .g2 { grid-template-columns: 1fr 1fr; }
        .g3 { grid-template-columns: 1fr 1fr 1fr; }
        .g4 { grid-template-columns: 2fr 1fr 1fr 1fr; }

        .btn { background: var(--primary); color: #000; border: none; border-radius: 10px; padding: 12px 18px; font-weight: 900; font-size: 0.8rem; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { background: #e8ff40; }
        .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text-muted); }
        .btn-ghost:hover { border-color: var(--error); color: #ff6b6b; }
        .btn-sm { padding: 8px 12px; font-size: 0.72rem; }

        .ficha { border: 1px solid var(--border); border-radius: 14px; padding: 18px; margin-bottom: 16px; background: var(--input-bg); }
        .ficha-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
        .ficha-head .dia { color: var(--primary); font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .ficha-head h3 { font-size: 1.05rem; font-weight: 800; }
        .ficha-head .obs { color: var(--text-muted); font-size: 0.8rem; margin-top: 4px; }
        .tag { display: inline-block; font-size: 0.62rem; font-weight: 800; text-transform: uppercase; padding: 3px 8px; border-radius: 20px; background: rgba(212,255,0,0.1); color: var(--primary); border: 1px solid rgba(212,255,0,0.3); margin-top: 6px; }

        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { text-align: left; padding: 9px 8px; font-size: 0.8rem; border-bottom: 1px solid var(--border); }
        th { color: var(--text-muted); text-transform: uppercase; font-size: 0.6rem; letter-spacing: 0.5px; }
        .ex-empty { color: var(--text-muted); font-size: 0.82rem; padding: 8px 0; }

        .add-ex { margin-top: 12px; border-top: 1px dashed var(--border); padding-top: 14px; }
        details summary { cursor: pointer; color: var(--primary); font-size: 0.78rem; font-weight: 700; list-style: none; }
        details summary::-webkit-details-marker { display: none; }

        @media (max-width: 680px) {
            .top-bar { padding: 14px 20px; }
            .g2, .g3, .g4 { grid-template-columns: 1fr; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            td { border: none; padding: 3px 0; }
            .ficha td::before { content: attr(data-label) ": "; color: var(--text-muted); font-weight: 700; }
        }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span> <span style="font-family:'Inter'; font-size:0.65rem; color:var(--text-muted); letter-spacing:1px; text-transform:uppercase;">| Ficha de treino</span></div>
    <div style="display:flex; gap:10px;">
        <a href="{{ route('academia.periodizacao.aluno', $cliente->id) }}" class="btn-top"><i class="ph ph-lightning"></i> Periodização</a>
        <a href="{{ route('academia.alunos') }}" class="btn-top"><i class="ph ph-arrow-left"></i> Voltar aos alunos</a>
    </div>
</div>

<div class="container">
    @if(session('success'))<div class="alert alert-ok"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-err"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>@endif

    <header class="page-head">
        @if($cliente->foto)
            <img src="{{ asset('storage/' . $cliente->foto) }}" alt="{{ $cliente->nome }}">
        @else
            <img src="https://ui-avatars.com/api/?name={{ urlencode($cliente->nome) }}&background=d4ff00&color=000">
        @endif
        <div>
            <h1>Ficha de {{ $cliente->nome }}</h1>
            <p>Monte os treinos da semana deste aluno. Ele verá em "Minha Ficha".</p>
        </div>
    </header>

    {{-- DATALIST DE EXERCÍCIOS (catálogo) --}}
    <datalist id="catalogoExercicios">
        @foreach($exerciciosData as $ex)
            <option value="{{ $ex['nome'] }}">{{ $ex['grupo'] }}</option>
        @endforeach
    </datalist>

    {{-- CRIAR FICHA --}}
    <div class="card">
        <h2><i class="ph ph-plus-circle"></i> Nova ficha (treino do dia)</h2>
        <form action="{{ route('academia.fichas.criar') }}" method="POST">
            @csrf
            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
            <div class="grid g3" style="margin-bottom:14px;">
                <div>
                    <label>Dia da semana</label>
                    <select name="dia_semana" required>
                        @foreach(['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $i => $dia)
                            <option value="{{ $i }}">{{ $dia }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Nome do treino</label>
                    <input type="text" name="nome_treino" placeholder="Ex: Peito e Tríceps" required>
                </div>
                <div>
                    <label>Nível</label>
                    <select name="nivel" id="nivelSelect" required>
                        <option value="iniciante">Iniciante</option>
                        <option value="avancado">Avançado</option>
                    </select>
                </div>
            </div>
            <div class="grid g2" style="margin-bottom:14px;">
                <div id="divisaoWrap" style="display:none;">
                    <label>Divisão (avançado)</label>
                    <input type="text" name="divisao" placeholder="Ex: Push / Pull / Legs">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Observações (aquecimento, alongamento...)</label>
                    <textarea name="observacoes" placeholder="Opcional"></textarea>
                </div>
            </div>
            <button type="submit" class="btn"><i class="ph ph-floppy-disk"></i> Criar ficha</button>
        </form>
    </div>

    {{-- FICHAS EXISTENTES --}}
    <div class="card">
        <h2><i class="ph ph-barbell"></i> Fichas cadastradas</h2>

        @forelse($fichas as $ficha)
            <div class="ficha">
                <div class="ficha-head">
                    <div>
                        <span class="dia">{{ $ficha->getDiaSemanaNome() }}</span>
                        <h3>{{ $ficha->nome_treino }}</h3>
                        @if($ficha->observacoes)<div class="obs">{{ $ficha->observacoes }}</div>@endif
                        <span class="tag">{{ ucfirst($ficha->nivel ?? 'iniciante') }}</span>
                        @if($ficha->divisao)<span class="tag">{{ $ficha->divisao }}</span>@endif
                    </div>
                    <form action="{{ route('academia.fichas.deletar', $ficha->id) }}" method="POST" onsubmit="return confirm('Excluir esta ficha e todos os exercícios?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm"><i class="ph ph-trash"></i> Excluir ficha</button>
                    </form>
                </div>

                @if($ficha->exercicios->isEmpty())
                    <p class="ex-empty">Nenhum exercício ainda. Adicione abaixo.</p>
                @else
                    <table>
                        <thead>
                            <tr><th>Exercício</th><th>Séries</th><th>Reps</th><th>Carga</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach($ficha->exercicios as $ex)
                                <tr>
                                    <td data-label="Exercício">
                                        {{ $ex->nome_exercicio }}
                                        @if($ex->observacoes)<div style="color:var(--text-muted); font-size:0.72rem;">{{ $ex->observacoes }}</div>@endif
                                        @if($ex->video)
                                            <button type="button" onclick="abrirVideo('{{ asset('storage/' . $ex->video) }}')" style="margin-top:4px; background:none; border:none; color:var(--primary); font-size:0.72rem; font-weight:700; cursor:pointer; padding:0; display:inline-flex; align-items:center; gap:5px;"><i class="ph ph-play-circle"></i> Ver vídeo</button>
                                        @endif
                                    </td>
                                    <td data-label="Séries">{{ $ex->series }}</td>
                                    <td data-label="Reps">{{ $ex->repeticoes }}</td>
                                    <td data-label="Carga">{{ $ex->peso ? $ex->peso . ' kg' : '—' }}</td>
                                    <td>
                                        <div style="display:flex; gap:6px; justify-content:flex-end;">
                                            <button type="button" class="btn btn-ghost btn-sm" style="border-color:var(--border); color:var(--primary);" onclick="toggleEditEx({{ $ex->id }})"><i class="ph ph-pen"></i></button>
                                            <form action="{{ route('academia.fichas.exercicio.deletar', $ex->id) }}" method="POST" onsubmit="return confirm('Remover este exercício?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-ghost btn-sm"><i class="ph ph-x"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="edit-ex-row" id="edit-ex-{{ $ex->id }}" style="display:none;">
                                    <td colspan="5">
                                        <form action="{{ route('academia.fichas.exercicio.editar', $ex->id) }}" method="POST" style="background:rgba(212,255,0,0.04); border:1px solid var(--border); border-radius:12px; padding:14px;" enctype="multipart/form-data" onsubmit="return videoOk(this);">
                                            @csrf @method('PUT')
                                            <div class="grid g4" style="margin-bottom:12px;">
                                                <div>
                                                    <label>Exercício</label>
                                                    <input type="text" name="nome_exercicio" list="catalogoExercicios" value="{{ $ex->nome_exercicio }}" required>
                                                </div>
                                                <div>
                                                    <label>Séries</label>
                                                    <input type="number" name="series" min="1" value="{{ $ex->series }}" required>
                                                </div>
                                                <div>
                                                    <label>Repetições</label>
                                                    <input type="number" name="repeticoes" min="1" value="{{ $ex->repeticoes }}" required>
                                                </div>
                                                <div>
                                                    <label>Carga (kg)</label>
                                                    <input type="number" step="0.01" name="peso" min="0" value="{{ $ex->peso }}" placeholder="Opcional">
                                                </div>
                                            </div>
                                            <div style="margin-bottom:12px;">
                                                <label>Observações / técnica</label>
                                                <textarea name="observacoes" placeholder="Opcional">{{ $ex->observacoes }}</textarea>
                                            </div>
                                            <div style="margin-bottom:12px;">
                                                <label><i class="ph ph-video-camera"></i> Vídeo demonstrativo (máx. 15s)</label>
                                                @if($ex->video)
                                                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
                                                        <button type="button" class="btn btn-ghost btn-sm" style="border-color:var(--border); color:var(--primary);" onclick="abrirVideo('{{ asset('storage/' . $ex->video) }}')"><i class="ph ph-play"></i> Ver vídeo atual</button>
                                                        <label style="display:flex; align-items:center; gap:6px; text-transform:none; font-weight:600; color:var(--text-muted); margin:0;">
                                                            <input type="checkbox" name="remover_video" value="1" style="width:auto;"> Remover vídeo
                                                        </label>
                                                    </div>
                                                @endif
                                                <input type="file" name="video" accept="video/*" onchange="validarVideo15s(this)">
                                                <small style="color:var(--text-muted);">Envie um arquivo para substituir o atual.</small>
                                            </div>
                                            <div style="display:flex; gap:8px;">
                                                <button type="submit" class="btn btn-sm"><i class="ph ph-floppy-disk"></i> Salvar</button>
                                                <button type="button" class="btn btn-ghost btn-sm" style="border-color:var(--border); color:var(--text-muted);" onclick="toggleEditEx({{ $ex->id }})">Cancelar</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <div class="add-ex">
                    <details>
                        <summary><i class="ph ph-plus"></i> Adicionar exercício</summary>
                        <form action="{{ route('academia.fichas.exercicio.adicionar', $ficha->id) }}" method="POST" style="margin-top:12px;" enctype="multipart/form-data" onsubmit="return videoOk(this);">
                            @csrf
                            <div class="grid g4" style="margin-bottom:12px;">
                                <div>
                                    <label>Exercício</label>
                                    <input type="text" name="nome_exercicio" list="catalogoExercicios" placeholder="Nome do exercício" required oninput="onExercicioInput(this)">
                                    <input type="hidden" name="video_catalogo" class="input-video-catalogo">
                                    <div class="preview-video-catalogo" style="display:none; margin-top:8px;">
                                        <video muted loop playsinline
                                               onclick="abrirVideo(this.dataset.full)"
                                               style="width:88px; height:88px; object-fit:cover; border-radius:10px; background:#000; cursor:pointer; border:1px solid var(--border);"
                                               title="Vídeo demonstrativo — clique para ampliar"></video>
                                    </div>
                                </div>
                                <div>
                                    <label>Séries</label>
                                    <input type="number" name="series" min="1" value="3" required>
                                </div>
                                <div>
                                    <label>Repetições</label>
                                    <input type="number" name="repeticoes" min="1" value="12" required>
                                </div>
                                <div>
                                    <label>Carga (kg)</label>
                                    <input type="number" step="0.01" name="peso" min="0" placeholder="Opcional">
                                </div>
                            </div>
                            <div style="margin-bottom:12px;">
                                <label>Observações / técnica</label>
                                <textarea name="observacoes" placeholder="Opcional"></textarea>
                            </div>
                            <div style="margin-bottom:12px;">
                                <label><i class="ph ph-video-camera"></i> Vídeo demonstrativo (máx. 15s)</label>
                                <input type="file" name="video" accept="video/*" onchange="validarVideo15s(this)">
                            </div>
                            <button type="submit" class="btn btn-sm"><i class="ph ph-plus"></i> Adicionar</button>
                        </form>
                    </details>
                </div>
            </div>
        @empty
            <p class="ex-empty">Nenhuma ficha cadastrada para este aluno ainda.</p>
        @endforelse
    </div>
</div>

<div id="videoModal" onclick="if(event.target===this)fecharVideo()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:9999; justify-content:center; align-items:center; padding:20px;">
    <div style="position:relative; max-width:520px; width:100%;">
        <button onclick="fecharVideo()" style="position:absolute; top:-38px; right:0; background:none; border:none; color:#fff; font-size:1.4rem; cursor:pointer;">✕</button>
        <video id="videoPlayer" controls playsinline style="width:100%; border-radius:14px; background:#000;"></video>
    </div>
</div>

<script>
    // Mapa nome do exercício -> caminho do vídeo demonstrativo (biblioteca SNR).
    const VIDEOS_EXERCICIOS = {!! json_encode(collect($exerciciosData)->filter(fn($e) => ! empty($e['video']))->pluck('video', 'nome'), JSON_UNESCAPED_UNICODE) !!};
    const STORAGE_BASE = "{{ asset('storage') }}";

    // Ao digitar/escolher um exercício do catálogo, casa o vídeo demonstrativo e mostra a prévia ao lado.
    function onExercicioInput(input) {
        const form    = input.closest('form');
        const hidden  = form.querySelector('.input-video-catalogo');
        const wrap    = form.querySelector('.preview-video-catalogo');
        if (!hidden || !wrap) return;
        const video   = wrap.querySelector('video');
        const path    = VIDEOS_EXERCICIOS[input.value.trim()];
        if (path) {
            const url = STORAGE_BASE + '/' + path;
            hidden.value = path;
            video.src = url;
            video.dataset.full = url;
            video.load();
            video.play().catch(() => {});
            wrap.style.display = 'block';
        } else {
            hidden.value = '';
            video.pause();
            video.removeAttribute('src');
            video.dataset.full = '';
            wrap.style.display = 'none';
        }
    }

    const nivelSelect = document.getElementById('nivelSelect');
    const divisaoWrap = document.getElementById('divisaoWrap');
    function toggleDivisao() {
        divisaoWrap.style.display = nivelSelect.value === 'avancado' ? '' : 'none';
    }
    nivelSelect.addEventListener('change', toggleDivisao);
    toggleDivisao();

    function toggleEditEx(id) {
        const row = document.getElementById('edit-ex-' + id);
        if (row) row.style.display = (row.style.display === 'none' || !row.style.display) ? 'table-row' : 'none';
    }

    // Limita o vídeo a 15 segundos (validação no navegador antes do upload).
    function validarVideo15s(input) {
        const file = input.files[0];
        if (!file) return;
        const v = document.createElement('video');
        v.preload = 'metadata';
        v.onloadedmetadata = function () {
            window.URL.revokeObjectURL(v.src);
            if (v.duration > 15.5) {
                alert('O vídeo deve ter no máximo 15 segundos. O selecionado tem ' + Math.round(v.duration) + 's.');
                input.value = '';
            }
        };
        v.src = window.URL.createObjectURL(file);
    }
    function videoOk(form) { return true; }

    function abrirVideo(url) {
        document.getElementById('videoPlayer').src = url;
        document.getElementById('videoModal').style.display = 'flex';
    }
    function fecharVideo() {
        const p = document.getElementById('videoPlayer');
        p.pause(); p.src = '';
        document.getElementById('videoModal').style.display = 'none';
    }
</script>
</body>
</html>
