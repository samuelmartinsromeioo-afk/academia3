<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações de Ficha — {{ $personal->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    @include('partials.brand-head')
    <style>
        :root {
            --primary: #7cff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --success: #00ff88;
            --error: #ff4444;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; padding: 0; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .page-title { color: var(--primary); font-size: 1.4rem; font-weight: 900; margin: 0 0 6px; }
        .page-sub { color: var(--text-muted); font-size: 0.85rem; margin: 0 0 30px; }
        .card { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border); padding: 24px; margin-bottom: 16px; transition: 0.3s; }
        .card:hover { border-color: rgba(124,255,0,0.2); }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; }
        .badge-pendente { background: rgba(255,165,0,0.15); color: #ffaa00; border: 1px solid rgba(255,165,0,0.3); }
        .badge-concluida { background: rgba(0,255,136,0.1); color: var(--success); border: 1px solid rgba(0,255,136,0.3); }
        .cliente-nome { font-size: 1.1rem; font-weight: 900; margin: 0 0 4px; }
        .valor-tag { color: var(--primary); font-weight: 900; font-size: 0.85rem; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .info-item label { display: block; color: var(--text-muted); font-size: 0.65rem; text-transform: uppercase; font-weight: 800; margin-bottom: 4px; }
        .info-item p { margin: 0; font-size: 0.85rem; color: var(--text-main); background: rgba(255,255,255,0.04); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); line-height: 1.5; }
        .info-item.full { grid-column: span 2; }
        .btn-concluir { background: var(--primary); color: #000; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 900; font-size: 0.8rem; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-concluir:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,255,0,0.2); }
        .btn-back { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 3rem; color: var(--text-muted); margin-bottom: 16px; display: block; }
        .empty-state p { color: var(--text-muted); font-size: 0.9rem; }
        .section-label { font-size: 0.7rem; color: var(--primary); text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .section-label::after { content: ""; flex: 1; height: 1px; background: var(--border); }
        .alert-success { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.3); color: var(--success); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }
        .alert-error { background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.3); color: var(--error); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }

        /* Cliente recorrente: fichas que o personal já montou para este aluno. */
        .recorrente { background: rgba(124,255,0,0.04); border: 1px dashed rgba(124,255,0,0.3); border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; }
        .recorrente-titulo { display: flex; align-items: center; gap: 8px; color: var(--primary); font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        .ficha-ant { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 8px 10px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); text-decoration: none; color: var(--text-main); margin-bottom: 6px; transition: 0.2s; }
        .ficha-ant:last-child { margin-bottom: 0; }
        .ficha-ant:hover { border-color: var(--primary); }
        .ficha-ant-nome { font-size: 0.85rem; font-weight: 700; }
        .ficha-ant-meta { color: var(--text-muted); font-size: 0.7rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .tag-inativa { font-size: 0.6rem; font-weight: 900; text-transform: uppercase; padding: 2px 7px; border-radius: 20px; background: rgba(255,255,255,0.07); color: var(--text-muted); border: 1px solid var(--border); }

        /* Aplicar um template já salvo direto na solicitação. */
        .tpl-form { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .tpl-form select { background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; font-size: 0.8rem; font-family: inherit; font-weight: 700; }
        .tpl-form select:focus { outline: none; border-color: var(--primary); }
        .btn-tpl { background: rgba(255,255,255,0.06); color: var(--text-main); border: 1px solid var(--border); padding: 10px 18px; border-radius: 10px; font-weight: 900; font-size: 0.8rem; cursor: pointer; text-transform: uppercase; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; }
        .btn-tpl:hover { border-color: var(--primary); color: var(--primary); }
        @media (max-width: 600px) {
            .tpl-form, .tpl-form select, .btn-tpl { width: 100%; }
            .info-grid { grid-template-columns: 1fr; }
            .info-item.full { grid-column: span 1; }
            .top-bar { padding: 15px 20px; }
        }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('personal.dashboard') }}" class="btn-back"><i class="ph ph-arrow-left"></i> Voltar</a>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="{{ $personal->foto ? asset('storage/'.$personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
        <span style="font-weight:700; font-size:0.9rem;">{{ $personal->nome }}</span>
    </div>
</div>

<div class="container">
    <div class="ed-eyebrow"><i class="ph ph-clipboard-text"></i> Pedidos</div><h1 class="ed-h">Solicitações de <span class="ed-mark">Ficha</span></h1>
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:30px;">
        <p class="page-sub" style="margin:0; flex:1; min-width:240px;">Fichas solicitadas pelos seus alunos. Monte a ficha no sistema e depois marque como concluída.</p>
        <a href="{{ route('templates.index') }}" class="btn-tpl" title="Seus modelos de ficha reutilizáveis">
            <i class="ph ph-cards"></i> Meus templates ({{ $templates->count() }})
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error"><i class="ph ph-warning-circle"></i> {{ session('error') }}</div>
    @endif

    @php $pendentes = $solicitacoes->where('status', 'pendente'); $concluidas = $solicitacoes->where('status', 'concluida'); @endphp

    @if($solicitacoes->isEmpty())
        <div class="empty-state">
            <i class="ph ph-tray"></i>
            <p>Nenhuma solicitação de ficha ainda.</p>
        </div>
    @else
        @if($pendentes->isNotEmpty())
        <div class="section-label">Pendentes ({{ $pendentes->count() }})</div>

        @foreach($pendentes as $s)
        <div class="card" style="border-left: 4px solid #ffaa00;">
            <div class="card-header">
                <div>
                    <p class="cliente-nome">{{ $s->cliente?->nome ?? 'Aluno' }}</p>
                    <span class="valor-tag"><i class="ph ph-currency-dollar"></i> R$ {{ number_format($s->valor, 2, ',', '.') }} — pago</span>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <span class="badge badge-pendente">Pendente</span>
                    <span style="color:var(--text-muted); font-size:0.7rem;">{{ $s->created_at->format('d/m/Y') }}</span>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label><i class="ph ph-target"></i> Objetivos</label>
                    <p>{{ $s->objetivos }}</p>
                </div>
                <div class="info-item">
                    <label><i class="ph ph-cell-signal-full"></i> Nível de Experiência</label>
                    <p>{{ ucfirst($s->nivel_experiencia) }}</p>
                </div>
                @if($s->condicoes_clinicas)
                <div class="info-item full">
                    <label><i class="ph ph-heartbeat"></i> Condições Clínicas</label>
                    <p>{{ $s->condicoes_clinicas }}</p>
                </div>
                @endif
                @if($s->observacoes)
                <div class="info-item full">
                    <label><i class="ph ph-chat-circle"></i> Observações</label>
                    <p>{{ $s->observacoes }}</p>
                </div>
                @endif
            </div>

            {{-- Cliente recorrente: já montei ficha para esta pessoa antes?
                 Se sim, mostra o trabalho anterior para ele partir dali em vez
                 de começar do zero. Fichas inativas entram de propósito — é
                 justamente o treino da temporada passada. --}}
            @php $anteriores = $fichasAnteriores[$s->id] ?? collect(); @endphp
            @if($anteriores->isNotEmpty())
            <div class="recorrente">
                <div class="recorrente-titulo">
                    <i class="ph ph-arrows-clockwise"></i>
                    Já foi seu aluno — {{ $anteriores->count() }} {{ $anteriores->count() === 1 ? 'ficha anterior' : 'fichas anteriores' }}
                </div>
                @foreach($anteriores->take(5) as $fa)
                <a href="{{ route('fichas-treino.aluno', ['clienteId' => $s->cliente_id]) }}" class="ficha-ant">
                    <span>
                        <span class="ficha-ant-nome">{{ $fa->nome_treino }}</span>
                        @unless($fa->ativo)<span class="tag-inativa">inativa</span>@endunless
                    </span>
                    <span class="ficha-ant-meta">
                        <span>{{ $fa->exercicios_count }} {{ $fa->exercicios_count === 1 ? 'exercício' : 'exercícios' }}</span>
                        <span>·</span>
                        <span>{{ $fa->getDiaSemanaNome() }}</span>
                        <span>·</span>
                        <span>{{ $fa->created_at?->format('d/m/y') }}</span>
                        <i class="ph ph-arrow-right"></i>
                    </span>
                </a>
                @endforeach
                @if($anteriores->count() > 5)
                <a href="{{ route('fichas-treino.aluno', ['clienteId' => $s->cliente_id]) }}" class="ficha-ant" style="justify-content:center; color:var(--text-muted); font-size:0.75rem;">
                    ver as outras {{ $anteriores->count() - 5 }}
                </a>
                @endif
            </div>
            @endif

            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                {{-- `origem=solicitacao` enxuga a tela de destino: quem chega por
                     aqui vem montar a ficha, então Periodização, Progresso e
                     Relatório ficam escondidos para não competir pela atenção.
                     Ver PersonalFichasTreinoLista.blade.php. --}}
                <a href="{{ route('fichas-treino.aluno', ['clienteId' => $s->cliente_id, 'origem' => 'solicitacao']) }}" class="btn-concluir" style="background:rgba(124,255,0,0.1); color:var(--primary); border:1px solid var(--primary);">
                    <i class="ph ph-plus"></i> Criar Ficha para {{ $s->cliente?->nome ?? 'Aluno' }}
                </a>

                {{-- Anamnese do aluno: é o que embasa a montagem do treino.
                     Lesões e restrições médicas vivem lá. --}}
                @if($temAnamnese->has($s->cliente_id))
                <a href="{{ route('anamnese.personal', $s->cliente_id) }}" class="btn-tpl">
                    <i class="ph ph-file-medical"></i> Ver anamnese
                </a>
                @else
                <span class="btn-tpl" style="opacity:0.45; cursor:not-allowed;" title="O aluno ainda não preencheu a anamnese">
                    <i class="ph ph-file-dashed"></i> Sem anamnese
                </span>
                @endif

                {{-- Concluir só depois de existir ficha DESTE pedido. A trava real
                     está em PersonalController@concluirSolicitacaoFicha; aqui o
                     botão desabilitado serve para explicar antes do clique. --}}
                @if($fichaFeita[$s->id] ?? false)
                <form action="{{ route('personal.solicitacoes-ficha.concluir', $s->id) }}" method="POST" onsubmit="return confirm('Marcar como concluída? O aluno será avisado de que a ficha está pronta.')">
                    @csrf
                    <button type="submit" class="btn-concluir"><i class="ph ph-check"></i> Marcar como Concluída</button>
                </form>
                @else
                <span class="btn-concluir" style="opacity:0.4; cursor:not-allowed; background:rgba(255,255,255,0.08); color:var(--text-muted);"
                      title="Monte a ficha primeiro — o aluno recebe o aviso de ficha pronta ao concluir">
                    <i class="ph ph-lock-simple"></i> Marcar como Concluída
                </span>
                @endif
            </div>
            @unless($fichaFeita[$s->id] ?? false)
            <p style="color:var(--text-muted); font-size:0.72rem; margin:10px 0 0; display:flex; align-items:center; gap:6px;">
                <i class="ph ph-info"></i> Monte a ficha para liberar a conclusão.
                @if($anteriores->isNotEmpty()) As fichas anteriores não contam — este pedido é novo. @endif
            </p>
            @endunless

            {{-- Aplicar um modelo já salvo. A rota é templates/{id}/aplicar, e o
                 id só é conhecido depois da escolha no select — por isso o
                 action é montado no submit. --}}
            @if($templates->isNotEmpty())
            <form method="POST" class="tpl-form" style="margin-top:12px;"
                  data-url="{{ route('templates.aplicar', '__ID__') }}"
                  onsubmit="return aplicarTemplate(this)">
                @csrf
                <input type="hidden" name="cliente_id" value="{{ $s->cliente_id }}">
                <select name="template_escolhido" required aria-label="Template">
                    <option value="">Aplicar template…</option>
                    @foreach($templates as $t)
                    <option value="{{ $t->id }}">{{ $t->nome }} ({{ count($t->exercicios ?? []) }} ex)</option>
                    @endforeach
                </select>
                <select name="dia_semana" required aria-label="Dia da semana">
                    <option value="">Dia…</option>
                    @foreach(['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $i => $nomeDia)
                    <option value="{{ $i }}">{{ $nomeDia }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-tpl"><i class="ph ph-lightning"></i> Aplicar</button>
            </form>
            @endif
        </div>
        @endforeach
        @endif

        @if($concluidas->isNotEmpty())
        <div class="section-label" style="margin-top:30px;">Concluídas ({{ $concluidas->count() }})</div>

        @foreach($concluidas as $s)
        <div class="card" style="opacity:0.7;">
            <div class="card-header">
                <div>
                    <p class="cliente-nome">{{ $s->cliente?->nome ?? 'Aluno' }}</p>
                    <span class="valor-tag">R$ {{ number_format($s->valor, 2, ',', '.') }}</span>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <span class="badge badge-concluida">Concluída</span>
                    <span style="color:var(--text-muted); font-size:0.7rem;">{{ $s->updated_at->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Objetivos</label>
                    <p>{{ $s->objetivos }}</p>
                </div>
                <div class="info-item">
                    <label>Nível</label>
                    <p>{{ ucfirst($s->nivel_experiencia) }}</p>
                </div>
            </div>
        </div>
        @endforeach
        @endif
    @endif
</div>

<script>
    // A rota de aplicar é templates/{id}/aplicar: o id vem do select, então o
    // action só pode ser montado aqui. Sem template escolhido, não envia.
    function aplicarTemplate(form) {
        var id = form.querySelector('[name="template_escolhido"]').value;
        if (!id) { return false; }
        form.action = form.dataset.url.replace('__ID__', id);
        return true;
    }
</script>

</body>
</html>
