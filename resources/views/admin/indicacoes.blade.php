<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupons e Indicações | Administração</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    {{-- Correção dos <select> em tema escuro (option branco em fundo branco). --}}
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">
    <style>
        :root {
            --primary: #7cff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #fff;
            --text-dim: #9ca3af;
            --border: rgba(255,255,255,.08);
            --danger: #ff6b6b;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background:var(--bg-dark); font-family:'Inter',sans-serif;
            color:var(--text-main); min-height:100vh; padding:30px 20px 60px;
        }
        .wrap { max-width:1180px; margin:0 auto; }
        .topo { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:28px; flex-wrap:wrap; }
        h1 { font-family:'Syncopate',sans-serif; font-size:1.35rem; text-transform:uppercase; }
        .btn-voltar {
            display:inline-flex; align-items:center; gap:8px; text-decoration:none; color:var(--text-dim);
            border:1px solid var(--border); border-radius:10px; padding:9px 16px; font-size:.85rem; transition:.25s;
        }
        .btn-voltar:hover { color:var(--primary); border-color:rgba(124,255,0,.4); }

        .alerta {
            padding:13px 18px; border-radius:12px; margin-bottom:20px; font-size:.9rem;
            background:rgba(124,255,0,.1); border:1px solid rgba(124,255,0,.35); color:var(--primary);
        }
        .alerta.erro { background:rgba(255,107,107,.1); border-color:rgba(255,107,107,.4); color:var(--danger); }

        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px; margin-bottom:24px; }
        .stat { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:20px; }
        .stat-label { font-size:.66rem; text-transform:uppercase; letter-spacing:1.6px; color:var(--text-dim); margin-bottom:9px; }
        .stat-valor { font-family:'Syncopate',sans-serif; font-size:1.55rem; color:var(--primary); }
        .stat-nota { font-size:.68rem; color:var(--text-dim); margin-top:7px; line-height:1.4; }

        .card { background:var(--card-bg); border:1px solid var(--border); border-radius:18px; padding:24px; margin-bottom:22px; }
        .titulo-secao { font-size:.7rem; text-transform:uppercase; letter-spacing:2px; color:var(--text-dim); margin-bottom:18px; }

        .form-linha { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; align-items:end; }
        label { display:block; font-size:.63rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:1.1px; margin-bottom:6px; }
        input, select {
            width:100%; background:rgba(255,255,255,.05); border:1px solid var(--border);
            border-radius:10px; padding:11px 13px; color:#fff; font-size:.87rem; outline:none; font-family:inherit;
        }
        input:focus, select:focus { border-color:var(--primary); }
        .btn {
            display:inline-flex; align-items:center; justify-content:center; gap:8px; cursor:pointer;
            border:none; border-radius:10px; padding:11px 18px; font-size:.83rem; font-weight:700;
            font-family:inherit; transition:.25s; text-decoration:none;
        }
        .btn-primary { background:var(--primary); color:#000; }
        .btn-primary:hover { background:#6bde00; }
        .btn-mini {
            background:transparent; border:1px solid var(--border); color:var(--text-dim);
            padding:6px 12px; font-size:.72rem; border-radius:8px; cursor:pointer; font-family:inherit; transition:.25s;
        }
        .btn-mini:hover { border-color:rgba(124,255,0,.45); color:var(--primary); }

        .tabela-scroll { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; min-width:820px; }
        th, td { text-align:left; padding:12px 10px; font-size:.85rem; border-bottom:1px solid var(--border); white-space:nowrap; }
        th { color:var(--text-dim); font-size:.66rem; text-transform:uppercase; letter-spacing:1.3px; font-weight:700; }
        .codigo { font-family:'Syncopate',sans-serif; font-size:.82rem; color:var(--primary); letter-spacing:1.5px; }
        .badge {
            display:inline-block; padding:3px 10px; border-radius:999px; font-size:.66rem;
            font-weight:700; text-transform:uppercase; letter-spacing:.6px;
            background:rgba(255,255,255,.06); color:var(--text-dim); border:1px solid var(--border);
        }
        .badge.on { background:rgba(124,255,0,.12); color:var(--primary); border-color:rgba(124,255,0,.3); }
        .badge.off { background:rgba(255,107,107,.1); color:var(--danger); border-color:rgba(255,107,107,.3); }
        .vazio { text-align:center; padding:40px; color:var(--text-dim); }
        .filtros { display:flex; gap:12px; flex-wrap:wrap; align-items:end; margin-bottom:18px; }
        .filtros > div { flex:1 1 180px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topo">
        <h1>Cupons &amp; Indicações</h1>
        <a href="{{ route('admin.dashboard') }}" class="btn-voltar"><i class="ph-bold ph-arrow-left"></i> Dashboard</a>
    </div>

    @if (session('sucesso'))
        <div class="alerta">{{ session('sucesso') }}</div>
    @endif
    @if (session('erro'))
        <div class="alerta erro">{{ session('erro') }}</div>
    @endif
    @if ($errors->any())
        <div class="alerta erro">{{ $errors->first() }}</div>
    @endif

    <div class="stats">
        <div class="stat">
            <div class="stat-label">Bônus liberado (sacável)</div>
            <div class="stat-valor">R$ {{ number_format($bonusTotal, 2, ',', '.') }}</div>
            <div class="stat-nota">{{ $totalIndicacoes }} indicação(ões) com janela fechada e {{ $meta }}+ alunos</div>
        </div>
        <div class="stat">
            <div class="stat-label">Acumulando na janela</div>
            <div class="stat-valor" style="color:#f0b429;">R$ {{ number_format($bonusPendente, 2, ',', '.') }}</div>
            <div class="stat-nota">{{ $pendentes }} indicação(ões) pendente(s)</div>
        </div>
        <div class="stat">
            <div class="stat-label">Saques a conferir</div>
            <div class="stat-valor" style="color:#f0b429;">R$ {{ number_format($saquesAbertos, 2, ',', '.') }}</div>
            <div class="stat-nota">
                R$ {{ number_format($saquesProcessando, 2, ',', '.') }} em processamento ·
                R$ {{ number_format($saquesPagos, 2, ',', '.') }} pagos
            </div>
        </div>
        <div class="stat">
            <div class="stat-label">Regra atual</div>
            <div class="stat-valor">{{ rtrim(rtrim(number_format($percentual * 100, 1, ',', '.'), '0'), ',') }}%</div>
            <div class="stat-nota">
                da COMISSÃO da plataforma em {{ $janelaDias }} dias · libera com {{ $meta }} alunos
                <br>≈ {{ rtrim(rtrim(number_format($percentual * \App\Services\AsaasService::feeRate() * 100, 2, ',', '.'), '0'), ',') }}% do bruto do indicado
            </div>
        </div>
    </div>

    {{-- Estado do pagamento automático: é dinheiro saindo sem humano no meio,
         então os limites vigentes ficam à vista, não só no .env. --}}
    <div class="card" style="border-color:{{ $saqueAuto ? 'rgba(124,255,0,.3)' : 'var(--border)' }};">
        <div class="titulo-secao">Pix automático do bônus de indicação</div>
        @if ($saqueAuto)
            <p style="font-size:.88rem; line-height:1.6;">
                <span class="badge on">Ligado</span>
                Pedido de até <strong>R$ {{ number_format($saqueAutoTeto, 2, ',', '.') }}</strong>
                sai por Pix automaticamente, com teto de
                <strong>R$ {{ number_format($saqueAutoTetoDiario, 2, ',', '.') }}</strong> por dia.
                Acima do teto, o pedido espera aqui.
            </p>
            <p style="font-size:.82rem; color:var(--text-dim); margin-top:10px;">
                Saldo na conta Asaas:
                @if ($saldoAsaas === null)
                    <span style="color:var(--danger);">não foi possível consultar agora</span>
                    — nenhum Pix automático sai sem confirmar saldo.
                @else
                    <strong style="color:{{ $saldoAsaas < $saquesAbertos ? 'var(--danger)' : 'var(--primary)' }};">
                        R$ {{ number_format($saldoAsaas, 2, ',', '.') }}
                    </strong>
                @endif
            </p>
        @else
            <p style="font-size:.88rem; line-height:1.6;">
                <span class="badge off">Desligado</span>
                Todo pedido de saque cai nesta fila para pagamento manual.
                Para ligar, defina <code>INDICACAO_SAQUE_AUTO=true</code> e rode <code>php artisan config:clear</code>.
            </p>
        @endif
    </div>

    <div class="card">
        <div class="titulo-secao">Pedidos de saque</div>

        @if ($saques->isEmpty())
            <div class="vazio">Nenhum pedido de saque.</div>
        @else
            <div class="tabela-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Indicador</th>
                            <th>Valor</th>
                            <th>Chave Pix</th>
                            <th>Status</th>
                            <th>Processado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($saques as $saque)
                            <tr>
                                <td>{{ $saque->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    {{ $saque->usuario->nome ?? 'Conta removida' }}
                                    <span style="color:var(--text-dim); font-size:.75rem;">
                                        ({{ class_basename($saque->usuario_type) }})
                                    </span>
                                </td>
                                <td>R$ {{ number_format((float) $saque->valor, 2, ',', '.') }}</td>
                                {{-- Chave completa só aqui, onde é preciso para pagar. --}}
                                <td style="user-select:all;">{{ $saque->pix_chave ?: '—' }}</td>
                                <td>
                                    @php
                                        $cls = match ($saque->status) {
                                            'pago' => 'on',
                                            'recusado', 'falhou' => 'off',
                                            default => '',
                                        };
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $saque->situacao() }}</span>
                                    @if ($saque->foiAutomatico())
                                        <div style="color:var(--text-dim); font-size:.68rem; margin-top:4px;">
                                            Pix automático{{ $saque->asaas_status ? ' · ' . $saque->asaas_status : '' }}
                                        </div>
                                    @endif
                                    @if ($saque->falha_motivo)
                                        <div style="color:var(--danger); font-size:.68rem; white-space:normal; max-width:200px;">
                                            {{ $saque->falha_motivo }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ $saque->processado_em?->format('d/m/Y') ?? '—' }}
                                    @if ($saque->receipt_url)
                                        <div style="font-size:.72rem;">
                                            <a href="{{ $saque->receipt_url }}" target="_blank" rel="noopener"
                                               style="color:var(--primary);">comprovante</a>
                                        </div>
                                    @endif
                                    @if ($saque->observacao)
                                        <div style="color:var(--text-dim); font-size:.72rem; white-space:normal; max-width:220px;">
                                            {{ $saque->observacao }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if ($saque->estaEmAberto())
                                        <div style="display:flex; gap:8px;">
                                            @if ($saqueAuto)
                                                {{-- Dispara o Pix com o valor do registro: o admin autoriza, não digita. --}}
                                                <form method="POST" action="{{ route('admin.indicacoes.saques.transferir', $saque->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-mini"
                                                            style="border-color:rgba(124,255,0,.45); color:var(--primary);">
                                                        Enviar Pix
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.indicacoes.saques.pagar', $saque->id) }}">
                                                @csrf
                                                <button type="submit" class="btn-mini">Marcar pago</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.indicacoes.saques.recusar', $saque->id) }}">
                                                @csrf
                                                <button type="submit" class="btn-mini">Recusar</button>
                                            </form>
                                        </div>
                                    @elseif ($saque->estaProcessando())
                                        <span style="color:var(--text-dim); font-size:.72rem;">
                                            no Asaas — aguardando desfecho
                                        </span>
                                    @else
                                        <span style="color:var(--text-dim); font-size:.75rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="titulo-secao">Novo cupom promocional</div>
        <form method="POST" action="{{ route('admin.indicacoes.store') }}">
            @csrf
            <div class="form-linha">
                <div>
                    <label>Código</label>
                    <input type="text" name="codigo" maxlength="32" placeholder="Auto se vazio" value="{{ old('codigo') }}">
                </div>
                <div>
                    <label>Descrição</label>
                    <input type="text" name="descricao" maxlength="255" placeholder="Campanha de lançamento" value="{{ old('descricao') }}">
                </div>
                <div>
                    <label>Bônus (R$)</label>
                    <input type="number" step="0.01" min="0" name="bonus_valor" placeholder="0,00" value="{{ old('bonus_valor') }}">
                </div>
                <div>
                    <label>Expira em</label>
                    <input type="date" name="expira_em" value="{{ old('expira_em') }}">
                </div>
                <div>
                    <label>Limite de usos</label>
                    <input type="number" min="1" name="limite_usos" placeholder="Ilimitado" value="{{ old('limite_usos') }}">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="ph-bold ph-plus"></i> Criar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="titulo-secao">Cupons</div>

        <form method="GET" action="{{ route('admin.indicacoes') }}" class="filtros">
            <div>
                <label>Buscar código</label>
                <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Ex: MARIA">
            </div>
            <div>
                <label>Tipo</label>
                <select name="tipo">
                    <option value="">Todos</option>
                    <option value="indicacao" @selected(request('tipo') === 'indicacao')>Indicação</option>
                    <option value="promocional" @selected(request('tipo') === 'promocional')>Promocional</option>
                </select>
            </div>
            <div style="flex:0 0 auto;">
                <button type="submit" class="btn btn-primary"><i class="ph-bold ph-magnifying-glass"></i> Filtrar</button>
            </div>
        </form>

        @if ($cupons->isEmpty())
            <div class="vazio">Nenhum cupom encontrado.</div>
        @else
            <div class="tabela-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Dono</th>
                            <th>Usos</th>
                            <th>Bônus</th>
                            <th>Validade</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cupons as $cupom)
                            <tr>
                                <td class="codigo">{{ $cupom->codigo }}</td>
                                <td><span class="badge">{{ ucfirst($cupom->tipo) }}</span></td>
                                <td>
                                    {{ $cupom->nomeDono() ?? '—' }}
                                    @if ($cupom->dono_type)
                                        <span style="color:var(--text-dim); font-size:.75rem;">
                                            ({{ class_basename($cupom->dono_type) }})
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $cupom->usos_count }}{{ $cupom->limite_usos ? ' / ' . $cupom->limite_usos : '' }}</td>
                                <td>
                                    @if ($cupom->tipo === \App\Models\Cupom::TIPO_INDICACAO)
                                        {{-- Indicação não tem valor fixo: é % do faturamento do indicado. --}}
                                        {{ rtrim(rtrim(number_format($percentual * 100, 1, ',', '.'), '0'), ',') }}% / {{ $janelaDias }}d
                                    @else
                                        R$ {{ number_format((float) $cupom->bonus_valor, 2, ',', '.') }}
                                    @endif
                                </td>
                                <td>{{ $cupom->expira_em?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $cupom->estaValido() ? 'on' : 'off' }}">
                                        {{ $cupom->estaValido() ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.indicacoes.toggle', $cupom->id) }}">
                                        @csrf
                                        <button type="submit" class="btn-mini">
                                            {{ $cupom->ativo ? 'Desativar' : 'Reativar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:18px;">{{ $cupons->links() }}</div>
        @endif
    </div>
</div>
</body>
</html>
