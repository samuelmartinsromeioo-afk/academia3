{{--
    Selo "Serviços financeiros Asaas" — exigência do programa BaaS.

    Este é o ÁTOMO: só a imagem linkada. Quem quer selo + frase de checkout usa
    `partials/asaas-pagamento`, que inclui este. Não duplique a <img> numa view:
    o arquivo do selo, a altura e a opacidade têm de mudar num lugar só.

    DE ONDE VEM A IMAGEM — o playbook de BaaS manda servir o selo do
    repositório privado do Asaas, por URL pública com `?id=`, e diz que é assim
    que o Asaas VERIFICA que a aplicação carregou o selo. A URL real é enviada
    pelo suporte durante a integração; os links do documento são exemplos e não
    devem ser usados.

    Então o caminho é configurável, e a ordem é esta:
      1. `empresa.psp_selo_url` / `psp_selo_url_claro` preenchidas → o selo vem
         do CDN do Asaas, que é o que o playbook pede. `https://baas.asaas.com`
         está no `img-src` de `config/csp.php`; sem isso o navegador bloquearia
         justamente a imagem que o programa exige estar visível.
      2. Vazias (estado atual, enquanto o suporte não envia a URL) → o SVG
         local de `public/img/asaas/`, byte a byte idêntico ao de
         baas.asaas.com (conferido).

    O fallback local existe porque selo ausente é descumprimento: é melhor
    servir a cópia idêntica do nosso domínio do que deixar a página sem selo
    esperando uma URL que ainda não chegou. O que o fallback NÃO faz é permitir
    a verificação do lado do Asaas — por isso ele é fallback, não destino.

    Nenhuma política de referrer é aplicada à <img>: o playbook pede
    explicitamente que o Referer não seja bloqueado, e o `Referrer-Policy:
    strict-origin-when-cross-origin` de `SecurityHeaders` envia a origem em
    requisição cross-origin, que é o que o Asaas precisa ver. Não troque esse
    header por `no-referrer`.

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

    // URL homologada do Asaas quando configurada; cópia local enquanto não.
    $arquivoEscuro = \App\Support\Empresa::get('psp_selo_url')
        ?: asset('img/asaas/selo-asaas-negativo-branco.svg');
    $arquivoClaro = \App\Support\Empresa::get('psp_selo_url_claro')
        ?: asset('img/asaas/selo-asaas-positivo.svg');

    $estilo = "height:{$altura}px; width:auto; opacity:0.9;";
@endphp
{{-- `rel="noopener noreferrer"` e `target="_blank"` são o que o playbook
     recomenda para o link do selo: o cliente abre o site do Asaas em outra aba
     sem perder a jornada em que está. O `noreferrer` aqui vale para a
     NAVEGAÇÃO ao clicar, não para o carregamento da <img> — o Referer da
     imagem continua saindo, que é o que o Asaas usa para verificar o selo. --}}
<a href="{{ \App\Support\Empresa::get('psp_site') }}" target="_blank" rel="noopener noreferrer"
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
