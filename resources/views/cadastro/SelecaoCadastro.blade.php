@extends('layouts.SelecaoCadastro')

@section('estilos')
<style>
    @keyframes selFadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .sel-header {
        width: 100%;
        margin: 0 auto 52px;
        text-align: center;
        animation: selFadeUp .5s ease both;
    }

    .sel-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        color: var(--text-dim);
        margin-bottom: 22px;
    }

    .sel-eyebrow .sel-num {
        color: var(--bg-dark);
        background: var(--primary);
        font-family: 'Syncopate', sans-serif;
        font-weight: 700;
        padding: 2px 9px;
        letter-spacing: 1px;
    }

    .sel-header h2 {
        font-family: 'Syncopate', sans-serif;
        font-weight: 700;
        font-size: clamp(1.6rem, 4vw, 2.5rem);
        line-height: 1.08;
        letter-spacing: -0.01em;
        text-transform: uppercase;
        margin: 0;
    }

    .sel-header h2 span {
        background: var(--primary);
        color: var(--bg-dark);
        padding: 0 .1em;
    }

    .sel-header p {
        color: var(--text-dim);
        font-size: 1.08rem;
        line-height: 1.6;
        margin: 20px 0 0;
    }

    /* Grid centralizado: 2 cards por linha, loja ocupa a linha inteira */
    .sel-grid {
        width: 100%;
        max-width: 820px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 22px;
        animation: selFadeUp .5s ease .12s both;
    }

    .sel-card--full {
        grid-column: 1 / -1;
    }

    @media (max-width: 700px) {
        .sel-grid {
            grid-template-columns: 1fr;
        }
        .sel-card--full {
            grid-column: auto;
        }
    }

    .sel-card {
        position: relative;
        min-height: 330px;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        text-decoration: none;
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        transition: transform .3s cubic-bezier(.2, .8, .3, 1), box-shadow .3s, border-color .3s;
    }

    .sel-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .5s cubic-bezier(.2, .8, .3, 1);
    }

    .sel-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(10, 11, 13, .15) 0%, rgba(10, 11, 13, .55) 48%, rgba(10, 11, 13, .96) 100%);
    }

    .sel-icon {
        position: absolute;
        top: 22px;
        left: 22px;
        width: 48px;
        height: 48px;
        border-radius: 13px;
        background: rgba(10, 11, 13, .55);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(212, 255, 0, .4);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: .3s;
    }

    .sel-index {
        position: absolute;
        top: 26px;
        right: 24px;
        font-family: 'Syncopate', sans-serif;
        font-size: 0.72rem;
        color: rgba(255, 255, 255, .5);
    }

    .sel-body {
        position: relative;
        padding: 24px 26px 26px;
    }

    .sel-body h3 {
        font-family: 'Syncopate', sans-serif;
        font-size: 1.02rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 0 0 10px;
    }

    .sel-body p {
        color: #c7ccd4;
        font-size: 0.86rem;
        line-height: 1.55;
        margin: 0 0 18px;
    }

    .sel-go {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .5px;
        color: #fff;
        transition: .3s;
    }

    .sel-go i { transition: transform .3s; }

    /* Hover: elevar card, aproximar imagem, acender ícone e seta */
    .sel-card:hover {
        border-color: rgba(212, 255, 0, .5);
        transform: translateY(-6px);
        box-shadow: 0 24px 60px rgba(0, 0, 0, .6), 0 0 0 1px rgba(212, 255, 0, .15);
    }
    .sel-card:hover .sel-img { transform: scale(1.07); }
    .sel-card:hover .sel-icon {
        background: var(--primary);
        color: var(--bg-dark);
        box-shadow: 0 0 24px rgba(212, 255, 0, .55);
    }
    .sel-card:hover .sel-go { color: var(--primary); gap: 14px; }
    .sel-card:hover .sel-go i { transform: translateX(4px); }
</style>
@endsection

@section('conteudo')
    <div class="sel-header">
        <div class="sel-eyebrow"><span class="sel-num">01</span> Comece agora</div>
        <h2>Escolha seu <span>Perfil de Acesso</span></h2>
        <p>Seja qual for o seu lugar no fitness, sua história começa aqui.</p>
    </div>

    <div class="sel-grid">
        <a href="{{ route('cadastro.ir', ['tipo' => 'personal']) }}" class="sel-card">
            <img class="sel-img" src="{{ asset('img/selecao/personal.jpg') }}" alt="Personal trainer">
            <div class="sel-overlay"></div>
            <div class="sel-icon"><i class="ph-bold ph-barbell"></i></div>
            <div class="sel-index">01</div>
            <div class="sel-body">
                <h3>Profissional da Educação Física</h3>
                <p>Personal trainer ou nutricionista: treinos e planos personalizados, acompanhamento de evolução e gestão da sua agenda.</p>
                <span class="sel-go">Começar agora <i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <a href="{{ route('cadastro.ir', ['tipo' => 'cliente']) }}" class="sel-card">
            <img class="sel-img" src="{{ asset('img/selecao/aluno.jpg') }}" alt="Aluno atleta">
            <div class="sel-overlay"></div>
            <div class="sel-icon"><i class="ph-bold ph-person-simple-run"></i></div>
            <div class="sel-index">02</div>
            <div class="sel-body">
                <h3>Aluno / Atleta</h3>
                <p>Visualize suas fichas de treino, registre suas cargas e acompanhe seu progresso físico detalhadamente.</p>
                <span class="sel-go">Acessar treinos <i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <a href="{{ route('cadastro.ir', ['tipo' => 'academia']) }}" class="sel-card">
            <img class="sel-img" src="{{ asset('img/selecao/academia.jpg') }}" alt="Academia">
            <div class="sel-overlay"></div>
            <div class="sel-icon"><i class="ph-bold ph-buildings"></i></div>
            <div class="sel-index">03</div>
            <div class="sel-body">
                <h3>Gestor Academia</h3>
                <p>Controle financeiro, gestão de professores, check-in de alunos e relatórios administrativos completos.</p>
                <span class="sel-go">Painel Gestor <i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <a href="{{ route('cadastro.ir', ['tipo' => 'studio']) }}" class="sel-card">
            <img class="sel-img" src="{{ asset('img/selecao/studio.jpg') }}" alt="Studio fitness">
            <div class="sel-overlay"></div>
            <div class="sel-icon"><i class="ph-bold ph-flower-lotus"></i></div>
            <div class="sel-index">04</div>
            <div class="sel-body">
                <h3>Studio Fitness</h3>
                <p>Gerencie planos, horários com vagas, alunos e receba pagamentos online direto na sua conta.</p>
                <span class="sel-go">Cadastrar studio <i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>

        <a href="{{ route('cadastro.ir', ['tipo' => 'loja']) }}" class="sel-card sel-card--full">
            <img class="sel-img" src="{{ asset('img/selecao/loja.jpg') }}" alt="Loja de suplementos">
            <div class="sel-overlay"></div>
            <div class="sel-icon"><i class="ph-bold ph-storefront"></i></div>
            <div class="sel-index">05</div>
            <div class="sel-body">
                <h3>Loja de Suplementos</h3>
                <p>Cadastre seus produtos, defina preços, controle o estoque e venda direto para os alunos da plataforma.</p>
                <span class="sel-go">Cadastrar loja <i class="ph-bold ph-arrow-right"></i></span>
            </div>
        </a>
    </div>
@endsection
