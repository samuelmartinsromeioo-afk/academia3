{{-- Rodapé de marca SNR·FIT com a voz da marca.
     Parâmetro opcional: $voz -> frase de assinatura emocional. --}}
@php
    $voz = $voz ?? 'Feito pra quem <strong>não para</strong>.';
@endphp
<footer class="snr-footer">
    <a class="snr-logo" href="{{ url('/') }}">SNR<span>FIT</span></a>
    <div class="snr-voice" style="margin-top:8px;">{!! $voz !!}</div>
    <div style="margin-top:10px; font-size:0.72rem; opacity:0.6;">
        © {{ date('Y') }} SnrFit — Treino que vira história.
    </div>
</footer>
