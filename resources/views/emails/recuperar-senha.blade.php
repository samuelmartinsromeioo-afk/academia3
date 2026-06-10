<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-family: 'Inter', Arial, sans-serif;
        }
        .container {
            max-width: 560px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #0a0b0d;
            padding: 32px;
            text-align: center;
        }
        .logo {
            font-size: 1.6rem;
            font-weight: 900;
            letter-spacing: 4px;
            color: #d4ff00;
            text-transform: uppercase;
        }
        .body {
            padding: 40px 36px;
            color: #333333;
        }
        .body h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #111111;
        }
        .body p {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #555555;
            margin-bottom: 16px;
        }
        .btn {
            display: inline-block;
            margin: 20px 0;
            padding: 14px 32px;
            background-color: #d4ff00;
            color: #0a0b0d;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            border-radius: 10px;
        }
        .divider {
            border: none;
            border-top: 1px solid #eeeeee;
            margin: 28px 0;
        }
        .link-fallback {
            font-size: 0.78rem;
            color: #888888;
            word-break: break-all;
        }
        .footer {
            background-color: #f8f8f8;
            padding: 20px 36px;
            text-align: center;
            font-size: 0.78rem;
            color: #aaaaaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SNR<span>FIT</span></div>
        </div>
 
        <div class="body">
            <h1>Olá, {{ $nome }}!</h1>
            <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>SnrFit</strong>.</p>
            <p>Clique no botão abaixo para criar uma nova senha. Este link é válido por <strong>60 minutos</strong>.</p>
 
            <a href="{{ $link }}" class="btn">Redefinir Minha Senha</a>
 
            <hr class="divider">
 
            <p>Se você não solicitou a recuperação de senha, ignore este e-mail. Sua senha continuará a mesma.</p>
 
            <p class="link-fallback">
                Caso o botão não funcione, copie e cole este link no seu navegador:<br>
                {{ $link }}
            </p>
        </div>
 
        <div class="footer">
            © {{ date('Y') }} SnrFit · Este é um e-mail automático, não responda.
        </div>
    </div>
</body>
</html>