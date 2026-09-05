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
