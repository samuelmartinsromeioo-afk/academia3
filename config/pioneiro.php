<?php

/*
|--------------------------------------------------------------------------
| Selo de Pioneiro
|--------------------------------------------------------------------------
| Quem está entre os primeiros a se cadastrar no seu estado ganha o selo.
| Vale para personal/nutricionista, academia, studio e loja.
|
| Rode `php artisan config:clear` após editar.
*/

return [

    // Quantos cadastros por estado (e por tipo) recebem o selo.
    'limite_por_estado' => env('PIONEIRO_LIMITE_POR_ESTADO', 100),

    // Paleta do selo e da coroa — "verde chumbo" (metálico escuro).
    'cor' => [
        'base'  => '#2f6b4a', // preenchimento do selo e da coroa
        'clara' => '#4a9c6d', // borda/realce, para não sumir no fundo escuro
        'check' => '#ffffff', // o "V" dentro do selo
    ],

    // Rótulo por tipo de cadastro, usado no tooltip do selo.
    'rotulos' => [
        'personal'      => 'personais',
        'nutricionista' => 'nutricionistas',
        'academia'      => 'academias',
        'studio'        => 'studios',
        'loja'          => 'lojas',
    ],

];
