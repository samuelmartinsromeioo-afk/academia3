<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequência — {{ $cliente->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00; --bg-dark: #0a0b0d; --card-bg: #16181d;
            --text-main: #fff; --text-muted: #a0a0a0; --border: rgba(255,255,255,0.08);
            --success: #00ff88; --error: #ff4444;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .page-title { color: var(--primary); font-size: 1.4rem; font-weight: 900; margin: 0 0 6px; }
        .page-sub { color: var(--text-muted); font-size: 0.85rem; margin: 0 0 24px; }
        .btn-back { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .section-label { font-size: 0.7rem; color: var(--primary); text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin: 28px 0 12px; display: flex; align-items: center; gap: 8px; }
        .section-label::after { content: ""; flex: 1; height: 1px; background: var(--border); }
        .card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border); padding: 20px; }
        .alert-success { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.3); color: var(--success); padding: 12px 16px; border-radius: 12px; margin-bottom: 18px; font-size: 0.85rem; font-weight: 700; }

        .class-hero { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .class-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; border-radius: 30px; font-size: 0.85rem; font-weight: 900; text-transform: uppercase; }
        .taxa-num { font-size: 2rem; font-weight: 900; }

        .resumo-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 4px; }
        .resumo-item { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; padding: 14px; }
        .resumo-item label { display: block; color: var(--text-muted); font-size: 0.62rem; text-transform: uppercase; font-weight: 800; margin-bottom: 6px; }
        .resumo-item .val { font-size: 1.3rem; font-weight: 900; }

        .filtro-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px; }
        .filtro-bar input[type=month] { background: var(--card-bg); border: 1px solid var(--border); color: #fff; padding: 9px 12px; border-radius: 10px; font-size: 0.85rem; outline: none; color-scheme: dark; }

        .dia-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 14px; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
        .dia-row:last-child { border-bottom: none; }
        .dia-info .data { font-weight: 800; font-size: 0.9rem; }
        .dia-info .dow { color: var(--text-muted); font-size: 0.72rem; }
        .mark-btns { display: flex; gap: 8px; }
        .btn-mark { border: 1px solid var(--border); background: rgba(255,255,255,0.04); color: var(--text-main); padding: 7px 13px; border-radius: 8px; font-size: 0.74rem; font-weight: 800; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; transition: 0.15s; }
        .btn-mark.pres:hover, .btn-mark.pres.on { background: rgba(0,255,136,0.15); border-color: var(--success); color: var(--success); }
        .btn-mark.falt:hover, .btn-mark.falt.on { background: rgba(255,68,68,0.15); border-color: var(--error); color: var(--error); }
        .status-badge { padding: 4px 11px; border-radius: 20px; font-size: 0.66rem; font-weight: 900; text-transform: uppercase; }
        .st-pres { background: rgba(0,255,136,0.12); color: var(--success); border: 1px solid rgba(0,255,136,0.3); }
        .st-falt { background: rgba(255,68,68,0.12); color: var(--error); border: 1px solid rgba(255,68,68,0.3); }

        .manual-form { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; }
        .manual-form input[type=date] { background: var(--card-bg); border: 1px solid var(--border); color: #fff; padding: 10px 12px; border-radius: 10px; outline: none; color-scheme: dark; font-size: 0.88rem; }
        .manual-form label { display:block; color: var(--text-muted); font-size: 0.62rem; text-transform: uppercase; font-weight: 800; margin-bottom: 6px; }

        .btn-del { background: transparent; border: 1px solid rgba(255,68,68,0.35); color: var(--error); padding: 6px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; cursor: pointer; }
        .btn-del:hover { background: rgba(255,68,68,0.12); }
        .empty { color: var(--text-muted); font-size: 0.85rem; padding: 12px 0; }
        .bar-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 0.8rem; }
        .bar-row .lbl { width: 110px; color: var(--text-muted); }
        .bar-track { flex: 1; height: 8px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden; }
        .bar-track div { height: 100%; background: var(--error); border-radius: 4px; }
        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="{{ route('personal.frequencia') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="{{ $personal->foto ? asset('storage/'.$personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
        <span style="font-weight:700; font-size:0.9rem;">{{ $personal->nome }}</span>
    </div>
</div>

<div class="container">
    <h1 class="page-title"><i class="fas fa-user-check" style="margin-right:10px;"></i>{{ $cliente->nome }}</h1>
    <p class="page-sub">Frequência, marcação de presença/falta e resumo do aluno.</p>

    @if(session('success'))
        <div class="alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif

    {{-- CLASSIFICAÇÃO + RESUMO --}}
    <div class="card">
        <div class="class-hero">
            <span class="class-badge" style="background: {{ $classificacao['cor'] }}1a; color: {{ $classificacao['cor'] }}; border: 1px solid {{ $classificacao['cor'] }}55;">
                <i class="fas fa-circle" style="font-size:0.6rem;"></i> {{ $classificacao['label'] }}
            </span>
            @if($classificacao['taxa'] !== null)
                <div>
                    <span class="taxa-num" style="color: {{ $classificacao['cor'] }};">{{ round($classificacao['taxa'] * 100) }}%</span>
                    <span style="color: var(--text-muted); font-size: 0.8rem;"> de presença</span>
                </div>
            @endif
        </div>

        <div class="resumo-grid" style="margin-top:18px;">
            <div class="resumo-item"><label>Presenças</label><div class="val" style="color:var(--success);">{{ $presentes }}</div></div>
            <div class="resumo-item"><label>Faltas (total)</label><div class="val" style="color:var(--error);">{{ $faltas }}</div></div>
            <div class="resumo-item"><label>Faltas no mês</label><div class="val">{{ $faltasMes }}</div></div>
            <div class="resumo-item">
                <label>Dia que mais falta</label>
                <div class="val" style="font-size:1rem;">
                    @if($diaMaisFalta)
                        {{ $diaMaisFalta['nome'] }} <span style="color:var(--text-muted); font-size:0.75rem; font-weight:600;">({{ $diaMaisFalta['qtd'] }}x)</span>
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MARCAÇÃO --}}
    <div class="section-label">Marcar presença / falta</div>

    <div class="filtro-bar">
        <form method="GET" action="{{ route('personal.frequencia.aluno', $cliente->id) }}" style="display:flex; gap:8px; align-items:center;">
            <label style="color:var(--text-muted); font-size:0.7rem; font-weight:800; text-transform:uppercase;"><i class="fas fa-filter"></i> Mês das aulas:</label>
            <input type="month" name="mes" value="{{ $mes }}" onchange="this.form.submit()">
        </form>
    </div>

    <div class="card" style="padding: 8px 14px;">
        @if($diasAgenda->isEmpty())
            <p class="empty"><i class="fas fa-info-circle"></i> Nenhuma aula agendada nesse mês. Use a marcação manual abaixo para registrar a frequência.</p>
        @else
            @foreach($diasAgenda as $dia)
                @php
                    $reg = $presencasPorData->get($dia);
                    $c = \Carbon\Carbon::parse($dia);
                @endphp
                <div class="dia-row">
                    <div class="dia-info">
                        <div class="data">{{ $c->format('d/m/Y') }}</div>
                        <div class="dow">{{ $diasSemana[$c->dayOfWeek] }}</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px;">
                        @if($reg)
                            <span class="status-badge {{ $reg->presente ? 'st-pres' : 'st-falt' }}">{{ $reg->presente ? 'Presente' : 'Faltou' }}</span>
                        @endif
                        <form method="POST" action="{{ route('personal.frequencia.marcar') }}" class="mark-btns">
                            @csrf
                            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                            <input type="hidden" name="data" value="{{ $dia }}">
                            <button class="btn-mark pres {{ $reg && $reg->presente ? 'on' : '' }}" name="presente" value="1"><i class="fas fa-check"></i> Foi</button>
                            <button class="btn-mark falt {{ $reg && !$reg->presente ? 'on' : '' }}" name="presente" value="0"><i class="fas fa-xmark"></i> Faltou</button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- MARCAÇÃO MANUAL --}}
    <div class="card" style="margin-top:12px;">
        <form method="POST" action="{{ route('personal.frequencia.marcar') }}" class="manual-form">
            @csrf
            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
            <div>
                <label>Marcar outra data</label>
                <input type="date" name="data" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <button class="btn-mark pres" name="presente" value="1"><i class="fas fa-check"></i> Presente</button>
            <button class="btn-mark falt" name="presente" value="0"><i class="fas fa-xmark"></i> Faltou</button>
        </form>
    </div>

    {{-- FALTAS POR DIA DA SEMANA --}}
    @if($faltasPorDia->isNotEmpty())
        <div class="section-label">Faltas por dia da semana</div>
        <div class="card">
            @php $maxFalta = $faltasPorDia->max(); @endphp
            @foreach($diasSemana as $num => $nome)
                @php $q = $faltasPorDia->get($num, 0); @endphp
                @if($q > 0)
                    <div class="bar-row">
                        <span class="lbl">{{ $nome }}</span>
                        <div class="bar-track"><div style="width: {{ $maxFalta > 0 ? round(($q / $maxFalta) * 100) : 0 }}%;"></div></div>
                        <b>{{ $q }}</b>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- HISTÓRICO --}}
    <div class="section-label">Histórico de registros</div>
    <div class="card" style="padding: 8px 14px;">
        @if($todas->isEmpty())
            <p class="empty">Nenhum registro de frequência ainda.</p>
        @else
            @foreach($todas as $reg)
                <div class="dia-row">
                    <div class="dia-info">
                        <div class="data">{{ $reg->data->format('d/m/Y') }}</div>
                        <div class="dow">{{ $diasSemana[$reg->data->dayOfWeek] }}</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="status-badge {{ $reg->presente ? 'st-pres' : 'st-falt' }}">{{ $reg->presente ? 'Presente' : 'Faltou' }}</span>
                        <form method="POST" action="{{ route('personal.frequencia.remover', $reg->id) }}" onsubmit="return confirm('Remover este registro?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-del"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

</body>
</html>
