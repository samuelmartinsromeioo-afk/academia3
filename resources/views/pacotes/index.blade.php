<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos Disponíveis - FITSYS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        :root { --primary: #d4ff00; --bg: #0a0a0a; --card: #141414; --border: #222; }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; padding: 0; margin: 0; }
        .top-bar { padding: 20px; background: var(--card); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 500px; margin: 40px auto; padding: 0 20px; }
        .price-item { 
            background: var(--card); border: 1px solid var(--border); border-radius: 16px; 
            padding: 20px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;
        }
        .freq { font-weight: 600; }
        .value { color: var(--primary); font-weight: 800; font-size: 1.2rem; }
    </style>
</head>
<body>
<div class="top-bar">
    <a href="javascript:history.back()" style="color: #888; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar</a>
    <span style="font-weight: 800; color: var(--primary);">FITSYS</span>
</div>

<div class="container">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">{{ $personal->nome }}</h2>
        <p style="color: #888; font-size: 0.9rem;">Escolha o plano ideal para você</p>
    </div>

    @forelse($precos as $p)
        <div class="price-item">
            <div class="freq">
                <i class="fas fa-calendar-alt" style="color: var(--primary); margin-right: 10px;"></i>
                {{ $p->frequencia }}x na semana
            </div>
            <div class="value">R$ {{ number_format($p->valor_mensal, 2, ',', '.') }}</div>
        </div>
    @empty
        <div style="text-align: center; color: #555; margin-top: 50px;">
            <i class="fas fa-info-circle" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            Este personal ainda não cadastrou valores.
        </div>
    @endforelse
</div>
</body>
</html>