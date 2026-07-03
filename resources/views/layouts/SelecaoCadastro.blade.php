<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
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
                radial-gradient(circle at 10% 20%, rgba(212, 255, 0, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(212, 255, 0, 0.05) 0%, transparent 20%);
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

        /* Container Principal */
        .selecao-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-text {
            text-align: center;
            margin-bottom: 50px;
        }

        .header-text h2 {
            font-size: clamp(1.8rem, 5vw, 2.8rem);
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .header-text h2 span {
            color: var(--primary);
            display: block;
        }

        .header-text p {
            color: var(--text-dim);
            margin-top: 15px;
            font-size: 1.1rem;
        }

        /* Grid de Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            width: 100%;
        }

        .card-link {
            text-decoration: none;
            color: inherit;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card-link:hover {
            transform: scale(1.03);
        }

        .card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(212, 255, 0, 0.1);
        }

        /* Ícone e Estilo Visual do Card */
        .icon-wrapper {
            width: 60px;
            height: 60px;
            background: rgba(212, 255, 0, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            transition: 0.3s;
        }

        .card:hover .icon-wrapper {
            background: var(--primary);
        }

        .icon-wrapper i {
            font-size: 1.5rem;
            color: var(--primary);
            transition: 0.3s;
        }

        .card:hover .icon-wrapper i {
            color: var(--bg-dark);
        }

        .card h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .card p {
            color: var(--text-dim);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Seta indicativa */
        .card-footer {
            margin-top: auto;
            padding-top: 30px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .go-btn {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            opacity: 0;
            transform: translateX(-10px);
            transition: 0.3s;
        }

        .card:hover .go-btn {
            opacity: 1;
            transform: translateX(0);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
            .header-text h2 { font-size: 2rem; }
        }
    </style>
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