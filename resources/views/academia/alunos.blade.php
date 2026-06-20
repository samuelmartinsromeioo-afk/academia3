<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos - {{ $academia->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .btn-ficha { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px; background: var(--primary); color: #000; border-radius: 10px; padding: 11px; font-weight: 800; font-size: 0.78rem; text-decoration: none; transition: 0.2s; }
        .btn-ficha:hover { background: #e8ff40; }

        @media (max-width: 600px) {
            .top-bar { padding: 14px 20px; }
            .container { margin: 20px auto; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span> <span style="font-family:'Inter'; font-size:0.65rem; color:var(--text-muted); letter-spacing:1px; text-transform:uppercase;">| Alunos</span></div>
    <a href="{{ route('academia.dashboard') }}" class="btn-top"><i class="fas fa-arrow-left"></i> Voltar ao painel</a>
</div>

<div class="container">
    <header class="page-head">
        <h1>Meus Alunos</h1>
        <p>{{ $alunos->count() }} aluno(s) vinculado(s) a {{ $academia->nome }}</p>
    </header>

    <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" id="buscaAluno" placeholder="Buscar aluno por nome...">
    </div>

    <div class="alunos-grid" id="gridAlunos">
        @forelse($alunos as $aluno)
            @php
                $nasc = $aluno->idade ? \Carbon\Carbon::parse($aluno->idade) : null;
            @endphp
            <div class="aluno-card" data-busca="{{ strtolower($aluno->nome) }}">
                <div class="aluno-head">
                    @if($aluno->foto)
                        <img src="{{ asset('storage/' . $aluno->foto) }}" alt="{{ $aluno->nome }}">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($aluno->nome) }}&background=d4ff00&color=000">
                    @endif
                    <div>
                        <h3>{{ $aluno->nome }}</h3>
                        <small>{{ $aluno->email }}</small>
                    </div>
                </div>

                <div class="aluno-data">
                    <div class="data-row">
                        <i class="fas fa-hourglass-half"></i>
                        <div>
                            <span class="label">Idade</span>
                            <span class="value">{{ $nasc ? $nasc->age . ' anos' : 'Não informada' }}</span>
                        </div>
                    </div>
                    <div class="data-row">
                        <i class="fas fa-cake-candles"></i>
                        <div>
                            <span class="label">Data de aniversário</span>
                            <span class="value">{{ $nasc ? $nasc->format('d/m/Y') : 'Não informada' }}</span>
                        </div>
                    </div>
                    <div class="data-row">
                        <i class="fas fa-heart-pulse"></i>
                        <div>
                            <span class="label">Condição clínica</span>
                            <span class="value">{{ $aluno->condicao_clinica ?: 'Nenhuma registrada' }}</span>
                        </div>
                    </div>
                    <div class="data-row">
                        <i class="fas fa-bullseye"></i>
                        <div>
                            <span class="label">Objetivo</span>
                            <span class="value">{{ $aluno->resumo_objetivo ?: 'Não informado' }}</span>
                        </div>
                    </div>
                </div>

                <a href="{{ route('academia.aluno-fichas', $aluno->id) }}" class="btn-ficha">
                    <i class="fas fa-clipboard-list"></i> Gerenciar Ficha
                </a>
            </div>
        @empty
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>Nenhum aluno vinculado à academia ainda.</p>
            </div>
        @endforelse
    </div>

    <div class="empty-state" id="semResultados" style="display:none;">
        <i class="fas fa-search"></i>
        <p>Nenhum aluno encontrado para a sua busca.</p>
    </div>
</div>

<script>
    const inputBusca = document.getElementById('buscaAluno');
    if (inputBusca) {
        inputBusca.addEventListener('input', () => {
            const termo = inputBusca.value.toLowerCase().trim();
            let visiveis = 0;
            document.querySelectorAll('#gridAlunos .aluno-card').forEach(card => {
                const ok = !termo || card.dataset.busca.includes(termo);
                card.style.display = ok ? '' : 'none';
                if (ok) visiveis++;
            });
            const vazio = document.getElementById('semResultados');
            if (vazio) vazio.style.display = visiveis === 0 ? '' : 'none';
        });
    }
</script>
</body>
</html>
