<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequência dos Alunos — {{ $personal->nome }}</title>
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
        .page-sub { color: var(--text-muted); font-size: 0.85rem; margin: 0 0 30px; }
        .btn-back { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .btn-primary { background: var(--primary); color: #000; border: none; padding: 9px 16px; border-radius: 10px; font-weight: 900; font-size: 0.75rem; cursor: pointer; text-transform: uppercase; text-decoration: none; display: inline-flex; align-items: center; gap: 7px; transition: 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(212,255,0,0.2); }
        .card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border); padding: 18px 20px; margin-bottom: 12px; }
        .aluno-row { display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; }
        .aluno-nome { font-size: 1rem; font-weight: 900; margin: 0 0 6px; }
        .freq-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 0.66rem; font-weight: 900; text-transform: uppercase; }
        .meta { color: var(--text-muted); font-size: 0.76rem; margin-top: 6px; display: flex; gap: 14px; flex-wrap: wrap; }
        .meta b { color: #fff; }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; display: block; opacity: 0.4; }
        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="{{ route('personal.dashboard') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="{{ $personal->foto ? asset('storage/'.$personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
        <span style="font-weight:700; font-size:0.9rem;">{{ $personal->nome }}</span>
    </div>
</div>

<div class="container">
    <h1 class="page-title"><i class="fas fa-user-check" style="margin-right:10px;"></i>Frequência dos Alunos</h1>
    <p class="page-sub">Marque presenças e faltas, acompanhe quem é assíduo e veja o resumo de cada aluno.</p>

    @if(session('success'))
        <div class="card" style="border-color: rgba(0,255,136,0.3); color: var(--success); font-size:0.85rem; font-weight:700;"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif

    @if($alunos->isEmpty())
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <p>Nenhum aluno encontrado ainda.</p>
        </div>
    @else
        @foreach($alunos as $aluno)
            @php $s = $stats[$aluno->id]; $c = $s['classificacao']; @endphp
            <div class="card">
                <div class="aluno-row">
                    <div>
                        <p class="aluno-nome">{{ $aluno->nome }}</p>
                        <span class="freq-badge" style="background: {{ $c['cor'] }}1a; color: {{ $c['cor'] }}; border: 1px solid {{ $c['cor'] }}55;">
                            <i class="fas fa-circle" style="font-size:0.5rem;"></i> {{ $c['label'] }}
                            @if($c['taxa'] !== null) · {{ round($c['taxa'] * 100) }}% @endif
                        </span>
                        <div class="meta">
                            <span><i class="fas fa-calendar-check" style="color:var(--success);"></i> <b>{{ $s['presentes'] }}</b> presença(s)</span>
                            <span><i class="fas fa-calendar-xmark" style="color:var(--error);"></i> <b>{{ $s['faltas'] }}</b> falta(s)</span>
                            <span><i class="fas fa-list-check"></i> <b>{{ $s['total'] }}</b> registro(s)</span>
                        </div>
                    </div>
                    <a href="{{ route('personal.frequencia.aluno', $aluno->id) }}" class="btn-primary"><i class="fas fa-calendar-day"></i> Marcar / Ver</a>
                </div>
            </div>
        @endforeach
    @endif
</div>

</body>
</html>
