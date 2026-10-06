{{--
    Layout dos documentos imprimíveis do SNR·FIT.

    Um HTML, duas apresentações (ver public/css/snrfit-doc.css):
      · padrão → fundo preto, letra branca, enxuto;
      · folha branca → claro e mais detalhado.

    O modo é a classe `folha` no <html>, trocada pelos dois botões. Fica
    guardado em localStorage para a escolha valer no próximo documento — quem
    imprime em folha branca costuma imprimir sempre assim.

    Marca d'água em todos, nos dois modos.

    Seções que o documento preenche:
      doc-titulo        obrigatória
      doc-sub           subtítulo (opcional)
      doc-emissor       quem emite: nome, registro, contato (opcional)
      doc-destinatario  dados de quem recebe — SÓ na folha branca
      doc-corpo         obrigatória
      doc-legenda       legenda das colunas — SÓ na folha branca
      doc-assinatura    nome/registro sob a linha — SÓ na folha branca
      doc-rodape        identificação à esquerda do rodapé (opcional)
--}}
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('doc-titulo') — SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&display=swap">
    <link rel="stylesheet" href="{{ asset('css/snrfit-doc.css') }}">
    @stack('doc-estilo')
</head>
<body>

<div class="doc-acoes">
    <button type="button" class="doc-btn" onclick="imprimir(false)">Imprimir escuro</button>
    <button type="button" class="doc-btn doc-btn--alt" onclick="imprimir(true)">Folha branca (detalhado)</button>
</div>

<div class="doc-marca" aria-hidden="true">
    <img src="{{ asset('SnrFit.png') }}" alt="">
</div>

<div class="doc-folha">
    <div class="doc-head">
        <div>
            <div class="doc-logo">
                <img src="{{ asset('SnrFit.png') }}" alt="SnrFit">
                <span>SNR·FIT</span>
            </div>
            <h1>@yield('doc-titulo')</h1>
            @hasSection('doc-sub')
                <div class="doc-sub">@yield('doc-sub')</div>
            @endif
        </div>
        @hasSection('doc-emissor')
            <div class="doc-emissor">
                @yield('doc-emissor')
                <div class="doc-dim doc-extra" style="margin-top:4px;">
                    Emitido em {{ now()->format('d/m/Y \à\s H:i') }}
                </div>
            </div>
        @endif
    </div>

    @hasSection('doc-destinatario')
        <div class="doc-extra">
            <div class="doc-dados">@yield('doc-destinatario')</div>
        </div>
    @endif

    @yield('doc-corpo')

    @hasSection('doc-legenda')
        <div class="doc-extra">
            <div class="doc-obs">
                <strong>Legenda</strong><br>
                @yield('doc-legenda')
            </div>
        </div>
    @endif

    @hasSection('doc-assinatura')
        {{-- `$assinaturaSempre` para documento em que a assinatura não é
             detalhe, é o documento: recibo sem assinatura não serve para nada.
             Nos outros ela fica só na folha branca. --}}
        <div class="{{ ($assinaturaSempre ?? false) ? '' : 'doc-extra' }}">
            <div class="doc-assina">
                <div class="linha"></div>
                @yield('doc-assinatura')
            </div>
        </div>
    @endif
</div>

<div class="doc-pe">
    <span>@yield('doc-rodape', 'SnrFit')</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

<script>
    // A escolha persiste entre documentos: quem imprime em folha branca
    // raramente quer o modo escuro no documento seguinte.
    var CHAVE = 'snrfit_doc_folha';

    function aplicarModo(folha) {
        document.documentElement.classList.toggle('folha', !!folha);
    }

    function imprimir(folha) {
        aplicarModo(folha);
        try { localStorage.setItem(CHAVE, folha ? '1' : '0'); } catch (e) { /* modo privado */ }
        // Deixa o navegador repintar com o modo novo antes de abrir o diálogo;
        // sem isso o Chrome ocasionalmente captura o estado anterior.
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () { window.print(); });
        });
    }

    try { aplicarModo(localStorage.getItem(CHAVE) === '1'); } catch (e) { /* ignora */ }
</script>

@stack('doc-script')
</body>
</html>
