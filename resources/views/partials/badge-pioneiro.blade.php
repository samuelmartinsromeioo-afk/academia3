{{--
    Selo de "Pioneiro" — estilo "verificado do Instagram": um disco recortado
    (starburst de 12 pontas) com um check dentro. Verde chumbo metálico.

    Parâmetros (todos opcionais):
      - $posicao : posição no estado (1..limite)
      - $estado  : UF (para o texto do tooltip)
      - $tipo    : chave de config('pioneiro.rotulos') — personal (padrão),
                   nutricionista, academia, studio, loja
      - $tamanho : lado do selo em px (padrão 18)
--}}
@php
    $posicao = $posicao ?? null;
    $estado  = $estado ?? null;
    $tipo    = $tipo ?? 'personal';
    $tamanho = $tamanho ?? 18;

    $rotulo = config('pioneiro.rotulos.' . $tipo, 'cadastros');
    $limite = (int) config('pioneiro.limite_por_estado', 100);

    $cores = config('pioneiro.cor');

    $titulo = $posicao
        ? 'Pioneiro: um dos ' . $limite . ' primeiros ' . $rotulo . ($estado ? ' de ' . $estado : '') . ' na plataforma (#' . $posicao . ')'
        : 'Pioneiro: um dos primeiros ' . $rotulo . ' da plataforma';

    // id único: o gradiente é referenciado por id e o selo aparece várias vezes na mesma página.
    $gid = 'pio' . uniqid();
@endphp
<svg class="selo-pioneiro" role="img" viewBox="0 0 24 24"
     width="{{ $tamanho }}" height="{{ $tamanho }}"
     style="display:inline-block; vertical-align:-0.15em; flex:none;"
     aria-label="{{ $titulo }}">
    <title>{{ $titulo }}</title>
    <defs>
        <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%"   stop-color="{{ $cores['clara'] }}"/>
            <stop offset="100%" stop-color="{{ $cores['base'] }}"/>
        </linearGradient>
    </defs>
    {{-- Starburst de 12 pontas, o mesmo contorno do selo verificado --}}
    <path fill="url(#{{ $gid }})"
          d="M12 .7l2.6 2.24 3.4-.5.98 3.3 3.3.98-.5 3.4L24 12l-2.22 2.6.5 3.4-3.3.98-.98 3.3-3.4-.5L12 23.3l-2.6-2.22-3.4.5-.98-3.3-3.3-.98.5-3.4L0 12l2.22-2.58-.5-3.4 3.3-.98.98-3.3 3.4.5z"/>
    {{-- Check --}}
    <path fill="none" stroke="{{ $cores['check'] }}" stroke-width="2.2"
          stroke-linecap="round" stroke-linejoin="round"
          d="M7.4 12.3l3.1 3.1 6.1-6.5"/>
</svg>
