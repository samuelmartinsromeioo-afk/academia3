<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Aula Cancelada</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f6f9; font-family: Arial, sans-serif; }
        .container { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background-color: #0a0b0d; padding: 32px; text-align: center; }
        .logo { font-size: 1.6rem; font-weight: 900; letter-spacing: 4px; color: #d4ff00; text-transform: uppercase; }
        .badge { display: inline-block; background: rgba(255,77,77,0.15); color: #ff4d4d; font-size: 0.8rem; padding: 4px 12px; border-radius: 20px; margin-top: 10px; }
        .body { padding: 36px; color: #333; }
        .body h1 { font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; }
        .body p { font-size: 0.95rem; line-height: 1.6; color: #555; margin-bottom: 12px; }
        .info-box { background: #fff5f5; border-left: 4px solid #ff4d4d; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .info-box p { margin: 4px 0; font-size: 0.9rem; color: #333; }
        .info-box strong { color: #111; }
        .justificativa { background: #f8f9fa; border-radius: 8px; padding: 14px 18px; margin: 16px 0; font-size: 0.9rem; color: #555; font-style: italic; }
        .footer { background: #f8f8f8; padding: 20px 36px; text-align: center; font-size: 0.78rem; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">FIT<span>SYS</span></div>
            <div class="badge">❌ Aula Cancelada</div>
        </div>
        <div class="body">
            <h1>Olá, {{ $cliente_nome }}!</h1>
            <p>Infelizmente sua aula foi cancelada pelo personal. Veja os detalhes:</p>
 
            <div class="info-box">
                <p><strong>🏋️ Personal:</strong> {{ $personal_nome }}</p>
                <p><strong>📅 Data:</strong> {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}</p>
                <p><strong>🕐 Horário:</strong> {{ $hora_inicio }} às {{ $hora_fim }}</p>
            </div>
 
            @if($justificativa)
            <p><strong>Justificativa:</strong></p>
            <div class="justificativa">"{{ $justificativa }}"</div>
            @endif
 
            <p>Acesse o sistema para reagendar uma nova aula com este ou outro personal disponível.</p>
        </div>
        <div class="footer">
            © {{ date('Y') }} FitSys · E-mail automático, não responda.
        </div>
    </div>
</body>
</html>