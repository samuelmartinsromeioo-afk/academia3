{{--
    Standalone como as demais telas do admin (dashboard, relatorio_financeiro,
    lista/detalhes não usam @extends). layouts.dashboard expõe @yield('content'),
    não 'conteudo' — estender errado renderizava a página inteira em branco.
--}}
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicações | SNR FIT Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#d4ff00; --bg:#0a0b0d; --card:#16181d; --dim:#9ca3af; --border:rgba(255,255,255,.08); --ok:#00ff88; --warn:#ff9500; --error:#ff4444; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:#fff; font-family:'Inter',sans-serif; padding:24px 16px 60px; }
        .wrap { max-width:1180px; margin:0 auto; }
        a.voltar { color:var(--dim); text-decoration:none; font-size:.85rem; display:inline-flex; gap:6px; align-items:center; margin-bottom:18px; }
        h1 { font-size:1.5rem; }
        .sub { color:var(--dim); font-size:.88rem; margin-top:4px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:16px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin:18px 0; }
        .btn { display:inline-flex; align-items:center; gap:6px; background:var(--primary); color:#000; font-weight:700; border:none; padding:8px 13px; border-radius:9px; cursor:pointer; font-size:.78rem; text-decoration:none; font-family:inherit; }
        .btn-ghost { background:transparent; color:#fff; border:1px solid var(--border); }
        .btn-danger { background:transparent; color:var(--error); border:1px solid rgba(255,68,68,.4); }
        .muted { color:var(--dim); }
        .badge { font-size:.66rem; font-weight:700; padding:3px 9px; border-radius:20px; background:rgba(255,255,255,.08); color:var(--dim); }
        .badge-ok { background:rgba(0,255,136,.12); color:var(--ok); }
        .badge-warn { background:rgba(255,149,0,.12); color:var(--warn); }
        table { width:100%; border-collapse:collapse; font-size:.85rem; }
        th,td { padding:10px 8px; border-bottom:1px solid var(--border); text-align:left; vertical-align:top; }
        th { font-size:.68rem; text-transform:uppercase; color:var(--dim); letter-spacing:.5px; }
        td.n, th.n { text-align:right; }
        .flash { padding:12px; border-radius:10px; margin-bottom:14px; font-size:.88rem; }
        .flash-ok { background:rgba(0,255,136,.1); border:1px solid rgba(0,255,136,.3); color:var(--ok); }
        .flash-err { background:rgba(255,68,68,.1); border:1px solid rgba(255,68,68,.3); color:var(--error); }
        .empty { text-align:center; color:var(--dim); padding:26px; font-size:.88rem; }
        .empty i { font-size:2rem; display:block; margin-bottom:8px; }
    </style>
</head>
<body>
<div class="wrap">
    <a href="{{ route('admin.dashboard') }}" class="voltar"><i class="ph ph-arrow-left"></i> Voltar ao dashboard</a>

    <h1>Programa de indicação</h1>
    <div class="sub">
        Créditos gerados para quem indicou. A transferência é feita fora do sistema —
        aqui você registra que ela aconteceu.
    </div>

    @if (session('success'))<div class="flash flash-ok" style="margin-top:16px;">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="flash flash-err" style="margin-top:16px;">{{ session('error') }}</div>@endif

    <div class="grid">
        <div class="card" style="margin:0;">
            <div class="muted" style="font-size:.7rem;">Total a pagar</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--primary);">R$ {{ number_format($totalAReceber, 2, ',', '.') }}</div>
        </div>
        <div class="card" style="margin:0;">
            <div class="muted" style="font-size:.7rem;">Já pago</div>
            <div style="font-size:1.6rem; font-weight:800;">R$ {{ number_format($totalPago, 2, ',', '.') }}</div>
        </div>
    </div>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
        @foreach (['a_receber' => 'A receber', 'pago' => 'Pagos', 'cancelado' => 'Cancelados', 'todos' => 'Todos'] as $k => $v)
            <a href="{{ route('admin.indicacoes', ['status' => $k]) }}" class="btn btn-ghost"
               @if($status === $k) style="border-color:var(--primary); color:var(--primary);" @endif>{{ $v }}</a>
        @endforeach
    </div>

    <div class="card">
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Data</th><th>Indicador</th><th>Indicado</th>
                        <th class="n">Comissão</th><th class="n">Crédito</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($creditos as $credito)
                    @php
                        // config('indicacao.tipos') guarda o NOME da classe (string),
                        // então precisa de ::find() estático, não de ?->find().
                        $classeIndicador = config('indicacao.tipos')[$credito->indicador_tipo] ?? null;
                        $indicador = $classeIndicador ? $classeIndicador::find($credito->indicador_id) : null;
                        $indicado = $credito->indicado();
                    @endphp
                    <tr>
                        <td class="muted">{{ $credito->created_at->format('d/m/Y') }}</td>
                        <td>
                            {{ $indicador->nome ?? '—' }}
                            <div class="muted" style="font-size:.7rem;">{{ config('indicacao.rotulos')[$credito->indicador_tipo] ?? $credito->indicador_tipo }}</div>
                        </td>
                        <td>
                            {{ $indicado->nome ?? '—' }}
                            <div class="muted" style="font-size:.7rem;">
                                {{ config('indicacao.rotulos')[$credito->indicado_tipo] ?? $credito->indicado_tipo }}
                                · {{ $credito->origem }}
                            </div>
                        </td>
                        <td class="n muted">R$ {{ number_format($credito->base_company_fee, 2, ',', '.') }}</td>
                        <td class="n"><strong>R$ {{ number_format($credito->valor, 2, ',', '.') }}</strong></td>
                        <td>
                            <span class="badge {{ $credito->status === 'pago' ? 'badge-ok' : ($credito->status === 'cancelado' ? '' : 'badge-warn') }}">
                                {{ \App\Models\IndicacaoCredito::STATUS[$credito->status] ?? $credito->status }}
                            </span>
                            @if ($credito->observacao)<div class="muted" style="font-size:.68rem;">{{ $credito->observacao }}</div>@endif
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            @if ($credito->status === 'a_receber')
                                <form method="POST" action="{{ route('admin.indicacoes.pago', $credito->id) }}" style="display:inline;"
                                      onsubmit="return confirm('Confirmar que o valor já foi transferido ao indicador?')">
                                    @csrf @method('PUT')
                                    <button class="btn"><i class="ph ph-check"></i> Marcar pago</button>
                                </form>
                                <form method="POST" action="{{ route('admin.indicacoes.cancelar', $credito->id) }}" style="display:inline;"
                                      onsubmit="this.observacao.value = prompt('Motivo do cancelamento:') || ''; return this.observacao.value !== '';">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="observacao">
                                    <button class="btn btn-danger"><i class="ph ph-x"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty"><i class="ph ph-gift"></i>Nenhum crédito nesta situação.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px;">{{ $creditos->links() }}</div>
    </div>
</div>
</body>
</html>
