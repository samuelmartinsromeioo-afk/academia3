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
    'versao' => '3.1',

    'vigente_desde' => '2026-10-14',

    /*
     * O que mudou nesta versão, em linguagem clara. Mostrado na tela de
     * reaceite: um aceite de "mudou alguma coisa" tem pouco valor probatório;
     * o usuário precisa saber o que está aceitando.
     *
     * Versão MENOR (3.1) porque nada aqui muda PREÇO, TAXA ou REPASSE — a
     * contraprestação segue exatamente a da 3.0. O que a 3.1 faz é de duas
     * naturezas, e as duas autorizam vigência imediata pela própria cláusula 19
     * (último item: alteração exigida por lei e correção de erro material):
     *
     *  1. AMPLIA DIREITOS, sem retirar nenhum: exportação dos dados e
     *     cancelamento de assinatura passaram a existir dentro do app,
     *     consentimento de saúde ganhou revogação própria (sem precisar apagar a
     *     conta) e a escolha de cookies passou a ser reversível no produto.
     *  2. CORRIGE o texto onde ele descrevia procedimento que o sistema não
     *     tinha — que é o defeito mais grave que um documento legal pode ter,
     *     porque a promessa é oponível a quem a escreveu.
     *
     * Por isso o reaceite de toda conta logada é adequado e não precisa dos 30
     * dias de aviso prévio: ninguém perde direito, posição ou valor com ela.
     */
    'resumo' => [
        'Nada muda no <strong>preço, na taxa ou no repasse</strong>: a regra da versão 3.0 continua valendo igual — você recebe integralmente o preço que cadastrou e a taxa é somada ao valor do aluno.',
        'O que mudou é o <strong>como fazer</strong>. Cada prazo do contrato agora diz se é em dias corridos, dias úteis ou horas, de quando começa a contar e o que acontece se cair em fim de semana ou feriado (cláusula 1.4), e cada direito tem o caminho de telas, o prazo e a confirmação que você recebe (cláusula 12.1).',
        '<strong>Pelo app</strong> você agora exporta seus dados, cancela assinatura e abre "Privacidade e meus dados" — antes isso só existia no site, embora os documentos já prometessem os dois.',
        'Os <strong>dados de saúde</strong> (anamnese) passaram a ter consentimento destacado e revogação própria: revogar apaga a anamnese e <strong>não</strong> encerra a sua conta.',
        'A escolha sobre <strong>cookies de medição</strong> pode ser mudada a qualquer momento no próprio site, sem limpar o navegador — e nenhum evento vai à Meta sem ela, nem na renovação de assinatura.',
        'Toda <strong>exclusão de conta e todo cancelamento</strong> passam a gerar confirmação por escrito, que serve de comprovante.',
        '<strong>Excluir a conta agora cancela as suas assinaturas mensais</strong> junto, no mesmo ato — antes a recorrência continuava cobrando depois do encerramento. Se você é profissional, a sua saída não cancela o plano dos seus alunos: eles são avisados para decidir.',
        'No cadastro de <strong>personal</strong>, o CREF passou a ser validado no formato, e a aprovação exige a <strong>conferência do registro no Conselho Regional</strong> — que fica registrada. Isso não é garantia de habilitação nem monitoramento contínuo: a cláusula 2.1 dos Termos do Personal diz exatamente o alcance.',
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
