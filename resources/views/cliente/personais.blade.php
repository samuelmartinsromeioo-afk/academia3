<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personais | SnrFit</title>
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
            --accent: #1a5fd4;
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
        .card:hover { transform: translateY(-4px); border-color: rgba(26,95,212,0.4); box-shadow: 0 12px 30px rgba(0,0,0,0.5); }

        .card-img {
            height: 160px;
            background: linear-gradient(135deg, rgba(26,95,212,0.18), rgba(212,255,0,0.06));
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
            border: 1px solid rgba(26,95,212,0.4);
        }

        .card-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }

        .card-body h3 { font-size: 1.05rem; font-weight: 800; margin-bottom: 6px; }

        .card-meta { color: var(--text-muted); font-size: 0.78rem; margin-bottom: 4px; display: flex; align-items: center; gap: 7px; }
        .card-meta i { color: var(--accent); width: 14px; text-align: center; }

        .rating { color: #ffc107; font-size: 0.8rem; margin: 8px 0 12px; }
        .rating .num { color: var(--text-muted); }

        .card-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding-top: 14px;
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
        <h1>Explorar <em>Personais</em></h1>
        <p>Encontre personal trainers, veja avaliações e contrate aulas avulsas ou pacotes.</p>
    </div>

    <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" id="buscaPersonal" placeholder="Buscar por nome ou cidade...">
    </div>

    @if ($personais->isEmpty())
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <p>Nenhum personal disponível no momento.</p>
        </div>
    @else
        <div class="grid" id="gridPersonais">
            @foreach ($personais as $personal)
                <div class="card" data-busca="{{ strtolower($personal->nome . ' ' . ($personal->cidade ?? '')) }}">
                    <div class="card-img">
                        @if ($personal->foto)
                            <img src="{{ asset('storage/' . $personal->foto) }}" alt="{{ $personal->nome }}">
                        @elseif ($personal->fotos->isNotEmpty())
                            <img src="{{ asset('storage/' . $personal->fotos->first()->path) }}" alt="{{ $personal->nome }}">
                        @else
                            <i class="fas fa-user-tie"></i>
                        @endif
                        <span class="card-badge"><i class="fas fa-user-tie"></i> Personal</span>
                    </div>
                    <div class="card-body">
                        <h3>{{ $personal->nome }}</h3>
                        @if ($personal->cidade)
                            <div class="card-meta"><i class="fas fa-map-marker-alt"></i> {{ $personal->cidade }}{{ $personal->estado ? ' - ' . $personal->estado : '' }}</div>
                        @endif

                        <div class="rating">
                            @if($personal->eh_novo_profissional)
                                <span class="num" style="color: var(--primary);"><i class="fas fa-seedling"></i> Novo profissional</span>
                            @else
                                @php $media = (float) $personal->media_avaliacao; @endphp
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-star {{ $i <= round($media) ? 'fas' : 'far' }}"></i>
                                @endfor
                                <span class="num">{{ $personal->media_avaliacao }} ({{ $personal->avaliacoes->count() }})</span>
                            @endif
                        </div>

                        <div class="card-footer">
                            <div class="preco">R$ {{ number_format($personal->valor_secao ?? 0, 2, ',', '.') }} <small>/aula</small></div>
                            <a href="{{ route('cliente.index') }}?personal={{ $personal->id }}" class="btn-detalhes">Ver detalhes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="empty-state" id="semResultados" style="display:none;">
            <i class="fas fa-search"></i>
            <p>Nenhum personal encontrado para a sua busca.</p>
        </div>
    @endif
</div>

<script>
    const inputBusca = document.getElementById('buscaPersonal');
    if (inputBusca) {
        inputBusca.addEventListener('input', () => {
            const termo = inputBusca.value.toLowerCase().trim();
            let visiveis = 0;
            document.querySelectorAll('#gridPersonais .card').forEach(card => {
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
