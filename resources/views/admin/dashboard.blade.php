<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Administração</title>
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
            --success: #28a745;
            --error: #ff4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        /* TOP BAR */
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
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary);
        }

        .admin-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid var(--error);
            color: var(--error);
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.8rem;
            transition: 0.2s;
        }

        .btn-logout:hover {
            background: var(--error);
            color: #fff;
        }

        /* CONTAINER */
        .container {
            max-width: 1400px;
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

        /* GRID DE CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(212, 255, 0, 0.05), transparent);
            opacity: 0;
            transition: 0.3s;
            pointer-events: none;
        }

        .stat-card:hover {
            border-color: rgba(212, 255, 0, 0.3);
            transform: translateY(-4px);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card-icon {
            width: 60px;
            height: 60px;
            background: rgba(212, 255, 0, 0.1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .stat-card-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
        }

        /* SEÇÃO DE ÚLTIMOS PERSONALS */
        .section-title {
            font-size: 1.3rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: var(--primary);
            border-radius: 2px;
        }

        .personals-list {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .personal-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border);
            transition: 0.2s;
        }

        .personal-item:last-child {
            border-bottom: none;
        }

        .personal-item:hover {
            background: rgba(212, 255, 0, 0.02);
        }

        .personal-info {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .personal-avatar {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(212, 255, 0, 0.1);
            border: 2px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: 800;
            font-size: 1.2rem;
        }

        .personal-details h3 {
            margin: 0 0 4px 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .personal-details p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0 12px;
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

        .btn-action {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-action:hover {
            background: var(--primary);
            color: #000;
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
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .top-bar {
                padding: 16px 20px;
            }

            .container {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .personal-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .personal-info {
                width: 100%;
                margin-bottom: 16px;
            }

            .status-badge,
            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>

{{-- TOP BAR --}}
<div class="top-bar">
    <h2><i class="fas fa-crown"></i> SNRFIT ADMIN</h2>
    <div class="admin-menu">
        <span style="color: var(--text-muted); font-size: 0.9rem;">
            <i class="fas fa-user-circle"></i> {{ session('admin_nome') ?? 'Admin' }}
        </span>
        <form action="{{ route('admin.logout') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </button>
        </form>
    </div>
</div>

<div class="container">
    {{-- CABEÇALHO DA PÁGINA --}}
    <div class="page-header">
        <h1>Dashboard Administrativo</h1>
        <p>Bem-vindo de volta! Aqui está um resumo do seu sistema.</p>
    </div>

    {{-- GRID DE ESTATÍSTICAS --}}
    <div class="stats-grid">
        {{-- TOTAL DE PERSONALS --}}
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-users"></i>
            </div>
            <p class="stat-card-label">Total de Personals</p>
            <h3 class="stat-card-value">{{ $totalPersonals ?? 0 }}</h3>
        </div>

        {{-- PERSONALS PENDENTES --}}
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-hourglass-half" style="color: #ffc107;"></i>
            </div>
            <p class="stat-card-label">Pendentes de Aprovação</p>
            <h3 class="stat-card-value" style="color: #ffc107;">{{ $personalsPendentes ?? 0 }}</h3>
        </div>

        {{-- PERSONALS APROVADOS --}}
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-check-circle" style="color: var(--success);"></i>
            </div>
            <p class="stat-card-label">Aprovados</p>
            <h3 class="stat-card-value" style="color: var(--success);">{{ $personalsAprovados ?? 0 }}</h3>
        </div>

        {{-- PERSONALS REJEITADOS --}}
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-times-circle" style="color: var(--error);"></i>
            </div>
            <p class="stat-card-label">Rejeitados</p>
            <h3 class="stat-card-value" style="color: var(--error);">{{ $personalsRejeitados ?? 0 }}</h3>
        </div>

        {{-- TOTAL DE CLIENTES --}}
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-user-graduate" style="color: #00d4ff;"></i>
            </div>
            <p class="stat-card-label">Total de Clientes</p>
            <h3 class="stat-card-value" style="color: #00d4ff;">{{ $totalClientes ?? 0 }}</h3>
        </div>

        {{-- LUCRO TOTAL --}}
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-chart-line" style="color: #00ff88;"></i>
            </div>
            <p class="stat-card-label">Lucro Total (Mês)</p>
            <h3 class="stat-card-value" style="color: #00ff88;">R$ {{ number_format($lucroTotal ?? 0, 2, ',', '.') }}</h3>
        </div>
    </div>

    {{-- ÚLTIMOS PERSONALS PENDENTES --}}
    <div>
        <h2 class="section-title">
            <i class="fas fa-clock"></i> Últimos Cadastros Pendentes
        </h2>

        @if($ultimosPendentes && $ultimosPendentes->count() > 0)
            <div class="personals-list">
                @foreach($ultimosPendentes as $personal)
                    <div class="personal-item">
                        <div class="personal-info">
                            <div class="personal-avatar">
                                {{ substr($personal->nome ?? 'A', 0, 1) }}
                            </div>
                            <div class="personal-details">
                                <h3>{{ $personal->nome ?? 'N/A' }}</h3>
                                <p>
                                    <i class="fas fa-envelope"></i> {{ $personal->email ?? 'N/A' }}
                                    <span style="margin: 0 8px; color: var(--border);">•</span>
                                    <i class="fas fa-calendar"></i> 
                                    @if($personal->created_at)
                                        {{ $personal->created_at->format('d/m/Y') }}
                                    @else
                                        Data não disponível
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="status-badge status-{{ $personal->status ?? 'pendente' }}">
                            {{ ucfirst($personal->status ?? 'pendente') }}
                        </span>
                        <a href="{{ route('admin.personals.detalhes', $personal->id) }}" class="btn-action">
                            <i class="fas fa-arrow-right"></i> Revisar
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <p>Nenhum personal pendente de aprovação! 🎉</p>
            </div>
        @endif
    </div>

    {{-- LINK PARA MENU --}}
    <div style="margin-top: 40px; text-align: center; padding: 20px; background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border);">
        <p style="color: var(--text-muted); margin-bottom: 16px;">
            <i class="fas fa-info-circle"></i> Navegue para revisar personals pendentes
        </p>
        <a href="{{ route('admin.personals.lista') }}" style="
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #000;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            transition: 0.2s;
        "
        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 25px rgba(212, 255, 0, 0.2)'"
        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'"
        >
            <i class="fas fa-list"></i> Ver Todos os Personals
        </a>
        <a href="{{ route('admin.studios.lista') }}" style="
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            transition: 0.2s;
            margin-left: 12px;
        "
        onmouseover="this.style.background='rgba(212, 255, 0, 0.1)'"
        onmouseout="this.style.background='transparent'"
        >
            <i class="fas fa-spa"></i> Ver Studios
        </a>
    </div>
</div>

</body>
</html>
