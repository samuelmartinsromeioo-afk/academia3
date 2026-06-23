<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $assunto }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #0a0b0d; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #14161a; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.4); }
        .header { background: #0a0b0d; padding: 28px 32px; text-align: center; border-bottom: 3px solid #d4ff00; }
        .header .logo { color: #d4ff00; margin: 0; font-size: 22px; font-weight: 800; letter-spacing: .5px; }
        .header h1 { color: #ffffff; margin: 12px 0 0; font-size: 20px; font-weight: 700; }
        .body { padding: 32px; color: #e6e6e6; }
        .body p { line-height: 1.65; font-size: 15px; margin: 0 0 14px; }
        .greeting { color: #d4ff00; font-weight: 700; }
        .message { background: #1b1e24; border-left: 3px solid #d4ff00; border-radius: 8px; padding: 18px 20px; color: #e6e6e6; line-height: 1.7; font-size: 15px; }
        .message strong { color: #ffffff; }
        .footer { background: #0a0b0d; padding: 20px 32px; text-align: center; font-size: 12px; color: #6b7280; }
        .footer a { color: #d4ff00; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p class="logo">SnrFit</p>
            <h1>{{ $assunto }}</h1>
        </div>
        <div class="body">
            <p>Olá, <span class="greeting">{{ $nome }}</span>!</p>
            @php
                // Reaproveita o texto do WhatsApp: escapa primeiro, depois converte
                // *negrito* e quebras de linha em HTML seguro.
                $html = e($corpo);
                $html = preg_replace('/\*(.+?)\*/s', '<strong>$1</strong>', $html);
                $html = nl2br($html);
            @endphp
            <div class="message">{!! $html !!}</div>
        </div>
        <div class="footer">
            Esta é uma notificação automática do SnrFit.<br>
            &copy; {{ date('Y') }} SnrFit. Todos os direitos reservados.
        </div>
    </div>
</body>
</html>
