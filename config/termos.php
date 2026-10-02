<?php

/**
 * Versão vigente dos Termos de Uso e o resumo do que mudou.
 * Depois de editar, rode: php artisan config:clear
 *
 * Esta é a ÚNICA fonte da verdade da versão. As views legais leem daqui e o
 * middleware VerificaAceiteTermos compara com o que cada conta já aceitou
 * (tabela `termo_aceites`). Subir `versao` aqui faz TODA conta logada cair na
 * tela de reaceite no próximo acesso — então só suba em mudança material, e
 * preencha `resumo` dizendo o que mudou, que é o que dá validade ao aceite.
 */
return [
    'versao' => '2.1',

    'vigente_desde' => '2026-10-02',

    /*
     * O que mudou nesta versão, em linguagem clara. Mostrado na tela de
     * reaceite: um aceite de "mudou alguma coisa" tem pouco valor probatório;
     * o usuário precisa saber o que está aceitando.
     */
    'resumo' => [
        'Criamos o <strong>Programa de Indicação ("Indique e ganhe")</strong>: cada conta recebe um código e pode indicar outras pessoas para a plataforma.',
        'Quem indica um profissional ou estabelecimento pode receber um <strong>bônus percentual</strong> sobre o que a conta indicada faturar na plataforma, dentro de um prazo de apuração.',
        'O bônus só fica <strong>disponível para saque</strong> quando duas condições acontecem juntas: o prazo de apuração encerra <strong>e</strong> a conta indicada atinge o número mínimo de alunos com pagamento confirmado.',
        '<strong>Indicar um aluno não gera bônus</strong> — o programa premia a indicação de profissionais e estabelecimentos.',
        'O saque é pago por <strong>Pix</strong>, na chave que você informar, e você é responsável por essa chave estar correta.',
        'Explicamos que o bônus é uma <strong>liberalidade</strong>: não é salário nem comissão, não cria vínculo de emprego e pode ser alterado ou encerrado, preservando o que já foi liberado.',
        'Descrevemos as <strong>condutas vedadas</strong> (auto-indicação, contas falsas, simulação de alunos) e as consequências, além do registro de IP para prevenção a fraude.',
    ],

    /*
     * Rotas liberadas do bloqueio de reaceite. Sem isto a tela de aceite, o
     * logout e a leitura dos próprios termos ficariam inacessíveis — o usuário
     * entraria num laço de redirecionamento sem saída.
     */
    'rotas_livres' => [
        'termos.aceite',
        'termos.aceite.registrar',
        'termos',
        'termos.aluno',
        'termos.personal',
        'termos.academia',
        'termos.studio',
        'termos.loja',
        'lgpd.politica',
        'privacidade',
        'login.index',
        'login.create',
        'login.store',
        'login.logout',
        'admin.logout',
    ],
];
