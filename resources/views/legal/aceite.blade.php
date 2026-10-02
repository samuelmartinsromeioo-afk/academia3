{{--
    Tela de reaceite dos Termos de Uso.

    Standalone de propósito: serve os cinco perfis e nenhum layout de papel
    serviria para todos (ver CLAUDE.md > Layouts are NOT interchangeable).

    O resumo vem de config('termos.resumo') e é impresso com {!! !!} para o
    <strong> sobreviver. É copy nossa, de arquivo de configuração — nada vindo de
    formulário pode ser interpolado ali.
--}}
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizamos os Termos de Uso | SNR FIT</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #7cff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #fff;
            --text-dim: #9ca3af;
            --border: rgba(255,255,255,.08);
            --danger: #ff6b6b;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: var(--bg-dark);
            background-image:
                radial-gradient(circle at 15% 10%, rgba(124,255,0,.07) 0%, transparent 24%),
                radial-gradient(circle at 85% 90%, rgba(124,255,0,.05) 0%, transparent 24%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            padding: 32px 20px 60px;
            line-height: 1.6;
        }
        .wrap { max-width: 720px; margin: 0 auto; }

        .logo {
            font-family:'Syncopate',sans-serif; font-size:1.4rem; letter-spacing:5px;
            color:var(--primary); text-align:center; display:block; margin-bottom:28px;
        }

        .selo {
            display:inline-flex; align-items:center; gap:8px; margin-bottom:16px;
            background:rgba(124,255,0,.1); border:1px solid rgba(124,255,0,.35);
            color:var(--primary); padding:6px 14px; border-radius:999px;
            font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;
        }

        h1 {
            font-family:'Syncopate',sans-serif; font-size:clamp(1.2rem,3.6vw,1.75rem);
            text-transform:uppercase; margin-bottom:12px; line-height:1.3;
        }
        .sub { color:var(--text-dim); font-size:.95rem; margin-bottom:28px; }

        .card {
            background:var(--card-bg); border:1px solid var(--border);
            border-radius:20px; padding:28px; margin-bottom:20px;
        }
        .titulo-secao {
            font-size:.72rem; text-transform:uppercase; letter-spacing:2px;
            color:var(--text-dim); margin-bottom:18px;
        }

        .mudancas { list-style:none; }
        .mudancas li {
            position:relative; padding-left:28px; margin-bottom:14px;
            font-size:.92rem; color:var(--text-dim);
        }
        .mudancas li::before {
            content:'\2713'; position:absolute; left:0; top:0;
            color:var(--primary); font-weight:700;
        }
        .mudancas strong { color:var(--text-main); }

        .leia {
            display:flex; gap:12px; flex-wrap:wrap; margin-top:4px;
        }
        .link-doc {
            display:inline-flex; align-items:center; gap:8px; text-decoration:none;
            color:var(--text-main); border:1px solid var(--border); border-radius:11px;
            padding:11px 16px; font-size:.85rem; transition:.25s;
        }
        .link-doc:hover { border-color:rgba(124,255,0,.45); color:var(--primary); }

        .aceite-box {
            display:flex; gap:13px; align-items:flex-start;
            background:rgba(124,255,0,.05); border:1px solid rgba(124,255,0,.25);
            border-radius:14px; padding:18px; margin-bottom:20px; cursor:pointer;
        }
        .aceite-box input { width:20px; height:20px; accent-color:var(--primary); flex-shrink:0; margin-top:1px; cursor:pointer; }
        .aceite-box span { font-size:.9rem; }
        .aceite-box a { color:var(--primary); }

        .btn {
            display:inline-flex; align-items:center; justify-content:center; gap:9px;
            cursor:pointer; border:none; border-radius:12px; padding:14px 24px;
            font-size:.92rem; font-weight:700; font-family:inherit;
            text-decoration:none; transition:.25s; width:100%;
        }
        .btn-primary { background:var(--primary); color:#000; }
        .btn-primary:hover { background:#6bde00; }

        .alerta {
            padding:13px 18px; border-radius:12px; margin-bottom:20px; font-size:.9rem;
            background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.4); color:var(--danger);
        }

        .rodape {
            text-align:center; margin-top:22px; font-size:.82rem; color:var(--text-dim);
        }
        .rodape form { display:inline; }
        .rodape button {
            background:none; border:none; color:var(--text-dim); cursor:pointer;
            font-family:inherit; font-size:.82rem; text-decoration:underline; padding:0;
        }
        .rodape button:hover { color:var(--primary); }
        .rodape a { color:var(--text-dim); }

        .nota-versao { font-size:.76rem; color:var(--text-dim); margin-top:16px; }

        @media (max-width:560px) { .card { padding:20px; } }
    </style>
</head>
<body>
<div class="wrap">
    <span class="logo">SNR</span>

    @if ($errors->any())
        <div class="alerta">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <span class="selo"><i class="ph-bold ph-file-text"></i> Versão {{ $versao }}</span>
        <h1>Atualizamos nossos Termos de Uso</h1>
        <p class="sub">
            Olá, {{ strtok(trim($usuario->nome ?? 'tudo bem'), ' ') }}. Antes de continuar,
            precisamos do seu aceite na nova versão. Leva um minuto.
        </p>

        <div class="titulo-secao">O que mudou</div>
        <ul class="mudancas">
            @foreach ($resumo as $item)
                {{-- Copy de config/termos.php; nada de formulário entra aqui. --}}
                <li>{!! $item !!}</li>
            @endforeach
        </ul>

        <div class="titulo-secao" style="margin-top:26px;">Leia os documentos</div>
        <div class="leia">
            <a class="link-doc" href="{{ $linkTermos }}" target="_blank" rel="noopener">
                <i class="ph-bold ph-scroll"></i> Termos de Uso
            </a>
            @if ($linkPerfil)
                <a class="link-doc" href="{{ $linkPerfil }}" target="_blank" rel="noopener">
                    <i class="ph-bold ph-user-circle"></i> Termos de {{ $perfilLabel }}
                </a>
            @endif
            <a class="link-doc" href="{{ route('lgpd.politica') }}" target="_blank" rel="noopener">
                <i class="ph-bold ph-lock-key"></i> Privacidade
            </a>
        </div>

        <p class="nota-versao">
            @if ($versaoAnterior)
                Você aceitou anteriormente a versão {{ $versaoAnterior }}.
            @endif
            Registramos a data, o horário e o seu IP no momento do aceite.
        </p>
    </div>

    <form method="POST" action="{{ route('termos.aceite.registrar') }}">
        @csrf
        <label class="aceite-box" for="aceito">
            <input type="checkbox" name="aceito" id="aceito" value="1" required>
            <span>
                Li e concordo com os <a href="{{ $linkTermos }}" target="_blank" rel="noopener">Termos de Uso</a>
                @if ($linkPerfil)
                    e com os <a href="{{ $linkPerfil }}" target="_blank" rel="noopener">Termos de {{ $perfilLabel }}</a>
                @endif
                na versão {{ $versao }}, e com a
                <a href="{{ route('lgpd.politica') }}" target="_blank" rel="noopener">Política de Privacidade</a>.
            </span>
        </label>

        <button type="submit" class="btn btn-primary">
            <i class="ph-bold ph-check-circle"></i> Aceitar e continuar
        </button>
    </form>

    <div class="rodape">
        Não concorda? Você pode
        {{-- Saída sempre disponível: ninguém fica preso nesta tela. --}}
        <form method="POST" action="{{ route('login.logout') }}">
            @csrf
            <button type="submit">sair da sua conta</button>
        </form>
        ou falar com a gente em
        <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.
    </div>
</div>
</body>
</html>
