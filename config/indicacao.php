<?php

/**
 * Programa de indicação ("Indique e ganhe") — revenue share.
 * Depois de editar, rode: php artisan config:clear
 *
 * Regra: quem indica recebe `percentual` de TUDO o que o indicado faturar na
 * plataforma durante `janela_dias`, contados da APROVAÇÃO do indicado. O valor
 * acumula durante a janela e só vira sacável quando ela fecha E o indicado tem
 * `meta_alunos` alunos com pagamento confirmado.
 *
 * Atenção ao percentual: a base é o FATURAMENTO BRUTO do indicado, não a
 * comissão da plataforma. Como o split manda 90% ao profissional e deixa 10%
 * com a plataforma, `percentual` em 0.10 consome toda a comissão daquele
 * indicado durante a janela (líquido zero nele por 35 dias). Foi uma decisão
 * de aquisição, não um descuido — mas é o número a mexer se a conta apertar.
 */
return [
    // Fração do faturamento bruto do indicado que vira bônus do indicador.
    'percentual' => (float) env('INDICACAO_PERCENTUAL', 0.10),

    // Tamanho da janela de apuração, em dias, a partir da aprovação do indicado.
    'janela_dias' => (int) env('INDICACAO_JANELA_DIAS', 35),

    // Quantos alunos o INDICADO precisa ter conquistado pela plataforma para
    // liberar o saque do indicador. "Mais de 5" = 6.
    // Conta apenas aluno com pagamento confirmado na SnrFit (ver
    // TemCupomIndicacao::alunosPelaPlataforma) — vínculo criado à mão não conta,
    // senão bastaria cadastrar 6 amigos para destravar o prêmio.
    'meta_alunos' => (int) env('INDICACAO_META_ALUNOS', 6),

    // Piso para pedir saque. Evita um pedido manual de centavos para o admin.
    'saque_minimo' => (float) env('INDICACAO_SAQUE_MINIMO', 20.00),

    /*
     * Pagamento AUTOMÁTICO via Pix (Asaas /transfers na conta da plataforma).
     *
     * Desligado por padrão de propósito: ligar isto faz dinheiro REAL sair da
     * conta sem passar por humano, então tem de ser uma decisão explícita de
     * ambiente, nunca um efeito de deploy. Com `false`, todo pedido cai na fila
     * do admin (comportamento manual).
     *
     * Os dois tetos são o limite de estrago: nenhuma falha isolada pode mandar
     * mais que `saque_auto_teto` de uma vez, nem mais que `saque_auto_teto_diario`
     * somando tudo no dia. Pedido acima do teto não é recusado — vira análise do
     * admin. Ver IndicacaoSaqueService::decidirPagamento().
     */
    'saque_automatico'        => (bool) env('INDICACAO_SAQUE_AUTO', false),
    'saque_auto_teto'         => (float) env('INDICACAO_SAQUE_AUTO_TETO', 300.00),
    'saque_auto_teto_diario'  => (float) env('INDICACAO_SAQUE_AUTO_TETO_DIARIO', 2000.00),

    // Texto mostrado ao lado do campo de cupom nos formulários de cadastro.
    'label'     => 'Cupom de indicação (opcional)',
    'ajuda'     => 'Recebeu o código de alguém? Informe aqui — quem indicou você ganha um bônus.',
    'invalido'  => 'Cupom inválido ou expirado. Confira o código ou deixe o campo em branco.',
    'proprio'   => 'Você não pode usar o seu próprio cupom de indicação.',

    // Copy do painel "Minhas indicações". :pct, :dias e :meta são substituídos.
    'painel' => [
        'titulo'  => 'Indique e ganhe',
        'chamada' => 'Compartilhe seu código. Você recebe :pct de tudo o que cada profissional, academia, studio ou loja faturar na SnrFit nos primeiros :dias dias.',
        'regra'   => 'O bônus acumula durante os :dias dias seguintes à aprovação do indicado e fica disponível para saque quando a janela fecha — e desde que o indicado tenha :meta alunos com pagamento confirmado. Uma vez liberado, não volta atrás.',
        'aluno'   => 'Indicação de aluno entra no seu histórico, mas não gera bônus — o prêmio é por trazer profissionais e negócios.',
        'saque'   => 'O saque cai na chave Pix que você informar. Nada é liberado antes de a janela do indicado fechar.',
        'saque_auto'   => 'Pedidos de até :teto são enviados automaticamente por Pix em poucos minutos. Acima disso, a equipe confere antes de pagar.',
        'saque_manual' => 'O pagamento é feito pela equipe em até 5 dias úteis.',
    ],
];
