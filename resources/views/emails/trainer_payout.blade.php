<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo de Repasse</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #6366f1; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .body { padding: 32px; color: #333; }
        .body p { line-height: 1.6; }
        .amount-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 24px; text-align: center; margin: 24px 0; }
        .amount-box .amount { font-size: 36px; font-weight: bold; color: #0284c7; }
        .amount-box .label { color: #666; font-size: 14px; margin-top: 4px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .footer { background: #f9f9f9; padding: 20px 32px; text-align: center; font-size: 13px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Resumo de Repasse Mensal</h1>
        </div>
        <div class="body">
            <p>Olá, <strong>{{ $personal->nome }}</strong>!</p>
            <p>Seu repasse referente ao pagamento de um cliente foi processado.</p>

            <div class="amount-box">
                <div class="amount">R$ {{ number_format($payout->amount, 2, ',', '.') }}</div>
                <div class="label">Valor líquido a receber (90% do total)</div>
            </div>

            <h3 style="color:#333;">Detalhes</h3>
            <div class="detail-row">
                <span style="color:#666;">Status do Repasse</span>
                <span style="font-weight:bold;">{{ $payout->status === 'pending' ? 'Aguardando processamento' : ucfirst($payout->status) }}</span>
            </div>
            <div class="detail-row">
                <span style="color:#666;">Data</span>
                <span style="font-weight:bold;">{{ $payout->created_at->format('d/m/Y') }}</span>
            </div>

            <p style="margin-top:28px; color:#666; font-size:14px;">
                O valor será transferido para sua conta conforme o acordo estabelecido com a academia.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SnrFit. Todos os direitos reservados.
        </div>
    </div>
</body>
</html>
