<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade — SNR FIT</title>
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
        <span class="logo">SNR FIT</span>
    </div>

    <div class="wrap">
        <h1>Política de Privacidade</h1>
        <p class="upd">Em conformidade com a Lei nº 13.709/2018 (LGPD) · Atualizada em {{ now()->format('d/m/Y') }}</p>

        <p>Esta política explica como o <strong>SNR FIT</strong> coleta, usa, compartilha e protege os seus dados pessoais, e quais são os seus direitos como titular.</p>

        <h2><i class="ph ph-database"></i> 1. Dados que coletamos</h2>
        <ul>
            <li><strong>Cadastro:</strong> nome, e-mail, telefone/WhatsApp, foto e, no caso de profissionais, CPF, CREF e endereço.</li>
            <li><strong>Dados de treino:</strong> fichas, exercícios, cargas, frequência e metas.</li>
            <li><strong>Medidas corporais e fotos de progresso</strong> (quando você as informa).</li>
            <li><strong>Dados de pagamento:</strong> processados pelo nosso parceiro <strong>Asaas</strong>; não armazenamos dados completos de cartão.</li>
            <li><strong>Mensagens</strong> trocadas no chat entre aluno e personal.</li>
            <li><strong>Localização aproximada</strong> — somente quando você ativa "Perto de mim" na busca, para ordenar os resultados por proximidade. Não rastreamos sua localização em segundo plano.</li>
        </ul>
        <div class="box sens">
            <strong><i class="ph ph-heartbeat"></i> Dados sensíveis de saúde (art. 11 da LGPD):</strong>
            a anamnese (PAR-Q, lesões, doenças, medicamentos, restrições) é um dado sensível, coletado <strong>mediante o seu consentimento</strong> e usado exclusivamente para o personal montar um treino seguro. Você pode deixá-la em branco ou removê-la a qualquer momento.
        </div>

        <h2><i class="ph ph-target"></i> 2. Para que usamos</h2>
        <ul>
            <li>Prestar o serviço: conectar alunos e personais, montar e acompanhar treinos.</li>
            <li>Processar pagamentos, assinaturas e compras na loja, e viabilizar a entrega dos produtos.</li>
            <li>Mostrar academias, personais, studios e lojas mais próximos, quando você ativa a localização na busca.</li>
            <li>Enviar comunicações do serviço (e-mail/WhatsApp e avisos no app).</li>
            <li>Medir e melhorar campanhas de marketing (Meta Pixel/API de Conversões) — <strong>somente com o seu consentimento</strong> (ver seção 8).</li>
            <li>Segurança, prevenção a fraudes e cumprimento de obrigações legais.</li>
        </ul>

        <h2><i class="ph ph-scales"></i> 3. Base legal</h2>
        <p>Tratamos seus dados com base na <strong>execução do contrato</strong> (uso da plataforma), no <strong>consentimento</strong> (especialmente para dados sensíveis de saúde) e no <strong>cumprimento de obrigações legais</strong> (ex.: registros financeiros).</p>

        <h2><i class="ph ph-share-network"></i> 4. Com quem compartilhamos</h2>
        <ul>
            <li><strong>Seu personal/academia:</strong> vê os dados necessários para te atender (treinos, evolução e anamnese).</li>
            <li><strong>Asaas</strong> (processamento de pagamentos), <strong>provedores de e-mail e WhatsApp</strong> (envio de notificações).</li>
            <li><strong>Meta</strong> (Facebook/Instagram) — apenas <strong>com o seu consentimento</strong>, para medição de anúncios via Pixel e API de Conversões (ver seção 8).</li>
            <li>Autoridades, quando exigido por lei.</li>
        </ul>
        <p class="muted">Não vendemos seus dados pessoais.</p>
        <div class="box">
            <strong><i class="ph ph-globe"></i> Transferência internacional:</strong> alguns desses parceiros (por exemplo, provedores de e-mail e o WhatsApp/Meta) podem tratar dados em servidores fora do Brasil. Nesses casos, o compartilhamento ocorre apenas para a finalidade descrita e com as salvaguardas exigidas pela LGPD.
        </div>

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

        <h2><i class="ph ph-cookie"></i> 8. Cookies e tecnologias de medição</h2>
        <p><strong>Cookies essenciais:</strong> usados para manter você autenticado e garantir o funcionamento e a segurança da plataforma. São indispensáveis e por isso não dependem de consentimento.</p>
        <p><strong>Cookies e ferramentas de marketing/medição:</strong> utilizamos o <strong>Meta Pixel</strong> e a <strong>API de Conversões da Meta</strong> (Facebook/Instagram) para medir o desempenho de anúncios e melhorar nossas campanhas. Essas ferramentas podem gravar cookies (por exemplo, <code>_fbp</code> e <code>_fbc</code>) e enviar à Meta eventos de navegação e de conversão (como cadastro, agendamento e compra), incluindo identificadores <strong>pseudonimizados com criptografia (hash SHA-256)</strong> e seu endereço IP.</p>
        <div class="box">
            <strong><i class="ph ph-check-circle"></i> Você no controle:</strong> essas ferramentas de marketing <strong>só são ativadas após o seu consentimento</strong> no banner de cookies. Enquanto você não aceitar, nada é enviado à Meta. Você pode recusar e continuar usando a plataforma normalmente, e pode rever sua escolha limpando os cookies do navegador.
        </div>
        <p class="muted">Não vendemos seus dados. O tratamento pela Meta segue as políticas do próprio parceiro.</p>

        <h2><i class="ph ph-device-mobile"></i> 9. Permissões e acessos no aplicativo</h2>
        <p>O aplicativo SNR FIT só solicita acesso a recursos do seu aparelho quando você usa a função correspondente, sempre por meio de um pedido explícito do sistema operacional que você pode recusar:</p>
        <ul>
            <li><strong>Câmera:</strong> para você tirar a foto de perfil ou registrar fotos de progresso do treino. Só é acionada quando você escolhe tirar uma foto.</li>
            <li><strong>Fotos / galeria:</strong> para você selecionar uma imagem já existente (foto de perfil, produtos da loja ou progresso). Acessamos apenas a imagem que você escolher, não a sua biblioteca inteira.</li>
            <li><strong>Localização (enquanto o app está em uso):</strong> usada apenas quando você toca em "Perto de mim" na busca, para ordenar academias, personais, studios e lojas do mais próximo ao mais distante. A posição é usada no momento da busca, não serve para rastrear você e você pode recusar, continuando a buscar por nome ou cidade.</li>
            <li><strong>Notificações:</strong> com a sua permissão, exibimos avisos do navegador (ex.: nova mensagem no chat, aula agendada, lembretes). Você pode recusar ou desativar nas configurações do navegador/dispositivo a qualquer momento, sem afetar o uso da plataforma.</li>
        </ul>
        <div class="box">
            O aplicativo <strong>não utiliza</strong> microfone, contatos, nem inteligência artificial para tratar os seus dados, e <strong>não</strong> rastreia sua localização em segundo plano. Caso alguma nova função passe a existir no futuro, esta política será atualizada e a permissão só será solicitada quando a função for realmente usada.
        </div>

        <h2><i class="ph ph-baby"></i> 10. Menores de idade</h2>
        <p>O uso da Plataforma como aluno é permitido a partir dos <strong>14 anos</strong>. Menores de 18 anos devem ter o <strong>consentimento e a assistência dos pais ou responsáveis legais</strong> — em especial para a coleta de dados de saúde (anamnese), que só ocorre com o consentimento do responsável. O cadastro como personal trainer exige idade mínima de <strong>18 anos</strong>. Caso identifiquemos dados de menores tratados sem a devida autorização, adotaremos medidas para excluí-los.</p>

        <h2><i class="ph ph-envelope"></i> 11. Contato (Encarregado/DPO)</h2>
        <p>Dúvidas ou solicitações sobre seus dados: <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.</p>

        <p class="muted" style="margin-top:30px;">Esta política pode ser atualizada. Avisaremos sobre mudanças relevantes.</p>
    </div>
</body>

</html>
