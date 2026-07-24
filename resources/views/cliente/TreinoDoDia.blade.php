<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treino do Dia</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #F4BE16; --bg-dark: #000000; --card-bg: #111317;
            --text-main: #ffffff; --text-muted: #9a9a9a; --green: #00e676; --red: #ff5252;
            --border: rgba(255, 255, 255, 0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-main); min-height: 100vh;
            background-image: radial-gradient(circle at 50% -10%, rgba(244, 190, 22, 0.12), transparent 50%);
        }
        a { color: inherit; text-decoration: none; }
        .top-bar {
            display: flex; align-items: center; gap: 15px; padding: 15px 40px;
            background: rgba(0, 0, 0, 0.6); border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);
        }
        .back-btn {
            background: var(--card-bg); border: 1px solid var(--border); color: var(--primary);
            width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: 0.3s;
        }
        .back-btn:hover { background: var(--primary); color: #000; }
        .top-bar .title { font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .top-bar .title i { color: var(--primary); }
        .container { max-width: 720px; margin: 28px auto; padding: 0 20px; }

        .alert { padding: 14px; border-radius: 12px; margin-bottom: 18px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(0, 230, 118, 0.1); color: var(--green); border: 1px solid var(--green); }

        .meso-bar {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 14px 18px; margin-bottom: 18px;
        }
        .meso-bar .nome { font-weight: 800; }
        .meso-bar .nome small { display: block; color: var(--text-muted); font-weight: 600; font-size: 0.72rem; margin-top: 2px; }
        .validade { font-size: 0.78rem; font-weight: 800; padding: 5px 12px; border-radius: 20px; }
        .validade.ok { background: rgba(0,230,118,0.12); color: var(--green); border: 1px solid rgba(0,230,118,0.4); }
        .validade.venc { background: rgba(255,82,82,0.15); color: var(--red); border: 1px solid var(--red); }

        .treino-card { background: var(--card-bg); border: 1px solid rgba(244,190,22,0.3); border-radius: 20px; padding: 26px; }
        .treino-head { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; }
        .letra {
            width: 64px; height: 64px; border-radius: 16px; flex-shrink: 0;
            background: radial-gradient(circle at 35% 30%, #ffe27a, var(--primary)); color: #000;
            display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 900;
            box-shadow: 0 0 22px rgba(244,190,22,0.35);
        }
        .treino-head .nome { font-size: 1.3rem; font-weight: 900; }
        .treino-head .sub { font-size: 0.78rem; color: var(--text-muted); margin-top: 3px; }
        .treino-obs { background: rgba(255,255,255,0.03); border-left: 3px solid var(--primary); border-radius: 8px; padding: 12px 14px; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 18px; line-height: 1.5; }

        table.ex { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        table.ex thead tr { border-bottom: 1px solid rgba(255,255,255,0.12); }
        table.ex th { text-align: left; padding: 10px 6px; color: var(--primary); font-weight: 900; font-size: 0.72rem; text-transform: uppercase; }
        table.ex td { padding: 11px 6px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        table.ex td.c, table.ex th.c { text-align: center; }
        .ex-nome { font-weight: 700; }

        .conclude { margin-top: 22px; }
        .btn-conclude {
            width: 100%; padding: 16px; border-radius: 14px; border: none; cursor: pointer;
            background: var(--primary); color: #000; font-weight: 900; font-size: 1rem;
            display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.2s;
        }
        .btn-conclude:hover { filter: brightness(1.1); }
        .btn-conclude.done { background: rgba(0,230,118,0.15); color: var(--green); border: 1px solid var(--green); cursor: default; }

        .proximo { margin-top: 18px; display: flex; align-items: center; gap: 12px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 14px 18px; }
        .proximo .mini { width: 38px; height: 38px; border-radius: 10px; background: rgba(244,190,22,0.12); border: 1px solid rgba(244,190,22,0.4); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 900; }
        .proximo .txt { font-size: 0.85rem; } .proximo .txt small { display: block; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 800; }

        .empty { text-align: center; padding: 70px 20px; color: var(--text-muted); background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; }
        .empty i { font-size: 3rem; color: var(--primary); margin-bottom: 16px; display: block; opacity: 0.8; }
        .empty p { font-size: 1.05rem; color: var(--text-main); margin-bottom: 6px; }

        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('cliente.index') }}" class="back-btn" title="Voltar"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-lightning"></i> Treino do Dia</span>
    </div>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
        @endif

        @if(!$meso || !$treino)
            <div class="empty">
                <i class="ph ph-lightning"></i>
                <p>Nenhuma periodização ativa.</p>
                <small>Seu personal vai montar seu ciclo de treinos A/B/C/D em breve.</small>
            </div>
        @else
            @php $venc = $meso->estaVencido(); $dias = $meso->diasRestantes(); @endphp
            <div class="meso-bar">
                <div class="nome">
                    {{ $meso->nome }}
                    <small>Treino {{ $treino->letra }} de {{ $meso->treinos->count() }} · rotação automática</small>
                </div>
                @if($venc)
                    <span class="validade venc"><i class="ph ph-warning"></i> Ciclo vencido</span>
                @elseif($dias !== null)
                    <span class="validade ok">{{ $dias }} dia(s) restante(s)</span>
                @endif
            </div>

            <div class="treino-card">
                <div class="treino-head">
                    <div class="letra">{{ $treino->letra }}</div>
                    <div>
                        <div class="nome">{{ $treino->nome_treino }}</div>
                        <div class="sub">Treino de hoje</div>
                    </div>
                </div>

                @if($treino->observacoes)
                    <div class="treino-obs">{{ $treino->observacoes }}</div>
                @endif

                @if($treino->exercicios->isEmpty())
                    <p style="color: var(--text-muted); text-align:center; padding: 16px 0;">Nenhum exercício cadastrado neste treino ainda.</p>
                @else
                    <table class="ex">
                        <thead>
                            <tr>
                                <th>Exercício</th>
                                <th class="c">Séries</th>
                                <th class="c">Reps</th>
                                <th class="c">Peso</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($treino->exercicios as $ex)
                                @php $videoEx = \App\Support\VideosExercicios::para($ex->nome_exercicio); @endphp
                                <tr>
                                    <td class="ex-nome">
                                        {{ $ex->nome_exercicio }}
                                        @if($videoEx)
                                            <button type="button" onclick="abrirVideo('{{ asset('storage/' . $videoEx) }}')" style="display:block; margin-top:4px; background:none; border:none; color:var(--primary); font-size:0.72rem; font-weight:700; cursor:pointer; padding:0;"><i class="ph ph-play-circle"></i> Ver execução</button>
                                        @endif
                                    </td>
                                    <td class="c">{{ $ex->series }}</td>
                                    <td class="c">{{ $ex->repeticoes }}</td>
                                    <td class="c">{{ $ex->peso ? $ex->peso.' kg' : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <div class="conclude">
                    @if($meso->concluidoHoje())
                        <button class="btn-conclude done" disabled><i class="ph ph-check-circle"></i> Treino de hoje concluído</button>
                    @else
                        <form method="POST" action="{{ route('periodizacao.concluir', $meso->id) }}">
                            @csrf
                            <button type="submit" class="btn-conclude"><i class="ph ph-lightning"></i> Concluir treino do dia</button>
                        </form>
                    @endif
                </div>
            </div>

            @if($proximo)
                <div class="proximo">
                    <div class="mini">{{ $proximo->letra }}</div>
                    <div class="txt">
                        <small>Próximo treino</small>
                        {{ $proximo->nome_treino }}
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- MODAL VÍDEO DEMONSTRATIVO --}}
    <div id="videoModal" onclick="if(event.target===this)fecharVideo()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:99999; justify-content:center; align-items:center; padding:20px;">
        <div style="position:relative; max-width:520px; width:100%;">
            <button type="button" onclick="fecharVideo()" style="position:absolute; top:-38px; right:0; background:none; border:none; color:#fff; font-size:1.4rem; cursor:pointer;">✕</button>
            <video id="videoPlayer" controls playsinline autoplay style="width:100%; border-radius:14px; background:#000;"></video>
        </div>
    </div>

    <script>
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
    </script>
</body>

</html>
