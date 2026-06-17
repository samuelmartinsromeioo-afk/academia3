<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academias | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --accent: #d4ff00;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 40px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 3px;
        }
        .logo span { color: var(--primary); }

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

        .container { max-width: 1200px; margin: 0 auto; padding: 36px 20px; }

        .welcome { margin-bottom: 28px; }
        .welcome h1 { font-size: 1.6rem; font-weight: 900; }
        .welcome h1 em { color: var(--accent); font-style: normal; }
        .welcome p { color: var(--text-muted); margin-top: 4px; font-size: 0.9rem; }

        .search-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0 14px;
            margin-bottom: 28px;
            max-width: 480px;
        }
        .search-wrapper i { color: var(--accent); }
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

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 22px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            transition: 0.25s;
            display: flex;
            flex-direction: column;
        }
        .card:hover { transform: translateY(-4px); border-color: rgba(212,255,0,0.4); box-shadow: 0 12px 30px rgba(0,0,0,0.5); }

        .card-img {
            height: 160px;
            background: linear-gradient(135deg, rgba(212,255,0,0.18), rgba(212,255,0,0.06));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 2.4rem;
            position: relative;
            overflow: hidden;
        }
        .card-img img { width: 100%; height: 100%; object-fit: cover; }

        .card-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(6px);
            color: var(--accent);
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 5px 11px;
            border-radius: 20px;
            border: 1px solid rgba(212,255,0,0.4);
        }

        .card-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }

        .card-body h3 { font-size: 1.05rem; font-weight: 800; margin-bottom: 6px; }

        .card-meta { color: var(--text-muted); font-size: 0.78rem; margin-bottom: 4px; display: flex; align-items: center; gap: 7px; }
        .card-meta i { color: var(--accent); width: 14px; text-align: center; }

        .card-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding-top: 14px;
            margin-top: 14px;
            border-top: 1px solid var(--border);
        }

        .preco { font-weight: 900; font-size: 1.05rem; }
        .preco small { color: var(--text-muted); font-weight: 400; font-size: 0.7rem; }

        .btn-detalhes {
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 800;
            font-size: 0.78rem;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-detalhes:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(212,255,0,0.25); }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; display: block; opacity: 0.4; color: var(--accent); }

        @media (max-width: 600px) {
            .top-bar { padding: 14px 20px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span></div>
    <a href="{{ route('cliente.index') }}" class="btn-top"><i class="fas fa-arrow-left"></i> Voltar ao painel</a>
</div>

<div class="container">
    <div class="welcome">
        <h1>Explorar <em>Academias</em></h1>
        <p>Encontre academias parceiras perto de você e contrate planos mensais.</p>
    </div>

    <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" id="buscaAcademia" placeholder="Buscar por nome, modalidade ou cidade...">
    </div>

    @if ($academias->isEmpty())
        <div class="empty-state">
            <i class="fas fa-building"></i>
            <p>Nenhuma academia disponível no momento.</p>
        </div>
    @else
        <div class="grid" id="gridAcademias">
            @foreach ($academias as $academia)
                <div class="card" data-busca="{{ strtolower($academia->nome . ' ' . ($academia->tipos_aulas ?? '') . ' ' . ($academia->cidade ?? '')) }}">
                    <div class="card-img">
                        @if ($academia->fotos->isNotEmpty())
                            <img src="{{ asset('storage/' . $academia->fotos->first()->path) }}" alt="{{ $academia->nome }}">
                        @else
                            <i class="fas fa-building"></i>
                        @endif
                        <span class="card-badge"><i class="fas fa-building"></i> Academia</span>
                    </div>
                    <div class="card-body">
                        <h3>{{ $academia->nome }}</h3>
                        @if ($academia->tipos_aulas)
                            <div class="card-meta"><i class="fas fa-dumbbell"></i> {{ $academia->tipos_aulas }}</div>
                        @endif
                        <div class="card-meta"><i class="fas fa-map-marker-alt"></i> {{ $academia->cidade }}{{ $academia->estado ? ' - ' . $academia->estado : '' }}</div>
                        @if ($academia->planos->isNotEmpty())
                            <div class="card-meta"><i class="fas fa-id-card"></i> Planos a partir de R$ {{ number_format($academia->planos->first()->valor, 2, ',', '.') }}/mês</div>
                        @endif

                        <div class="card-footer">
                            <div class="preco">R$ {{ number_format($academia->valor_mensalidade ?? 0, 2, ',', '.') }} <small>/mês</small></div>
                            <a href="{{ route('cliente.index') }}?academia={{ $academia->id }}" class="btn-detalhes">Ver detalhes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="empty-state" id="semResultados" style="display:none;">
            <i class="fas fa-search"></i>
            <p>Nenhuma academia encontrada para a sua busca.</p>
        </div>
    @endif
</div>

<script>
    const inputBusca = document.getElementById('buscaAcademia');
    if (inputBusca) {
        inputBusca.addEventListener('input', () => {
            const termo = inputBusca.value.toLowerCase().trim();
            let visiveis = 0;
            document.querySelectorAll('#gridAcademias .card').forEach(card => {
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
