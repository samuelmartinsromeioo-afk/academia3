<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $loja->nome }} | SnrFit</title>
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
            --loja-color: #ff9d2e;
            --whats: #25d366;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%); font-family: 'Inter', sans-serif; color: var(--text-main); min-height: 100vh; }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 40px; background: rgba(0, 0, 0, 0.3); border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);
        }
        .logo { font-family: 'Syncopate', sans-serif; font-size: 1.1rem; letter-spacing: 3px; }
        .logo span { color: var(--primary); }
        .btn-top {
            background: transparent; border: 1px solid var(--border); color: var(--text-main);
            padding: 9px 16px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.78rem;
            transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 1100px; margin: 0 auto; padding: 36px 20px; }

        .loja-header {
            display: flex; gap: 22px; align-items: center; flex-wrap: wrap;
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 26px; margin-bottom: 30px;
        }
        .loja-logo {
            width: 88px; height: 88px; border-radius: 18px; object-fit: cover; border: 1px solid var(--border);
            background: rgba(255,157,46,0.08); display: flex; align-items: center; justify-content: center;
            color: var(--loja-color); font-size: 2rem; flex-shrink: 0;
        }
        .loja-header h1 { font-size: 1.5rem; font-weight: 900; }
        .loja-header .meta { color: var(--text-muted); font-size: 0.85rem; margin-top: 6px; display: flex; align-items: center; gap: 8px; }
        .loja-header .meta i { color: var(--loja-color); }
        .loja-header p.desc { color: var(--text-muted); font-size: 0.9rem; margin-top: 10px; line-height: 1.5; }

        .btn-whats {
            background: var(--whats); color: #fff; border: none; border-radius: 12px; padding: 13px 20px;
            font-weight: 800; font-size: 0.9rem; cursor: pointer; text-decoration: none; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 8px; margin-left: auto;
        }
        .btn-whats:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(37,211,102,0.3); }

        .section-title {
            font-size: 1.1rem; font-weight: 900; color: var(--loja-color); margin: 0 0 18px;
            display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px;
        }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }

        .prod-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; transition: 0.2s; }
        .prod-card:hover { border-color: rgba(255,157,46,0.4); transform: translateY(-3px); }
        .prod-img {
            height: 150px; background: rgba(255,157,46,0.06); display: flex; align-items: center; justify-content: center;
            color: var(--loja-color); font-size: 2rem; position: relative; overflow: hidden;
        }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; }
        .prod-img .esgotado {
            position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;
        }
        .prod-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }
        .prod-cat { font-size: 0.66rem; color: var(--loja-color); text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 4px; }
        .prod-body h3 { font-size: 0.98rem; font-weight: 700; margin-bottom: 6px; }
        .prod-body p { color: var(--text-muted); font-size: 0.8rem; line-height: 1.45; margin-bottom: 12px; }
        .prod-foot { margin-top: auto; display: flex; justify-content: space-between; align-items: center; }
        .prod-preco { color: var(--primary); font-weight: 900; font-size: 1.1rem; }
        .prod-estoque { font-size: 0.7rem; color: var(--text-muted); }
        .prod-estoque.ok { color: var(--whats); }

        .empty-state { text-align: center; padding: 70px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; display: block; opacity: 0.4; color: var(--loja-color); }

        @media (max-width: 600px) { .top-bar { padding: 14px 20px; } .btn-whats { margin-left: 0; width: 100%; justify-content: center; } }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span></div>
    <a href="{{ route('lojas.explorar') }}" class="btn-top"><i class="fas fa-arrow-left"></i> Todas as lojas</a>
</div>

<div class="container">
    @php
        $whatsDigits = preg_replace('/\D/', '', $loja->whatsapp ?? '');
        if ($whatsDigits && strlen($whatsDigits) <= 11) { $whatsDigits = '55' . $whatsDigits; }
        $whatsMsg = rawurlencode("Olá! Vi a loja {$loja->nome} na SnrFit e gostaria de fazer um pedido.");
    @endphp

    <div class="loja-header">
        <div class="loja-logo">
            @if ($loja->logo)
                <img src="{{ asset('storage/' . $loja->logo) }}" alt="{{ $loja->nome }}" style="width:100%;height:100%;object-fit:cover;border-radius:18px;">
            @else
                <i class="fas fa-store"></i>
            @endif
        </div>
        <div style="flex:1; min-width:200px;">
            <h1>{{ $loja->nome }}</h1>
            <div class="meta"><i class="fas fa-map-marker-alt"></i> {{ $loja->cidade }}{{ $loja->estado ? ' - ' . $loja->estado : '' }}</div>
            @if ($loja->descricao)
                <p class="desc">{{ $loja->descricao }}</p>
            @endif
        </div>
        @if ($whatsDigits)
            <a href="https://wa.me/{{ $whatsDigits }}?text={{ $whatsMsg }}" target="_blank" class="btn-whats">
                <i class="fa-brands fa-whatsapp"></i> Falar com a loja
            </a>
        @endif
    </div>

    <h2 class="section-title"><i class="fas fa-box-open"></i> Produtos</h2>

    @if ($loja->produtos->isEmpty())
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>Esta loja ainda não cadastrou produtos.</p>
        </div>
    @else
        <div class="grid">
            @foreach ($loja->produtos as $produto)
                <div class="prod-card">
                    <div class="prod-img">
                        @if ($produto->imagem)
                            <img src="{{ asset('storage/' . $produto->imagem) }}" alt="{{ $produto->nome }}">
                        @else
                            <i class="fas fa-box"></i>
                        @endif
                        @if ($produto->estoque <= 0)
                            <div class="esgotado">Esgotado</div>
                        @endif
                    </div>
                    <div class="prod-body">
                        @if ($produto->categoria)
                            <div class="prod-cat">{{ $produto->categoria }}</div>
                        @endif
                        <h3>{{ $produto->nome }}</h3>
                        @if ($produto->descricao)
                            <p>{{ \Illuminate\Support\Str::limit($produto->descricao, 80) }}</p>
                        @endif
                        <div class="prod-foot">
                            <span class="prod-preco">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
                            @if ($produto->estoque > 0)
                                <span class="prod-estoque ok"><i class="fas fa-check"></i> Em estoque</span>
                            @else
                                <span class="prod-estoque">Indisponível</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

</body>
</html>
