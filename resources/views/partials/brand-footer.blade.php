{{-- Rodapé de marca SNR·FIT com a voz da marca.
     Parâmetro opcional: $voz -> frase de assinatura emocional. --}}
@php
    $voz = $voz ?? 'Feito pra quem <strong>não para</strong>.';
@endphp
<footer class="snr-footer">
    <a class="snr-logo" href="{{ url('/') }}">SNR<span>FIT</span></a>
    <div class="snr-voice" style="margin-top:8px;">{!! $voz !!}</div>
    <div class="snr-asaas-selo" style="margin-top:16px;">
        @include('partials.selo-asaas', ['altura' => 40, 'imprimivel' => false])
    </div>
    {{-- Quem responde pelo dinheiro, dito no rodapé de toda página que tem este
         partial — não só no checkout. O selo sozinho mostra a marca; esta linha
         é que atribui a responsabilidade pela liquidação. --}}
    <div style="margin-top:8px; font-size:0.7rem; opacity:0.55; max-width:420px; margin-left:auto; margin-right:auto; line-height:1.45;">
        Serviços financeiros e liquidação dos pagamentos por
        <strong>{{ \App\Support\Empresa::get('psp_razao_social') }}</strong>, instituição
        autorizada e regulada pelo Banco Central do Brasil.
        A {{ \App\Support\Empresa::get('marca') }} não é instituição de pagamento.
    </div>
    <div style="margin-top:10px; font-size:0.72rem; opacity:0.6;">
        © {{ date('Y') }} SnrFit — Treino que vira história.
    </div>
</footer>
