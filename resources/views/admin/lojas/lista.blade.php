<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lojas - Administração</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00; --bg-dark: #0a0b0d; --card-bg: #16181d;
            --text-main: #ffffff; --text-muted: #a0a0a0; --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04); --error: #ff4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%); font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: var(--text-main); }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center; padding: 20px 40px;
            background: rgba(0, 0, 0, 0.3); border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);
        }
        .top-bar h2 { font-size: 1.2rem; font-weight: 900; color: var(--primary); }
        .btn-back {
            background: transparent; border: 1px solid var(--primary); color: var(--primary);
            padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.8rem;
            transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-back:hover { background: var(--primary); color: #000; }

        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }

        .filters { display: flex; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; }
        .filter-group { display: flex; gap: 8px; align-items: center; }
        .filter-group label { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .filter-select, .filter-input {
            background: var(--input-bg); border: 1px solid var(--border); color: var(--text-main);
            padding: 10px 14px; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; transition: 0.2s;
        }
        .filter-select:focus, .filter-input:focus { border-color: var(--primary); background: rgba(255, 255, 255, 0.06); }

        .table-container { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; margin-bottom: 32px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: rgba(212, 255, 0, 0.05); border-bottom: 2px solid var(--border); }
        th { padding: 16px; text-align: left; font-weight: 800; font-size: 0.75rem; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 16px; border-bottom: 1px solid var(--border); }
        tbody tr { transition: 0.2s; }
        tbody tr:hover { background: rgba(212, 255, 0, 0.02); }
        tbody tr:last-child td { border-bottom: none; }

        .loja-name { display: flex; align-items: center; gap: 12px; font-weight: 600; }
        .loja-avatar {
            width: 40px; height: 40px; border-radius: 10px; background: rgba(212, 255, 0, 0.1);
            border: 1px solid var(--primary); display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 700;
        }

        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .status-aprovado { background: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.2); }
        .status-rejeitado { background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); }

        .btn-view {
            background: transparent; border: 1px solid var(--primary); color: var(--primary);
            padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.7rem;
            text-transform: uppercase; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-view:hover { background: var(--primary); color: #000; }

        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 3rem; color: var(--text-muted); margin-bottom: 16px; display: block; }
        .empty-state p { color: var(--text-muted); font-size: 1.1rem; }

        @media (max-width: 768px) {
            .filters { flex-direction: column; }
            .table-container { overflow-x: auto; }
            th, td { padding: 12px 8px; font-size: 0.7rem; }
            .loja-name { flex-direction: column; gap: 4px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h2><i class="fas fa-store"></i> Gerenciar Lojas</h2>
    <a href="{{ route('admin.dashboard') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<div class="container">
    @if(session('success'))
        <div style="background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.4); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #4caf50; font-weight: 700;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="filters">
        <form method="GET" action="{{ route('admin.lojas.lista') }}" style="display: flex; gap: 16px; flex-wrap: wrap; width: 100%;">
            <div class="filter-group">
                <label>Status:</label>
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="todos" {{ $filtro === 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="aprovado" {{ $filtro === 'aprovado' ? 'selected' : '' }}>Ativas</option>
                    <option value="rejeitado" {{ $filtro === 'rejeitado' ? 'selected' : '' }}>Bloqueadas</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Buscar:</label>
                <input type="text" name="busca" class="filter-input" placeholder="Nome, email ou CNPJ..." value="{{ $busca ?? '' }}">
            </div>
            <button type="submit" style="background: var(--primary); color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.8rem; text-transform: uppercase;">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </form>
    </div>

    @if($lojas && count($lojas) > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Loja</th>
                        <th>Email</th>
                        <th>CNPJ</th>
                        <th>Cidade</th>
                        <th style="text-align:center;">Produtos</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lojas as $loja)
                        <tr>
                            <td>
                                <div class="loja-name">
                                    <div class="loja-avatar">{{ substr($loja->nome ?? 'L', 0, 1) }}</div>
                                    <span>{{ $loja->nome ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td style="color: var(--text-muted);">{{ $loja->email ?? 'N/A' }}</td>
                            <td style="color: var(--text-muted);">{{ $loja->cnpj ?? 'N/A' }}</td>
                            <td style="color: var(--text-muted);">{{ $loja->cidade }}/{{ $loja->estado }}</td>
                            <td style="text-align:center; font-weight:700;">{{ $loja->produtos_count }}</td>
                            <td>
                                <span class="status-badge status-{{ $loja->status ?? 'aprovado' }}">
                                    {{ ($loja->status ?? 'aprovado') === 'rejeitado' ? 'Bloqueada' : 'Ativa' }}
                                </span>
                            </td>
                            <td style="color: var(--text-muted);">{{ $loja->created_at ? $loja->created_at->format('d/m/Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('admin.lojas.detalhes', $loja->id) }}" class="btn-view"><i class="fas fa-eye"></i> Ver</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="table-container">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Nenhuma loja encontrada com esses critérios.</p>
            </div>
        </div>
    @endif
</div>

</body>
</html>
