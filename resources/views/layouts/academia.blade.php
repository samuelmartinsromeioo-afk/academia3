<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'RG Power Gym')</title>

    <!-- BELEZA DO SITE -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f6f9;
        }

        .navbar-brand {
            font-weight: bold;
            letter-spacing: 1px;
        }

        .nav-link {
            font-weight: 500;
        }

        .nav-link:hover {
            color: #ffffff !important;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(218, 10, 10, 0.08);
        }
    </style>
</head>
<body>

<!-- BARRA DE LOCALIZAÇÃOo -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="bi bi-car-front-fill me-2"></i>RG Power Gym
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto gap-2">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('academia.edit') }}">
                        <i class="bi bi-people-fill me-1"></i>Perfil
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('academia.notificar') }}">
                        <i class="bi bi-car-front me-1"></i>Notificar usuários
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('academia.relatorios') }}">
                        <i class="bi bi-car-front me-1"></i>Relatórios
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 flex-fill">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @yield('content')
</main>

<!-- BELEZA DO SITE -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>