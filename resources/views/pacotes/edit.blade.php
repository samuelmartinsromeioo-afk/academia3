<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Planos - SNRFIT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        :root { --primary: #d4ff00; --bg: #0a0a0a; --card: #141414; --border: #222; }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 25px; }
        h2 { color: var(--primary); margin-bottom: 25px; }
        .input-group { margin-bottom: 15px; }
        label { display: block; font-size: 0.8rem; color: #888; margin-bottom: 8px; }
        .input-wrapper { 
            background: #1d1d1d; border: 1px solid var(--border); border-radius: 12px;
            padding: 12px; display: flex; align-items: center; gap: 10px;
        }
        .input-wrapper input { background: transparent; border: none; color: white; width: 100%; outline: none; }
        .btn-save { 
            background: var(--primary); color: black; border: none; padding: 15px; 
            width: 100%; border-radius: 12px; font-weight: 900; cursor: pointer; margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="javascript:history.back()" style="color: #888; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar</a>
    
    <div class="card" style="margin-top: 20px;">
        <h2>Configurar Planos</h2>
        <form action="{{ route('pacotes.store') }}" method="POST">
            @csrf
            <input type="hidden" name="personal_id" value="{{ auth()->user()->id }}">

            @foreach([1, 2, 3, 4, 5, 6, 7] as $vezes)
            <div class="input-group">
                <label>{{ $vezes }}x na semana (Valor Mensal)</label>
                <div class="input-wrapper">
                    <i class="fas fa-tag" style="color: var(--primary);"></i>
                    <input type="number" step="0.01" name="precos[{{ $vezes }}]" 
                           value="{{ $precosSalvos[$vezes] ?? '' }}" placeholder="R$ 0,00">
                </div>
            </div>
            @endforeach

            <button type="submit" class="btn-save">SALVAR ALTERAÇÕES</button>
        </form>
    </div>
</div>
</body>
</html>