<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devoluções - Administração</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.meta-pixel')
    @include('partials.brand-head')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0a0b0d; color: #eef0f2; padding: 28px 20px 60px; }
        .wrap { max-width: 1100px; margin: 0 auto; }
        h1 { font-family: 'Syncopate', sans-serif; font-size: 1.3rem; text-transform: uppercase; margin-bottom: 6px; }
        .sub { color: #9aa1ab; font-size: 0.85rem; margin-bottom: 22px; }
        .back { display: inline-flex; align-items: center; gap: 6px; color: #9aa1ab; text-decoration: none; font-size: 0.8rem; margin-bottom: 18px; }
        .back:hover { color: #7cff00; }

        .resumo { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .kpi { background: #16181d; border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; padding: 16px 20px; min-width: 180px; }
        .kpi label { display: block; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 1px; color: #9aa1ab; font-weight: 800; margin-bottom: 6px; }
        .kpi .v { font-size: 1.4rem; font-weight: 800; }
        .kpi .v.alerta { color: #ffb020; }

        .filtros { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .filtros a { padding: 8px 14px; border-radius: 9px; border: 1px solid rgba(255,255,255,0.1); color: #cfd3da; text-decoration: none; font-size: 0.76rem; font-weight: 700; }
        .filtros a.on { background: #7cff00; color: #0a0b0d; border-color: #7cff00; }

        .card { background: #16181d; border: 1px solid rgba(255,255,255,0.07); border-radius: 16px; padding: 18px 20px; margin-bottom: 12px; }
        .linha { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: flex-start; }
        .quem { font-weight: 800; font-size: 0.95rem; }
        .meta { color: #9aa1ab; font-size: 0.78rem; line-height: 1.6; margin-top: 4px; }
        .valor { font-size: 1.25rem; font-weight: 800; color: #7cff00; white-space: nowrap; }

        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 0.64rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .b-pendente { background: rgba(255,176,32,0.15); color: #ffb020; }
        .b-devolvido { background: rgba(0,255,136,0.13); color: #00ff88; }
        .b-recusado { background: rgba(255,68,68,0.13); color: #ff4444; }
        .aviso { background: rgba(255,176,32,0.1); border: 1px solid rgba(255,176,32,0.3); color: #ffb020; padding: 8px 12px; border-radius: 9px; font-size: 0.74rem; margin-top: 10px; }

        form.acoes { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; align-items: center; }
        form.acoes input[type=text] { flex: 1 1 260px; background: #0a0b0d; border: 1px solid rgba(255,255,255,0.12); color: #fff; padding: 9px 12px; border-radius: 9px; font-size: 0.8rem; font-family: inherit; }
        button { border: none; cursor: pointer; padding: 9px 16px; border-radius: 9px; font-weight: 800; font-size: 0.76rem; font-family: inherit; }
        .b-ok { background: #7cff00; color: #0a0b0d; }
        .b-no { background: transparent; color: #ff4444; border: 1px solid rgba(255,68,68,0.4); }

        .alert { padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 0.85rem; font-weight: 700; }
        .a-ok { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.3); color: #00ff88; }
        .a-err { background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.3); color: #ff4444; }
        .vazio { color: #9aa1ab; text-align: center; padding: 40px 0; }
    </style>
</head>
<body class="ed-page">
<div class="wrap">
    <a href="{{ route('admin.dashboard') }}" class="back"><i class="ph ph-arrow-left"></i> Voltar ao painel</a>

    <h1>Devoluções</h1>
    <p class="sub">
        Aulas avulsas canceladas pelo aluno dentro do prazo de {{ \App\Services\AgendaService::HORAS_ANTECEDENCIA_CANCELAMENTO }}h.
        O sistema não devolve sozinho — a cobrança já foi dividida com o profissional. Devolva por fora e dê baixa aqui.
    </p>

    @if(session('success'))<div class="alert a-ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert a-err">{{ session('error') }}</div>@endif

    <div class="resumo">
        <div class="kpi"><label>Pendentes</label><div class="v alerta">{{ $qtdPendente }}</div></div>
        <div class="kpi"><label>Total a devolver</label><div class="v alerta">R$ {{ number_format((float) $totalPendente, 2, ',', '.') }}</div></div>
    </div>

    <div class="filtros">
        @foreach(['pendente' => 'Pendentes', 'devolvido' => 'Devolvidos', 'recusado' => 'Recusados', 'todos' => 'Todos'] as $chave => $rotulo)
            <a href="{{ route('admin.estornos', ['status' => $chave]) }}" class="{{ $status === $chave ? 'on' : '' }}">{{ $rotulo }}</a>
        @endforeach
    </div>

    @forelse($estornos as $e)
        <div class="card">
            <div class="linha">
                <div>
                    <div class="quem">
                        {{ $e->cliente->nome ?? 'Aluno removido' }}
                        <span class="badge b-{{ $e->status }}">{{ $e->status }}</span>
                    </div>
                    <div class="meta">
                        Profissional: {{ $e->personal->nome ?? '—' }}<br>
                        @if($e->agenda)
                            Aula: {{ \Carbon\Carbon::parse($e->agenda->data)->format('d/m/Y') }}
                            às {{ substr($e->agenda->hora_inicio ?? '', 0, 5) }}<br>
                        @endif
                        Pedido em {{ $e->created_at?->format('d/m/Y H:i') }}
                        @if($e->motivo)<br>Motivo do aluno: “{{ $e->motivo }}”@endif
                        @if($e->resolvido_em)<br>Resolvido em {{ $e->resolvido_em->format('d/m/Y H:i') }}@endif
                        @if($e->observacao_admin)<br>Observação: {{ $e->observacao_admin }}@endif
                    </div>
                    @unless($e->payment_id)
                        <div class="aviso">
                            <i class="ph ph-warning"></i> Sem pagamento vinculado — confira o valor no Asaas antes de devolver.
                            (Aulas criadas antes desta funcionalidade não guardavam o vínculo.)
                        </div>
                    @endunless
                </div>
                <div class="valor">R$ {{ number_format((float) $e->valor, 2, ',', '.') }}</div>
            </div>

            @if($e->status === \App\Models\Estorno::STATUS_PENDENTE)
                <form class="acoes" method="POST" action="{{ route('admin.estornos.devolver', $e->id) }}">
                    @csrf
                    <input type="text" name="observacao_admin" placeholder="Observação (opcional): como devolveu, comprovante...">
                    <button class="b-ok" type="submit"><i class="ph ph-check"></i> Marcar devolvido</button>
                    <button class="b-no" type="submit" formaction="{{ route('admin.estornos.recusar', $e->id) }}">Recusar</button>
                </form>
                <div class="meta" style="margin-top:6px;">Para recusar, a observação é obrigatória — ela vai para o aluno.</div>
            @endif
        </div>
    @empty
        <p class="vazio"><i class="ph ph-check-circle"></i> Nenhuma devolução {{ $status === 'todos' ? '' : $status }} por aqui.</p>
    @endforelse

    {{ $estornos->links() }}
</div>
</body>
</html>
