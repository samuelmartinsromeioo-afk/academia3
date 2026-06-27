<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade — SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root { --primary:#F4BE16; --bg:#0a0b0d; --card:#14161a; --text:#e8e8e8; --muted:#9a9a9a; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:var(--text); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; line-height:1.7; }
        a { color:var(--primary); }
        .top { padding:18px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; position:sticky; top:0; background:rgba(10,11,13,0.9); backdrop-filter:blur(8px); }
        .top a.back { color:var(--primary); }
        .top .logo { font-weight:900; color:var(--primary); letter-spacing:0.5px; }
        .wrap { max-width:820px; margin:0 auto; padding:30px 22px 70px; }
        h1 { color:var(--primary); font-size:1.8rem; font-weight:900; margin-bottom:6px; }
        .upd { color:var(--muted); font-size:0.82rem; margin-bottom:26px; }
        h2 { color:#fff; font-size:1.15rem; margin:28px 0 10px; display:flex; align-items:center; gap:10px; }
        h2 i { color:var(--primary); font-size:0.95rem; }
        p, li { color:var(--text); font-size:0.95rem; margin-bottom:8px; }
        ul { padding-left:22px; margin-bottom:10px; }
        .box { background:var(--card); border:1px solid var(--border); border-left:3px solid var(--primary); border-radius:10px; padding:14px 16px; margin:12px 0; font-size:0.9rem; }
        .sens { border-left-color:#ff5252; }
        .muted { color:var(--muted); font-size:0.85rem; }
    </style>
</head>

<body class="ed-page">
    <div class="top">
        <a href="{{ url()->previous() }}" class="back"><i class="ph ph-arrow-left"></i></a>
        <span class="logo">SnrFit</span>
    </div>

    <div class="wrap">
        <h1>Política de Privacidade</h1>
        <p class="upd">Em conformidade com a Lei nº 13.709/2018 (LGPD) · Atualizada em {{ now()->format('d/m/Y') }}</p>

        <p>Esta política explica como o <strong>SnrFit</strong> coleta, usa, compartilha e protege os seus dados pessoais, e quais são os seus direitos como titular.</p>

        <h2><i class="ph ph-database"></i> 1. Dados que coletamos</h2>
        <ul>
            <li><strong>Cadastro:</strong> nome, e-mail, telefone/WhatsApp, foto e, no caso de profissionais, CPF, CREF e endereço.</li>
            <li><strong>Dados de treino:</strong> fichas, exercícios, cargas, frequência e metas.</li>
            <li><strong>Medidas corporais e fotos de progresso</strong> (quando você as informa).</li>
            <li><strong>Dados de pagamento:</strong> processados pelo nosso parceiro <strong>Asaas</strong>; não armazenamos dados completos de cartão.</li>
            <li><strong>Mensagens</strong> trocadas no chat entre aluno e personal.</li>
        </ul>
        <div class="box sens">
            <strong><i class="ph ph-heartbeat"></i> Dados sensíveis de saúde (art. 11 da LGPD):</strong>
            a anamnese (PAR-Q, lesões, doenças, medicamentos, restrições) é um dado sensível, coletado <strong>mediante o seu consentimento</strong> e usado exclusivamente para o personal montar um treino seguro. Você pode deixá-la em branco ou removê-la a qualquer momento.
        </div>

        <h2><i class="ph ph-target"></i> 2. Para que usamos</h2>
        <ul>
            <li>Prestar o serviço: conectar alunos e personais, montar e acompanhar treinos.</li>
            <li>Processar pagamentos e assinaturas.</li>
            <li>Enviar comunicações do serviço (e-mail/WhatsApp e avisos no app).</li>
            <li>Segurança, prevenção a fraudes e cumprimento de obrigações legais.</li>
        </ul>

        <h2><i class="ph ph-scales"></i> 3. Base legal</h2>
        <p>Tratamos seus dados com base na <strong>execução do contrato</strong> (uso da plataforma), no <strong>consentimento</strong> (especialmente para dados sensíveis de saúde) e no <strong>cumprimento de obrigações legais</strong> (ex.: registros financeiros).</p>

        <h2><i class="ph ph-share-network"></i> 4. Com quem compartilhamos</h2>
        <ul>
            <li><strong>Seu personal/academia:</strong> vê os dados necessários para te atender (treinos, evolução e anamnese).</li>
            <li><strong>Asaas</strong> (processamento de pagamentos), <strong>provedores de e-mail e WhatsApp</strong> (envio de notificações).</li>
            <li>Autoridades, quando exigido por lei.</li>
        </ul>
        <p class="muted">Não vendemos seus dados pessoais.</p>

        <h2><i class="ph ph-clock"></i> 5. Por quanto tempo guardamos</h2>
        <p>Mantemos seus dados enquanto sua conta estiver ativa. Ao excluir a conta, anonimizamos seu perfil e apagamos os dados pessoais e sensíveis. Registros financeiros podem ser retidos pelo prazo exigido pela legislação fiscal.</p>

        <h2><i class="ph ph-shield-check"></i> 6. Seus direitos (LGPD)</h2>
        <p>Você pode, a qualquer momento:</p>
        <ul>
            <li><strong>Acessar</strong> e <strong>exportar</strong> seus dados (formato legível por máquina).</li>
            <li><strong>Corrigir</strong> dados incompletos ou desatualizados.</li>
            <li><strong>Excluir</strong> sua conta e dados pessoais.</li>
            <li><strong>Revogar o consentimento</strong> para dados sensíveis.</li>
        </ul>
        <div class="box">
            Exerça esses direitos na página <strong>“Privacidade e meus dados”</strong> dentro do app (menu da sua conta), ou pelo contato abaixo.
        </div>

        <h2><i class="ph ph-lock"></i> 7. Segurança</h2>
        <p>Senhas são armazenadas com hash (bcrypt); o acesso é protegido por sessão, limite de tentativas de login e cabeçalhos de segurança. Recomendamos uso sob conexão HTTPS.</p>

        <h2><i class="ph ph-cookie"></i> 8. Cookies</h2>
        <p>Utilizamos apenas cookies essenciais de sessão, necessários para manter você autenticado. Não usamos cookies de rastreamento de terceiros.</p>

        <h2><i class="ph ph-envelope"></i> 9. Contato (Encarregado/DPO)</h2>
        <p>Dúvidas ou solicitações sobre seus dados: <a href="mailto:privacidade@snrfit.com.br">privacidade@snrfit.com.br</a>.</p>

        <p class="muted" style="margin-top:30px;">Esta política pode ser atualizada. Avisaremos sobre mudanças relevantes.</p>
    </div>
</body>

</html>
