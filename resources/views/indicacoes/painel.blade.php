<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('indicacao.painel.titulo') }} | SNR</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7cff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #fff;
            --text-dim: #9ca3af;
            --border: rgba(255,255,255,.08);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: var(--bg-dark);
            background-image:
                radial-gradient(circle at 12% 15%, rgba(124,255,0,.06) 0%, transparent 22%),
                radial-gradient(circle at 88% 85%, rgba(124,255,0,.05) 0%, transparent 22%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            padding: 32px 20px 60px;
        }
        .wrap { max-width: 960px; margin: 0 auto; }

        .topo { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:34px; flex-wrap:wrap; }
        .logo { font-family:'Syncopate',sans-serif; font-size:1.5rem; letter-spacing:5px; color:var(--primary); }
        .btn-voltar {
            display:inline-flex; align-items:center; gap:8px; text-decoration:none;
            color:var(--text-dim); border:1px solid var(--border); border-radius:10px;
            padding:9px 16px; font-size:.85rem; transition:.25s;
        }
        .btn-voltar:hover { color:var(--primary); border-color:rgba(124,255,0,.4); }

        h1 { font-family:'Syncopate',sans-serif; font-size:clamp(1.3rem,3.4vw,1.9rem); text-transform:uppercase; margin-bottom:10px; }
        h1 span { background:var(--primary); color:var(--bg-dark); padding:0 .12em; }
        .sub { color:var(--text-dim); font-size:.98rem; line-height:1.6; margin-bottom:30px; max-width:640px; }

        .card {
            background:var(--card-bg); border:1px solid var(--border);
            border-radius:20px; padding:28px; margin-bottom:22px;
        }

        .codigo-box { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
        .codigo {
            font-family:'Syncopate',sans-serif; font-size:clamp(1.4rem,5vw,2.1rem);
            letter-spacing:4px; color:var(--primary);
            background:rgba(124,255,0,.07); border:1px dashed rgba(124,255,0,.45);
            border-radius:14px; padding:14px 22px; user-select:all;
        }
        .acoes { display:flex; gap:10px; flex-wrap:wrap; }
        .btn {
            display:inline-flex; align-items:center; gap:8px; cursor:pointer;
            border:none; border-radius:11px; padding:12px 18px;
            font-size:.85rem; font-weight:700; text-decoration:none; transition:.25s;
        }
        .btn-primary { background:var(--primary); color:#000; }
        .btn-primary:hover { background:#6bde00; transform:translateY(-2px); }
        .btn-ghost { background:transparent; color:var(--text-main); border:1px solid var(--border); }
        .btn-ghost:hover { border-color:rgba(124,255,0,.45); color:var(--primary); }

        .link-convite {
            margin-top:18px; padding:12px 14px; border-radius:11px;
            background:rgba(255,255,255,.04); border:1px solid var(--border);
            font-size:.8rem; color:var(--text-dim); word-break:break-all;
        }

        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:22px; }
        .stat { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:22px; }
        .stat-label { font-size:.68rem; text-transform:uppercase; letter-spacing:1.6px; color:var(--text-dim); margin-bottom:10px; }
        .stat-valor { font-family:'Syncopate',sans-serif; font-size:1.7rem; color:var(--primary); }

        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:13px 10px; font-size:.86rem; border-bottom:1px solid var(--border); }
        th { color:var(--text-dim); font-size:.68rem; text-transform:uppercase; letter-spacing:1.4px; font-weight:700; }
        .badge {
            display:inline-block; padding:3px 10px; border-radius:999px;
            font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px;
            background:rgba(124,255,0,.12); color:var(--primary); border:1px solid rgba(124,255,0,.3);
        }
        .stat-nota { font-size:.7rem; color:var(--text-dim); margin-top:7px; line-height:1.4; }

        .regra {
            display:flex; gap:12px; align-items:flex-start;
            background:rgba(255,255,255,.03); border:1px solid var(--border);
            border-radius:14px; padding:16px 18px; margin-bottom:22px;
            font-size:.84rem; color:var(--text-dim); line-height:1.55;
        }
        .regra i { color:var(--primary); font-size:1.1rem; flex-shrink:0; margin-top:1px; }

        .prog {
            width:92px; height:5px; border-radius:99px; overflow:hidden;
            background:rgba(255,255,255,.1); margin-bottom:5px;
        }
        .prog span { display:block; height:100%; background:var(--primary); border-radius:99px; }
        .prog-txt { font-size:.7rem; color:var(--text-dim); }
        .prog-txt.ok { color:var(--primary); display:inline-flex; align-items:center; gap:5px; }
        .valor-ok { color:var(--primary); font-weight:700; }
        .valor-espera { color:#f0b429; }

        .vazio { text-align:center; padding:44px 20px; color:var(--text-dim); }
        .vazio i { font-size:2.4rem; color:rgba(124,255,0,.35); display:block; margin-bottom:14px; }
        .titulo-secao { font-size:.72rem; text-transform:uppercase; letter-spacing:2px; color:var(--text-dim); margin-bottom:18px; }
        .paginacao { margin-top:18px; }
        .paginacao a, .paginacao span { color:var(--text-dim); }
        /* No celular a data sai primeiro; o progresso até a meta é o que importa. */
        @media (max-width:560px) { .card { padding:20px; } th:nth-child(4), td:nth-child(4) { display:none; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topo">
        <span class="logo">SNR</span>
        <a href="{{ $voltar }}" class="btn-voltar"><i class="ph-bold ph-arrow-left"></i> Voltar ao painel</a>
    </div>

    <h1>{{ config('indicacao.painel.titulo') }}</h1>
    <p class="sub">{{ str_replace(':meta', $meta, config('indicacao.painel.chamada')) }}</p>

    <div class="card">
        <div class="titulo-secao">Seu código de indicação</div>
        <div class="codigo-box">
            <span class="codigo" id="codigo">{{ $cupom->codigo }}</span>
            <div class="acoes">
                <button type="button" class="btn btn-primary" id="btnCopiar">
                    <i class="ph-bold ph-copy"></i> Copiar link
                </button>
                <a class="btn btn-ghost" target="_blank" rel="noopener"
                   href="https://wa.me/?text={{ urlencode('Vem treinar comigo na SnrFit! Use meu código ' . $cupom->codigo . ' no cadastro: ' . $linkConvite) }}">
                    <i class="ph-bold ph-whatsapp-logo"></i> WhatsApp
                </a>
            </div>
        </div>
        <div class="link-convite" id="linkConvite">{{ $linkConvite }}</div>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="stat-label">Bônus liberado</div>
            <div class="stat-valor">R$ {{ number_format($bonus, 2, ',', '.') }}</div>
            <div class="stat-nota">{{ $total }} indicação(ões) com meta batida</div>
        </div>
        <div class="stat">
            <div class="stat-label">Aguardando meta</div>
            <div class="stat-valor" style="color:#f0b429;">R$ {{ number_format($bonusPendente, 2, ',', '.') }}</div>
            <div class="stat-nota">{{ $pendentes }} indicação(ões) a caminho</div>
        </div>
        <div class="stat">
            <div class="stat-label">Bônus por indicação</div>
            <div class="stat-valor">R$ {{ number_format((float) $cupom->bonus_valor, 2, ',', '.') }}</div>
            <div class="stat-nota">libera com {{ $meta }} alunos do indicado</div>
        </div>
    </div>

    <div class="regra">
        <i class="ph-bold ph-info"></i>
        <div>
            <p>{{ str_replace(':meta', $meta, config('indicacao.painel.regra')) }}</p>
            <p style="margin-top:8px; opacity:.8;">{{ config('indicacao.painel.aluno') }}</p>
        </div>
    </div>

    <div class="card">
        <div class="titulo-secao">Quem entrou pelo seu código</div>

        @if ($indicacoes->isEmpty())
            <div class="vazio">
                <i class="ph-bold ph-users-three"></i>
                Ainda não há indicações. Compartilhe seu código para começar.
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Perfil</th>
                        <th>Progresso</th>
                        <th>Data</th>
                        <th>Bônus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($indicacoes as $uso)
                        @php
                            $alunos = $uso->geraBonus() ? $uso->alunosDoIndicado() : null;
                            $pct    = $alunos === null ? 0 : min(100, (int) round($alunos / max($meta, 1) * 100));
                        @endphp
                        <tr>
                            <td>{{ $uso->usuario->nome ?? 'Conta removida' }}</td>
                            <td><span class="badge">{{ $uso->tipoLabel() }}</span></td>
                            <td>
                                @if (! $uso->geraBonus())
                                    <span class="prog-txt">—</span>
                                @elseif ($uso->estaLiberado())
                                    <span class="prog-txt ok"><i class="ph-bold ph-check-circle"></i> Meta batida</span>
                                @else
                                    <div class="prog"><span style="width:{{ $pct }}%"></span></div>
                                    <span class="prog-txt">{{ $alunos }} / {{ $meta }} alunos</span>
                                @endif
                            </td>
                            <td>{{ $uso->created_at?->format('d/m/Y') }}</td>
                            <td>
                                @if (! $uso->geraBonus())
                                    <span class="badge">{{ $uso->situacao() }}</span>
                                @else
                                    <span class="{{ $uso->estaLiberado() ? 'valor-ok' : 'valor-espera' }}">
                                        R$ {{ number_format((float) $uso->bonus_valor, 2, ',', '.') }}
                                    </span>
                                    <div class="prog-txt">{{ $uso->situacao() }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="paginacao">{{ $indicacoes->links() }}</div>
        @endif
    </div>
</div>

<script>
document.getElementById('btnCopiar').addEventListener('click', function () {
    const link = document.getElementById('linkConvite').textContent.trim();
    const btn  = this;

    navigator.clipboard.writeText(link).then(() => {
        btn.innerHTML = '<i class="ph-bold ph-check"></i> Copiado!';
        setTimeout(() => { btn.innerHTML = '<i class="ph-bold ph-copy"></i> Copiar link'; }, 2200);
    }).catch(() => {
        window.prompt('Copie o link de convite:', link);
    });
});
</script>
</body>
</html>
