<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Personal | FITSYS</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
            --input-bg: rgba(255, 255, 255, 0.03);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(212, 255, 0, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(212, 255, 0, 0.05) 0%, transparent 20%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 60px 20px;
        }

        .auth-container { width: 100%; max-width: 700px; }

        .auth-header { text-align: center; margin-bottom: 50px; }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 2.2rem;
            letter-spacing: 6px;
            color: var(--primary);
            text-transform: uppercase;
            display: block;
        }

        .auth-card {
            background: var(--card-bg);
            padding: 50px;
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px; /* Aumentei o gap para evitar "embolar" */
        }

        .form-group { margin-bottom: 10px; position: relative; }
        .full-width { grid-column: span 2; }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 15px;
        }

        .input-wrapper { 
            position: relative; 
            display: flex; 
            align-items: center; 
        }

        /* AJUSTE DO ÍCONE: Centralizado e isolado */
        .input-wrapper i {
            position: absolute;
            left: 20px;
            width: 20px; /* Largura fixa para o ícone */
            text-align: center;
            color: var(--text-dim);
            z-index: 2; /* Garante que fique acima do fundo do input */
            pointer-events: none; /* Deixa o clique passar para o input */
        }

        .input-wrapper input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            /* PADDING-LEFT AUMENTADO para 60px para dar espaço ao ícone */
            padding: 18px 20px 18px 60px; 
            border-radius: 16px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        /* Estilo especial para o input de arquivo (sem ícone interno para não embolar) */
        .file-input { padding: 12px 20px !important; }

        /* Remove ícone nativo de data no Chrome para não embolar com o nosso */
        input::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            margin-right: 5px;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(212, 255, 0, 0.04);
        }

        .btn-register {
            width: 100%;
            background: var(--primary);
            color: var(--bg-dark);
            border: none;
            padding: 22px;
            border-radius: 16px;
            font-weight: 800;
            font-size: 1.1rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
            grid-column: span 2;
        }

        @media (max-width: 650px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width, .btn-register { grid-column: span 1; }
        }
    </style>
</head>
</html>