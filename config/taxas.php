<?php

/*
|--------------------------------------------------------------------------
| Taxas — comissão da plataforma e custo do gateway (Asaas)
|--------------------------------------------------------------------------
|
| São DOIS números com naturezas diferentes, e confundi-los é o erro caro:
|
|  - `comissao` é NOSSA receita. É um ACRÉSCIMO ao preço do profissional:
|    ele pede R$ 100, o cliente paga R$ 110, ele recebe os R$ 100 cheios.
|    Vive aqui mas é lida por `AsaasService::FEE_RATE` — ver o comentário lá.
|
|  - `asaas.*` é CUSTO de quem recebe. Não é receita de ninguém: é o que a
|    Asaas cobra por processar. No Pix a plataforma absorve (sai dos nossos
|    10%); no cartão é repassada ao cliente, porque é ele que escolhe o meio
|    caro. Por isso os valores precisam bater com o seu contrato Asaas: eles
|    entram na conta do que é cobrado do cliente e no valor mínimo aceito.
|
| Os defaults são a tabela pública do Asaas (plano sem mensalidade) e são
| CONSERVADORES de propósito: superestimar a taxa faz a plataforma receber
| alguns centavos a mais; subestimar faz a Asaas recusar o split, e aí a
| cobrança falha na cara do cliente. Se o seu contrato tem taxa menor, ajuste
| no `.env` — não no código.
|
| Depois de editar: `php artisan config:clear`.
|
*/

return [

    // Comissão da plataforma sobre o preço do profissional (0.10 = +10%).
    'comissao' => (float) env('PLATAFORMA_COMISSAO', 0.10),

    'asaas' => [

        // Taxa fixa por Pix RECEBIDO. Entra no valor mínimo de cobrança: a
        // comissão precisa cobrar esta taxa, senão o repasse integral ao
        // profissional não cabe no líquido e a Asaas recusa o split.
        'pix_fixa' => (float) env('ASAAS_TAXA_PIX', 1.99),

        // Cartão de crédito: percentual sobre o valor da transação + fixa.
        'cartao_percentual' => (float) env('ASAAS_TAXA_CARTAO_PERCENTUAL', 0.0499),
        'cartao_fixa' => (float) env('ASAAS_TAXA_CARTAO_FIXA', 0.49),

    ],

];
