<?php

/**
 * Programa de indicação ("Indique e ganhe").
 * Depois de editar, rode: php artisan config:clear
 */
return [
    // Bônus por indicação. Só vira resgatável quando o indicado bate a meta abaixo.
    'bonus' => env('INDICACAO_BONUS', 30.00),

    // Quantos alunos o INDICADO precisa ter conquistado pela plataforma para
    // liberar o bônus de quem o indicou. "Mais de 5" = 6.
    // Conta apenas aluno com pagamento confirmado na SnrFit (ver
    // TemCupomIndicacao::alunosPelaPlataforma) — vínculo criado à mão não conta,
    // senão bastaria cadastrar 6 amigos para destravar o prêmio.
    'meta_alunos' => env('INDICACAO_META_ALUNOS', 6),

    // Texto mostrado ao lado do campo de cupom nos formulários de cadastro.
    'label'     => 'Cupom de indicação (opcional)',
    'ajuda'     => 'Recebeu o código de alguém? Informe aqui — quem indicou você ganha um bônus.',
    'invalido'  => 'Cupom inválido ou expirado. Confira o código ou deixe o campo em branco.',
    'proprio'   => 'Você não pode usar o seu próprio cupom de indicação.',

    // Copy do painel "Minhas indicações".
    'painel' => [
        'titulo'  => 'Indique e ganhe',
        'chamada' => 'Compartilhe seu código. Cada profissional, academia, studio ou loja que entrar por ele te rende um bônus assim que conquistar :meta alunos pela SnrFit.',
        'regra'   => 'O bônus fica reservado desde o cadastro e vira resgatável quando o indicado chega a :meta alunos com pagamento confirmado na plataforma. Uma vez liberado, não volta atrás.',
        'aluno'   => 'Indicação de aluno entra no seu histórico, mas não gera bônus — o prêmio é por trazer profissionais e negócios.',
    ],
];
