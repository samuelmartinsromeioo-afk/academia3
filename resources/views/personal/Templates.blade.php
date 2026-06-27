<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates de Ficha</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root { --primary:#F4BE16; --bg-dark:#000; --card-bg:#111317; --field:#1a1d23; --text-main:#fff; --text-muted:#9a9a9a; --green:#00e676; --red:#ff5252; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); min-height:100vh; background-image:radial-gradient(circle at 12% -10%, rgba(244,190,22,0.1), transparent 45%); }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:15px 40px; background:rgba(0,0,0,0.6); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .back-btn:hover { background:var(--primary); color:#000; }
        .top-bar .title { font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px; } .top-bar .title i { color:var(--primary); }
        .container { max-width:820px; margin:26px auto; padding:0 20px; }
        h1 { font-size:1.6rem; font-weight:900; color:var(--primary); margin-bottom:6px; display:flex; align-items:center; gap:10px; }
        .subtitle { color:var(--text-muted); font-size:0.88rem; margin-bottom:22px; }
        .alert-ok { background:rgba(0,230,118,0.1); color:var(--green); border:1px solid var(--green); padding:14px; border-radius:12px; margin-bottom:16px; display:flex; gap:10px; align-items:center; font-size:0.9rem; }
        .alert-err { background:rgba(255,82,82,0.12); color:var(--red); border:1px solid var(--red); padding:14px; border-radius:12px; margin-bottom:16px; display:flex; gap:10px; align-items:center; font-size:0.9rem; }
        .panel { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:18px; }
        .panel-title { font-size:0.74rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--primary); font-weight:900; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
        label.lbl { font-size:0.6rem; text-transform:uppercase; color:var(--text-muted); font-weight:900; display:block; margin-bottom:4px; }
        input, select { width:100%; padding:10px; background:var(--field); border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:9px; font-size:0.9rem; font-family:inherit; }
        input:focus, select:focus { outline:none; border-color:var(--primary); }
        .row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px; } .row .fg { flex:1; min-width:120px; display:flex; flex-direction:column; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 16px; border:none; border-radius:10px; font-weight:900; font-size:0.8rem; cursor:pointer; }
        .btn-primary { background:var(--primary); color:#000; } .btn-primary:hover { filter:brightness(1.1); }
        .btn-ghost { background:var(--field); color:#fff; border:1px solid var(--border); }
        .btn-danger { background:rgba(255,82,82,0.12); color:var(--red); border:1px solid var(--red); }
        .btn-sm { padding:7px 11px; font-size:0.72rem; }
        .tpl { border:1px solid var(--border); border-radius:14px; padding:16px; margin-bottom:14px; }
        .tpl-head { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px; }
        .tpl-head .nome { font-weight:900; font-size:1.05rem; }
        .badge { font-size:0.62rem; font-weight:800; padding:3px 9px; border-radius:20px; background:rgba(244,190,22,0.12); color:var(--primary); border:1px solid rgba(244,190,22,0.4); text-transform:uppercase; }
        table { width:100%; border-collapse:collapse; font-size:0.84rem; margin-bottom:10px; }
        th { text-align:left; padding:7px 6px; color:var(--primary); font-size:0.64rem; text-transform:uppercase; font-weight:900; }
        td { padding:8px 6px; border-bottom:1px solid rgba(255,255,255,0.05); } td.c { text-align:center; }
        .sub-form { margin-top:10px; padding-top:12px; border-top:1px dashed var(--border); }
        .empty { color:var(--text-muted); text-align:center; padding:24px 0; }
        @media (max-width:600px){ .top-bar{padding:15px 20px;} }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('fichas-treino.alunos') }}" class="back-btn"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-copy"></i> Templates de Ficha</span>
    </div>

    <div class="container">
        <div class="ed-eyebrow"><i class="ph ph-copy"></i> Modelos</div><h1 class="ed-h">Templates de <span class="ed-mark">Ficha</span></h1>
        <p class="subtitle">Modelos reutilizáveis para montar fichas rápido e aplicar a qualquer aluno.</p>

        @if(session('success'))<div class="alert-ok"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert-err"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>@endif

        <div class="panel">
            <div class="panel-title"><i class="ph ph-plus"></i> Novo template</div>
            <form method="POST" action="{{ route('templates.criar') }}">
                @csrf
                <div class="row">
                    <div class="fg" style="flex:2;"><label class="lbl">Nome</label><input type="text" name="nome" placeholder="ex: Peito e Tríceps (Hipertrofia)" required></div>
                    <div class="fg"><label class="lbl">Nível</label><select name="nivel"><option value="iniciante">Iniciante</option><option value="avancado">Avançado</option></select></div>
                </div>
                <button class="btn btn-primary"><i class="ph ph-copy"></i> Criar template</button>
            </form>
        </div>

        @if($templates->isEmpty())
            <div class="empty"><i class="ph ph-copy" style="font-size:2.2rem; color:var(--primary); display:block; margin-bottom:10px; opacity:0.7;"></i>Nenhum template ainda. Crie um acima ou use "Salvar como template" numa ficha existente.</div>
        @else
            @foreach($templates as $t)
                <div class="tpl">
                    <div class="tpl-head">
                        <div class="nome">{{ $t->nome }} <span class="badge">{{ $t->nivel }}</span></div>
                        <form method="POST" action="{{ route('templates.deletar', $t->id) }}" onsubmit="return confirm('Excluir template?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button></form>
                    </div>

                    @if(empty($t->exercicios))
                        <p class="empty" style="padding:8px 0;">Sem exercícios. Adicione abaixo.</p>
                    @else
                        <table>
                            <thead><tr><th>Exercício</th><th class="c">Séries</th><th class="c">Reps</th><th class="c">Peso</th><th></th></tr></thead>
                            <tbody>
                                @foreach($t->exercicios as $i => $ex)
                                <tr>
                                    <td>{{ $ex['nome'] ?? '-' }}</td><td class="c">{{ $ex['series'] ?? '-' }}</td><td class="c">{{ $ex['repeticoes'] ?? '-' }}</td><td class="c">{{ isset($ex['peso']) && $ex['peso'] !== null ? $ex['peso'].' kg' : '-' }}</td>
                                    <td class="c"><form method="POST" action="{{ route('templates.exercicio.del', [$t->id, $i]) }}">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" style="padding:5px 9px;"><i class="ph ph-x"></i></button></form></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    {{-- add exercício --}}
                    <form method="POST" action="{{ route('templates.exercicio.add', $t->id) }}" class="sub-form">
                        @csrf
                        <div class="row">
                            <div class="fg" style="flex:2;"><label class="lbl">Exercício</label><input type="text" name="nome_exercicio" required></div>
                            <div class="fg"><label class="lbl">Séries</label><input type="number" name="series" min="1" value="3" required></div>
                            <div class="fg"><label class="lbl">Reps</label><input type="number" name="repeticoes" min="1" value="10" required></div>
                            <div class="fg"><label class="lbl">Peso</label><input type="number" step="0.5" name="peso"></div>
                            <div class="fg" style="justify-content:flex-end;"><button class="btn btn-ghost btn-sm"><i class="ph ph-plus"></i> Add</button></div>
                        </div>
                    </form>

                    {{-- aplicar a aluno --}}
                    <form method="POST" action="{{ route('templates.aplicar', $t->id) }}" class="sub-form">
                        @csrf
                        <div class="row">
                            <div class="fg"><label class="lbl">Aplicar ao aluno</label>
                                <select name="cliente_id" required><option value="">Selecione...</option>@foreach($alunos as $al)<option value="{{ $al->id }}">{{ $al->nome }}</option>@endforeach</select>
                            </div>
                            <div class="fg"><label class="lbl">Dia da semana</label>
                                <select name="dia_semana">
                                    @foreach(['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $idx => $dia)
                                        <option value="{{ $idx }}" {{ $idx === 1 ? 'selected' : '' }}>{{ $dia }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="fg" style="justify-content:flex-end;"><button class="btn btn-primary btn-sm"><i class="ph ph-magic-wand"></i> Aplicar</button></div>
                        </div>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
</body>

</html>
