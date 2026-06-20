<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Pacote Contratado</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f6f9; font-family: Arial, sans-serif; }
        .container { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background-color: #0a0b0d; padding: 32px; text-align: center; }
        .logo { font-size: 1.6rem; font-weight: 900; letter-spacing: 4px; color: #d4ff00; text-transform: uppercase; }
        .badge { display: inline-block; background: rgba(212,255,0,0.15); color: #d4ff00; font-size: 0.8rem; padding: 4px 12px; border-radius: 20px; margin-top: 10px; }
        .body { padding: 36px; color: #333; }
        .body h1 { font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; }
        .body p { font-size: 0.95rem; line-height: 1.6; color: #555; margin-bottom: 12px; }
        .info-box { background: #f8f9fa; border-left: 4px solid #d4ff00; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .info-box p { margin: 4px 0; font-size: 0.9rem; color: #333; }
        .info-box strong { color: #111; }
        .footer { background: #f8f8f8; padding: 20px 36px; text-align: center; font-size: 0.78rem; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SNR<span>FIT</span></div>
            <div class="badge">🎉 Novo Pacote Contratado</div>
        </div>
        <div class="body">
            <h1>Olá, {{ $personal_nome }}!</h1>
            <p>Boa notícia! Um aluno acabou de fechar um pacote com você. Confira os detalhes:</p>

            <div class="info-box">
                <p><strong>👤 Aluno:</strong> {{ $cliente_nome }}</p>
                <p><strong>📧 E-mail:</strong> {{ $cliente_email }}</p>
                <p><strong>📦 Frequência:</strong> {{ $frequencia }}x por semana</p>
                <p><strong>💰 Valor mensal:</strong> R$ {{ number_format((float) $valor_mensal, 2, ',', '.') }}</p>
                <p><strong>🗓️ Aulas agendadas:</strong> {{ $dias_total }} treino(s)</p>
                <p><strong>⏰ Horário:</strong> {{ $hora_inicio }} - {{ $hora_fim }}</p>
                <p><strong>📅 Período:</strong> {{ $data_inicio }} até {{ $data_fim }}</p>
            </div>

            <p>A cobrança é mensal (assinatura) e o repasse cai automaticamente na sua carteira. Acesse seu painel para acompanhar a agenda e confirmar a presença do aluno.</p>
        </div>
        <div class="footer">
            © {{ date('Y') }} SnrFit · E-mail automático, não responda.
        </div>
    </div>
</body>
</html>
