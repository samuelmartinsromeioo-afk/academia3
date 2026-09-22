<?php

/*
|--------------------------------------------------------------------------
| Textos de UI / produto (editáveis)
|--------------------------------------------------------------------------
| Centraliza rótulos e microcopy do cadastro de profissional e do módulo de
| nutrição, para não deixar strings de marketing/produto hardcoded no meio da
| lógica. Edite aqui e rode `php artisan config:clear` para aplicar.
|
| Acesse com config('textos.profissional.diferenciais.confiabilidade'), etc.
*/

return [

    'profissional' => [

        // Passo 1 do cadastro: escolha do tipo de profissional.
        'seletor_titulo' => 'Que tipo de profissional é você?',
        'seletor_ajuda' => 'Isso define os campos e ferramentas do seu cadastro.',

        'tipos' => [
            'PERSONAL_TRAINER' => [
                'label' => 'Personal Trainer',
                'descricao' => 'Treinos, agenda de consultoria e evolução dos alunos.',
                'form_titulo' => 'Dados do Personal Trainer',
                'conselho' => 'CREF',
                'conselho_placeholder' => 'Ex: 123456-G/SP',
            ],
            'NUTRITIONIST' => [
                'label' => 'Nutricionista',
                'descricao' => 'Planos alimentares, antropometria e acompanhamento clínico.',
                'form_titulo' => 'Dados do Nutricionista',
                'conselho' => 'CRN',
                'conselho_placeholder' => 'Ex: 12345/3',
            ],
        ],

        // Especialidades por tipo (multi-seleção).
        'especialidades' => [
            'NUTRITIONIST' => [
                'Nutrição Clínica',
                'Nutrição Esportiva',
                'Emagrecimento',
                'Nutrição Materno-Infantil',
                'Nutrição Comportamental',
                'Nutrição Vegetariana/Vegana',
                'Nutrição Funcional',
                'Nutrição Estética',
                'Transtornos Alimentares',
                'Nutrição Oncológica',
            ],
            'PERSONAL_TRAINER' => [
                'Musculação',
                'Emagrecimento',
                'Hipertrofia',
                'Treinamento Funcional',
                'Condicionamento Físico',
                'Reabilitação',
                'Terceira Idade',
                'Preparação Esportiva',
            ],
        ],

        'modalidades' => ['Presencial', 'Online', 'Híbrido'],

        // Mensagem de erro do CRN (formato + região 1–11).
        'crn_erro' => 'Informe um CRN válido, incluindo a região — ex.: 12345/3',

        // Microcopy de diferencial (dores dos concorrentes). Tom sutil.
        'diferenciais' => [
            'confiabilidade' => 'Seus planos alimentares ficam salvos com histórico de versões. Nada se perde.',
            'portabilidade' => 'Você pode exportar seus dados e planos a qualquer momento. Sem amarras.',
            'escuta' => 'Aqui você sugere e acompanha o que estamos construindo. Nosso roadmap é público e feito com nutricionistas.',
            'transparencia' => 'Cancele quando quiser, sem letra miúda. Você avisa e pronto.',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Tela de "cadastro recebido" (resources/views/cadastro/sucesso.blade.php)
    |----------------------------------------------------------------------
    | Mensagem de boas-vindas de quem acabou de se cadastrar e precisa esperar
    | a aprovação do administrador. A chave é o `cad_tipo` que o controller
    | manda no redirect; `padrao` cobre quem cair aqui sem tipo (ex.: F5 na
    | página, que perde o flash da sessão).
    |
    | Os parágrafos aceitam <strong> e são impressos com {!! !!} — é copy de
    | config, escrita por nós, nunca entrada de usuário. Não jogue dado de
    | formulário aqui dentro.
    */
    'boas_vindas' => [

        'padrao' => [
            'eyebrow' => 'Cadastro recebido',
            'titulo_inicio' => 'Agora você é',
            'titulo_destaque' => 'da casa',
            'paragrafos' => [
                'Obrigado por escolher a <strong>SnrFit</strong>. Cada cadastro que chega aqui é alguém apostando na gente — e a gente aposta de volta, com o mesmo tamanho.',
                'Seu cadastro está <strong>em análise</strong>. A gente olha um por um, com calma, porque quem entra aqui passa a representar a plataforma inteira. Costuma ser rápido; se demorar um pouco, é porque estamos olhando com atenção.',
                'Assim que o acesso for liberado, <strong>a gente te avisa</strong> e já entra com tudo pronto. Até lá, guarda essa: o passo mais difícil, o de começar, você já deu. <strong>A partir de agora, você é da família SnrFit.</strong>',
            ],
            'destaques' => [
                ['icone' => 'ph-gauge', 'texto' => 'Tudo num painel só'],
                ['icone' => 'ph-megaphone', 'texto' => 'Visibilidade de verdade'],
                ['icone' => 'ph-users-three', 'texto' => 'Uma comunidade que treina junto'],
            ],
        ],

        'academia' => [
            'eyebrow' => 'Cadastro recebido',
            'titulo_inicio' => 'Obrigado por crescer',
            'titulo_destaque' => 'com a gente',
            'paragrafos' => [
                'Obrigado por escolher a <strong>SnrFit</strong> para fazer parte da história da sua academia. Ter o seu nome aqui é uma honra — e o nosso compromisso é simples: <strong>a gente só cresce quando você cresce</strong>.',
                'Seu cadastro está <strong>em análise</strong>. Conferimos uma academia por vez, com calma, porque quem entra aqui passa a representar a plataforma inteira — é isso que faz o aluno confiar no que encontra do outro lado. Costuma ser rápido; se demorar um pouco, é porque estamos olhando com atenção.',
                'Nada de contrato, fidelidade ou mensalidade para usar a plataforma. Assim que o acesso for liberado, <strong>a gente te avisa</strong> e já encontra tudo pronto: gestão completa no painel, a sua academia no mapa e alunos novos conhecendo o seu espaço.',
                'Seja muito bem-vinda à família SnrFit. <strong>Vamos longe juntos.</strong>',
            ],
            'destaques' => [
                ['icone' => 'ph-gauge', 'texto' => 'Gestão completa num só painel'],
                ['icone' => 'ph-megaphone', 'texto' => 'Divulgação da sua academia'],
                ['icone' => 'ph-users-three', 'texto' => 'Visibilidade para novos alunos'],
            ],
        ],

        'studio' => [
            'eyebrow' => 'Cadastro recebido',
            'titulo_inicio' => 'Bora encher',
            'titulo_destaque' => 'essas turmas',
            'paragrafos' => [
                'Obrigado por escolher a <strong>SnrFit</strong> para abrir as portas do seu studio para mais gente. Turma cheia, aluno fiel, horário que encaixa — a gente sabe o que sustenta esse tipo de negócio, e é exatamente aí que viemos ajudar.',
                'Seu cadastro está <strong>em análise</strong>. Conferimos um studio por vez, com calma, porque quem entra aqui passa a representar a plataforma inteira — é isso que faz o aluno confiar no que encontra do outro lado. Costuma ser rápido; se demorar um pouco, é porque estamos olhando com atenção.',
                'Nada de contrato, fidelidade ou mensalidade para usar a plataforma. Assim que o acesso for liberado, <strong>a gente te avisa</strong> e já encontra tudo pronto: agenda das turmas, controle de vagas e o seu studio aparecendo para quem procura aula perto de casa.',
                'Seja muito bem-vindo à família SnrFit. <strong>Vamos longe juntos.</strong>',
            ],
            'destaques' => [
                ['icone' => 'ph-calendar-check', 'texto' => 'Agenda e turmas sob controle'],
                ['icone' => 'ph-map-pin', 'texto' => 'Seu studio no mapa da cidade'],
                ['icone' => 'ph-users-three', 'texto' => 'Alunos novos batendo na porta'],
            ],
        ],

        'loja' => [
            'eyebrow' => 'Cadastro recebido',
            'titulo_inicio' => 'Sua loja na frente',
            'titulo_destaque' => 'de quem treina',
            'paragrafos' => [
                'Obrigado por escolher a <strong>SnrFit</strong> para colocar a sua loja na frente de quem treina. Aqui o seu produto não disputa atenção com o mundo inteiro: ele aparece para quem já está no ritmo e sabe o que procura.',
                'Seu cadastro está <strong>em análise</strong>. Conferimos uma loja por vez, com calma, porque quem entra aqui passa a representar a plataforma inteira — é isso que faz o cliente confiar no que encontra do outro lado. Costuma ser rápido; se demorar um pouco, é porque estamos olhando com atenção.',
                'Nada de contrato, fidelidade ou mensalidade para usar a plataforma. Assim que o acesso for liberado, <strong>a gente te avisa</strong> e já encontra tudo pronto: vitrine dentro do app, estoque e pedidos no painel.',
                'Seja muito bem-vinda à família SnrFit. <strong>Bora vender.</strong>',
            ],
            'destaques' => [
                ['icone' => 'ph-storefront', 'texto' => 'Sua vitrine dentro do app'],
                ['icone' => 'ph-package', 'texto' => 'Estoque e pedidos num painel só'],
                ['icone' => 'ph-users-three', 'texto' => 'Público que já treina e compra'],
            ],
        ],

        'personal' => [
            'eyebrow' => 'Cadastro recebido',
            'titulo_inicio' => 'Você acabou de entrar',
            'titulo_destaque' => 'pro time',
            'paragrafos' => [
                'Obrigado por escolher a <strong>SnrFit</strong> para construir a sua carreira. Todo dia tem gente procurando alguém para puxar o treino, cobrar presença e provar que dá certo. <strong>Esse alguém agora pode ser você.</strong>',
                'Seu cadastro está <strong>em análise</strong>: conferimos o CREF e os dados de cada profissional, um por um. Leva um tempinho — e é justamente essa porta que faz o aluno confiar em quem está do outro lado. Costuma ser rápido; se demorar um pouco, é porque estamos olhando com atenção.',
                'Assim que o acesso for liberado, <strong>a gente te avisa</strong> e já encontra tudo pronto: agenda, alunos, pacotes e recebimentos no mesmo lugar. Sem contrato e sem mensalidade para usar a plataforma.',
                'A partir de agora você é da família SnrFit. <strong>Aqui ninguém treina sozinho.</strong>',
            ],
            'destaques' => [
                ['icone' => 'ph-calendar-check', 'texto' => 'Agenda e alunos num painel só'],
                ['icone' => 'ph-magnifying-glass', 'texto' => 'Seu perfil visível para quem procura'],
                ['icone' => 'ph-wallet', 'texto' => 'Pacotes e recebimentos organizados'],
            ],
        ],

        'nutricionista' => [
            'eyebrow' => 'Cadastro recebido',
            'titulo_inicio' => 'Você acabou de entrar',
            'titulo_destaque' => 'pro time',
            'paragrafos' => [
                'Obrigado por escolher a <strong>SnrFit</strong> para cuidar dos seus pacientes. A gente sabe o tamanho do que você carrega em cada consulta — e o nosso trabalho é te dar ferramenta à altura, para o seu tempo ir para as pessoas e não para a planilha.',
                'Seu cadastro está <strong>em análise</strong>: conferimos o CRN e os dados de cada profissional, um por um. Leva um tempinho — e é justamente essa porta que faz o paciente confiar em quem está do outro lado. Costuma ser rápido; se demorar um pouco, é porque estamos olhando com atenção.',
                'Assim que o acesso for liberado, <strong>a gente te avisa</strong> e já encontra tudo pronto: planos alimentares, antropometria, agenda e o portal do paciente. Sem contrato e sem mensalidade para usar a plataforma.',
                'A partir de agora você é da família SnrFit. <strong>Vamos cuidar de muita gente juntos.</strong>',
            ],
            'destaques' => [
                ['icone' => 'ph-fork-knife', 'texto' => 'Planos alimentares com histórico'],
                ['icone' => 'ph-device-mobile', 'texto' => 'Portal do paciente sem login'],
                ['icone' => 'ph-chart-line-up', 'texto' => 'Agenda, cobranças e evolução'],
            ],
        ],
    ],

    'nutri' => [
        'painel_titulo' => 'Painel do Nutricionista',
        'objetivos' => [
            'Emagrecimento',
            'Ganho de massa muscular',
            'Manutenção de peso',
            'Reeducação alimentar',
            'Performance esportiva',
            'Controle de doença (diabetes, hipertensão, etc.)',
            'Saúde materno-infantil',
            'Outro',
        ],
        'refeicoes_padrao' => [
            'Café da manhã',
            'Lanche da manhã',
            'Almoço',
            'Lanche da tarde',
            'Jantar',
            'Ceia',
        ],
    ],
];
