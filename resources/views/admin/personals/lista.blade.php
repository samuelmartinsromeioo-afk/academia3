<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personals - Administração</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
            --error: #ff4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .top-bar h2 {
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--primary);
        }

        .btn-back {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.8rem;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: var(--primary);
            color: #000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 900;
        }

        /* FILTROS */
        .filters {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .filter-group label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .filter-select,
        .filter-input {
            background: var(--input-bg);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 10px 14px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: 0.2s;
        }

        .filter-select:focus,
        .filter-input:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.06);
        }

        /* TABELA */
        .table-container {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(212, 255, 0, 0.05);
            border-bottom: 2px solid var(--border);
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 800;
            font-size: 0.75rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }

        tbody tr {
            transition: 0.2s;
        }

        tbody tr:hover {
            background: rgba(212, 255, 0, 0.02);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .personal-name {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .personal-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(212, 255, 0, 0.1);
            border: 1px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .status-pendente {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .status-aprovado {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .status-rejeitado {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.2);
        }

        .btn-view {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-view:hover {
            background: var(--primary);
            color: #000;
        }

        /* PAGINAÇÃO */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 32px;
        }

        .pagination a,
        .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text-main);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.8rem;
            transition: 0.2s;
            cursor: pointer;
        }

        .pagination a:hover {
            border-color: var(--primary);
            background: rgba(212, 255, 0, 0.1);
            color: var(--primary);
        }

        .pagination .active {
            background: var(--primary);
            color: #000;
            border-color: var(--primary);
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            display: block;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }

            .table-container {
                overflow-x: auto;
            }

            th, td {
                padding: 12px 8px;
                font-size: 0.7rem;
            }

            .personal-name {
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>
</head>
<body>

{{-- TOP BAR --}}
<div class="top-bar">
    <h2><i class="fas fa-list"></i> Gerenciar Personals</h2>
    <a href="{{ route('admin.dashboard') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="container">
    {{-- FILTROS --}}
    <div class="filters">
        <form method="GET" action="{{ route('admin.personals.lista') }}" style="display: flex; gap: 16px; flex-wrap: wrap; width: 100%;">
            <div class="filter-group">
                <label>Status:</label>
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="todos" {{ $filtro === 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="pendente" {{ $filtro === 'pendente' ? 'selected' : '' }}>Pendentes</option>
                    <option value="aprovado" {{ $filtro === 'aprovado' ? 'selected' : '' }}>Aprovados</option>
                    <option value="rejeitado" {{ $filtro === 'rejeitado' ? 'selected' : '' }}>Rejeitados</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Buscar:</label>
                <input type="text" name="busca" class="filter-input" placeholder="Nome ou email..." value="{{ $busca ?? '' }}">
            </div>

            <button type="submit" style="
                background: var(--primary);
                color: #000;
                border: none;
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 700;
                cursor: pointer;
                font-size: 0.8rem;
                text-transform: uppercase;
            ">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </form>
    </div>

    {{-- TABELA DE PERSONALS --}}
    @if($personalsComLucro && count($personalsComLucro) > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Personal</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Lucro (Mês)</th>
                        <th>Data de Cadastro</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($personalsComLucro as $personal)
                        <tr>
                            <td>
                                <div class="personal-name">
                                    <div class="personal-avatar">
                                        {{ substr($personal->nome ?? 'A', 0, 1) }}
                                    </div>
                                    <span>{{ $personal->nome ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td style="color: var(--text-muted);">{{ $personal->email ?? 'N/A' }}</td>
                            <td>
                                <span class="status-badge status-{{ $personal->status ?? 'pendente' }}">
                                    {{ ucfirst($personal->status ?? 'pendente') }}
                                </span>
                            </td>
                            <td style="color: var(--primary); font-weight: 700;">
                                R$ {{ number_format($personal->lucro_mes ?? 0, 2, ',', '.') }}
                            </td>
                            <td style="color: var(--text-muted);">
                                {{ $personal->created_at ? $personal->created_at->format('d/m/Y') : 'Data não disponível' }}
                            </td>
                            <td>
                                <a href="{{ route('admin.personals.detalhes', $personal->id) }}" class="btn-view">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- PAGINAÇÃO --}}
        {{-- Implementar paginação manual se necessário --}}
    @else
        <div class="table-container">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Nenhum personal encontrado com esses critérios.</p>
            </div>
        </div>
    @endif
</div>

</body>
</html>
