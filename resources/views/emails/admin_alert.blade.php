<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Alerta Admin - Falha de Pagamento</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #f97316; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 32px; color: #333; }
        .body p { line-height: 1.6; }
        .alert-box { background: #fff7ed; border-left: 4px solid #f97316; padding: 16px; border-radius: 4px; margin: 16px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .label { color: #666; }
        .value { font-weight: bold; }
        .footer { background: #f9f9f9; padding: 20px 32px; text-align: center; font-size: 13px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠ Alerta: Falha de Pagamento</h1>
        </div>
        <div class="body">
            <p>Uma tentativa de pagamento falhou e requer atenção.</p>

            <div class="alert-box">
                <strong>Motivo da falha:</strong> {{ $errorMessage }}
            </div>

            <h3 style="color:#333;">Dados do Pagamento</h3>
            <div class="detail-row">
                <span class="label">Payment ID</span>
                <span class="value">#{{ $payment->id }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Stripe Intent</span>
                <span class="value">{{ $payment->stripe_payment_intent_id }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Valor</span>
                <span class="value">R$ {{ number_format($payment->amount_total, 2, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Método</span>
                <span class="value">{{ match($payment->payment_method) { 'credit_card' => 'Cartão de Crédito', 'debit_card' => 'Cartão de Débito', 'pix' => 'PIX', default => $payment->payment_method ?? 'N/A' } }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Tentativas</span>
                <span class="value">{{ $payment->retry_count }}</span>
            </div>

            <h3 style="color:#333; margin-top:24px;">Cliente</h3>
            <div class="detail-row">
                <span class="label">Nome</span>
                <span class="value">{{ $cliente?->nome ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Email</span>
                <span class="value">{{ $cliente?->email ?? 'N/A' }}</span>
            </div>

            <h3 style="color:#333; margin-top:24px;">Personal Trainer</h3>
            <div class="detail-row">
                <span class="label">Nome</span>
                <span class="value">{{ $personal?->nome ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Email</span>
                <span class="value">{{ $personal?->email ?? 'N/A' }}</span>
            </div>

            <p style="margin-top:28px; font-size:13px; color:#666;">
                Gerado em {{ now()->format('d/m/Y H:i:s') }}
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SnrFit Admin. Sistema interno.
        </div>
    </div>
</body>
</html>
