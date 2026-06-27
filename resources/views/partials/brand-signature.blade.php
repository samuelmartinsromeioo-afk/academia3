{{-- Assinatura da marca SNR·FIT (logo + slogan).
     Parâmetros opcionais:
       $href     -> link do logo (default: home/login)
       $tagline  -> mostra o slogan abaixo do logo (default: true)
       $slogan   -> texto do slogan (default: oficial)
--}}
@php
    $href    = $href    ?? url('/');
    $tagline = $tagline ?? true;
    $slogan  = $slogan  ?? 'Treino que vira história';
@endphp
<div class="snr-lockup">
    <a class="snr-logo" href="{{ $href }}">SNR<span>FIT</span></a>
    @if($tagline)
        <span class="snr-tagline">{{ $slogan }}</span>
    @endif
</div>
