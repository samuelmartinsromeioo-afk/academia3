<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - FitConnect</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
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

<body class="ed-page">

    <div class="top-bar">
        <a href="/" class="logo">
            <i class="ph ph-barbell"></i> SNR
        </a>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button class="print-btn" onclick="window.print()">
                <i class="ph ph-printer"></i> Imprimir
            </button>
            <a href="/" class="back-btn">
                <i class="ph ph-caret-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="ph ph-file-text"></i> Termos de Uso</h1>
            <p>Leia com atenção antes de usar nossa plataforma</p>
            <p style="margin-top: 10px; font-size: 0.85rem; color: rgba(255,255,255,0.5);">
                Última atualização: {{ date('d/m/Y') }}
            </p>
        </div>

        <div class="content">

            <div class="toc">
                <h3><i class="ph ph-list"></i> Índice</h3>
                <ul>
                    <li><a href="#secao-1">1. Apresentação e Aceitação dos Termos</a></li>
                    <li><a href="#secao-2">2. Descrição da Plataforma</a></li>
                    <li><a href="#secao-3">3. Categorias de Usuários</a></li>
                    <li><a href="#secao-4">4. Elegibilidade e Cadastro</a></li>
                    <li><a href="#secao-5">5. Direitos e Responsabilidades</a></li>
                    <li><a href="#secao-6">6. Saúde e Segurança</a></li>
                    <li><a href="#secao-7">7. Pagamento e Preços</a></li>
                    <li><a href="#secao-8">8. Avaliações e Feedback</a></li>
                    <li><a href="#secao-9">9. Rescisão e Suspensão</a></li>
                    <li><a href="#secao-10">10. Limitação de Responsabilidade</a></li>
                    <li><a href="#secao-11">11. Propriedade Intelectual</a></li>
                    <li><a href="#secao-12">12. Proteção de Dados</a></li>
                    <li><a href="#secao-13">13. Lei Aplicável</a></li>
                </ul>
            </div>

            <h2 id="secao-1">1. APRESENTAÇÃO E ACEITAÇÃO DOS TERMOS</h2>
            <p>Bem-vindo ao <strong>SNR</strong>. Estes Termos de Uso ("Termos") regem o acesso e o uso da Plataforma e de todos os serviços, conteúdos e funcionalidades oferecidos por nossa empresa ("Nós", "Nossa", "Nossos" ou "Plataforma").</p>
            <p>Ao acessar, cadastrar-se, usar a Plataforma ou aceitar estes Termos, você ("Usuário" ou "Você") concorda integralmente com todas as disposições aqui contidas. Se você não concordar com qualquer parte destes Termos, não deve utilizar a Plataforma.</p>

            <h2 id="secao-2">2. DESCRIÇÃO DA PLATAFORMA</h2>
            <p>Nossa Plataforma funciona como um marketplace que conecta usuários que buscam serviços de personal training com profissionais credenciados ("Personals" ou "Prestadores de Serviço").</p>
            <h3>Funcionalidades Principais</h3>
            <ul>
                <li>Localização de personals trainers próximos</li>
                <li>Agendamento de sessões de treino</li>
                <li>Comunicação entre usuários e personals</li>
                <li>Histórico de sessões e avaliações</li>
                <li>Pagamento integrado de serviços</li>
                <li>Sistema de avaliações e reputação</li>
            </ul>

            <h2 id="secao-3">3. CATEGORIAS DE USUÁRIOS</h2>
            <h3>Usuários Finais (Clientes)</h3>
            <p>Pessoas que utilizam a Plataforma para encontrar, contratar e pagar por serviços de personal training.</p>
            <h3>Prestadores de Serviço (Personals)</h3>
            <p>Profissionais credenciados e qualificados que oferecem serviços de treinamento pessoal através da Plataforma.</p>

            <h2 id="secao-4">4. ELEGIBILIDADE E CADASTRO</h2>
            <h3>Requisitos Gerais</h3>
            <ul>
                <li>Ter no mínimo 18 anos de idade</li>
                <li>Ser residente no Brasil</li>
                <li>Concordar com estes Termos de Uso</li>
                <li>Fornecer informações verdadeiras, precisas e completas</li>
                <li>Manter seus dados cadastrais atualizados</li>
            </ul>
            <h3>Cadastro de Personals Trainers</h3>
            <p>Além dos requisitos acima, personals trainers devem:</p>
            <ul>
                <li>Possuir CPF ou CNPJ válido</li>
                <li>Apresentar comprovante de qualificação profissional reconhecida (certificado de formação em educação física ou similar)</li>
                <li>Apresentar documentos de identificação válidos</li>
                <li>Ter comprovante de endereço</li>
                <li>Passar por verificação de antecedentes conforme legislação aplicável</li>
                <li>Aceitar o Código de Conduta para Prestadores de Serviço</li>
            </ul>

            <h2 id="secao-5">5. DIREITOS E RESPONSABILIDADES DOS USUÁRIOS</h2>
            <h3>Sua Responsabilidade</h3>
            <p>Você é responsável por:</p>
            <ul>
                <li>Manter a confidencialidade de sua senha e informações de acesso</li>
                <li>Notificar-nos imediatamente de qualquer uso não autorizado de sua conta</li>
                <li>Garantir que todas as informações fornecidas sejam precisas e completas</li>
                <li>Respeitar os direitos de propriedade intelectual da Plataforma e de outros usuários</li>
                <li>Cumprir todas as leis e regulamentos aplicáveis</li>
            </ul>
            <h3>Proibições</h3>
            <p>Você se compromete a NÃO:</p>
            <ul>
                <li>Transferir sua conta ou permitir que terceiros a utilizem</li>
                <li>Usar linguagem abusiva, discriminatória ou ofensiva</li>
                <li>Realizar assédio, ameaças ou violência contra outros usuários</li>
                <li>Violar privacidade ou direitos de imagem de terceiros</li>
                <li>Coletar dados pessoais de outros usuários sem consentimento</li>
                <li>Usar a Plataforma para atividades ilegais ou fraudulentas</li>
                <li>Falsificar identidade ou fornecer informações enganosas</li>
                <li>Interferir no funcionamento técnico da Plataforma</li>
                <li>Fazer spam, phishing ou outras práticas prejudiciais</li>
            </ul>

            <h2 id="secao-6">6. SAÚDE E SEGURANÇA</h2>
            <h3>Avaliação de Saúde</h3>
            <p>Usuários clientes devem informar ao personal trainer sobre qualquer condição de saúde, lesão, gravidez, medicações ou restrições que possam afetar a prática de exercícios.</p>
            <h3>Responsabilidade por Lesões</h3>
            <ul>
                <li>A Plataforma não se responsabiliza por lesões, danos ou acidentes decorrentes da prática de exercícios</li>
                <li>Personal trainers devem estar devidamente preparados e seguir protocolos de segurança</li>
                <li>Usuários reconhecem que atividade física apresenta riscos inerentes</li>
                <li>Recomenda-se consulta com médico antes de iniciar qualquer programa de exercícios</li>
            </ul>
            <div class="highlighted">
                <strong>⚠️ Aviso Importante:</strong> Personal trainers não estão autorizados a fornecer orientações médicas, diagnósticos ou tratamento. Qualquer questão de saúde deve ser direcionada a profissionais médicos qualificados.
            </div>

            <h2 id="secao-7">7. PAGAMENTO E PREÇOS</h2>
            <h3>Estrutura de Preços</h3>
            <ul>
                <li>Personal trainers definem seus próprios preços e condições de serviço</li>
                <li>Preços e promoções são informados na Plataforma antes da confirmação do agendamento</li>
                <li>Cobranças incluem a taxa de operação da Plataforma</li>
            </ul>
            <h3>Métodos de Pagamento</h3>
            <ul>
                <li>Cartão de crédito e débito</li>
                <li>Carteiras digitais</li>
                <li>PIX</li>
                <li>Outros métodos integrados à Plataforma</li>
                <li>Todos os pagamentos são processados por processadores de pagamento certificados</li>
            </ul>
            <h3>Reembolsos e Cancelamentos</h3>
            <ul>
                <li>Cancelamentos pelo cliente: sujeito à política de cancelamento do personal trainer</li>
                <li>Cancelamentos pelo personal: cliente tem direito a reembolso ou reagendamento</li>
                <li>Cancelamentos com menos de 24h de antecedência podem resultar em multa</li>
                <li>Reembolsos são processados dentro de 5 a 10 dias úteis</li>
            </ul>

            <h2 id="secao-8">8. AVALIAÇÕES E FEEDBACK</h2>
            <h3>Sistema de Avaliação</h3>
            <ul>
                <li>Usuários podem avaliar personals após as sessões</li>
                <li>Personals podem avaliar usuários clientes</li>
                <li>Avaliações devem ser honestas, justas e baseadas em experiência real</li>
            </ul>
            <h3>Restrições sobre Avaliações</h3>
            <ul>
                <li>Avaliações falsas, ofensivas ou discriminatórias são proibidas</li>
                <li>Avaliações extorsivas ou visando prejudicar reputação serão removidas</li>
                <li>Usuários podem reportar avaliações inadequadas</li>
            </ul>

            <h2 id="secao-9">9. RESCISÃO E SUSPENSÃO DE CONTA</h2>
            <h3>Cancelamento por Usuário</h3>
            <p>Você pode cancelar sua conta a qualquer momento através das configurações de perfil.</p>
            <h3>Suspensão ou Cancelamento pela Plataforma</h3>
            <p>Podemos suspender ou cancelar sua conta se você:</p>
            <ul>
                <li>Violar estes Termos ou legislação aplicável</li>
                <li>Praticar fraude ou atividades ilícitas</li>
                <li>Ofender ou prejudicar outros usuários</li>
                <li>Descumprir políticas de saúde e segurança</li>
                <li>Não manter informações cadastrais atualizadas</li>
                <li>Abandonar a Plataforma por período prolongado</li>
            </ul>

            <h2 id="secao-10">10. LIMITAÇÃO DE RESPONSABILIDADE</h2>
            <h3>Serviço no Estado "Como Está"</h3>
            <p>A Plataforma é fornecida no estado "como está" sem garantias de qualquer tipo, expressas ou implícitas.</p>
            <h3>Isenção de Garantias</h3>
            <p>Não garantimos que a Plataforma será:</p>
            <ul>
                <li>Ininterrupta ou livre de erros</li>
                <li>Segura contra acessos não autorizados</li>
                <li>Adequada para qualquer propósito específico seu</li>
                <li>Livre de vírus ou componentes prejudiciais</li>
            </ul>
            <h3>Responsabilidade Máxima</h3>
            <p>Nossa responsabilidade total não excederá o valor total que você pagou à Plataforma nos últimos 12 meses.</p>

            <h2 id="secao-11">11. PROPRIEDADE INTELECTUAL</h2>
            <h3>Propriedade da Plataforma</h3>
            <p>Todo conteúdo, design, código, marcas registradas, logos e elementos da Plataforma são propriedade exclusiva nossa ou licenciados.</p>
            <h3>Licença de Uso</h3>
            <p>Concedemos licença limitada, não exclusiva e revogável para que você use a Plataforma conforme estes Termos. Esta licença não inclui direito de reproduzir, copiar, modificar ou criar trabalhos derivados.</p>

            <h2 id="secao-12">12. PROTEÇÃO DE DADOS E PRIVACIDADE</h2>
            <h3>Dados Pessoais</h3>
            <p>Sua privacidade é importante. Consulte nossa <strong>Política de Privacidade</strong> para detalhes completos sobre como coletamos, usamos e protegemos seus dados.</p>
            <h3>Conformidade com LGPD</h3>
            <p>Cumprimos a Lei Geral de Proteção de Dados (LGPD) e regulamentos brasileiros de proteção de dados.</p>
            <h3>Dados de Saúde</h3>
            <p>Dados relacionados a saúde e condições físicas receberão proteção especial e só serão compartilhados com consentimento explícito.</p>

            <h2 id="secao-13">13. LEI APLICÁVEL E CONTATO</h2>
            <h3>Lei Aplicável</h3>
            <p>Estes Termos são regidos pelas leis da República Federativa do Brasil, especificamente conforme legislação do estado onde está sediada a Plataforma.</p>
            <h3>Contato e Suporte</h3>
            <p>Para questões sobre estes Termos, entre em contato conosco:</p>
            <ul>
                <li><strong>Email:</strong> suporte@snrfittech.com</li>
                <li><strong>Telefone:</strong> +5531989542169</li>
                
            </ul>

            <div class="highlighted">
                <strong>✅ Aceitação:</strong> Ao usar a Plataforma, você confirma que leu, compreendeu e concorda com todos os termos e condições acima descritos.
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
