{{--
    Atribuição de responsabilidade financeira no rodapé dos e-mails de dinheiro.

    O "Playbook de BaaS para clientes Asaas" exige a aplicação da marca e a
    atribuição "em todos os fluxos, páginas e COMUNICAÇÕES que envolvam
    operações financeiras de pagamentos" — e e-mail de pagamento confirmado,
    pagamento recusado e repasse são exatamente isso. Nenhum deles dizia quem
    processa o pagamento: o aluno lia "seu pagamento foi processado" assinado
    pela SNR FIT, que é a impressão que o programa veda.

    POR QUE AQUI NÃO ENTRA O SELO EM IMAGEM: o selo é SVG, e o Gmail — o cliente
    de e-mail da maioria dos destinatários — não renderiza SVG; vários clientes
    também bloqueiam imagem remota por padrão. Um selo que não aparece é pior
    que a menção textual, porque dá a aparência de cumprido sem cumprir. O
    playbook prevê a menção textual para documentos; é ela que serve a este
    canal. Se um dia houver PNG homologado, ele entra aqui.

    Texto puro, sem classe CSS: estes cinco e-mails têm cada um o seu `<style>`
    e nenhum compartilha layout. Estilo inline é o que funciona igual nos cinco.
--}}
<div style="font-size:11px; line-height:1.5; color:#999; text-align:center; padding:0 32px 18px;">
    Serviços financeiros e liquidação dos pagamentos por
    <strong style="color:#777;">{{ \App\Support\Empresa::get('psp_razao_social') }}</strong>,
    instituição de pagamento autorizada a funcionar pelo Banco Central do Brasil.
    A {{ \App\Support\Empresa::get('marca') }} não é instituição de pagamento e não guarda nem
    movimenta esses valores.
    <br>
    Dúvidas sobre a operação financeira (conta de pagamento, liquidação, transferências e saques)
    podem ser tratadas direto com o Asaas:
    @if($pspEmailAsaas = \App\Support\Empresa::get('psp_suporte_email'))
        <a href="mailto:{{ $pspEmailAsaas }}" style="color:#777;">{{ $pspEmailAsaas }}</a>
    @endif
    @if($pspTelAsaas = \App\Support\Empresa::get('psp_suporte_telefone'))
        · {{ $pspTelAsaas }}@if($notaTelAsaas = \App\Support\Empresa::get('psp_suporte_telefone_nota')) ({{ $notaTelAsaas }})@endif
    @endif
</div>
