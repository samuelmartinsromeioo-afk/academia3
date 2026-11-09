<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('doc_titulo', 'Documento Legal') — SNR FIT</title>
    <link rel="icon" type="image/png" href="{{ asset('icone-snrfit.png') }}">
    @include('partials.pwa')
    @include('partials.brand-head')
    <style>
        :root {
            --primary: #7cff00;
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
            background: rgba(124, 255, 0, 0.1); border: 1px solid rgba(124, 255, 0, 0.35);
            color: var(--primary); padding: 6px 14px; border-radius: 999px;
            font-size: 0.8rem; font-weight: 700;
        }

        .content {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 20px; padding: 40px; line-height: 1.8;
        }
        .content h2 {
            color: var(--primary); font-size: 1.4rem; margin-top: 40px; margin-bottom: 18px;
            padding-bottom: 10px; border-bottom: 2px solid rgba(124, 255, 0, 0.2);
        }
        .content h2:first-of-type { margin-top: 0; }
        .content h3 { color: #fff; font-size: 1.1rem; margin-top: 25px; margin-bottom: 12px; }
        .content p { margin-bottom: 15px; color: var(--text-muted); }
        .content ul, .content ol { margin-left: 25px; margin-bottom: 15px; }
        .content li { margin-bottom: 10px; color: var(--text-muted); }
        .content strong { color: var(--primary); }
        .content a { color: var(--primary); }

        .highlighted {
            background: rgba(124, 255, 0, 0.08); border-left: 4px solid var(--primary);
            padding: 15px 20px; border-radius: 8px; margin: 20px 0;
        }
        .warn { border-left-color: #ff5252; background: rgba(255, 82, 82, 0.06); }
        .warn strong { color: #ff8a8a; }

        /* Nota de fundamentação legal: subordinada ao texto da cláusula, porque
           é a citação da norma, não a regra contratada. */
        .content p.muted, .content .muted { font-size: 0.82rem; opacity: 0.75; }
        .content p.muted strong { color: inherit; }

        .toc {
            background: rgba(124, 255, 0, 0.05); border: 1px solid rgba(124, 255, 0, 0.2);
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
        .doc-nav a.active { border-color: var(--primary); color: var(--primary); background: rgba(124,255,0,0.08); }

        /* Atribuição de responsabilidade financeira, no topo do documento.
           Caixa neutra (não verde-marca) de propósito: é identificação de
           TERCEIRO, e pintá-la com a cor da SNR FIT sugeriria o contrário do
           que a frase diz. */
        .selo-responsavel {
            display: flex; flex-direction: column; align-items: center; gap: 10px;
            margin: 22px auto 0; max-width: 560px; padding: 16px 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border); border-radius: 14px;
        }
        .selo-responsavel p {
            margin: 0; font-size: 0.78rem; line-height: 1.5;
            text-align: center; color: var(--text-muted);
        }
        .selo-responsavel strong { color: rgba(255, 255, 255, 0.85); }

        .footer {
            text-align: center; margin-top: 50px; padding-top: 30px;
            border-top: 1px solid var(--border); color: var(--text-muted); font-size: 0.9rem;
        }

        /* Tabelas dos documentos (ex.: a de procedimentos da cláusula 12.1).
           Mesmo desenho da Política, que já tinha o seu. O `.tbl-wrap` com
           overflow é obrigatório: sem ele a tabela de quatro colunas estoura a
           largura no celular e empurra a página inteira para o lado. */
        .tbl-wrap { overflow-x: auto; margin: 18px 0; }
        .content table { width: 100%; border-collapse: collapse; font-size: 0.86rem; min-width: 680px; }
        .content th, .content td {
            border: 1px solid var(--border); padding: 9px 11px;
            text-align: left; vertical-align: top;
        }
        .content th { background: rgba(124, 255, 0, 0.07); color: var(--primary); font-weight: 700; }
        .content td strong { color: var(--text-main); }

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

        /* Marca d'água — a mesma dos outros documentos imprimíveis.
           `position: fixed` faz repetir em toda página impressa. */
        .doc-marca { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; z-index: 0; }
        .doc-marca img { width: 58%; max-width: 440px; opacity: 0.06; transform: rotate(-24deg); }
        .top-bar, .container { position: relative; z-index: 1; }

        @media print {
            .top-bar, .print-btn, .back-btn, .doc-nav { display: none; }

            /* Folha branca, como nos outros documentos imprimíveis. A marca
               d'água é fundo: sem o color-adjust o navegador a descarta. */
            .doc-marca img {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            html, body { background: #fff; }
            .content { background: transparent; border: none; padding: 0; }
            .header h1, .content h2, .content h3 { color: #000; }
            .content h2 { border-bottom: 2px solid #ccc; }
            .content p, .content li, .content td, .content th { color: #333; }
            /* Impresso, não há como rolar: a tabela tem de caber na folha. */
            .tbl-wrap { overflow: visible; }
            .content table { min-width: 0; font-size: 0.74rem; }
            .content th { background: #f0f0f0; color: #000; }

            /* A atribuição de responsabilidade financeira SAI NO PAPEL: é
               cláusula, não enfeite de tela. Só troca as cores para a folha
               branca — o selo positivo quem liga é `partials/selo-asaas`. */
            .selo-responsavel { background: transparent; border: 1px solid #ccc; }
            .selo-responsavel p { color: #333; }
            .selo-responsavel strong { color: #000; }

            /* O selo do rodapé não se repete na folha: o do topo já identifica
               o processador, e dois selos na mesma página impressa é ruído. */
            .footer .selo-asaas-rodape { display: none; }
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

    <div class="doc-marca" aria-hidden="true">
        <img src="{{ asset('SnrFit.png') }}" alt="">
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="ph ph-file-text"></i> @yield('doc_titulo', 'Documento Legal')</h1>
            <p>@yield('doc_subtitulo', 'Leia com atenção antes de usar a plataforma')</p>
            @hasSection('doc_perfil')
            <div class="perfil-tag"><i class="ph ph-user-circle"></i> @yield('doc_perfil')</div>
            @endif
            {{--
                A data vem de `config('termos.vigente_desde')`, NUNCA de date('d/m/Y').
                Com a data de hoje, o documento impresso afirmava ter sido atualizado
                no dia em que foi impresso — num contrato, uma afirmação falsa sobre a
                própria vigência, e justamente a que importa quando se discute qual
                versão a pessoa aceitou.
            --}}
            <p style="margin-top: 12px; font-size: 0.85rem; color: rgba(255,255,255,0.5);">
                Em vigor desde {{ \Carbon\Carbon::parse(config('termos.vigente_desde'))->format('d/m/Y') }}
                · Versão @yield('doc_versao', '1.0')
            </p>

            {{--
                Quem responde pelos serviços financeiros, dito ANTES do corpo do
                documento. Em todos os seis documentos legais e na própria tela
                de aceite, porque o layout é compartilhado.

                Fica aqui, e não só no checkout, por uma razão de contrato: o
                CDC (art. 4º, III e art. 6º, III) dá ao consumidor o direito de
                saber com quem contrata, e a liquidação do dinheiro não é feita
                por nós — quem a faz tem de estar identificado no documento, não
                apenas na tela de pagar. O detalhamento (razão social, CNPJ,
                responsabilidades) está na seção 0.1 dos Termos; aqui é só a
                atribuição, em destaque.

                `imprimivel` é obrigatório: esta página tem botão "Imprimir" e
                vira folha BRANCA no papel, onde o selo negativo-branco ficaria
                invisível.
            --}}
            <div class="selo-responsavel">
                @include('partials.selo-asaas', ['altura' => 34, 'imprimivel' => true])
                <p>
                    Serviços financeiros e liquidação dos pagamentos por
                    <strong>{{ \App\Support\Empresa::get('psp_razao_social') }}</strong>,
                    instituição autorizada e regulada pelo <strong>Banco Central do Brasil</strong>.
                    A {{ \App\Support\Empresa::get('marca') }} <strong>não é instituição de
                        pagamento</strong> e não detém, em nome próprio, os valores pagos.
                    {{--
                        A remissão tem de ser um LINK para as condições gerais, não
                        "cláusula 0.1". Este layout serve os SEIS documentos, e nos
                        cinco de perfil a numeração é a deles — "cláusula 0.1" ali
                        aponta para uma cláusula que não existe no documento que a
                        pessoa está lendo.
                    --}}
                    <a href="{{ route('termos') }}#s0-1">Quem responde pelo quê</a>.
                </p>
            </div>

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
            <p><strong>Em vigor desde:</strong> {{ \Carbon\Carbon::parse(config('termos.vigente_desde'))->format('d/m/Y') }}
                · <strong>Versão:</strong> @yield('doc_versao', '1.0')</p>
            <p style="margin-top: 10px;">
                {{ \App\Support\Empresa::get('razao_social') }} · CNPJ {{ \App\Support\Empresa::get('cnpj') }}<br>
                Contato: <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}" style="color:var(--primary);">{{ \App\Support\Empresa::get('email_suporte') }}</a>
            </p>

            {{-- Escondido na impressão (ver @media print): o selo do topo já
                 identifica o processador na folha. --}}
            <div class="selo-asaas-rodape" style="margin-top: 18px;">
                @include('partials.selo-asaas', ['altura' => 30, 'imprimivel' => false])
            </div>
            {{--
                Rever a escolha de cookies de dentro do documento que a explica.
                Antes a Política instruía a "limpar os cookies do navegador",
                que é um procedimento do navegador e não da Plataforma — e
                deixava o titular sem caminho para revogar pelo mesmo meio em
                que consentiu. O botão só aparece quando há banner configurado,
                porque `banner-cookies` não define nada sem Pixel.
            --}}
            <p style="margin-top: 16px;">
                <button type="button" onclick="window.snrfitCookies && window.snrfitCookies.abrir()"
                    style="background:transparent;border:1px solid rgba(255,255,255,0.22);color:inherit;
                           border-radius:999px;padding:7px 14px;font-size:0.8rem;cursor:pointer;font-family:inherit;">
                    <i class="ph ph-cookie"></i> Preferências de cookies
                </button>
            </p>

            <p style="margin-top: 16px;">© {{ date('Y') }} {{ \App\Support\Empresa::get('marca') }}. Todos os direitos reservados.</p>
        </div>
    </div>

    {{-- Só a gestão da ESCOLHA: este partial nunca carrega o Pixel, e é por
         isso que pode entrar numa página legal. --}}
    @include('partials.banner-cookies')

</body>

</html>
