{{--
    Selo + atribuição para telas de GESTÃO DE VALORES: saldo, saque, extrato,
    conta de recebimentos e bônus de indicação.

    Por que não reusar `partials/asaas-pagamento`: aquele é o enquadramento de
    CHECKOUT e diz "pagamento processado com segurança pela Asaas" — frase certa
    para quem está pagando, errada para quem está olhando o próprio saldo ou
    pedindo um saque. Aqui o que o playbook de BaaS exige que fique claro é
    outra coisa: a conta onde o dinheiro está e a transferência que vai sair são
    operadas pelo Asaas, não pela SNR FIT.

    O playbook pede o selo em "todos os fluxos, páginas e comunicações que
    envolvam operações financeiras de pagamentos", e nomeia a tela de criação de
    subconta. Saldo e saque são justamente "gestão de valores", a hipótese que a
    Resolução Conjunta nº 16/2025 descreve.

    Parâmetros, todos opcionais:
      $tema     -> 'claro' para fundo claro; padrão escuro.
      $suporte  -> true (padrão) imprime também a linha de suporte do Asaas, que
                   o playbook exige estar acessível em operação financeira.
      $texto    -> sobrescreve a frase de atribuição (ex.: tela de subconta).
--}}
@php
    $tema = $tema ?? 'escuro';
    $suporte = $suporte ?? true;

    $corTexto = $tema === 'claro' ? '#555' : 'rgba(255,255,255,0.6)';
    $corBorda = $tema === 'claro' ? 'rgba(0,0,0,0.08)' : 'rgba(255,255,255,0.08)';
    $psp = \App\Support\Empresa::get('psp_razao_social');
    $marca = \App\Support\Empresa::get('marca');

    $texto = $texto ?? "A sua conta de recebimentos, a liquidação e as transferências são
        operadas por <strong>{$psp}</strong>, instituição de pagamento autorizada a funcionar pelo
        Banco Central do Brasil. A {$marca} não guarda nem movimenta esses valores.";
@endphp
<div class="asaas-recebimentos-selo"
     style="display:flex; flex-direction:column; align-items:center; gap:8px; margin-top:16px;
            padding-top:14px; border-top:1px solid {{ $corBorda }};">
    {{-- `imprimivel` explícito: @include herda o escopo de quem inclui, e um
         `$imprimivel` da página de fora vazaria para dentro do selo. --}}
    @include('partials.selo-asaas', ['tema' => $tema, 'altura' => 28, 'imprimivel' => false])

    <p style="margin:0; font-size:0.72rem; line-height:1.45; text-align:center; color:{{ $corTexto }};">
        {!! $texto !!}
    </p>

    @if($suporte)
        <div style="text-align:center;">
            @include('partials.asaas-suporte', ['compacto' => true, 'tema' => $tema])
        </div>
    @endif
</div>
