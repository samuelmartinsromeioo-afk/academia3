<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seus dados na SNR FIT</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #0a0b0d; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #14161a; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.4); }
        .header { background: #0a0b0d; padding: 28px 32px; text-align: center; border-bottom: 3px solid #7cff00; }
        .header .logo { color: #7cff00; margin: 0; font-size: 22px; font-weight: 800; letter-spacing: .5px; }
        .header h1 { color: #ffffff; margin: 12px 0 0; font-size: 20px; font-weight: 700; }
        .body { padding: 32px; color: #e6e6e6; }
        .body p { line-height: 1.65; font-size: 15px; margin: 0 0 14px; }
        .greeting { color: #7cff00; font-weight: 700; }
        .message { background: #1b1e24; border-left: 3px solid #7cff00; border-radius: 8px; padding: 18px 20px; color: #e6e6e6; line-height: 1.7; font-size: 15px; }
        .message strong { color: #ffffff; }
        .footer { background: #0a0b0d; padding: 20px 32px; text-align: center; font-size: 12px; color: #6b7280; }
        .footer a { color: #7cff00; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p class="logo">SnrFit</p>
            <h1>Seus dados, como você pediu</h1>
        </div>
        <div class="body">
            <p>Olá, <span class="greeting">{{ $nome }}</span>!</p>
            <div class="message">
                Em anexo está a cópia completa dos seus dados na SNR FIT, no arquivo
                <strong>{{ $nomeArquivo }}</strong>.
                <br><br>
                O formato é <strong>JSON</strong>, que é legível por máquina — é o que a
                <strong>LGPD (art. 18, II e V)</strong> exige de portabilidade, para você poder
                levar os dados a outro fornecedor sem depender de nós. Qualquer editor de texto
                abre o arquivo.
                <br><br>
                <strong>Guarde este e-mail com cuidado:</strong> o anexo reúne num só lugar tudo
                o que temos sobre você, inclusive dados de saúde, se você os preencheu.
                <br><br>
                A senha <strong>não</strong> vai no arquivo — ela é guardada apenas como resumo
                criptográfico e nem nós podemos lê-la.
                <br><br>
                Se <strong>não</strong> foi você quem pediu esta cópia, troque sua senha e
                responda a esta mensagem imediatamente.
            </div>
        </div>
        <div class="footer">
            {{ \App\Support\Empresa::get('razao_social') }} · CNPJ {{ \App\Support\Empresa::get('cnpj') }}<br>
            Encarregado de Dados: {{ \App\Support\Empresa::get('dpo_nome') }} —
            <a href="mailto:{{ \App\Support\Empresa::get('dpo_email') }}">{{ \App\Support\Empresa::get('dpo_email') }}</a><br>
            &copy; {{ date('Y') }} {{ \App\Support\Empresa::get('marca') }}. Todos os direitos reservados.
        </div>
    </div>
</body>
</html>
