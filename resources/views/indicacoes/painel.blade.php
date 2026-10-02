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

        .alerta {
            padding:13px 18px; border-radius:12px; margin-bottom:20px; font-size:.9rem; line-height:1.5;
        }
        .alerta.ok { background:rgba(124,255,0,.1); border:1px solid rgba(124,255,0,.35); color:var(--primary); }
        .alerta.erro { background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.4); color:#ff6b6b; }

        .aviso-saque {
            display:flex; gap:10px; align-items:flex-start;
            font-size:.86rem; color:var(--text-dim); line-height:1.55;
        }
        .aviso-saque i { color:#f0b429; font-size:1.1rem; flex-shrink:0; margin-top:1px; }
        .aviso-saque.ok { color:var(--text-main); }
        .aviso-saque.ok i { color:var(--primary); }

        .form-saque { display:flex; gap:12px; align-items:end; flex-wrap:wrap; margin-top:18px; }
        .form-saque label {
            display:block; font-size:.63rem; font-weight:700; color:var(--primary);
            text-transform:uppercase; letter-spacing:1.1px; margin-bottom:6px;
        }
        .form-saque input {
            width:100%; background:rgba(255,255,255,.05); border:1px solid var(--border);
            border-radius:10px; padding:12px 14px; color:#fff; font-size:.88rem;
            outline:none; font-family:inherit;
        }
        .form-saque input:focus { border-color:var(--primary); }
        .nota-pix { font-size:.74rem; color:var(--text-dim); margin-top:10px; line-height:1.5; }

        /* Extrato por indicado: linha expansível sob a linha da indicação. */
        .linha-extrato > td { padding:0 10px 14px; border-bottom:1px solid var(--border); }
        .linha-extrato summary {
            cursor:pointer; display:inline-block; font-size:.74rem; color:var(--text-dim);
            padding:5px 0; list-style:none; transition:.2s;
        }
        .linha-extrato summary::marker, .linha-extrato summary::-webkit-details-marker { display:none; }
        .linha-extrato summary::before { content:'▸ '; color:var(--primary); }
        .linha-extrato details[open] summary::before { content:'▾ '; }
        .linha-extrato summary:hover { color:var(--primary); }
        /* No celular o extrato rola na horizontal em vez de esticar a página. */
        .extrato-scroll { overflow-x:auto; margin-top:10px; }
        table.extrato {
            background:rgba(255,255,255,.03);
            border:1px solid var(--border); border-radius:10px; overflow:hidden;
            min-width:420px;
        }
        table.extrato th, table.extrato td {
            padding:9px 12px; font-size:.78rem; border-bottom:1px solid var(--border);
            white-space:nowrap;
        }
        table.extrato tr:last-child td { border-bottom:none; }
        table.extrato th { font-size:.62rem; letter-spacing:1.1px; }
        .extrato-total td { font-weight:700; background:rgba(124,255,0,.04); }
        .extrato-nota { font-size:.72rem; color:var(--text-dim); margin-top:8px; line-height:1.5; }

        .vazio { text-align:center; padding:44px 20px; color:var(--text-dim); }
        .vazio i { font-size:2.4rem; color:rgba(124,255,0,.35); display:block; margin-bottom:14px; }
        .titulo-secao { font-size:.72rem; text-transform:uppercase; letter-spacing:2px; color:var(--text-dim); margin-bottom:18px; }
        .paginacao { margin-top:18px; }
        .paginacao a, .paginacao span { color:var(--text-dim); }
        /* No celular o rótulo do perfil sai primeiro: janela e bônus é o que importa.
           Escopado na tabela de indicados para não comer coluna da tabela de saques,
           e com `>` para não atingir a tabela do extrato, que é aninhada nesta. */
        @media (max-width:560px) {
            .card { padding:20px; }
            .tabela-indicados > thead > tr > th:nth-child(2),
            .tabela-indicados > tbody > tr > td:nth-child(2) { display:none; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topo">
        <span class="logo">SNR</span>
        <a href="{{ $voltar }}" class="btn-voltar"><i class="ph-bold ph-arrow-left"></i> Voltar ao painel</a>
    </div>

    @php
        // :pct, :dias e :meta vêm de config/indicacao.php — copy nossa, não input.
        $copy = fn ($chave) => str_replace(
            [':pct', ':dias', ':meta'],
            [rtrim(rtrim(number_format($percentual * 100, 1, ',', '.'), '0'), ',') . '%', $janelaDias, $meta],
            config('indicacao.painel.' . $chave)
        );
    @endphp

    <h1>{{ config('indicacao.painel.titulo') }}</h1>
    <p class="sub">{{ $copy('chamada') }}</p>

    @if (session('sucesso'))
        <div class="alerta ok">{{ session('sucesso') }}</div>
    @endif
    @if ($errors->any())
        <div class="alerta erro">{{ $errors->first() }}</div>
    @endif

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
            <div class="stat-label">Disponível para saque</div>
            <div class="stat-valor">R$ {{ number_format($saldo, 2, ',', '.') }}</div>
            <div class="stat-nota">janela fechada e meta batida</div>
        </div>
        <div class="stat">
            <div class="stat-label">Acumulando</div>
            <div class="stat-valor" style="color:#f0b429;">R$ {{ number_format($bonusPendente, 2, ',', '.') }}</div>
            <div class="stat-nota">{{ $pendentes }} indicação(ões) na janela ou aguardando meta</div>
        </div>
        <div class="stat">
            <div class="stat-label">Em análise</div>
            <div class="stat-valor" style="color:#f0b429;">R$ {{ number_format($emSaque, 2, ',', '.') }}</div>
            <div class="stat-nota">saque pedido, aguardando o Pix</div>
        </div>
        <div class="stat">
            <div class="stat-label">Já recebido</div>
            <div class="stat-valor">R$ {{ number_format($sacado, 2, ',', '.') }}</div>
            <div class="stat-nota">{{ $total }} indicação(ões) liberada(s) no total</div>
        </div>
    </div>

    <div class="regra">
        <i class="ph-bold ph-info"></i>
        <div>
            <p>{{ $copy('regra') }}</p>
            <p style="margin-top:8px; opacity:.8;">{{ config('indicacao.painel.aluno') }}</p>
            <p style="margin-top:8px; opacity:.8;">
                {{ config('indicacao.painel.saque') }}
                @if ($saqueAuto)
                    {{ str_replace(':teto', 'R$ ' . number_format($saqueAutoTeto, 2, ',', '.'), config('indicacao.painel.saque_auto')) }}
                @else
                    {{ config('indicacao.painel.saque_manual') }}
                @endif
            </p>
        </div>
    </div>

    <div class="card">
        <div class="titulo-secao">Sacar bônus</div>

        @if ($temAberto)
            <p class="aviso-saque">
                <i class="ph-bold ph-hourglass-medium"></i>
                Você já tem um pedido de R$ {{ number_format($emSaque, 2, ',', '.') }} em andamento.
                Assim que ele for concluído você pode pedir o próximo.
            </p>
        @elseif ($saldo < $saqueMinimo)
            <p class="aviso-saque">
                <i class="ph-bold ph-lock-simple"></i>
                Saldo disponível de R$ {{ number_format($saldo, 2, ',', '.') }}.
                O saque abre a partir de R$ {{ number_format($saqueMinimo, 2, ',', '.') }}, e só entra na conta
                o bônus de indicação cuja janela de {{ $janelaDias }} dias já fechou.
            </p>
        @else
            @php $viraPixNaHora = $saqueAuto && $saldo <= $saqueAutoTeto; @endphp
            <p class="aviso-saque ok">
                <i class="ph-bold ph-check-circle"></i>
                R$ {{ number_format($saldo, 2, ',', '.') }} liberados.
                @if ($viraPixNaHora)
                    Confira a chave Pix com atenção — o envio é imediato e não dá para desfazer.
                @else
                    Informe a chave Pix para receber.
                @endif
            </p>
            <form method="POST" action="{{ route('indicacoes.saque') }}" class="form-saque">
                @csrf
                <div style="flex:1 1 260px;">
                    <label for="pix_chave">Chave Pix</label>
                    <input type="text" id="pix_chave" name="pix_chave" maxlength="140" required
                           autocomplete="off" placeholder="CPF, e-mail, telefone ou chave aleatória"
                           value="{{ old('pix_chave') }}">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="ph-bold ph-hand-coins"></i> Solicitar R$ {{ number_format($saldo, 2, ',', '.') }}
                </button>
            </form>
            <p class="nota-pix">
                O valor é calculado pelo sistema a partir das indicações já liberadas — não é possível pedir um valor diferente.
                @unless ($viraPixNaHora)
                    @if ($saqueAuto)
                        Como o valor passa de R$ {{ number_format($saqueAutoTeto, 2, ',', '.') }}, a equipe confere antes de pagar.
                    @endif
                @endunless
            </p>
        @endif

        @if ($meusSaques->isNotEmpty())
            <div class="titulo-secao" style="margin-top:26px;">Seus pedidos</div>
            <table>
                <thead>
                    <tr><th>Data</th><th>Valor</th><th>Chave Pix</th><th>Situação</th></tr>
                </thead>
                <tbody>
                    @foreach ($meusSaques as $saque)
                        <tr>
                            <td>{{ $saque->created_at?->format('d/m/Y') }}</td>
                            <td>R$ {{ number_format((float) $saque->valor, 2, ',', '.') }}</td>
                            <td>{{ $saque->pixMascarada() }}</td>
                            <td>
                                <span class="badge">{{ $saque->situacao() }}</span>
                                @if ($saque->receipt_url)
                                    <div class="prog-txt">
                                        <a href="{{ $saque->receipt_url }}" target="_blank" rel="noopener"
                                           style="color:var(--primary);">ver comprovante</a>
                                    </div>
                                @endif
                                @if ($saque->observacao)
                                    <div class="prog-txt">{{ $saque->observacao }}</div>
                                @endif
                                @if ($saque->status === \App\Models\IndicacaoSaque::STATUS_FALHOU && $saque->falha_motivo)
                                    {{-- Falha devolve o saldo: o motivo é o que o usuário precisa corrigir. --}}
                                    <div class="prog-txt">{{ $saque->falha_motivo }} O valor voltou para o seu saldo.</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <div class="titulo-secao">Quem entrou pelo seu código</div>

        @if ($indicacoes->isEmpty())
            <div class="vazio">
                <i class="ph-bold ph-users-three"></i>
                Ainda não há indicações. Compartilhe seu código para começar.
            </div>
        @else
            <table class="tabela-indicados">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Perfil</th>
                        <th>Janela de {{ $janelaDias }} dias</th>
                        <th>Meta de alunos</th>
                        <th>Bônus acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($indicacoes as $uso)
                        @php
                            $alunos = $uso->geraBonus() ? $uso->alunosDoIndicado() : null;
                            $pctMeta = $alunos === null ? 0 : min(100, (int) round($alunos / max($meta, 1) * 100));
                            // Quanto da janela já correu, para a barra de progresso.
                            $pctJanela = 0;
                            if ($uso->janelaIniciada()) {
                                $total = max(1, $uso->janela_inicio->diffInHours($uso->janela_fim));
                                $corrido = $uso->janela_inicio->diffInHours(now(), false);
                                $pctJanela = max(0, min(100, (int) round($corrido / $total * 100)));
                            }
                        @endphp
                        <tr>
                            <td>
                                {{ $uso->usuario->nome ?? 'Conta removida' }}
                                <div class="prog-txt">entrou em {{ $uso->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td><span class="badge">{{ $uso->tipoLabel() }}</span></td>
                            <td>
                                @if (! $uso->geraBonus())
                                    <span class="prog-txt">—</span>
                                @elseif (! $uso->janelaIniciada())
                                    <span class="prog-txt">Começa na aprovação</span>
                                @else
                                    <div class="prog"><span style="width:{{ $pctJanela }}%"></span></div>
                                    <span class="prog-txt">
                                        @if ($uso->janelaAberta())
                                            faltam {{ $uso->diasRestantes() }}d — até {{ $uso->janela_fim->format('d/m/Y') }}
                                        @else
                                            encerrada em {{ $uso->janela_fim->format('d/m/Y') }}
                                        @endif
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if (! $uso->geraBonus())
                                    <span class="prog-txt">—</span>
                                @elseif ($uso->estaLiberado())
                                    <span class="prog-txt ok"><i class="ph-bold ph-check-circle"></i> Batida</span>
                                @else
                                    <div class="prog"><span style="width:{{ $pctMeta }}%"></span></div>
                                    <span class="prog-txt">{{ $alunos }} / {{ $meta }} alunos</span>
                                @endif
                            </td>
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

                        {{-- Extrato: de quais receitas do indicado saiu o valor.
                             Sem JS: <details> nativo, uma linha por crédito. --}}
                        @if ($uso->creditos->isNotEmpty())
                            <tr class="linha-extrato">
                                <td colspan="5">
                                    <details>
                                        @php
                                            $qtd = $uso->creditos->count();
                                            $resumo = 'Ver extrato — ' . $qtd . ' ' . ($qtd === 1 ? 'receita' : 'receitas')
                                                . ' de ' . ($uso->usuario->nome ?? 'indicado');
                                        @endphp
                                        <summary>{{ $resumo }}</summary>
                                        <div class="extrato-scroll">
                                        <table class="extrato">
                                            <thead>
                                                <tr>
                                                    <th>Data</th>
                                                    <th>Origem</th>
                                                    <th>Faturou</th>
                                                    <th>Sua parte</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($uso->creditos as $credito)
                                                    <tr>
                                                        <td>{{ $credito->ocorreu_em?->format('d/m/Y') ?? '—' }}</td>
                                                        <td>{{ $credito->origemLabel() }}</td>
                                                        <td>R$ {{ number_format((float) $credito->base_valor, 2, ',', '.') }}</td>
                                                        <td>
                                                            <span class="valor-ok">R$ {{ number_format((float) $credito->valor, 2, ',', '.') }}</span>
                                                            <span class="prog-txt">
                                                                ({{ rtrim(rtrim(number_format((float) $credito->percentual * 100, 1, ',', '.'), '0'), ',') }}%)
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                <tr class="extrato-total">
                                                    <td colspan="2">Total</td>
                                                    <td>R$ {{ number_format((float) $uso->creditos->sum('base_valor'), 2, ',', '.') }}</td>
                                                    <td class="valor-ok">R$ {{ number_format((float) $uso->creditos->sum('valor'), 2, ',', '.') }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        </div>
                                        @if ($uso->janelaAberta())
                                            <p class="extrato-nota">
                                                A janela fecha em {{ $uso->janela_fim->format('d/m/Y') }} —
                                                receita nova do indicado até lá ainda entra nesta conta.
                                            </p>
                                        @else
                                            <p class="extrato-nota">
                                                Janela encerrada em {{ $uso->janela_fim?->format('d/m/Y') }}:
                                                este valor não muda mais.
                                            </p>
                                        @endif
                                    </details>
                                </td>
                            </tr>
                        @endif
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
