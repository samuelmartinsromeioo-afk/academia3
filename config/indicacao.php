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
 * ─── A BASE DE CÁLCULO (leia antes de mexer em `percentual`) ────────────────
 *
 * A base é o VALOR BRUTO GERADO pela conta indicada na plataforma — o que os
 * alunos dela pagaram, `payments.amount_total`, antes de qualquer split. NÃO é
 * o que o profissional leva para casa, e NÃO é a comissão da plataforma.
 *
 * Com os números de hoje (split 90/10, `percentual` 0.10), uma conta indicada
 * que gera R$ 2.500,00 na janela:
 *
 *   Bruto gerado pelo indicado ....... R$ 2.500,00   <- a BASE do cálculo
 *   Vai para o profissional (90%) .... R$ 2.250,00   (split do marketplace, intacto)
 *   Comissão da plataforma (10%) ..... R$   250,00
 *   Bônus de quem indicou (10%) ...... R$   250,00
 *
 * ATENÇÃO: os dois últimos R$ 250,00 são O MESMO DINHEIRO, não duas quantias.
 * O bônus sai de dentro da comissão: com `percentual` igual aos 10% do split, a
 * plataforma arrecada R$ 250 e repassa R$ 250 ao indicador, ficando com LÍQUIDO
 * ZERO naquela conta durante os 35 dias. Ela não guarda R$ 250 E paga outros
 * R$ 250 — para isso acontecer o `percentual` teria de ser menor que 0.10 (ex.:
 * 0.05 deixaria R$ 125 para cada lado).
 *
 * Isso foi uma decisão de aquisição (o custo de trazer a conta é toda a margem
 * dos primeiros 35 dias, e depois da janela a comissão volta inteira), não um
 * descuido. `percentual` é o único número a mexer se a conta apertar — e mexer
 * nele muda quanto sobra para a plataforma na mesma proporção.
 */
return [
    // Fração do VALOR BRUTO gerado pelo indicado que vira bônus do indicador.
    // Igual aos 10% do split = a plataforma fica no zero a zero na janela.
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

    // Valor usado no exemplo numérico do painel. O bônus do exemplo é calculado
    // na view a partir de `percentual`, para o exemplo nunca mentir se o
    // percentual mudar.
    'exemplo_base' => (float) env('INDICACAO_EXEMPLO_BASE', 2500.00),

    // Copy do painel "Minhas indicações". :pct, :dias, :meta, :base e :bonus
    // são substituídos.
    'painel' => [
        'titulo'  => 'Indique e ganhe',
        'chamada' => 'Compartilhe seu código. Você recebe :pct de tudo o que cada profissional, academia, studio ou loja faturar na SnrFit nos primeiros :dias dias.',

        // Explica SOBRE O QUE incidem os 10%. É a dúvida que mais aparece:
        // o percentual é do valor cheio gerado, não do que o indicado leva.
        'base_titulo'   => 'Sobre qual valor incidem os :pct',
        'base'          => 'Os :pct incidem sobre o valor CHEIO que a conta indicada gera na SnrFit — tudo o que os alunos dela pagaram pela plataforma, antes de qualquer repasse ou desconto. Não é :pct do que o profissional recebe no fim do mês, nem :pct do lucro da SnrFit.',
        'base_exemplo'  => 'Um profissional que você indicou gera :base em pagamentos dentro da janela de :dias dias. O seu bônus é :bonus.',
        'base_rodape'   => 'Mais abaixo, o extrato de cada indicação abre receita por receita: quanto o indicado faturou e quanto virou seu.',
        'regra'   => 'O bônus acumula durante os :dias dias seguintes à aprovação do indicado e fica disponível para saque quando a janela fecha — e desde que o indicado tenha :meta alunos com pagamento confirmado. Uma vez liberado, não volta atrás.',
        'aluno'   => 'Indicação de aluno entra no seu histórico, mas não gera bônus — o prêmio é por trazer profissionais e negócios.',
        'saque'   => 'O saque cai na chave Pix que você informar. Nada é liberado antes de a janela do indicado fechar.',
        'saque_auto'   => 'Pedidos de até :teto são enviados automaticamente por Pix em poucos minutos. Acima disso, a equipe confere antes de pagar.',
        'saque_manual' => 'O pagamento é feito pela equipe em até 5 dias úteis.',
    ],
];
