<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'SnrFit')</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.meta-pixel')
    @include('partials.pwa')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    @include('partials.brand-head')
</head>
<body class="snr-dark ed-page">

<nav class="navbar navbar-expand-lg mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">
            <span class="snr-logo">SNR<span>FIT</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto gap-2">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('cliente.edit') }}">
                        <i class="bi bi-people-fill me-1"></i>Perfil
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('cliente.notificar') }}">
                        <i class="bi bi-car-front me-1"></i>Notificações
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('cliente.contratar-personal') }}">
                        <i class="bi bi-car-front me-1"></i>Contratar personal
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('cliente.contratar-academia') }}">
                        <i class="bi bi-car-front me-1"></i>Contratar academia
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

@include('partials.brand-footer')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>