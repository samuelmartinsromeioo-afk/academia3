<?php

/*
|--------------------------------------------------------------------------
| Conselhos Regionais de Educação Física (CREF) — consulta pública
|--------------------------------------------------------------------------
|
| Mapa de UF → página de consulta pública do conselho que atende aquele estado.
| É o que o admin abre para conferir, em um clique, o registro declarado por
| quem se cadastrou como personal.
|
| ─── POR QUE ISTO É UM CONFIG, E NÃO CÓDIGO ─────────────────────────────────
|
| Este mapa ENVELHECE, e envelhece de um jeito específico: conselhos se
| desmembram. O Espírito Santo era atendido pelo CREF1 (RJ/ES) e hoje tem o
| CREF22/ES; Sergipe saía do CREF13 (BA/SE) e hoje tem o CREF20/SE. Cada
| desmembramento muda a URL correta para um estado inteiro — e um link errado
| manda o admin conferir no conselho errado, onde o profissional legítimo
| simplesmente não aparece. Isso é pior que não ter link: produz uma
| "não encontrado" falsa sobre pessoa real.
|
| Por isso: editável sem deploy, e com `fallback` para quando a UF não estiver
| mapeada. Depois de editar, rode `php artisan config:clear`.
|
| ─── POR QUE NÃO HÁ CONSULTA AUTOMÁTICA ─────────────────────────────────────
|
| Não existe API pública nacional. O CONFEF tem uma busca em HTML, e cada
| regional tem o seu próprio sistema — vários em ASP.NET (plataforma Implanta,
| que exige sessão e `__VIEWSTATE`), outros em plataformas distintas. Raspar 20+
| sites diferentes como etapa BLOQUEANTE do cadastro seria:
|
|   - frágil: quebra quando qualquer conselho muda o HTML, e a quebra aparece
|     como "profissional não encontrado", acusando gente honesta;
|   - lento: a plataforma ASP.NET exige duas requisições por consulta;
|   - perigoso: conselho fora do ar passaria a barrar cadastro legítimo.
|
| Então a consulta automática NÃO existe de propósito. O que existe é:
|
|   1. validação de FORMATO (`App\Support\Cref`), determinística e bloqueante;
|   2. este mapa, que transforma a conferência manual do admin — que ele já faz,
|      porque todo personal nasce `pendente` — de "descobrir qual conselho
|      atende o Acre e procurar o site" em um clique.
|
| Se um dia valer a pena automatizar, o caminho honesto é uma API paga de
| consulta a conselhos (ex.: Infosimples), e aí entram duas consequências que
| precisam estar nos documentos ANTES: custo por consulta e envio do CREF/CPF
| do profissional a um terceiro, que é transferência de dado pessoal a operador
| novo (art. 7º, V, e art. 39 da LGPD).
|
| ─── AS URLS ────────────────────────────────────────────────────────────────
|
| Só entram aqui URLs CONFERIDAS. Link inventado é pior que ausente: manda o
| admin a uma página que não existe e ele conclui que o problema é o
| profissional. As UFs ainda não mapeadas caem no `fallback` do CONFEF, que
| atende o país inteiro — ver a lista de pendências em
| docs/auditoria-legal-apple.md.
|
*/

return [

    /*
     * Busca nacional do CONFEF. Usada quando a UF não tem URL própria mapeada
     * abaixo, e como ponto de partida para completar este arquivo.
     */
    'fallback' => env('CREF_CONSULTA_FALLBACK', 'https://www.confef.org.br/confef/registrados/'),

    /*
     * Consulta pública por UF. Chave = sigla do estado do conselho emissor, a
     * mesma que `App\Support\Cref` extrai do registro digitado.
     */
    'consulta' => [
        'SP' => 'https://cref-sp.implanta.net.br/servicosOnline/Publico/ConsultaInscritos',
        'RS' => 'https://cref-rs.implanta.net.br/servicosonline/Publico/ConsultaInscritos/',
        'SC' => 'https://online.crefsc.org.br/spw/consultacadastral/Principal.aspx',
        'BA' => 'https://www.cref13.org.br/cref13/consultas/',

        // CREF18 atende PA e AP — os dois apontam para o mesmo serviço.
        'PA' => 'https://servicos.cref18.org.br/',
        'AP' => 'https://servicos.cref18.org.br/',
    ],

    /*
     * Qual conselho atende cada UF, para o admin saber ONDE está conferindo —
     * e para a mensagem dizer "CREF9/PR" em vez de "o conselho do Paraná".
     *
     * Só os desmembramentos recentes importam aqui, e são exatamente os que
     * costumam estar errados em listas antigas: ES saiu do CREF1 (hoje CREF22),
     * SE saiu do CREF13 (hoje CREF20), TO é atendido junto com GO pelo CREF14,
     * e o CREF8 atende quatro estados do Norte (AM, AC, RO, RR).
     */
    'regional' => [
        'RJ' => 'CREF1/RJ',
        'RS' => 'CREF2/RS',
        'SC' => 'CREF3/SC',
        'SP' => 'CREF4/SP',
        'CE' => 'CREF5/CE',
        'MG' => 'CREF6/MG',
        'DF' => 'CREF7/DF',
        'AM' => 'CREF8/AM-AC-RO-RR',
        'AC' => 'CREF8/AM-AC-RO-RR',
        'RO' => 'CREF8/AM-AC-RO-RR',
        'RR' => 'CREF8/AM-AC-RO-RR',
        'PR' => 'CREF9/PR',
        'PB' => 'CREF10/PB',
        'MS' => 'CREF11/MS',
        'PE' => 'CREF12/PE',
        'BA' => 'CREF13/BA',
        'GO' => 'CREF14/GO-TO',
        'TO' => 'CREF14/GO-TO',
        'PI' => 'CREF15/PI',
        'RN' => 'CREF16/RN',
        'MT' => 'CREF17/MT',
        'PA' => 'CREF18/PA-AP',
        'AP' => 'CREF18/PA-AP',
        'AL' => 'CREF19/AL',
        'SE' => 'CREF20/SE',
        'MA' => 'CREF21/MA',
        'ES' => 'CREF22/ES',
    ],

];
