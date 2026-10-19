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
|  ENDEREÇO FÍSICO: informado pelo titular em 2026-10-19.
|
|  Esteve removido entre 13/10 e 19/10, e o custo dessa ausência está
|  registrado aqui porque ele era real: o art. 2º, I e II, do Decreto nº
|  7.962/2013 exige que o fornecedor exiba endereço FÍSICO em local de
|  destaque, a falta é infração ao CDC por si e enfraquece as cláusulas de
|  limitação de responsabilidade do próprio contrato. Com o endereço publicado,
|  a cláusula 21 também passou a poder NOMEAR a comarca do foro, em vez de
|  deixá-la "determinável pelo registro no CNPJ".
|
|  TELEFONE: continua não divulgado, por decisão do titular. Não há chave para
|  ele e nenhum documento o exibe. O art. 2º, II, do Decreto pede as "demais
|  informações necessárias para localização e contato", hoje supridas apenas
|  pelo e-mail — que é um canal real e atendido, mas é só um.
|
|  O endereço do documento legal tem de ser o do REGISTRO NO CNPJ, não o de
|  correspondência: é por ele que o consumidor localiza a empresa no cadastro
|  e no Judiciário, e é ele que define a comarca da sede.
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

    // ── Endereço da sede, como consta do registro no CNPJ ───────────────────
    //
    // Separado em partes, e não numa string só, porque `uf` e `cidade` são
    // lidas isoladamente: a cidade nomeia a comarca do foro na cláusula 21 dos
    // Termos. Uma string única obrigaria a view a fatiar texto para descobrir
    // a comarca, que é exatamente o tipo de lógica que não pode morar lá.
    'endereco' => env('EMPRESA_ENDERECO', 'Rua Dom Marcos Teixeira'),
    'numero' => env('EMPRESA_NUMERO', '19'),
    'bairro' => env('EMPRESA_BAIRRO', 'Campo Alegre'),
    'cidade' => env('EMPRESA_CIDADE', 'Belo Horizonte'),
    'uf' => env('EMPRESA_UF', 'MG'),

    // PENDENTE: o CEP ainda não foi informado. Placeholder visível em vez de
    // adivinhado — a mesma rua pode ter CEPs diferentes por faixa de
    // numeração, e CEP errado em documento legal é declaração falsa.
    //
    // `Empresa::enderecoCompleto()` OMITE a parte pendente em vez de imprimir
    // o colchete: o documento público mostra o endereço sem CEP, que é menos
    // completo mas verdadeiro.
    //
    // NÃO está em `Empresa::OBRIGATORIOS` de propósito — o Decreto exige
    // "endereço físico", e rua + número + bairro + cidade + UF já é um. Ver a
    // justificativa completa no docblock de `OBRIGATORIOS`. Preencher aqui
    // basta; nada mais precisa mudar.
    'cep' => env('EMPRESA_CEP', '[CEP]'),

    // Canal de atendimento. Único canal divulgado: não há telefone/WhatsApp
    // publicado (ver o bloco sobre endereço e telefone acima).
    'email_suporte' => env('EMPRESA_EMAIL_SUPORTE', 'suporte@snrfittech.com'),

    // Encarregado pelo Tratamento de Dados Pessoais (LGPD, art. 41). Pode ser
    // pessoa natural ou jurídica, e o NOME tem de ser divulgado — não basta o
    // e-mail genérico de suporte.
    //
    // Era só 'Samuel'. O art. 41, §1º, exige que a IDENTIDADE do Encarregado
    // seja divulgada publicamente, e primeiro nome não identifica ninguém: o
    // titular que precisa acionar o Encarregado — ou a ANPD, num procedimento —
    // tem de saber de quem se trata. Nome completo é o mínimo para isso.
    'dpo_nome' => env('EMPRESA_DPO_NOME', 'Samuel Martins Romeiro'),
    'dpo_email' => env('EMPRESA_DPO_EMAIL', 'suporte@snrfittech.com'),

    // Processador de pagamentos. A Plataforma NÃO é instituição de pagamento;
    // quem é autorizado a operar arranjo de pagamento é o parceiro.
    'processador_pagamentos' => env('EMPRESA_PSP', 'Asaas (Asaas Gestão Financeira S.A.)'),

];
