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
    //
    // Esta chave é o TERMO DEFINIDO da cláusula 1.3 dos Termos e aparece nos
    // seis documentos legais. Mudar o texto dela muda os seis de uma vez — é
    // por isso que ela não repete em view nenhuma.
    'processador_pagamentos' => env('EMPRESA_PSP', 'Asaas (Asaas Gestão Financeira S.A.)'),

    // Qualificação completa do processador, para a seção 0.1 dos Termos.
    //
    // POR QUE SEPARADO de `processador_pagamentos`: aquela chave é o termo
    // definido que o contrato usa dezenas de vezes ("conforme o Processador de
    // Pagamentos"), e precisa ser curta. Estes dois identificam a instituição
    // uma única vez, onde o CDC exige saber com quem se contrata.
    //
    // O CNPJ é o que INDIVIDUALIZA a instituição responsável pela liquidação:
    // sem ele o documento nomeia uma marca, não uma pessoa jurídica acionável.
    //
    // O CNPJ está no DEFAULT, e não só no `.env`, de propósito: `.env` não é
    // versionado, então um valor só local deixaria a linha OMITIDA justamente
    // em produção, que é onde o documento produz efeito. Isto não é segredo
    // nem varia por ambiente — é a identificação de uma pessoa jurídica
    // pública, igual à razão social logo abaixo.
    //
    // Informado pelo titular em 2026-11-09, a partir do contrato BaaS, e com os
    // dígitos verificadores conferidos. A regra que continua valendo: nunca
    // preencha este campo com número obtido de memória ou de busca na web. Num
    // contrato, CNPJ errado é identificação FALSA da instituição que detém o
    // dinheiro dos usuários — pior que a ausência, porque ausência ninguém lê
    // como afirmação. Se algum dia houver dúvida, esvazie em vez de adivinhar:
    // vazio, a linha é omitida da seção 0.1 (igual ao CEP), sem quebrar nada.
    // A razão social é a do MODELO DE CLÁUSULA do "Playbook de BaaS para
    // clientes Asaas" (slide 11), que é o documento oficial do prestador: ali a
    // instituição se qualifica como "ASAAS GESTÃO FINANCEIRA INSTITUIÇÃO DE
    // PAGAMENTOS S.A., instituição de pagamento autorizada a funcionar pelo
    // Banco Central do Brasil".
    //
    // Era 'Asaas Gestão Financeira S.A.' — nome que o próprio playbook também
    // usa, no fim da mesma cláusula. Os dois correspondem ao mesmo CNPJ, mas o
    // playbook manda aplicar a cláusula como está escrita e veda ao tomador
    // "alterar nem criar documentos legais relacionados ao processamento
    // financeiro de pagamentos" — então quem manda é a forma completa, que é a
    // que nomeia a licença.
    'psp_razao_social' => env('EMPRESA_PSP_RAZAO_SOCIAL', 'Asaas Gestão Financeira Instituição de Pagamentos S.A.'),
    'psp_cnpj' => env('EMPRESA_PSP_CNPJ', '19.540.550/0001-21'),
    'psp_site' => env('EMPRESA_PSP_SITE', 'https://asaas.com'),

    /*
    |--------------------------------------------------------------------------
    | Suporte do Asaas ao cliente final (exigência do playbook BaaS)
    |--------------------------------------------------------------------------
    |
    | O playbook é explícito: "O cliente final deve ter acesso claro às
    | informações de suporte do Asaas, sempre que utilizar operações
    | financeiras de pagamentos processadas pela plataforma" e "o parceiro é
    | responsável por garantir que o cliente final saiba que pode acionar o
    | Asaas".
    |
    | Isto NÃO substitui o nosso suporte (`email_suporte`), e os dois têm
    | escopos diferentes de propósito: o nosso atende o produto e o início de
    | qualquer dúvida de cobrança; o do Asaas atende a operação financeira —
    | conta de pagamento, liquidação, saque, bloqueio de wallet —, que é
    | justamente o que não está sob o nosso controle. Omitir o canal dele era
    | deixar o usuário sem porta para o único responsável por essa parte.
    |
    | O 0800 é de ATENDIMENTO A PESSOA JURÍDICA, conforme o playbook, e isso
    | tem de aparecer junto do número: a maior parte dos nossos clientes finais
    | é pessoa física, e mandar um aluno ligar num canal que não o atende é
    | pior que não divulgar o número. Para pessoa física o caminho é o e-mail.
    |
    | Valores conforme o playbook recebido em 25/11/2026. Não altere de memória
    | nem de busca na web: canal de suporte errado em página de pagamento manda
    | o usuário para o vazio no momento em que ele mais precisa de resposta.
    */
    'psp_suporte_telefone' => env('EMPRESA_PSP_SUPORTE_TELEFONE', '0800 009 0037'),
    'psp_suporte_telefone_nota' => env('EMPRESA_PSP_SUPORTE_TELEFONE_NOTA', 'somente pessoa jurídica; o mesmo número recebe mensagens'),
    'psp_suporte_email' => env('EMPRESA_PSP_SUPORTE_EMAIL', 'contato@asaas.com.br'),

    /*
    |--------------------------------------------------------------------------
    | Selo "Serviços financeiros Asaas" — URL homologada
    |--------------------------------------------------------------------------
    |
    | O playbook determina que os selos sejam servidos do repositório privado
    | via CDN do Asaas, por URL pública personalizada com um `?id=`, e avisa
    | que os exemplos impressos no documento NÃO devem ser usados: a URL real
    | é enviada pelo time de suporte durante a integração.
    |
    | Enquanto estas chaves estiverem vazias, `partials/selo-asaas` serve o SVG
    | local de `public/img/asaas/` — o selo continua visível, que é o requisito
    | de fundo. Preenchendo-as, o selo passa a vir do CDN do Asaas, e só então
    | o Asaas consegue VERIFICAR que a aplicação carregou o selo (é para isso
    | que o playbook pede que o Referer não seja bloqueado na <img>).
    |
    | Ao preencher: use a URL inteira, com o `?id=`, exatamente como o suporte
    | do Asaas enviar, e confirme que `https://baas.asaas.com` continua no
    | `img-src` de `config/csp.php` — sem isso o navegador bloqueia justamente
    | a imagem que o programa exige estar visível.
    */
    'psp_selo_url' => env('EMPRESA_PSP_SELO_URL', ''),          // variante negativa (fundo escuro)
    'psp_selo_url_claro' => env('EMPRESA_PSP_SELO_URL_CLARO', ''), // variante positiva (fundo claro)

];
