{{--
    Página standalone de propósito: os layouts dos quatro papéis são
    incompatíveis entre si (layouts.nutri usa @yield('conteudo'), academia e
    dashboard usam @yield('content') e layouts.personal não tem @yield nenhum).
    Estender qualquer um deles renderizaria em branco para os outros.
--}}
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indique e ganhe | SNR FIT</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#d4ff00; --bg:#0a0b0d; --card:#16181d; --dim:#9ca3af; --border:rgba(255,255,255,.08); --ok:#00ff88; --warn:#ff9500; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:#fff; font-family:'Inter',sans-serif; padding:24px 16px 60px; }
        .wrap { max-width:960px; margin:0 auto; }
        a.voltar { color:var(--dim); text-decoration:none; font-size:.85rem; display:inline-flex; gap:6px; align-items:center; margin-bottom:18px; }
        h1 { font-size:1.5rem; }
        .sub { color:var(--dim); font-size:.88rem; margin-top:4px; line-height:1.6; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:16px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px; margin-bottom:16px; }
        .btn { display:inline-flex; align-items:center; gap:8px; background:var(--primary); color:#000; font-weight:700; border:none; padding:11px 16px; border-radius:10px; cursor:pointer; font-size:.85rem; text-decoration:none; font-family:inherit; }
        .btn-ghost { background:transparent; color:#fff; border:1px solid var(--border); }
        .muted { color:var(--dim); }
        .badge { font-size:.66rem; font-weight:700; padding:3px 9px; border-radius:20px; background:rgba(255,255,255,.08); color:var(--dim); }
        .badge-ok { background:rgba(0,255,136,.12); color:var(--ok); }
        .badge-warn { background:rgba(255,149,0,.12); color:var(--warn); }
        table { width:100%; border-collapse:collapse; font-size:.85rem; }
        th,td { padding:10px 8px; border-bottom:1px solid var(--border); text-align:left; }
        th { font-size:.68rem; text-transform:uppercase; color:var(--dim); letter-spacing:.5px; }
        td.n, th.n { text-align:right; }
        .empty { text-align:center; color:var(--dim); padding:26px; font-size:.88rem; }
        .empty i { font-size:2rem; display:block; margin-bottom:8px; }
        .codigo { font-size:2.1rem; font-weight:800; letter-spacing:4px; color:var(--primary); }
    </style>
</head>
<body>
<div class="wrap">
    <a href="{{ $voltarUrl }}" class="voltar"><i class="ph ph-arrow-left"></i> Voltar ao painel</a>

    <h1>Indique e ganhe</h1>
    <div class="sub">
        Para cada profissional que entrar com o seu código, você recebe
        <strong style="color:var(--primary);">{{ number_format($percentual * 100, 0) }}%</strong>
        de tudo que a plataforma arrecadar dele nos primeiros <strong>{{ $janelaDias }} dias</strong>
        a partir da aprovação do cadastro dele.
    </div>

    <div class="card" style="margin-top:18px; border-color:rgba(212,255,0,.3);">
        <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:center; justify-content:space-between;">
            <div>
                <div class="muted" style="font-size:.7rem; text-transform:uppercase; letter-spacing:1px;">Seu código</div>
                <div class="codigo">{{ $resumo['codigo'] }}</div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button class="btn btn-ghost" onclick="copiar(@json($resumo['codigo']))"><i class="ph ph-copy"></i> Copiar código</button>
                <button class="btn" onclick="copiar(@json($linkConvite))"><i class="ph ph-link"></i> Copiar link</button>
            </div>
        </div>
        <div class="muted" style="font-size:.72rem; margin-top:12px; word-break:break-all;">{{ $linkConvite }}</div>
    </div>

    <div class="grid">
        <div class="card" style="margin:0;">
            <div class="muted" style="font-size:.7rem;">A receber</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--primary);">R$ {{ number_format($resumo['a_receber'], 2, ',', '.') }}</div>
        </div>
        <div class="card" style="margin:0;">
            <div class="muted" style="font-size:.7rem;">Já recebido</div>
            <div style="font-size:1.6rem; font-weight:800;">R$ {{ number_format($resumo['recebido'], 2, ',', '.') }}</div>
        </div>
        <div class="card" style="margin:0;">
            <div class="muted" style="font-size:.7rem;">Indicados</div>
            <div style="font-size:1.6rem; font-weight:800;">{{ count($resumo['indicados']) }}</div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom:12px; font-size:1rem;">Quem entrou com seu código</h3>
        @forelse ($resumo['indicados'] as $ind)
            <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:12px 0; border-bottom:1px solid var(--border);">
                <div>
                    <strong>{{ $ind['model']->nome }}</strong>
                    <span class="badge">{{ config('indicacao.rotulos')[$ind['tipo']] ?? $ind['tipo'] }}</span>
                    <div class="muted" style="font-size:.75rem; margin-top:3px;">
                        @if (! $ind['model']->indicacao_inicio)
                            Aguardando aprovação do cadastro — a janela ainda não começou.
                        @elseif ($ind['janela_aberta'])
                            <span class="badge badge-ok">Janela aberta</span>
                            até {{ $ind['fim_janela']->format('d/m/Y') }}
                        @else
                            Janela encerrada em {{ $ind['fim_janela']->format('d/m/Y') }}
                        @endif
                    </div>
                </div>
                <div style="text-align:right;">
                    <strong style="color:var(--primary);">R$ {{ number_format($ind['gerado'], 2, ',', '.') }}</strong>
                    <div class="muted" style="font-size:.7rem;">rendeu a você</div>
                </div>
            </div>
        @empty
            <div class="empty"><i class="ph ph-users-three"></i>Ninguém usou seu código ainda. Compartilhe o link acima.</div>
        @endforelse
    </div>

    @if ($resumo['extrato']->isNotEmpty())
        <div class="card">
            <h3 style="margin-bottom:12px; font-size:1rem;">Extrato</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Data</th><th>Indicado</th><th class="n">Comissão gerada</th><th class="n">Sua parte</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($resumo['extrato'] as $c)
                        <tr>
                            <td class="muted">{{ $c->created_at->format('d/m/Y') }}</td>
                            <td>{{ optional($c->indicado())->nome ?? '—' }}</td>
                            <td class="n muted">R$ {{ number_format($c->base_company_fee, 2, ',', '.') }}</td>
                            <td class="n"><strong>R$ {{ number_format($c->valor, 2, ',', '.') }}</strong></td>
                            <td><span class="badge {{ $c->status === 'pago' ? 'badge-ok' : ($c->status === 'cancelado' ? '' : 'badge-warn') }}">{{ \App\Models\IndicacaoCredito::STATUS[$c->status] ?? $c->status }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
    function copiar(texto) {
        navigator.clipboard.writeText(texto)
            .then(() => alert('Copiado!\n\n' + texto))
            .catch(() => prompt('Copie manualmente:', texto));
    }
</script>
</body>
</html>
