<?php

/**
 * Programa de indicação ("Indique e ganhe").
 * Depois de editar, rode: php artisan config:clear
 */
return [
    // Bônus creditado ao indicador a cada cadastro confirmado com o código dele.
    'bonus' => env('INDICACAO_BONUS', 10.00),

    // Texto mostrado ao lado do campo de cupom nos formulários de cadastro.
    'label'     => 'Cupom de indicação (opcional)',
    'ajuda'     => 'Recebeu o código de alguém? Informe aqui — quem indicou você ganha um bônus.',
    'invalido'  => 'Cupom inválido ou expirado. Confira o código ou deixe o campo em branco.',
    'proprio'   => 'Você não pode usar o seu próprio cupom de indicação.',

    // Copy do painel "Minhas indicações".
    'painel' => [
        'titulo'   => 'Indique e ganhe',
        'chamada'  => 'Compartilhe seu código. A cada cadastro confirmado com ele, você acumula bônus.',
    ],
];
