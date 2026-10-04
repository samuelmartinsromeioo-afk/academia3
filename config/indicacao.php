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
 * A base é a COMISSÃO QUE A PLATAFORMA GANHOU com a conta indicada
 * (`payments.company_fee`) — ou seja, são 10% DOS 10%, não 10% do valor cheio.
 *
 * A base NÃO é o faturamento bruto do indicado, NÃO é o que o profissional
 * leva para casa, e o bônus NUNCA sai do repasse dele.
 *
 * Com os números de hoje (split 90/10, `percentual` 0.10), uma conta indicada
 * que fatura R$ 2.500,00 na janela:
 *
 *   Bruto faturado pelo indicado ..... R$ 2.500,00   (não é a base)
 *   Vai para o profissional (90%) .... R$ 2.250,00   (split intacto, nada sai daqui)
 *   Comissão da plataforma (10%) ..... R$   250,00   <- a BASE do cálculo
 *   Bônus de quem indicou (10% dela) . R$    25,00
 *   Fica com a plataforma ............ R$   225,00
 *
 * Então o bônus equivale a 1% do bruto (10% de 10%), e a plataforma segue
 * ganhando 9% do bruto na janela. Teto estrutural: como o bônus sai de dentro
 * da comissão, ele não pode passar do que entrou — `percentual` em 1.0 seria o
 * limite (comissão inteira), e é por isso que percentual() trava em [0, 1].
 *
 * `percentual` é o único número a mexer para alterar a divisão: 0.10 = R$ 25
 * para o indicador e R$ 225 para a plataforma; 0.50 = R$ 125 para cada lado.
 */
return [
    // Fração da COMISSÃO DA PLATAFORMA que vira bônus do indicador.
    // 0.10 = 10% dos nossos 10% = 1% do bruto do indicado.
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
    // Atenção à ordem de grandeza: como o bônus é 1% do bruto do indicado,
    // R$ 20 aqui equivalem a R$ 2.000 faturados por ele dentro da janela.
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

    // Copy do painel "Minhas indicações". :pct, :dias, :meta, :taxa, :base,
    // :comissao e :bonus são substituídos.
    'painel' => [
        'titulo'  => 'Indique e ganhe',
        'chamada' => 'Compartilhe seu código. Você recebe :pct da comissão que a SnrFit ganhar com cada profissional, academia, studio ou loja que entrar pelo seu código, nos primeiros :dias dias.',

        // Explica SOBRE O QUE incidem os 10% — é a dúvida que mais aparece.
        // O percentual é da comissão da plataforma, não do valor cheio.
        'base_titulo'   => 'Sobre qual valor incidem os :pct',
        'base'          => 'Os :pct incidem sobre a COMISSÃO DA SNRFIT, não sobre o valor cheio que o indicado fatura. De cada pagamento feito pela plataforma, :taxa fica com a SnrFit e o resto vai para o profissional — e é desses :taxa que sai o seu bônus. Nada do que você recebe sai do bolso de quem você indicou.',
        'base_exemplo'  => 'Um profissional que entrou pelo seu código fatura :base na janela de :dias dias. A comissão da SnrFit nisso é :comissao, e o seu bônus é :bonus.',
        'base_rodape'   => 'Mais abaixo, o extrato de cada indicação abre receita por receita: quanto o indicado faturou, quanto foi de comissão e quanto virou seu.',
        'regra'   => 'O bônus acumula durante os :dias dias seguintes à aprovação do indicado e fica disponível para saque quando a janela fecha — e desde que o indicado tenha :meta alunos com pagamento confirmado. Uma vez liberado, não volta atrás.',
        'aluno'   => 'Indicação de aluno entra no seu histórico, mas não gera bônus — o prêmio é por trazer profissionais e negócios.',
        'saque'   => 'O saque cai na chave Pix que você informar. Nada é liberado antes de a janela do indicado fechar.',
        'saque_auto'   => 'Pedidos de até :teto são enviados automaticamente por Pix em poucos minutos. Acima disso, a equipe confere antes de pagar.',
        'saque_manual' => 'O pagamento é feito pela equipe em até 5 dias úteis.',
    ],
];
