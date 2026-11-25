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
    'versao' => '3.2',

    'vigente_desde' => '2026-11-25',

    /*
     * O que mudou nesta versão, em linguagem clara. Mostrado na tela de
     * reaceite: um aceite de "mudou alguma coisa" tem pouco valor probatório;
     * o usuário precisa saber o que está aceitando.
     *
     * Versão MENOR (3.2) porque nada aqui muda PREÇO, TAXA ou REPASSE — a
     * contraprestação segue exatamente a da 3.0. A 3.2 é de UMA natureza só:
     * conformidade com o programa de Banking as a Service do Processador de
     * Pagamentos, conforme o "Playbook de BaaS para clientes Asaas" e a
     * Resolução Conjunta nº 16/2025 do Banco Central.
     *
     * O que ela faz, e por que é material o suficiente para pedir reaceite:
     *
     *  1. ACRESCENTA a cláusula "Prestação de Serviços Financeiros" nos seis
     *     documentos, no texto do MODELO do próprio Asaas — quem presta o
     *     serviço financeiro, em que qualidade a SNR FIT atua (integradora
     *     tecnológica e distribuidora da experiência do produto) e a ciência do
     *     cliente de que a responsabilidade regulatória é do Asaas.
     *  2. RETIRA do texto a afirmação de que a Plataforma atuava como
     *     "subadquirente/facilitadora". Esse rótulo nomeia atividade de
     *     instituição de pagamento, que a SNR FIT não exerce e não é autorizada
     *     a exercer — e o playbook veda ao tomador apresentar-se assim. Era
     *     declaração incorreta sobre quem responde pelo dinheiro, num documento
     *     oponível a quem o escreveu.
     *  3. PUBLICA os canais de suporte do Asaas, que o programa exige estarem
     *     acessíveis ao cliente final sempre que ele usa a operação financeira.
     *     Antes os documentos diziam "fale com a gente primeiro" sem informar
     *     que existe a porta do prestador.
     *
     * Nenhum direito é retirado e nenhum valor muda: o usuário passa a ler com
     * precisão quem guarda e quem movimenta o dinheiro — informação, não
     * obrigação nova. Por isso a vigência é imediata pela própria cláusula 19
     * (último item: alteração exigida por lei/regulação e correção de erro
     * material), e o reaceite de toda conta logada é adequado: o aceite anterior
     * foi dado sobre um texto que descrevia errado o papel da Plataforma.
     *
     * (O resumo da 3.1 — prazos, app, dados de saúde, cookies — saiu daqui
     * porque este campo é "o que mudou NESTA versão"; o histórico fica no git.)
     */
    'resumo' => [
        'Nada muda no <strong>preço, na taxa ou no repasse</strong>: você continua recebendo integralmente o preço que cadastrou, e a taxa segue somada ao valor do aluno.',
        'Os documentos agora trazem a cláusula <strong>"Prestação de Serviços Financeiros"</strong>, no texto do próprio Asaas: os serviços financeiros desta plataforma — conta de pagamento, processamento das transações, transferências e saques — são prestados pela <strong>Asaas Gestão Financeira Instituição de Pagamentos S.A.</strong>, autorizada pelo Banco Central do Brasil.',
        'Ficou escrito, com todas as letras, que a <strong>SNR FIT atua como integradora tecnológica</strong> e distribuidora da experiência do produto — <strong>não</strong> é instituição financeira nem de pagamento, e não faz intermediação financeira em nome próprio.',
        'Saiu do texto a expressão <strong>"subadquirente/facilitadora"</strong>, que sugeria um papel de pagamentos que a SNR FIT não tem. Quem exerce essa atividade, e responde por ela, é só o Asaas.',
        'Você passa a ter os <strong>canais de suporte do Asaas</strong> nos documentos e na central de ajuda, e pode acioná-lo diretamente sobre conta de pagamento, liquidação, transferências e saques. O nosso suporte continua sendo a porta de entrada para preço, agenda, cancelamento e reembolso.',
        'O <strong>selo "Serviços financeiros Asaas"</strong> passa a aparecer também nas telas de saldo, saque, bônus de indicação e abertura da conta de recebimentos, e nos e-mails de pagamento — para você sempre saber quem está movimentando o dinheiro.',
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
