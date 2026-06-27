<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Financeiro - Administração</title>
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
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
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
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* CARD DE RESUMO */
        .summary-card {
            background: linear-gradient(135deg, rgba(212, 255, 0, 0.1), rgba(212, 255, 0, 0.02));
            border: 2px solid rgba(212, 255, 0, 0.3);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            text-align: center;
        }

        .summary-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .summary-card .total {
            font-size: 3rem;
            font-weight: 900;
            color: var(--primary);
        }

        /* TABELA */
        .table-container {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 40px;
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

        tbody tr:hover {
            background: rgba(212, 255, 0, 0.02);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .personal-badge {
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

        .money {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .commission {
            color: #00ff88;
            font-weight: 700;
        }

        .total-row {
            background: rgba(212, 255, 0, 0.05);
            font-weight: 900;
            border-top: 2px solid var(--primary);
        }

        .total-row td {
            padding: 20px 16px;
            color: var(--primary);
            font-size: 1.1rem;
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

        .filter-select {
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

        .filter-select:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.06);
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
            .table-container {
                overflow-x: auto;
            }

            th, td {
                padding: 12px 8px;
                font-size: 0.7rem;
            }

            .summary-card .total {
                font-size: 2rem;
            }

            .filters {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="ed-page">

{{-- TOP BAR --}}
<div class="top-bar">
    <h2><i class="ph ph-chart-pie"></i> Relatório Financeiro</h2>
    <a href="{{ route('admin.dashboard') }}" class="btn-back">
        <i class="ph ph-arrow-left"></i> Voltar
    </a>
</div>

<div class="container">
    {{-- CABEÇALHO --}}
    <div class="page-header">
        <h1>Relatório Financeiro</h1>
        <p>Visualize o faturamento e comissões de todos os personals aprovados.</p>
    </div>

    {{-- FILTROS --}}
    <div class="filters">
        <form method="GET" action="{{ route('admin.relatorio-financeiro') }}" style="display: flex; gap: 16px; flex-wrap: wrap; width: 100%;">
            <div class="filter-group">
                <label>Mês:</label>
                <select name="mes" class="filter-select" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(2025, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="filter-group">
                <label>Ano:</label>
                <select name="ano" class="filter-select" onchange="this.form.submit()">
                    @for($a = 2024; $a <= 2026; $a++)
                        <option value="{{ $a }}" {{ $ano == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endfor
                </select>
            </div>
        </form>
    </div>

    {{-- CARD DE RESUMO --}}
    <div class="summary-card">
        <p><i class="ph ph-currency-dollar"></i> TOTAL FATURADO</p>
        <div class="total">R$ {{ number_format($totalGeral, 2, ',', '.') }}</div>
        <p style="font-size: 0.8rem; margin-top: 12px;">
            Referente ao mês de {{ \Carbon\Carbon::createFromDate($ano, $mes, 1)->translatedFormat('F \\d\\e Y') }}
        </p>
    </div>

    {{-- TABELA --}}
    @if(count($dados) > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Personal</th>
                        <th>Aulas de Pacote</th>
                        <th>Aulas Avulsas</th>
                        <th>Total</th>
                        <th>Comissão (10%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dados as $item)
                        <tr>
                            <td>
                                <div class="personal-badge">
                                    <div class="personal-avatar">
                                        {{ substr($item['personal']->nome, 0, 1) }}
                                    </div>
                                    <span>{{ $item['personal']->nome }}</span>
                                </div>
                            </td>
                            <td class="money">R$ {{ number_format($item['pacotes'], 2, ',', '.') }}</td>
                            <td class="money">R$ {{ number_format($item['avulsas'], 2, ',', '.') }}</td>
                            <td class="money" style="font-size: 1.2rem;">
                                R$ {{ number_format($item['total'], 2, ',', '.') }}
                            </td>
                            <td class="commission">R$ {{ number_format($item['comissao'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    {{-- LINHA DE TOTAL --}}
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">
                            <strong>TOTAL GERAL:</strong>
                        </td>
                        <td>R$ {{ number_format($totalGeral, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($totalGeral * 0.1, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- RESUMO --}}
        <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 24px;">
            <h3 style="color: var(--primary); margin-bottom: 16px;">
                <i class="ph ph-info"></i> Resumo do Período
            </h3>
            <p style="color: var(--text-muted); margin: 0;">
                Total faturado: <strong style="color: var(--primary); font-size: 1.1rem;">R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong>
            </p>
            <p style="color: var(--text-muted); margin: 8px 0 0 0;">
                Comissão total (10%): <strong style="color: #00ff88; font-size: 1.1rem;">R$ {{ number_format($totalGeral * 0.1, 2, ',', '.') }}</strong>
            </p>
        </div>
    @else
        <div class="table-container">
            <div class="empty-state">
                <i class="ph ph-tray"></i>
                <p>Nenhum dado financeiro disponível para este período.</p>
            </div>
        </div>
    @endif
</div>

</body>
</html>
