<?php

/*
|--------------------------------------------------------------------------
| Identificação do titular da plataforma
|--------------------------------------------------------------------------
|
| Fonte ÚNICA dos dados de qualificação da SNR FIT. Os Termos de Uso, a
| Política de Privacidade e qualquer documento legal leem daqui — nunca
| escreva CNPJ, razão social ou endereço dentro de uma view.
|
| POR QUE ISTO É OBRIGATÓRIO, e não burocracia:
|
|  - Decreto nº 7.962/2013, art. 2º, I e II (regulamenta o CDC para o comércio
|    eletrônico): o site tem de exibir, em local de destaque, nome empresarial,
|    número de inscrição no CNPJ e endereço físico e eletrônico. A ausência
|    desses dados é, por si, infração ao CDC e enfraquece qualquer cláusula de
|    limitação de responsabilidade que o contrato tente invocar.
|  - LGPD, art. 41: o controlador deve divulgar a identidade e o contato do
|    Encarregado (DPO).
|  - CDC, art. 4º, III e art. 6º, III: o consumidor tem direito de saber com
|    QUEM está contratando.
|
| ────────────────────────────────────────────────────────────────────────────
|  ATENÇÃO: os valores com `[...]` são LACUNAS A PREENCHER.
|
|  Eles aparecem literalmente nos documentos públicos, de propósito: um
|  placeholder visível é um defeito que alguém conserta, enquanto um CNPJ
|  inventado é uma declaração falsa num documento que produz efeito jurídico.
|  Nunca preencha isto com dado fictício "só para sair do ar".
|
|  Enquanto houver lacuna, `App\Support\Empresa::incompleto()` devolve true —
|  use para barrar o deploy em produção se quiser.
| ────────────────────────────────────────────────────────────────────────────
|
|  ENDEREÇO FÍSICO E TELEFONE: removidos por decisão do titular (2026-10-13).
|
|  Não existe chave para eles aqui e nenhum documento legal os exibe. A decisão
|  é consciente e tem um custo conhecido: o art. 2º, I e II, do Decreto nº
|  7.962/2013 exige que o fornecedor exiba endereço FÍSICO em local de destaque,
|  e o art. 41 da LGPD pede um canal de contato do Encarregado — aqui suprido
|  apenas pelo e-mail. Enquanto não houver endereço divulgado, a Plataforma está
|  em desconformidade formal com o Decreto, o que é infração ao CDC por si e
|  enfraquece as cláusulas de limitação de responsabilidade do contrato.
|
|  Se um dia o endereço for fornecido, o caminho é reintroduzir as chaves aqui
|  (`endereco`, `cidade`, `uf`, `cep`), voltar a listá-las em
|  `Empresa::OBRIGATORIOS` e exibi-las na seção 0 dos Termos e na seção 1 da
|  Política — NUNCA escrevendo o dado dentro de uma view.
| ────────────────────────────────────────────────────────────────────────────
|
| Depois de editar: `php artisan config:clear`.
|
*/

return [

    // Nome fantasia / marca. Este já é conhecido.
    'marca' => env('EMPRESA_MARCA', 'SNR FIT'),

    // Razão social completa, como consta no cartão CNPJ.
    //
    // Era 'SNR FIT' — o nome fantasia, não a razão social. Num documento que
    // identifica o fornecedor para o art. 2º, I, do Decreto nº 7.962/2013 os
    // dois não são intercambiáveis: a razão social é quem RESPONDE, e é por ela
    // que o consumidor localiza a empresa no cadastro do CNPJ e no Judiciário.
    // Um contrato assinado com o nome fantasia enfraquece a própria
    // identificação que a cláusula 0 existe para dar.
    'razao_social' => env('EMPRESA_RAZAO_SOCIAL', 'SNR FIT TECH LTDA'),

    // Apenas dígitos ou formatado — como vai aparecer no documento.
    'cnpj' => env('EMPRESA_CNPJ', '66.915.488/0001-95'),

    // Canal de atendimento. Único canal divulgado: não há telefone/WhatsApp
    // publicado (ver o bloco sobre endereço e telefone acima).
    'email_suporte' => env('EMPRESA_EMAIL_SUPORTE', 'suporte@snrfittech.com'),

    // Encarregado pelo Tratamento de Dados Pessoais (LGPD, art. 41). Pode ser
    // pessoa natural ou jurídica, e o NOME tem de ser divulgado — não basta o
    // e-mail genérico de suporte.
    'dpo_nome' => env('EMPRESA_DPO_NOME', 'Samuel'),
    'dpo_email' => env('EMPRESA_DPO_EMAIL', 'suporte@snrfittech.com'),

    // Processador de pagamentos. A Plataforma NÃO é instituição de pagamento;
    // quem é autorizado a operar arranjo de pagamento é o parceiro.
    'processador_pagamentos' => env('EMPRESA_PSP', 'Asaas (Asaas Gestão Financeira S.A.)'),

];
