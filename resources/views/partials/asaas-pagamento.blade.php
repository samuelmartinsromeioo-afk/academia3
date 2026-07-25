{{-- Selo obrigatório do programa BaaS Asaas + aviso explícito de que o
     pagamento é processado pela Asaas. Usar dentro de fluxos de checkout/pagamento.
     Parâmetro opcional:
       $tema -> 'claro' usa o selo positivo (fundo claro);
                qualquer outro valor usa o negativo-branco (fundo escuro, padrão). --}}
@php
    $tema = $tema ?? 'escuro';
    $selo = $tema === 'claro' ? 'selo-asaas-positivo.svg' : 'selo-asaas-negativo-branco.svg';
    $corTexto = $tema === 'claro' ? '#555' : 'rgba(255,255,255,0.6)';
@endphp
<div class="asaas-pagamento-selo" style="display:flex; flex-direction:column; align-items:center; gap:8px; margin-top:18px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.08);">
    <a href="https://asaas.com" target="_blank" rel="noopener" aria-label="Serviços financeiros fornecidos pela Asaas">
        <img src="{{ asset('img/asaas/' . $selo) }}"
             alt="Serviços financeiros Asaas" height="30" style="height:30px; width:auto; opacity:0.9;">
    </a>
    <p style="margin:0; font-size:0.72rem; line-height:1.4; text-align:center; color:{{ $corTexto }};">
        <i class="ph ph-lock-key" style="vertical-align:-1px;"></i>
        Pagamento processado com segurança pela <strong>Asaas</strong>, instituição responsável pelos serviços financeiros desta plataforma.
    </p>
</div>
