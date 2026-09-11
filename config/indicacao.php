<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Programa de indicação entre profissionais
    |--------------------------------------------------------------------------
    |
    | Quem indica recebe uma fatia do que a PLATAFORMA arrecadar do indicado
    | durante a janela. A base do cálculo é `payments.company_fee` (a comissão da
    | plataforma), não o faturamento bruto do indicado.
    |
    | Exemplo: indicado gera R$ 2.500 de comissão para a plataforma na janela
    | → indicador recebe R$ 250.
    |
    */

    'percentual' => env('INDICACAO_PERCENTUAL', 0.10),

    'janela_dias' => env('INDICACAO_JANELA_DIAS', 35),

    /*
    | Só profissionais participam — cliente e loja ficam de fora de propósito.
    | A chave é o tipo gravado em `indicado_por_tipo`; o valor é a tabela.
    */
    'tipos' => [
        'personal' => \App\Models\Cadastro\Personal::class,
        'academia' => \App\Models\Cadastro\Academia::class,
        'studio' => \App\Models\Cadastro\Studio::class,
    ],

    /*
    | Rótulo exibido ao usuário. "personal" cobre personal trainer e
    | nutricionista, porque ambos vivem na tabela `personals`.
    */
    'rotulos' => [
        'personal' => 'Profissional',
        'academia' => 'Academia',
        'studio' => 'Studio',
    ],
];
