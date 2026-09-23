<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faltas e reposições - SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.meta-pixel')
    @include('partials.brand-head')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0a0b0d; color: #eef0f2; padding: 24px 18px 60px; }
        .wrap { max-width: 900px; margin: 0 auto; }
        .back { display: inline-flex; align-items: center; gap: 6px; color: #9aa1ab; text-decoration: none; font-size: 0.8rem; margin-bottom: 16px; }
        .back:hover { color: #7cff00; }
        h1 { font-family: 'Syncopate', sans-serif; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 6px; }
        .sub { color: #9aa1ab; font-size: 0.85rem; margin-bottom: 20px; }

        .card { background: #16181d; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 18px 20px; margin-bottom: 12px; }
        .card.pendente { border-color: rgba(255,176,32,0.38); }
        .cab { display: flex; justify-content: space-between; gap: 14px; flex-wrap: wrap; align-items: flex-start; }
        .quem { font-weight: 800; font-size: 0.97rem; }
        .meta { color: #9aa1ab; font-size: 0.79rem; line-height: 1.65; margin-top: 5px; }
        .risco { text-decoration: line-through; }

        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 0.63rem; font-weight: 800; text-transform: uppercase; white-space: nowrap; }
        .b-pendente { background: rgba(255,176,32,0.15); color: #ffb020; }
        .b-aceita { background: rgba(0,255,136,0.13); color: #00ff88; }
        .b-recusada { background: rgba(255,68,68,0.13); color: #ff4444; }
        .b-avulsa { background: rgba(255,255,255,0.08); color: #9aa1ab; }

        .aviso { border-radius: 10px; padding: 11px 14px; margin-top: 12px; font-size: 0.8rem; line-height: 1.55; }
        .aviso.ok { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.25); color: #00ff88; }
        .aviso.neutro { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.09); color: #cfd3da; }

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

    <h1>Faltas e reposições</h1>
    <p class="sub">
        Aulas que os alunos desmarcaram.
        @if($pendentes > 0)
            <b style="color:#ffb020;">{{ $pendentes }} esperando você marcar a reposição.</b>
        @endif
    </p>

    @if(session('success'))<div class="alert a-ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert a-err">{{ session('error') }}</div>@endif

    @forelse($faltas as $falta)
        @php
            $pedido = $pedidos[$falta->id] ?? null;
            $estorno = $estornos[$falta->id] ?? null;
            $ehPacote = $falta->tipo_aula === 'pacote';
            $aguardando = $pedido && $pedido->estaPendente();
        @endphp

        <div class="card {{ $aguardando ? 'pendente' : '' }}">
            <div class="cab">
                <div>
                    <div class="quem">{{ $falta->cliente->nome ?? 'Aluno' }}</div>
                    <div class="meta">
                        <span class="risco">
                            {{ \Carbon\Carbon::parse($falta->data)->format('d/m/Y') }}
                            às {{ substr($falta->hora_inicio ?? '', 0, 5) }}
                        </span>
                        · {{ $ehPacote ? 'Pacote' : 'Avulsa' }}
                        @if($falta->cancelado_em)<br>Avisou em {{ \Carbon\Carbon::parse($falta->cancelado_em)->format('d/m/Y H:i') }}@endif
                        @if($falta->justificativa_cancelamento)<br>“{{ $falta->justificativa_cancelamento }}”@endif
                    </div>
                </div>

                <div>
                    @if($pedido)
                        <span class="badge b-{{ $pedido->status }}">
                            {{ $pedido->status === 'pendente' ? 'repor' : $pedido->status }}
                        </span>
                    @else
                        <span class="badge b-avulsa">cancelada</span>
                    @endif
                </div>
            </div>

            {{-- PACOTE AGUARDANDO: você escolhe o dia e a hora --}}
            @if($aguardando)
                <form method="POST" action="{{ route('personal.reposicoes.aceitar', $pedido->id) }}">
                    @csrf
                    <div class="campos">
                        <div class="campo">
                            <label>Remarcar para</label>
                            <input type="date" name="data" required min="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="campo">
                            <label>Início</label>
                            <input type="time" name="hora_inicio" required>
                        </div>
                        <div class="campo">
                            <label>Fim</label>
                            <input type="time" name="hora_fim" required>
                        </div>
                        <button class="b-ok" type="submit"><i class="ph ph-check"></i> Confirmar reposição</button>
                    </div>
                    <div class="campo" style="margin-top:10px;">
                        <label>Recado para o aluno</label>
                        <input type="text" name="resposta" placeholder="Opcional no aceite.">
                    </div>
                </form>

                <div class="sep">
                    <form method="POST" action="{{ route('personal.reposicoes.recusar', $pedido->id) }}">
                        @csrf
                        <div class="campos">
                            <div class="campo" style="flex:1 1 320px;">
                                <label>Não vou conseguir repor — explique ao aluno</label>
                                <input type="text" name="resposta" required minlength="5" placeholder="Ex: essa semana está cheia, consigo só na outra.">
                            </div>
                            <button class="b-no" type="submit">Recusar</button>
                        </div>
                    </form>
                </div>

            {{-- PACOTE JÁ RESPONDIDO --}}
            @elseif($pedido && $pedido->agendaReposta)
                <div class="aviso ok">
                    <i class="ph ph-calendar-check"></i>
                    Reposta em <b>{{ \Carbon\Carbon::parse($pedido->agendaReposta->data)->format('d/m/Y') }}
                    às {{ substr($pedido->agendaReposta->hora_inicio ?? '', 0, 5) }}</b>.
                    @if($pedido->resposta)<br>Você disse: “{{ $pedido->resposta }}”@endif
                </div>
            @elseif($pedido && $pedido->status === 'recusada')
                <div class="aviso neutro">
                    Você recusou a reposição. “{{ $pedido->resposta }}”
                </div>

            {{-- AVULSA: o dinheiro voltou, então não há aula a repor --}}
            @else
                <div class="aviso neutro">
                    <i class="ph ph-info"></i>
                    Aula avulsa cancelada dentro do prazo.
                    @if($estorno)
                        O valor de <b>R$ {{ number_format((float) $estorno->valor, 2, ',', '.') }}</b>
                        {{ $estorno->status === 'devolvido' ? 'foi devolvido ao aluno.' : 'está para ser devolvido ao aluno.' }}
                    @endif
                    Não há aula a repor — se ele quiser voltar, precisa marcar uma nova.
                </div>
            @endif
        </div>
    @empty
        <p class="vazio"><i class="ph ph-check-circle"></i> Nenhum aluno desmarcou aula até agora.</p>
    @endforelse
</div>
</body>
</html>
