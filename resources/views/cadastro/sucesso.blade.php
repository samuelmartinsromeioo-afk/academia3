<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro recebido — SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.brand-head')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 28px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #fff;
        }
        .card {
            position: relative; width: 100%; max-width: 600px; text-align: center;
            background: linear-gradient(180deg, #16181d, #0a0b0d);
            border: 1px solid rgba(212,255,0,0.22);
            border-radius: 26px; padding: 52px 40px 40px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.55);
        }
        .selo {
            width: 96px; height: 96px; margin: 0 auto 26px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: radial-gradient(circle at 34% 28%, rgba(255,255,255,0.35), transparent 55%), #d4ff00;
            box-shadow: 0 0 0 7px rgba(212,255,0,0.08), 0 0 40px rgba(212,255,0,0.35);
            animation: selo .6s cubic-bezier(.2,1.2,.3,1.4) both;
        }
        @keyframes selo { 0% { transform: scale(0) rotate(-20deg); } 100% { transform: scale(1) rotate(0); } }
        .selo i { font-size: 2.8rem; color: #0a0b0d; }

        .eyebrow {
            font-size: 0.66rem; font-weight: 800; letter-spacing: 3px; text-transform: uppercase;
            color: #d4ff00; margin-bottom: 12px;
        }
        h1 {
            font-family: 'Syncopate', sans-serif; font-weight: 700; text-transform: uppercase;
            font-size: clamp(1.3rem, 4vw, 1.9rem); line-height: 1.2; letter-spacing: -0.5px; margin-bottom: 22px;
        }
        h1 .mark { background: #d4ff00; color: #0a0b0d; padding: 0 0.1em; }
        p { color: #cfd3da; font-size: 1.02rem; line-height: 1.7; margin-bottom: 18px; text-align: left; }
        p:last-of-type { margin-bottom: 30px; }
        p strong { color: #fff; font-weight: 600; }

        .destaques {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 0 0 30px; text-align: center;
        }
        .destaques .item {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px; padding: 16px 10px;
        }
        .destaques .item i { color: #d4ff00; font-size: 1.5rem; display: block; margin-bottom: 8px; }
        .destaques .item span { font-size: 0.74rem; color: #cfd3da; font-weight: 600; line-height: 1.3; display: block; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: #d4ff00; color: #0a0b0d; text-decoration: none;
            padding: 15px 30px; border-radius: 14px; font-weight: 800; font-size: 0.95rem;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(212,255,0,0.25); }

        .assinatura { margin-top: 26px; font-size: 0.7rem; color: rgba(255,255,255,0.45); }
        .assinatura .marca { font-family: 'Syncopate', sans-serif; font-weight: 700; letter-spacing: 2px; color: rgba(255,255,255,0.7); }
        .assinatura .marca b { color: #d4ff00; }

        @media (max-width: 560px) { .destaques { grid-template-columns: 1fr; } p { text-align: center; } }
    </style>
</head>
<body class="ed-page">
    @php $tipo = session('cad_tipo', 'academia'); @endphp
    <div class="card">
        <div class="selo"><i class="ph-fill ph-check"></i></div>
        <div class="eyebrow">Cadastro recebido</div>

        @if($tipo === 'academia')
            <h1>Obrigado por crescer <span class="mark">com a gente</span></h1>

            <p>
                Obrigado por escolher a <strong>SnrFit</strong> para fazer parte da trajetória da sua academia.
                É uma honra ter o seu negócio conosco — e o nosso compromisso é <strong>crescer junto com você</strong>.
            </p>
            <p>
                Seu cadastro foi enviado para análise da nossa equipe. Em breve <strong>entraremos em contato</strong>
                para alinhar um <strong>valor mensal sob medida</strong>, garantindo que a sua academia aproveite
                tudo o que a plataforma oferece — gestão completa, divulgação do seu espaço e visibilidade para novos alunos.
            </p>
            <p>
                Assim que o seu acesso for aprovado, você será avisado e poderá entrar normalmente.
                Seja muito bem-vindo à SnrFit. <strong>Vamos longe juntos.</strong>
            </p>

            <div class="destaques">
                <div class="item"><i class="ph ph-gauge"></i><span>Gestão completa num só painel</span></div>
                <div class="item"><i class="ph ph-megaphone"></i><span>Divulgação da sua academia</span></div>
                <div class="item"><i class="ph ph-users-three"></i><span>Visibilidade para novos alunos</span></div>
            </div>
        @else
            <h1>Cadastro <span class="mark">enviado</span></h1>
            <p style="text-align:center;">
                Recebemos o seu cadastro com sucesso. Nossa equipe vai analisar os seus dados e,
                assim que for aprovado, você poderá acessar a plataforma normalmente. <strong>Bora pra cima!</strong>
            </p>
        @endif

        <a href="{{ route('login.index') }}" class="btn">
            <i class="ph ph-arrow-left"></i> Voltar ao início
        </a>

        <div class="assinatura">
            <span class="marca">SNR<b>·</b>FIT</span> — Treino que vira história.
        </div>
    </div>
</body>
</html>
