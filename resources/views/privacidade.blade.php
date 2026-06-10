<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - FitConnect</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255, 255, 255, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1117 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-main);
            line-height: 1.6;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .back-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .back-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .header p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .content {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            line-height: 1.8;
        }

        .content h2 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-top: 40px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(212, 255, 0, 0.2);
        }

        .content h2:first-of-type {
            margin-top: 0;
        }

        .content h3 {
            color: #fff;
            font-size: 1.1rem;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .content p {
            margin-bottom: 15px;
            color: var(--text-muted);
        }

        .content ul {
            margin-left: 25px;
            margin-bottom: 15px;
        }

        .content li {
            margin-bottom: 10px;
            color: var(--text-muted);
        }

        .content strong {
            color: var(--primary);
        }

        .highlighted {
            background: rgba(212, 255, 0, 0.08);
            border-left: 4px solid var(--primary);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .toc {
            background: rgba(212, 255, 0, 0.05);
            border: 1px solid rgba(212, 255, 0, 0.2);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 40px;
        }

        .toc h3 {
            margin-top: 0;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .toc ul {
            list-style: none;
            margin-left: 0;
        }

        .toc li {
            margin-bottom: 8px;
        }

        .toc a {
            color: var(--primary);
            text-decoration: none;
            transition: 0.2s;
        }

        .toc a:hover {
            text-decoration: underline;
        }

        .print-btn {
            background: var(--primary);
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 900;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .print-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .top-bar {
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .content {
                padding: 25px;
            }

            .container {
                padding: 20px 15px;
            }
        }

        @media print {
            .top-bar,
            .print-btn,
            .back-btn {
                display: none;
            }

            body {
                background: #fff;
            }

            .content {
                background: transparent;
                border: none;
                padding: 0;
            }

            .header h1 {
                color: #000;
            }

            .content h2 {
                color: #000;
                border-bottom: 2px solid #ccc;
            }

            .content h3 {
                color: #000;
            }

            .content p,
            .content li {
                color: #333;
            }
        }
    </style>
</head>

<body>

    <div class="top-bar">
        <a href="/" class="logo">
            <i class="fas fa-dumbbell"></i> SNR
        </a>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button class="print-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <a href="/" class="back-btn">
                <i class="fas fa-chevron-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Política de Privacidade</h1>
            <p>Como protegemos seus dados pessoais</p>
            <p style="margin-top: 10px; font-size: 0.85rem; color: rgba(255,255,255,0.5);">
                Última atualização: {{ date('d/m/Y') }}
            </p>
        </div>

        <div class="content">

            <div class="toc">
                <h3><i class="fas fa-list"></i> Índice</h3>
                <ul>
                    <li><a href="#secao-1">1. Introdução</a></li>
                    <li><a href="#secao-2">2. Informações que Coletamos</a></li>
                    <li><a href="#secao-3">3. Como Usamos Suas Informações</a></li>
                    <li><a href="#secao-4">4. Compartilhamento de Dados</a></li>
                    <li><a href="#secao-5">5. Proteção de Dados</a></li>
                    <li><a href="#secao-6">6. Seus Direitos</a></li>
                    <li><a href="#secao-7">7. Conformidade com LGPD</a></li>
                    <li><a href="#secao-8">8. Retenção de Dados</a></li>
                    <li><a href="#secao-9">9. Contato</a></li>
                </ul>
            </div>

            <h2 id="secao-1">1. INTRODUÇÃO</h2>
            <p>A <strong>SNR</strong> ("nós", "nossa" ou "Plataforma") é comprometida em proteger sua privacidade e garantir uma experiência segura. Esta Política de Privacidade explica como coletamos, usamos, divulgamos e salvaguardamos suas informações ao usar nossa Plataforma.</p>
            <p>Leia esta Política de Privacidade com cuidado. Se você não concordar com nossas práticas, não use a Plataforma.</p>

            <h2 id="secao-2">2. INFORMAÇÕES QUE COLETAMOS</h2>
            <p>Coletamos informações que você nos fornece diretamente e informações coletadas automaticamente quando você usa a Plataforma.</p>
            <h3>Informações Fornecidas Diretamente</h3>
            <ul>
                <li><strong>Informações de Conta:</strong> Nome, email, telefone, CPF/CNPJ, data de nascimento</li>
                <li><strong>Informações de Perfil:</strong> Foto de perfil, biografia, localização, preferências</li>
                <li><strong>Informações de Saúde:</strong> Condições clínicas, objetivos de fitness, restrições de exercício</li>
                <li><strong>Informações de Pagamento:</strong> Dados de cartão, histórico de transações (processados por terceiros)</li>
                <li><strong>Comunicação:</strong> Mensagens, avaliações, comentários</li>
            </ul>
            <h3>Informações Coletadas Automaticamente</h3>
            <ul>
                <li>Endereço IP e identificador de dispositivo</li>
                <li>Tipo de navegador e sistema operacional</li>
                <li>Histórico de navegação na Plataforma</li>
                <li>Localização geográfica (se autorizado)</li>
                <li>Cookies e tecnologias de rastreamento similar</li>
            </ul>

            <h2 id="secao-3">3. COMO USAMOS SUAS INFORMAÇÕES</h2>
            <p>Usamos as informações coletadas para os seguintes fins:</p>
            <ul>
                <li>Fornecer, manter e melhorar a Plataforma e seus serviços</li>
                <li>Processar transações e enviar informações relacionadas</li>
                <li>Enviar notificações, atualizações e informações sobre a Plataforma</li>
                <li>Responder suas perguntas, comentários e solicitações</li>
                <li>Personalizar sua experiência na Plataforma</li>
                <li>Analisar o uso da Plataforma para melhorar nossos serviços</li>
                <li>Detectar e prevenir fraude e atividades ilícitas</li>
                <li>Cumprir obrigações legais e regulamentares</li>
            </ul>

            <div class="highlighted">
                <strong>✅ Base Legal:</strong> Processamos seus dados com base em seu consentimento, cumprimento de contrato, interesses legítimos ou conformidade legal.
            </div>

            <h2 id="secao-4">4. COMPARTILHAMENTO DE DADOS</h2>
            <h3>Quando Compartilhamos Seus Dados</h3>
            <p>Não compartilhamos informações pessoais com terceiros sem seu consentimento, exceto nos seguintes casos:</p>
            <ul>
                <li><strong>Prestadores de Serviço:</strong> Provedores de pagamento, hospedagem, análise de dados</li>
                <li><strong>Requisitos Legais:</strong> Quando exigido por lei, ordem judicial ou regulamento</li>
                <li><strong>Proteção de Direitos:</strong> Para proteger direitos, privacidade, segurança nossos e de terceiros</li>
                <li><strong>Pessoal Trainer e Clientes:</strong> Informações básicas são compartilhadas entre partes para agendamento</li>
            </ul>
            <h3>Dados Não Compartilhados</h3>
            <p>Informações de saúde sensível não são compartilhadas sem consentimento explícito, exceto com o personal trainer contratado para fins de segurança.</p>

            <h2 id="secao-5">5. PROTEÇÃO DE DADOS</h2>
            <h3>Medidas de Segurança</h3>
            <p>Implementamos medidas de segurança técnicas, administrativas e físicas para proteger suas informações pessoais contra acesso não autorizado, alteração ou destruição.</p>
            <ul>
                <li>Criptografia de dados em trânsito (SSL/TLS)</li>
                <li>Criptografia de dados em repouso</li>
                <li>Controle de acesso baseado em funções</li>
                <li>Monitoramento de segurança contínuo</li>
                <li>Política de segurança da informação</li>
            </ul>
            <h3>Limitações de Segurança</h3>
            <p>Embora implementemos medidas robustas, nenhuma transmissão de dados pela Internet é 100% segura. Não garantimos segurança absoluta de suas informações.</p>

            <h2 id="secao-6">6. SEUS DIREITOS</h2>
            <p>De acordo com a Lei Geral de Proteção de Dados (LGPD), você tem os seguintes direitos:</p>
            <ul>
                <li><strong>Direito de Acesso:</strong> Acessar seus dados pessoais que possuímos</li>
                <li><strong>Direito de Correção:</strong> Solicitar correção de dados imprecisos</li>
                <li><strong>Direito ao Esquecimento:</strong> Solicitar exclusão de seus dados</li>
                <li><strong>Direito de Portabilidade:</strong> Receber seus dados em formato estruturado</li>
                <li><strong>Direito de Oposição:</strong> Opor-se ao processamento de seus dados</li>
                <li><strong>Direito de Revogar Consentimento:</strong> Revogar consentimento a qualquer momento</li>
            </ul>
            <p style="margin-top: 20px;">Para exercer esses direitos, entre em contato conosco através das informações fornecidas na seção de Contato.</p>

            <h2 id="secao-7">7. CONFORMIDADE COM LGPD</h2>
            <p>Somos um controlador de dados sob a LGPD e nos comprometemos a cumprir todas as disposições da lei.</p>
            <h3>Nossas Obrigações</h3>
            <ul>
                <li>Informar transparentemente sobre coleta e uso de dados</li>
                <li>Obter consentimento antes de processar dados sensíveis</li>
                <li>Manter registros de processamento de dados</li>
                <li>Permitir que indivíduos exerçam seus direitos</li>
                <li>Notificar autoridades sobre brechas de dados</li>
                <li>Realizar avaliações de impacto quando necessário</li>
            </ul>

            <h2 id="secao-8">8. RETENÇÃO DE DADOS</h2>
            <p>Retemos seus dados pessoais apenas pelo tempo necessário para atingir os fins para os quais foram coletados. O período de retenção varia conforme o tipo de dado:</p>
            <ul>
                <li><strong>Dados de Conta:</strong> Durante a vigência da conta + 2 anos após cancelamento</li>
                <li><strong>Dados de Transação:</strong> Conforme exigência legal (geralmente 5-10 anos)</li>
                <li><strong>Dados de Comunicação:</strong> Até 1 ano após última interação</li>
                <li><strong>Dados de Log:</strong> Até 6 meses</li>
            </ul>

            <h2 id="secao-9">9. CONTATO</h2>
            <p>Se tiver dúvidas sobre esta Política de Privacidade ou sobre como tratamos seus dados, entre em contato conosco:</p>
            <ul>
                <li><strong>Email:</strong> suporte@snrfittech.com</li>
                <li><strong>Telefone:</strong> +5531989542169</li>
            </ul>
            <p style="margin-top: 20px;">Você também pode entrar em contato com a Autoridade Nacional de Proteção de Dados (ANPD) se tiver reclamações sobre o tratamento de seus dados.</p>

            <div class="highlighted">
                <strong>📱 Seu Direito:</strong> Se acredita que seus direitos foram violados, pode fazer uma reclamação junto à ANPD.
            </div>

        </div>

        <div class="footer">
            <p><strong>Última atualização:</strong> {{ date('d/m/Y') }}</p>
            <p><strong>Versão:</strong> 1.0</p>
            <p style="margin-top: 20px;">© 2024 FitConnect. Todos os direitos reservados.</p>
        </div>

    </div>

</body>

</html>
