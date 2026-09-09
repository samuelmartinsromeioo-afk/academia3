<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte — SNR FIT</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255, 255, 255, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1117 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-main); line-height: 1.6; min-height: 100vh;
        }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 40px; background: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);
        }
        .logo {
            font-size: 1.5rem; font-weight: 900; color: var(--primary);
            display: flex; align-items: center; gap: 10px; text-decoration: none;
        }
        .back-btn {
            background: var(--card-bg); border: 1px solid var(--border); color: var(--text-main);
            padding: 10px 20px; border-radius: 10px; text-decoration: none;
            display: flex; align-items: center; gap: 8px; font-size: 0.9rem; transition: .3s;
        }
        .back-btn:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 820px; margin: 0 auto; padding: 40px 20px; }

        .header { text-align: center; margin-bottom: 40px; }
        .header h1 {
            font-size: 2.2rem; margin-bottom: 10px; color: var(--primary);
            display: flex; align-items: center; justify-content: center; gap: 12px;
        }
        .header p { color: var(--text-muted); }

        .card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 20px; padding: 32px; margin-bottom: 24px;
        }
        .card h2 {
            color: var(--primary); font-size: 1.35rem; margin-bottom: 18px;
            padding-bottom: 10px; border-bottom: 2px solid rgba(212, 255, 0, 0.2);
            display: flex; align-items: center; gap: 10px;
        }
        .card h3 { color: #fff; font-size: 1.05rem; margin-top: 22px; margin-bottom: 6px; }
        .card p { color: var(--text-muted); margin-bottom: 12px; }
        .card a { color: var(--primary); }
        .card strong { color: var(--primary); }

        .contact-line { font-size: 1.15rem; margin: 10px 0 4px; }
        .muted-sm { color: var(--text-muted); font-size: 0.9rem; }

        .cta {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 16px;
            background: var(--primary); color: #0a0b0d; font-weight: 800;
            padding: 13px 22px; border-radius: 12px; text-decoration: none;
        }
        .cta:hover { filter: brightness(1.08); }

        .highlighted {
            background: rgba(212, 255, 0, 0.08); border-left: 4px solid var(--primary);
            padding: 15px 20px; border-radius: 8px; margin: 16px 0;
        }
        .highlighted strong { color: var(--primary); }

        .doc-links { display: flex; flex-wrap: wrap; gap: 12px; }
        .doc-links a {
            background: rgba(212, 255, 0, 0.06); border: 1px solid rgba(212, 255, 0, 0.25);
            color: var(--primary); padding: 10px 16px; border-radius: 999px;
            text-decoration: none; font-size: 0.9rem;
        }
        .doc-links a:hover { background: rgba(212, 255, 0, 0.12); }

        .footer {
            text-align: center; margin-top: 40px; padding-top: 24px;
            border-top: 1px solid var(--border); color: var(--text-muted); font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .top-bar { padding: 15px 20px; }
            .header h1 { font-size: 1.6rem; }
            .card { padding: 24px; }
            .container { padding: 24px 15px; }
        }
    </style>
</head>

<body>

    <div class="top-bar">
        <a href="/" class="logo"><i class="ph ph-barbell"></i> SNR FIT</a>
        <a href="/" class="back-btn"><i class="ph ph-caret-left"></i> Voltar</a>
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="ph ph-lifebuoy"></i> Central de Suporte</h1>
            <p>Estamos aqui para ajudar você a usar o SNR FIT.</p>
        </div>

        <div class="card">
            <h2><i class="ph ph-envelope-simple"></i> Fale com a gente</h2>
            <p>Dúvidas sobre o app, conta, pagamentos ou problemas técnicos? É só escrever:</p>
            <p class="contact-line"><strong>suporte@snrfittech.com</strong></p>
            <p class="muted-sm">Respondemos em até 48 horas úteis.</p>
            <a class="cta" href="mailto:suporte@snrfittech.com?subject=Suporte%20SNR%20FIT">
                <i class="ph ph-paper-plane-tilt"></i> Enviar e-mail
            </a>
        </div>

        <div class="card">
            <h2><i class="ph ph-question"></i> Perguntas frequentes</h2>

            <h3>Como faço login no app?</h3>
            <p>Abra o SNR FIT e informe seu e-mail (ou CNPJ, no caso de academia/studio/loja) e a senha. Não é preciso configurar nenhum servidor — o app conecta automaticamente.</p>

            <h3>Esqueci minha senha</h3>
            <p>Escreva para <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a> com o e-mail da sua conta que ajudamos a redefinir.</p>

            <h3>Como crio uma conta?</h3>
            <p>Na tela inicial, toque em <strong>Criar conta</strong> e escolha o perfil: aluno, personal trainer, academia, studio ou loja. Perfis profissionais passam por aprovação.</p>

            <h3>Dúvidas sobre pagamentos e assinaturas</h3>
            <p>Os pagamentos são processados com segurança pela Asaas. Para cobranças, cancelamentos ou reembolsos, fale com <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.</p>

            <h3>O app não abre ou apresenta um problema</h3>
            <p>Feche e abra o app novamente e verifique sua conexão com a internet. Se persistir, envie para <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a> o modelo do aparelho e a versão do sistema.</p>
        </div>

        <div class="card">
            <h2><i class="ph ph-user-minus"></i> Excluir sua conta e seus dados</h2>
            <p>Você pode excluir sua conta a qualquer momento, direto no app:</p>
            <div class="highlighted">
                <strong>Configurações → Excluir conta</strong> → confirme com sua senha. Seus dados pessoais são anonimizados/apagados.
            </div>
            <p>Prefere que a gente faça por você? Envie o pedido para <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.</p>
        </div>

        <div class="card">
            <h2><i class="ph ph-file-text"></i> Documentos</h2>
            <div class="doc-links">
                <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>
                <a href="{{ route('termos') }}">Termos de Uso</a>
            </div>
        </div>

        <div class="footer">
            <p>SNR FIT — Treine junto. Evolua junto.</p>
            <p style="margin-top: 8px;">Suporte: <a href="mailto:suporte@snrfittech.com" style="color:var(--primary);">suporte@snrfittech.com</a></p>
            <p style="margin-top: 12px;">© {{ date('Y') }} SNR FIT. Todos os direitos reservados.</p>
        </div>
    </div>

</body>

</html>
