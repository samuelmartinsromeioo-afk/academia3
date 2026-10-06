{{--
    Layout dos documentos imprimíveis do SNR·FIT.

    Folha branca, tinta preta, marca d'água (ver public/css/snrfit-doc.css).

    Houve uma versão com dois modos — o escuro da marca e a folha branca,
    alternáveis. O escuro ficou ruim no papel e saiu; sobrou o modo que
    funciona num documento, e com ele todo o detalhamento que antes era
    exclusivo da folha branca.

    Seções que o documento preenche:
      doc-titulo        obrigatória
      doc-sub           subtítulo (opcional)
      doc-emissor       quem emite: nome, registro, contato (opcional)
      doc-destinatario  dados de quem recebe (opcional)
      doc-corpo         obrigatória
      doc-legenda       legenda das colunas (opcional)
      doc-assinatura    nome/registro sob a linha (opcional)
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
    {{-- Versão pelo mtime do arquivo. Sem isso o navegador serve a folha de
         estilo que ele já tem em cache, e uma mudança de visual simplesmente
         não chega — foi o que aconteceu ao trocar o modo escuro pelo claro:
         a CSS antiga em cache continuou pintando o documento de preto. --}}
    <link rel="stylesheet" href="{{ asset('css/snrfit-doc.css').'?v='.(@filemtime(public_path('css/snrfit-doc.css')) ?: 1) }}">
    @stack('doc-estilo')
</head>
<body>

<div class="doc-acoes">
    <button type="button" class="doc-btn" onclick="window.print()">Imprimir / salvar PDF</button>
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
        <div class="doc-assina">
            <div class="linha"></div>
            @yield('doc-assinatura')
        </div>
    @endif
</div>

<div class="doc-pe">
    <span>@yield('doc-rodape', 'SnrFit')</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

@stack('doc-script')
</body>
</html>
