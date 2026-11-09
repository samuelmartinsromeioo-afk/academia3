{{--
    Selo "Serviços financeiros Asaas" — exigência do programa BaaS.

    Este é o ÁTOMO: só a imagem linkada. Quem quer selo + frase de checkout usa
    `partials/asaas-pagamento`, que inclui este. Não duplique a <img> numa view:
    o arquivo do selo, a altura e a opacidade têm de mudar num lugar só.

    O arquivo é servido do NOSSO domínio (`public/img/asaas/`), não de
    baas.asaas.com. Três razões, todas concretas:
      1. `baas.asaas.com` não está no `img-src` do `config/csp.php` — apontar
         para lá faria o navegador BLOQUEAR o selo, justamente a imagem que o
         programa exige estar visível.
      2. Um asset do selo fora do ar derrubaria o selo de todas as páginas.
      3. A URL do Asaas carrega `?id=<uuid>`, ou seja um request a terceiro em
         toda página — inclusive nas legais, onde o projeto evita de propósito
         qualquer carga externa sem consentimento.
    Os SVG locais são byte a byte idênticos aos de baas.asaas.com (conferido).

    Parâmetros, todos opcionais:
      $altura     -> altura em px (padrão 30).
      $tema       -> 'escuro' (padrão) usa o negativo-branco, para fundo escuro.
                     'claro' usa o positivo, para fundo claro.
      $imprimivel -> true desenha AS DUAS variantes e alterna por @media print.
                     Obrigatório em página escura que também é impressa: no
                     papel branco o negativo-branco fica branco no branco, isto
                     é, invisível — e a página legal tem botão "Imprimir".
--}}
@php
    $altura = $altura ?? 30;
    $tema = $tema ?? 'escuro';
    $imprimivel = $imprimivel ?? false;

    $alt = 'Serviços financeiros Asaas';
    $arquivoEscuro = asset('img/asaas/selo-asaas-negativo-branco.svg');
    $arquivoClaro = asset('img/asaas/selo-asaas-positivo.svg');

    $estilo = "height:{$altura}px; width:auto; opacity:0.9;";
@endphp
<a href="{{ \App\Support\Empresa::get('psp_site') }}" target="_blank" rel="noopener"
   aria-label="Serviços financeiros fornecidos pela {{ \App\Support\Empresa::get('psp_razao_social') }}"
   style="display:inline-block; line-height:0;">
    @if($imprimivel)
        {{-- Só UM dos dois é visível por vez; o outro sai do fluxo e do leitor
             de tela, para a imagem não ser anunciada duas vezes. --}}
        <img src="{{ $arquivoEscuro }}" alt="{{ $alt }}"
             class="selo-asaas-tela" style="{{ $estilo }}">
        <img src="{{ $arquivoClaro }}" alt="" aria-hidden="true"
             class="selo-asaas-papel" style="{{ $estilo }} display:none;">
    @else
        <img src="{{ $tema === 'claro' ? $arquivoClaro : $arquivoEscuro }}"
             alt="{{ $alt }}" style="{{ $estilo }}">
    @endif
</a>
@once
    <style>
        @media print {
            .selo-asaas-tela { display: none !important; }
            /* `inline-block`, não `inline`: a altura em px não se aplica a
               elemento inline e o selo sairia no tamanho natural do SVG. */
            .selo-asaas-papel { display: inline-block !important; }
            /* Sem isto o navegador descarta a cor do SVG na impressão. */
            .selo-asaas-papel { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
@endonce
