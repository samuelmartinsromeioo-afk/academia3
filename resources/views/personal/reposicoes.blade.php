<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reposições - SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.meta-pixel')
    @include('partials.brand-head')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0a0b0d; color: #eef0f2; padding: 24px 18px 60px; }
        .wrap { max-width: 860px; margin: 0 auto; }
        .back { display: inline-flex; align-items: center; gap: 6px; color: #9aa1ab; text-decoration: none; font-size: 0.8rem; margin-bottom: 16px; }
        .back:hover { color: #7cff00; }
        h1 { font-family: 'Syncopate', sans-serif; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 6px; }
        .sub { color: #9aa1ab; font-size: 0.85rem; margin-bottom: 20px; }

        .card { background: #16181d; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 18px 20px; margin-bottom: 12px; }
        .card.pendente { border-color: rgba(255,176,32,0.35); }
        .quem { font-weight: 800; font-size: 0.97rem; }
        .meta { color: #9aa1ab; font-size: 0.79rem; line-height: 1.65; margin-top: 5px; }
        .sugestao { background: rgba(124,255,0,0.07); border: 1px solid rgba(124,255,0,0.22); border-radius: 10px; padding: 10px 13px; margin-top: 11px; font-size: 0.82rem; }

        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 0.63rem; font-weight: 800; text-transform: uppercase; }
        .b-pendente { background: rgba(255,176,32,0.15); color: #ffb020; }
        .b-aceita { background: rgba(0,255,136,0.13); color: #00ff88; }
        .b-recusada { background: rgba(255,68,68,0.13); color: #ff4444; }
        .b-cancelada { background: rgba(255,255,255,0.08); color: #9aa1ab; }

        form { margin-top: 14px; }
        .campos { display: flex; gap: 8px; flex-wrap: wrap; align-items: end; }
        .campo label { display: block; font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.6px; color: #9aa1ab; font-weight: 800; margin-bottom: 5px; }
        input { background: #0a0b0d; border: 1px solid rgba(255,255,255,0.12); color: #fff; padding: 9px 11px; border-radius: 9px; font-size: 0.82rem; font-family: inherit; color-scheme: dark; }
        input[type=text] { width: 100%; }
        button { border: none; cursor: pointer; padding: 10px 16px; border-radius: 9px; font-weight: 800; font-size: 0.77rem; font-family: inherit; }
        .b-ok { background: #7cff00; color: #0a0b0d; }
        .b-no { background: transparent; color: #ff4444; border: 1px solid rgba(255,68,68,0.4); }
        .sep { border-top: 1px solid rgba(255,255,255,0.07); margin: 14px 0 0; padding-top: 12px; }

        .alert { padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 0.85rem; font-weight: 700; }
        .a-ok { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.3); color: #00ff88; }
        .a-err { background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.3); color: #ff4444; }
        .vazio { color: #9aa1ab; text-align: center; padding: 40px 0; }
    </style>
</head>
<body class="ed-page">
<div class="wrap">
    <a href="{{ route('personal.dashboard') }}" class="back"><i class="ph ph-arrow-left"></i> Voltar</a>

    <h1>Reposições</h1>
    <p class="sub">Alunos de pacote que avisaram falta e querem repor a aula. Quem define o horário é você.</p>

    @if(session('success'))<div class="alert a-ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert a-err">{{ session('error') }}</div>@endif

    @forelse($pedidos as $p)
        <div class="card {{ $p->status === 'pendente' ? 'pendente' : '' }}">
            <div class="quem">
                {{ $p->cliente->nome ?? 'Aluno' }}
                <span class="badge b-{{ $p->status }}">{{ $p->status }}</span>
            </div>
            <div class="meta">
                @if($p->agenda)
                    Aula perdida: {{ \Carbon\Carbon::parse($p->agenda->data)->format('d/m/Y') }}
                    às {{ substr($p->agenda->hora_inicio ?? '', 0, 5) }}<br>
                @endif
                Avisou em {{ $p->created_at?->format('d/m/Y H:i') }}
                @if($p->motivo)<br>Motivo: “{{ $p->motivo }}”@endif
            </div>

            @if($p->data_sugerida)
                <div class="sugestao">
                    <i class="ph ph-lightbulb"></i> Sugestão do aluno:
                    <b>{{ $p->data_sugerida->format('d/m/Y') }}@if($p->hora_sugerida) às {{ substr($p->hora_sugerida, 0, 5) }}@endif</b>
                </div>
            @else
                <div class="sugestao"><i class="ph ph-lightbulb"></i> O aluno não sugeriu horário.</div>
            @endif

            @if($p->estaPendente())
                <form method="POST" action="{{ route('personal.reposicoes.aceitar', $p->id) }}">
                    @csrf
                    <div class="campos">
                        <div class="campo">
                            <label>Data da reposição</label>
                            <input type="date" name="data" required
                                   value="{{ $p->data_sugerida?->format('Y-m-d') }}"
                                   min="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="campo">
                            <label>Início</label>
                            <input type="time" name="hora_inicio" required value="{{ $p->hora_sugerida ? substr($p->hora_sugerida, 0, 5) : '' }}">
                        </div>
                        <div class="campo">
                            <label>Fim</label>
                            <input type="time" name="hora_fim" required>
                        </div>
                        <button class="b-ok" type="submit"><i class="ph ph-check"></i> Confirmar reposição</button>
                    </div>
                    <div class="campo" style="margin-top:10px;">
                        <label>Recado para o aluno</label>
                        <input type="text" name="resposta" placeholder="Opcional no aceite. Obrigatório se for recusar.">
                    </div>
                </form>

                <div class="sep">
                    <form method="POST" action="{{ route('personal.reposicoes.recusar', $p->id) }}">
                        @csrf
                        <div class="campos">
                            <div class="campo" style="flex:1 1 320px;">
                                <label>Não consigo repor — explique ou proponha outro horário</label>
                                <input type="text" name="resposta" required minlength="5" placeholder="Ex: nessa semana não tenho horário, consegue dia 30 às 9h?">
                            </div>
                            <button class="b-no" type="submit">Recusar</button>
                        </div>
                    </form>
                </div>
            @elseif($p->resposta)
                <div class="meta" style="margin-top:10px;">Sua resposta: “{{ $p->resposta }}”</div>
            @endif

            @if($p->agendaReposta)
                <div class="meta" style="margin-top:8px; color:#00ff88;">
                    <i class="ph ph-calendar-check"></i>
                    Reposta em {{ \Carbon\Carbon::parse($p->agendaReposta->data)->format('d/m/Y') }}
                    às {{ substr($p->agendaReposta->hora_inicio ?? '', 0, 5) }}
                </div>
            @endif
        </div>
    @empty
        <p class="vazio"><i class="ph ph-check-circle"></i> Nenhum pedido de reposição por aqui.</p>
    @endforelse
</div>
</body>
</html>
