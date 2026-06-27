{{--
    Selo de "Pioneiro": personal entre os 100 primeiros a se cadastrar no seu estado.
    Parâmetros (opcionais):
      - $posicao : posição do personal no estado (1..100)
      - $estado  : UF do personal (para o texto do tooltip)
--}}
@php
    $posicao = $posicao ?? null;
    $estado  = $estado ?? null;
    $titulo  = $posicao
        ? 'Um dos 100 primeiros personais' . ($estado ? ' de ' . $estado : '') . ' a entrar na plataforma (#' . $posicao . ')'
        : 'Um dos primeiros personais da plataforma';
@endphp
<span class="badge-pioneiro" title="{{ $titulo }}"
      style="display:inline-flex; align-items:center; gap:5px; background:linear-gradient(135deg,#FFE259,#FFA751); color:#1a1200; font-weight:800; font-size:0.62rem; text-transform:uppercase; letter-spacing:0.5px; padding:4px 10px; border-radius:20px; box-shadow:0 2px 10px rgba(255,170,40,0.35); border:1px solid rgba(255,210,80,0.7); white-space:nowrap;">
    <i class="ph-fill ph-crown" style="color:#000;"></i> Pioneiro
</span>
