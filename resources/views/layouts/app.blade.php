<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Cliente | FIT SYS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #d4ff00;
            --primary-hover: #b8de00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-dim: #9ca3af;
            --input-bg: rgba(255, 255, 255, 0.05);
            --error: #ff4444;
            --success: #00c853;
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
            padding: 40px 20px;
        }

        .auth-container { width: 100%; max-width: 850px; animation: fadeIn 0.6s ease-out; }

        .auth-header { text-align: center; margin-bottom: 30px; }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 2.2rem;
            letter-spacing: 6px;
            color: var(--primary);
            text-transform: uppercase;
        }

        .auth-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .section-title {
            grid-column: span 2;
            font-size: 0.8rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 15px 0 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 5px;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 20px; }
        .full-width { grid-column: span 2; }

        .form-group label {
            display: block;
            font-size: .65rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 6px;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0 15px;
            transition: 0.3s;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary);
            background: rgba(212, 255, 0, 0.03);
        }

        .input-wrapper i { color: var(--text-dim); width: 20px; text-align: center; margin-right: 10px; font-size: 0.9rem; }

        .input-wrapper input, .input-wrapper select, .input-wrapper textarea {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 0;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
        }

        textarea { resize: none; }

        .btn-register {
            grid-column: span 2;
            background: var(--primary);
            color: #000;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-weight: 800;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-register:hover { transform: translateY(-2px); background: var(--primary-hover); }

        .loading-text { font-size: 0.7rem; color: var(--primary); display: none; margin-top: 4px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } .full-width, .section-title { grid-column: span 1; } }
    </style>
</head>
<body>