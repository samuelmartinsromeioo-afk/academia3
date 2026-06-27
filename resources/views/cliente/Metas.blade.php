<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Metas</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root { --primary:#F4BE16; --bg-dark:#000; --card-bg:#111317; --field:#1a1d23; --text-main:#fff; --text-muted:#9a9a9a; --green:#00e676; --red:#ff5252; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); min-height:100vh; background-image:radial-gradient(circle at 50% -10%, rgba(244,190,22,0.12), transparent 50%); }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:15px 40px; background:rgba(0,0,0,0.6); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .back-btn:hover { background:var(--primary); color:#000; }
        .top-bar .title { font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px; }
        .top-bar .title i { color:var(--primary); }
        .container { max-width:720px; margin:26px auto; padding:0 20px; }
        h1 { font-size:1.6rem; font-weight:900; color:var(--primary); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-ok { background:rgba(0,230,118,0.1); color:var(--green); border:1px solid var(--green); padding:14px; border-radius:12px; margin-bottom:18px; display:flex; gap:10px; align-items:center; font-size:0.9rem; }
        .panel { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:20px; }
        .panel-title { font-size:0.74rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--primary); font-weight:900; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        label.lbl { font-size:0.62rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); font-weight:900; display:block; margin-bottom:4px; }
        input, select { width:100%; padding:10px; background:var(--field); border:1px solid rgba(255,255,255,0.1); color:var(--text-main); border-radius:9px; font-size:0.9rem; font-family:inherit; }
        input:focus, select:focus { outline:none; border-color:var(--primary); }
        .row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
        .row .fg { flex:1; min-width:130px; display:flex; flex-direction:column; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 18px; border:none; border-radius:10px; font-weight:900; font-size:0.82rem; cursor:pointer; transition:0.2s; }
        .btn-primary { background:var(--primary); color:#000; } .btn-primary:hover { filter:brightness(1.1); }
        .btn-ghost { background:var(--field); color:var(--text-main); border:1px solid var(--border); }
        .btn-danger { background:rgba(255,82,82,0.12); color:var(--red); border:1px solid var(--red); }
        .btn-sm { padding:7px 11px; font-size:0.72rem; }
        .meta { border:1px solid var(--border); border-radius:14px; padding:16px; margin-bottom:12px; }
        .meta.ok { border-color:rgba(0,230,118,0.5); }
        .meta-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:10px; }
        .meta-head .tit { font-weight:800; }
        .meta-head .tipo { font-size:0.64rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; margin-top:3px; }
        .badge-ok { font-size:0.62rem; font-weight:900; color:var(--green); background:rgba(0,230,118,0.12); border:1px solid rgba(0,230,118,0.4); padding:3px 9px; border-radius:20px; }
        .bar { height:9px; background:rgba(255,255,255,0.08); border-radius:6px; overflow:hidden; margin:8px 0 6px; }
        .bar > span { display:block; height:100%; border-radius:6px; background:var(--primary); }
        .meta-foot { display:flex; justify-content:space-between; align-items:center; gap:10px; font-size:0.76rem; color:var(--text-muted); }
        .meta-acoes { display:flex; gap:8px; }
        .empty { color:var(--text-muted); text-align:center; padding:30px 0; }
        @media (max-width:600px){ .top-bar{padding:15px 20px;} }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('cliente.index') }}" class="back-btn"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-target"></i> Minhas Metas</span>
    </div>

    <div class="container">
        <div class="ed-eyebrow"><i class="ph ph-target"></i> Objetivos</div><h1 class="ed-h">Minhas <span class="ed-mark">Metas</span></h1>

        @if(session('success'))
            <div class="alert-ok"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
        @endif

        <div class="panel">
            <div class="panel-title"><i class="ph ph-plus"></i> Nova meta</div>
            <form method="POST" action="{{ route('metas.salvar') }}">
                @csrf
                <div class="row">
                    <div class="fg" style="flex:2;">
                        <label class="lbl">Tipo</label>
                        <select name="tipo" id="tipoSel" onchange="toggleCampos()">
                            <option value="treinos_mes">Treinos no mês</option>
                            <option value="carga">Carga em um exercício</option>
                            <option value="livre">Meta livre</option>
                        </select>
                    </div>
                    <div class="fg js-alvo">
                        <label class="lbl">Alvo</label>
                        <input type="number" step="0.5" name="alvo" placeholder="ex: 12">
                    </div>
                </div>
                <div class="row">
                    <div class="fg" style="flex:2;">
                        <label class="lbl">Título</label>
                        <input type="text" name="titulo" placeholder="ex: Treinar 12x este mês" required>
                    </div>
                    <div class="fg js-prazo">
                        <label class="lbl">Prazo (opcional)</label>
                        <input type="date" name="prazo">
                    </div>
                </div>
                <div class="row js-exercicio" style="display:none;">
                    <div class="fg">
                        <label class="lbl">Exercício</label>
                        <select name="exercicio">
                            <option value="">Selecione...</option>
                            @foreach($exercicios as $ex)
                                <option value="{{ $ex }}">{{ $ex }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary"><i class="ph ph-target"></i> Criar meta</button>
            </form>
        </div>

        @if($metas->isEmpty())
            <div class="empty"><i class="ph ph-target" style="font-size:2.2rem; color:var(--primary); display:block; margin-bottom:10px; opacity:0.7;"></i>Você ainda não tem metas. Defina uma acima! 🎯</div>
        @else
            @foreach($metas as $m)
                @php $p = $m->progresso; @endphp
                <div class="meta {{ $p['atingida'] ? 'ok' : '' }}">
                    <div class="meta-head">
                        <div>
                            <div class="tit">{{ $m->titulo }}</div>
                            <div class="tipo">{{ $m->tipoLabel() }}{{ $m->exercicio ? ' · '.$m->exercicio : '' }}{{ $m->criada_por_personal_id ? ' · definida pelo personal' : '' }}</div>
                        </div>
                        @if($p['atingida'])<span class="badge-ok"><i class="ph ph-check"></i> Atingida</span>@endif
                    </div>

                    @if($m->tipo !== 'livre')
                        <div class="bar"><span style="width: {{ $p['percent'] }}%; {{ $p['atingida'] ? 'background:var(--green);' : '' }}"></span></div>
                        <div class="meta-foot">
                            <span>{{ rtrim(rtrim(number_format($p['atual'],2,',','.'),'0'),',') }} / {{ rtrim(rtrim(number_format($p['alvo'],2,',','.'),'0'),',') }} ({{ $p['percent'] }}%)</span>
                            <span class="meta-acoes">
                                @if($m->prazo)<span>até {{ $m->prazo->format('d/m/Y') }}</span>@endif
                                <form method="POST" action="{{ route('metas.excluir', $m->id) }}" onsubmit="return confirm('Remover meta?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button></form>
                            </span>
                        </div>
                    @else
                        <div class="meta-foot">
                            <span>{{ $m->concluida ? 'Concluída ✅' : 'Em andamento' }}{{ $m->prazo ? ' · até '.$m->prazo->format('d/m/Y') : '' }}</span>
                            <span class="meta-acoes">
                                <form method="POST" action="{{ route('metas.alternar', $m->id) }}">@csrf<button class="btn btn-ghost btn-sm">{{ $m->concluida ? 'Reabrir' : 'Concluir' }}</button></form>
                                <form method="POST" action="{{ route('metas.excluir', $m->id) }}" onsubmit="return confirm('Remover meta?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button></form>
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <script>
        function toggleCampos() {
            const tipo = document.getElementById('tipoSel').value;
            document.querySelectorAll('.js-exercicio').forEach(e => e.style.display = tipo === 'carga' ? 'flex' : 'none');
            document.querySelectorAll('.js-alvo').forEach(e => e.style.display = tipo === 'livre' ? 'none' : 'flex');
        }
        toggleCampos();
    </script>
</body>

</html>
