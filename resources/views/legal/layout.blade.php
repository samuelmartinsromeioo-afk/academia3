<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('doc_titulo', 'Documento Legal') — SNR FIT</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1117 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-main);
            line-height: 1.6;
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
            padding: 10px 20px; border-radius: 10px; cursor: pointer; transition: 0.3s;
            text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;
        }
        .back-btn:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }

        .header { text-align: center; margin-bottom: 50px; }
        .header h1 { font-size: 2.3rem; margin-bottom: 10px; color: var(--primary); }
        .header p { color: var(--text-muted); font-size: 1rem; }

        .perfil-tag {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 16px;
            background: rgba(212, 255, 0, 0.1); border: 1px solid rgba(212, 255, 0, 0.35);
            color: var(--primary); padding: 6px 14px; border-radius: 999px;
            font-size: 0.8rem; font-weight: 700;
        }

        .content {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 20px; padding: 40px; line-height: 1.8;
        }
        .content h2 {
            color: var(--primary); font-size: 1.4rem; margin-top: 40px; margin-bottom: 18px;
            padding-bottom: 10px; border-bottom: 2px solid rgba(212, 255, 0, 0.2);
        }
        .content h2:first-of-type { margin-top: 0; }
        .content h3 { color: #fff; font-size: 1.1rem; margin-top: 25px; margin-bottom: 12px; }
        .content p { margin-bottom: 15px; color: var(--text-muted); }
        .content ul, .content ol { margin-left: 25px; margin-bottom: 15px; }
        .content li { margin-bottom: 10px; color: var(--text-muted); }
        .content strong { color: var(--primary); }
        .content a { color: var(--primary); }

        .highlighted {
            background: rgba(212, 255, 0, 0.08); border-left: 4px solid var(--primary);
            padding: 15px 20px; border-radius: 8px; margin: 20px 0;
        }
        .warn { border-left-color: #ff5252; background: rgba(255, 82, 82, 0.06); }
        .warn strong { color: #ff8a8a; }

        .toc {
            background: rgba(212, 255, 0, 0.05); border: 1px solid rgba(212, 255, 0, 0.2);
            border-radius: 12px; padding: 25px; margin-bottom: 40px;
        }
        .toc h3 { margin-top: 0; color: var(--primary); margin-bottom: 15px; }
        .toc ul { list-style: none; margin-left: 0; }
        .toc li { margin-bottom: 8px; }
        .toc a { color: var(--primary); text-decoration: none; transition: 0.2s; }
        .toc a:hover { text-decoration: underline; }

        .doc-nav {
            display: flex; flex-wrap: wrap; gap: 10px; margin: 30px 0 0;
        }
        .doc-nav a {
            background: var(--card-bg); border: 1px solid var(--border); color: var(--text-muted);
            padding: 8px 14px; border-radius: 999px; text-decoration: none; font-size: 0.82rem;
        }
        .doc-nav a:hover { border-color: var(--primary); color: var(--primary); }
        .doc-nav a.active { border-color: var(--primary); color: var(--primary); background: rgba(212,255,0,0.08); }

        .footer {
            text-align: center; margin-top: 50px; padding-top: 30px;
            border-top: 1px solid var(--border); color: var(--text-muted); font-size: 0.9rem;
        }

        .print-btn {
            background: var(--primary); color: #000; border: none; padding: 12px 25px;
            border-radius: 10px; cursor: pointer; font-weight: 900; transition: 0.3s;
            display: flex; align-items: center; gap: 8px; font-size: 0.9rem;
        }
        .print-btn:hover { filter: brightness(1.1); transform: translateY(-2px); }

        @media (max-width: 768px) {
            .top-bar { padding: 15px 20px; }
            .header h1 { font-size: 1.6rem; }
            .content { padding: 25px; }
            .container { padding: 20px 15px; }
        }

        @media print {
            .top-bar, .print-btn, .back-btn, .doc-nav { display: none; }
            body { background: #fff; }
            .content { background: transparent; border: none; padding: 0; }
            .header h1, .content h2, .content h3 { color: #000; }
            .content h2 { border-bottom: 2px solid #ccc; }
            .content p, .content li { color: #333; }
        }
    </style>
</head>

<body class="ed-page">

    <div class="top-bar">
        <a href="/" class="logo"><i class="ph ph-barbell"></i> SNR FIT</a>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button class="print-btn" onclick="window.print()">
                <i class="ph ph-printer"></i> Imprimir
            </button>
            <a href="/" class="back-btn"><i class="ph ph-caret-left"></i> Voltar</a>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="ph ph-file-text"></i> @yield('doc_titulo', 'Documento Legal')</h1>
            <p>@yield('doc_subtitulo', 'Leia com atenção antes de usar a plataforma')</p>
            @hasSection('doc_perfil')
            <div class="perfil-tag"><i class="ph ph-user-circle"></i> @yield('doc_perfil')</div>
            @endif
            <p style="margin-top: 12px; font-size: 0.85rem; color: rgba(255,255,255,0.5);">
                Última atualização: {{ date('d/m/Y') }} · Versão @yield('doc_versao', '1.0')
            </p>

            <div class="doc-nav">
                <a href="{{ route('termos') }}" class="@yield('nav_index')">Visão geral</a>
                <a href="{{ route('termos.aluno') }}" class="@yield('nav_aluno')">Aluno</a>
                <a href="{{ route('termos.personal') }}" class="@yield('nav_personal')">Personal</a>
                <a href="{{ route('termos.academia') }}" class="@yield('nav_academia')">Academia</a>
                <a href="{{ route('termos.studio') }}" class="@yield('nav_studio')">Studio</a>
                <a href="{{ route('termos.loja') }}" class="@yield('nav_loja')">Loja</a>
            </div>
        </div>

        <div class="content">
            @yield('doc_conteudo')
        </div>

        <div class="footer">
            <p><strong>Última atualização:</strong> {{ date('d/m/Y') }} · <strong>Versão:</strong> @yield('doc_versao', '1.0')</p>
            <p style="margin-top: 10px;">Contato: <a href="mailto:suporte@snrfittech.com" style="color:var(--primary);">suporte@snrfittech.com</a></p>
            <p style="margin-top: 16px;">© {{ date('Y') }} SNR FIT. Todos os direitos reservados.</p>
        </div>
    </div>

</body>

</html>
