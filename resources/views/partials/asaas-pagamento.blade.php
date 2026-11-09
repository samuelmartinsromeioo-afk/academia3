{{-- Selo obrigatório do programa BaaS Asaas + aviso explícito de que o
     pagamento é processado pela Asaas. Usar dentro de fluxos de checkout/pagamento.

     A <img> do selo vive em `partials/selo-asaas` (o átomo) — este partial é só
     o enquadramento de checkout (frase + borda). Não traga a <img> de volta
     para cá: eram duas cópias do caminho do arquivo e da altura.

     Parâmetro opcional:
       $tema -> 'claro' usa o selo positivo (fundo claro);
                qualquer outro valor usa o negativo-branco (fundo escuro, padrão). --}}
@php
    $tema = $tema ?? 'escuro';
    $corTexto = $tema === 'claro' ? '#555' : 'rgba(255,255,255,0.6)';
@endphp
<div class="asaas-pagamento-selo" style="display:flex; flex-direction:column; align-items:center; gap:8px; margin-top:18px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.08);">
    {{-- `imprimivel` explícito: @include herda as variáveis do escopo de quem
         inclui, e sem fixá-lo aqui um `$imprimivel` definido na página de fora
         vazaria para dentro do selo. --}}
    @include('partials.selo-asaas', ['tema' => $tema, 'altura' => 30, 'imprimivel' => false])
    <p style="margin:0; font-size:0.72rem; line-height:1.4; text-align:center; color:{{ $corTexto }};">
        <i class="ph ph-lock-key" style="vertical-align:-1px;"></i>
        Pagamento processado com segurança pela <strong>Asaas</strong>, instituição responsável pelos serviços financeiros desta plataforma.
    </p>
</div>
