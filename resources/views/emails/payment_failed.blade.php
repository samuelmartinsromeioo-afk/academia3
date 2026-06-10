<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Falha no Pagamento</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #ef4444; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .body { padding: 32px; color: #333; }
        .body p { line-height: 1.6; }
        .error-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; border-radius: 4px; margin: 20px 0; color: #b91c1c; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .label { color: #666; }
        .value { font-weight: bold; }
        .btn { display: inline-block; margin-top: 24px; padding: 12px 28px; background: #ef4444; color: #fff; text-decoration: none; border-radius: 6px; font-size: 15px; }
        .footer { background: #f9f9f9; padding: 20px 32px; text-align: center; font-size: 13px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✗ Falha no Pagamento</h1>
        </div>
        <div class="body">
            <p>Olá, <strong>{{ $cliente->nome }}</strong>!</p>
            <p>Infelizmente seu pagamento não pôde ser processado. Por favor, tente novamente ou entre em contato com seu banco.</p>

            <div class="error-box">
                <strong>Motivo:</strong> {{ $errorMessage }}
            </div>

            <h3 style="margin-top:28px; color:#333;">Detalhes da Tentativa</h3>
            <div class="detail-row">
                <span class="label">Valor</span>
                <span class="value">R$ {{ number_format($payment->amount_total, 2, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Método</span>
                <span class="value">{{ match($payment->payment_method) { 'credit_card' => 'Cartão de Crédito', 'debit_card' => 'Cartão de Débito', 'pix' => 'PIX', default => $payment->payment_method } }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Tentativas</span>
                <span class="value">{{ $payment->retry_count }}</span>
            </div>

            <a href="{{ config('app.url') }}/cliente/pacotes" class="btn">Tentar Novamente</a>

            <p style="margin-top:28px;">Se o problema persistir, entre em contato com nosso suporte.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SnrFit. Todos os direitos reservados.
        </div>
    </div>
</body>
</html>
