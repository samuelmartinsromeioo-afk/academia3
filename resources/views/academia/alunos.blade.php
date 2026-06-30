<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos - {{ $academia->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --error: #ff4444;
            --border: rgba(255, 255, 255, 0.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: rgba(0,0,0,0.4);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        .top-bar .logo { font-weight: 900; letter-spacing: 2px; }
        .top-bar .logo span { color: var(--primary); }

        .btn-top {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 9px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.78rem;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }
        .btn-top.primary { background: var(--primary); color: #000; border-color: var(--primary); }
        .btn-top.primary:hover { filter: brightness(1.1); color: #000; }
        .top-actions { display: flex; align-items: center; gap: 10px; }

        .flash-success {
            max-width: 1100px;
            margin: 20px auto -6px;
            padding: 0 20px;
        }
        .flash-success .inner {
            background: rgba(0,255,136,0.10);
            border: 1px solid rgba(0,255,136,0.45);
            color: #00ff88;
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }

        header.page-head { margin-bottom: 24px; }
        header.page-head h1 { font-size: 1.8rem; font-weight: 900; }
        header.page-head p { color: var(--text-muted); font-size: 0.85rem; margin-top: 5px; }

        .search-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0 14px;
            margin-bottom: 24px;
            max-width: 460px;
        }
        .search-wrapper i { color: var(--primary); }
        .search-wrapper input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 13px 12px;
            color: #fff;
            outline: none;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .alunos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }

        .aluno-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            transition: 0.25s;
        }
        .aluno-card:hover { border-color: rgba(212,255,0,0.3); transform: translateY(-3px); }

        .aluno-head { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
        .aluno-head img { width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0; }
        .aluno-head h3 { font-size: 1.05rem; font-weight: 800; }
        .aluno-head small { color: var(--text-muted); font-size: 0.75rem; }

        .aluno-data { display: flex; flex-direction: column; gap: 10px; }
        .data-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.82rem;
            border-top: 1px solid var(--border);
            padding-top: 10px;
        }
        .data-row:first-child { border-top: none; padding-top: 0; }
        .data-row i { color: var(--primary); width: 16px; text-align: center; margin-top: 2px; flex-shrink: 0; }
        .data-row .label { color: var(--text-muted); text-transform: uppercase; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.5px; display: block; margin-bottom: 1px; }
        .data-row .value { color: #fff; line-height: 1.4; }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
            grid-column: 1 / -1;
        }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; display: block; opacity: 0.4; color: var(--primary); }

        .aluno-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 16px; }
        .btn-ficha { display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--primary); color: #000; border-radius: 10px; padding: 11px; font-weight: 800; font-size: 0.78rem; text-decoration: none; transition: 0.2s; }
        .btn-ficha:hover { background: #e8ff40; }
        .btn-periodizacao { display: flex; align-items: center; justify-content: center; gap: 8px; background: transparent; color: var(--primary); border: 1px solid rgba(212,255,0,0.4); border-radius: 10px; padding: 11px; font-weight: 800; font-size: 0.78rem; text-decoration: none; transition: 0.2s; }
        .btn-periodizacao:hover { background: rgba(212,255,0,0.1); border-color: var(--primary); }
        .btn-avaliacao { grid-column: 1 / -1; display: flex; align-items: center; justify-content: center; gap: 8px; background: transparent; color: var(--text-main); border: 1px solid var(--border); border-radius: 10px; padding: 11px; font-weight: 800; font-size: 0.78rem; text-decoration: none; transition: 0.2s; }
        .btn-avaliacao:hover { border-color: var(--primary); color: var(--primary); }

        .ctx-banner { background: rgba(212,255,0,0.08); border: 1px solid rgba(212,255,0,0.3); color: #cfe88a; border-radius: 12px; padding: 12px 16px; font-size: 0.82rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .ctx-banner i { color: var(--primary); }
        .filial-group { margin-bottom: 30px; }
        .filial-group-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px; margin-bottom: 14px; border-left: 3px solid var(--primary); background: rgba(255,255,255,0.03); border-radius: 8px; }
        .filial-group-head span { font-weight: 800; font-size: 0.92rem; display: inline-flex; align-items: center; gap: 8px; }
        .filial-group-head .fg-count { color: var(--text-muted); font-weight: 700; font-size: 0.74rem; }

        @media (max-width: 600px) {
            .top-bar { padding: 14px 20px; }
            .container { margin: 20px auto; }
        }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span> <span style="font-family:'Inter'; font-size:0.65rem; color:var(--text-muted); letter-spacing:1px; text-transform:uppercase;">| Alunos</span></div>
    <div class="top-actions">
        <a href="{{ route('academia.alunos.criar') }}" class="btn-top primary"><i class="ph ph-user-plus"></i> Cadastrar aluno</a>
        <a href="{{ route('academia.dashboard') }}" class="btn-top"><i class="ph ph-arrow-left"></i> Voltar ao painel</a>
    </div>
</div>

@if(session('success'))
    <div class="flash-success">
        <div class="inner"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    </div>
@endif

<div class="container">
    <header class="page-head">
        <h1>Meus Alunos</h1>
        @if($filialAtual)
            <p>Filial <strong>{{ $filialAtual->nome }}</strong> · {{ $alunos->count() }} aluno(s)</p>
        @else
            <p>Conta principal · {{ $alunos->count() }} aluno(s) em {{ $academia->nome }} (todas as filiais)</p>
        @endif
    </header>

    @if($filialAtual)
        <div class="ctx-banner"><i class="ph ph-git-branch"></i> Você está na subconta da filial <strong>{{ $filialAtual->nome }}</strong> — vê apenas os alunos desta unidade.</div>
    @endif

    <div class="search-wrapper">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" id="buscaAluno" placeholder="Buscar aluno por nome...">
    </div>

    @if($alunos->isEmpty())
        <div class="empty-state">
            <i class="ph ph-users"></i>
            <p>Nenhum aluno {{ $filialAtual ? 'nesta filial' : 'vinculado à academia' }} ainda.</p>
        </div>
    @elseif($alunosPorFilial->isNotEmpty())
        {{-- Conta principal: alunos separados por filial para comparar as unidades. --}}
        @foreach($alunosPorFilial as $grupo)
            @php $nomeFilial = $grupo->first()->filial?->nome ?? 'Matriz (sem filial)'; @endphp
            <div class="filial-group">
                <div class="filial-group-head">
                    <span><i class="ph ph-git-branch"></i> {{ $nomeFilial }}</span>
                    <span class="fg-count">{{ $grupo->count() }} aluno(s)</span>
                </div>
                <div class="alunos-grid">
                    @foreach($grupo as $aluno)
                        @include('academia._aluno_card')
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        {{-- Subconta de filial: lista única. --}}
        <div class="alunos-grid">
            @foreach($alunos as $aluno)
                @include('academia._aluno_card')
            @endforeach
        </div>
    @endif

    <div class="empty-state" id="semResultados" style="display:none;">
        <i class="ph ph-magnifying-glass"></i>
        <p>Nenhum aluno encontrado para a sua busca.</p>
    </div>
</div>

<script>
    const inputBusca = document.getElementById('buscaAluno');
    if (inputBusca) {
        inputBusca.addEventListener('input', () => {
            const termo = inputBusca.value.toLowerCase().trim();
            let visiveis = 0;
            document.querySelectorAll('.aluno-card').forEach(card => {
                const ok = !termo || card.dataset.busca.includes(termo);
                card.style.display = ok ? '' : 'none';
                if (ok) visiveis++;
            });
            // Esconde o cabeçalho de uma filial quando nenhum aluno dela está visível.
            document.querySelectorAll('.filial-group').forEach(grupo => {
                const algum = grupo.querySelector('.aluno-card:not([style*="display: none"])');
                grupo.style.display = algum ? '' : 'none';
            });
            const vazio = document.getElementById('semResultados');
            if (vazio) vazio.style.display = visiveis === 0 ? '' : 'none';
        });
    }
</script>
</body>
</html>
