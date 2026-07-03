@extends('layouts.SelecaoCadastro')

@section('conteudo')
    <div class="header-text">
        <div class="ed-eyebrow" style="margin-bottom:16px;"><span class="ed-num">01</span> Comece agora</div>
        <h2 class="ed-h">Escolha seu <span>Perfil de Acesso</span></h2>
        <p>Seja qual for o seu lugar no fitness, sua história começa aqui.</p>
    </div>

    <div class="cards-grid">
        <a href="{{ route('cadastro.ir', ['tipo' => 'personal']) }}" class="card-link">
            <div class="card">
                <div class="icon-wrapper">
                    <i class="ph ph-barbell"></i>
                </div>
                <h3>Personal Trainer</h3>
                <p>Crie treinos personalizados, acompanhe a evolução de seus alunos e gerencie sua agenda de consultoria.</p>
                <div class="card-footer">
                    <span class="go-btn">Começar agora <i class="ph ph-arrow-right"></i></span>
                </div>
            </div>
        </a>

        <a href="{{ route('cadastro.ir', ['tipo' => 'cliente']) }}" class="card-link">
            <div class="card">
                <div class="icon-wrapper">
                    <i class="ph ph-person-simple-run"></i>
                </div>
                <h3>Aluno / Atleta</h3>
                <p>Visualize suas fichas de treino, registre suas cargas e acompanhe seu progresso físico detalhadamente.</p>
                <div class="card-footer">
                    <span class="go-btn">Acessar treinos <i class="ph ph-arrow-right"></i></span>
                </div>
            </div>
        </a>

        <a href="{{ route('cadastro.ir', ['tipo' => 'academia']) }}" class="card-link">
            <div class="card">
                <div class="icon-wrapper">
                    <i class="ph ph-buildings"></i>
                </div>
                <h3>Gestor Academia</h3>
                <p>Controle financeiro, gestão de professores, check-in de alunos e relatórios administrativos completos.</p>
                <div class="card-footer">
                    <span class="go-btn">Painel Gestor <i class="ph ph-arrow-right"></i></span>
                </div>
            </div>
        </a>

        <a href="{{ route('cadastro.ir', ['tipo' => 'studio']) }}" class="card-link">
            <div class="card">
                <div class="icon-wrapper">
                    <i class="ph ph-flower-lotus"></i>
                </div>
                <h3>Studio Fitness</h3>
                <p>Gerencie planos, horários com vagas, alunos e receba pagamentos online direto na sua conta.</p>
                <div class="card-footer">
                    <span class="go-btn">Cadastrar studio <i class="ph ph-arrow-right"></i></span>
                </div>
            </div>
        </a>

        <a href="{{ route('cadastro.ir', ['tipo' => 'loja']) }}" class="card-link">
            <div class="card">
                <div class="icon-wrapper">
                    <i class="ph ph-storefront"></i>
                </div>
                <h3>Loja de Suplementos</h3>
                <p>Cadastre seus produtos fitness, defina preços, controle o estoque e venda direto para os alunos da plataforma.</p>
                <div class="card-footer">
                    <span class="go-btn">Cadastrar loja <i class="ph ph-arrow-right"></i></span>
                </div>
            </div>
        </a>
    </div>
@endsection
