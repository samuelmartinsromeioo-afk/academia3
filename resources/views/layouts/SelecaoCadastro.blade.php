<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.meta-pixel')
    @include('partials.pwa')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">

    <style>
        :root {
            --primary: #d4ff00; /* Neon Lime */
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-dark);
            background-image:
                radial-gradient(circle at 18% -6%, rgba(212, 255, 0, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 92% 106%, rgba(255, 255, 255, 0.025) 0%, transparent 38%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header / Navbar Simples */
        header {
            padding: 2rem;
            text-align: center;
        }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 4px;
            color: var(--primary);
            text-transform: uppercase;
        }

        /* Container Principal — expansivo, ocupa a largura toda */
        .selecao-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: clamp(20px, 4vw, 56px);
        }
    </style>
    @yield('estilos')
</head>
<body class="ed-page">

    <header>
        <div class="logo">SNR<span>FIT</span></div>
        <div style="font-family:'Inter',sans-serif; font-size:0.62rem; font-weight:600; letter-spacing:3px; text-transform:uppercase; color:var(--text-dim); margin-top:8px;">Treino que vira história</div>
    </header>

    <main class="selecao-container">
        @yield('conteudo')
    </main>

    <footer class="snr-footer">
        <div class="snr-voice">Feito pra quem <strong>não para</strong>.</div>
        <div style="margin-top:8px; font-size:0.72rem; opacity:0.55;">© {{ date('Y') }} SnrFit</div>
    </footer>

</body>
</html>
