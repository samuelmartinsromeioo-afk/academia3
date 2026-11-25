{{--
    Canais de suporte do Asaas, para o cliente final.

    POR QUE ESTE PARTIAL EXISTE: o "Playbook de BaaS para clientes Asaas"
    obriga o tomador a garantir que o cliente final SAIBA que pode acionar o
    Asaas diretamente para suporte das operações financeiras de pagamentos — e
    a ter acesso claro a esses canais sempre que usar essas operações. O site
    tinha o nosso suporte em toda parte e o do Asaas em lugar nenhum: quem
    tinha problema de conta de recebimentos, liquidação ou saque era instruído
    a "procurar o suporte do Asaas" sem nunca receber o endereço dele.

    NÃO substitui `empresa.email_suporte`. Os escopos são diferentes e a
    distinção é a própria divisão de responsabilidade do modelo BaaS: produto,
    preço e instrução da cobrança são nossos; conta de pagamento, custódia,
    liquidação, saque e bloqueio são do Asaas.

    O 0800 sai com a nota "somente pessoa jurídica" porque é o que o playbook
    informa. A maioria dos nossos clientes finais é pessoa física, e um número
    que não atende PF, divulgado sem a ressalva, manda a pessoa para uma
    ligação que termina sem resposta. Para PF o caminho é o e-mail.

    Parâmetros, todos opcionais:
      $compacto -> true imprime uma única linha de texto (para rodapé de modal
                   e de página legal); false (padrão) imprime o bloco com
                   título e lista de canais.
      $tema     -> 'claro' para fundo claro; qualquer outro valor = fundo
                   escuro (padrão).
--}}
@php
    $compacto = $compacto ?? false;
    $tema = $tema ?? 'escuro';

    $psp = \App\Support\Empresa::get('psp_razao_social');
    $pspTelefone = \App\Support\Empresa::get('psp_suporte_telefone');
    $pspTelefoneNota = \App\Support\Empresa::get('psp_suporte_telefone_nota');
    $pspEmail = \App\Support\Empresa::get('psp_suporte_email');

    $corTexto = $tema === 'claro' ? '#555' : 'rgba(255,255,255,0.65)';
    $corLink = $tema === 'claro' ? '#0b7285' : 'var(--primary, #7cff00)';
    $corTitulo = $tema === 'claro' ? '#222' : '#fff';
    // Tel sem espaços: alguns discadores não limpam o número.
    $telLimpo = preg_replace('/\D/', '', $pspTelefone);
@endphp

@if($compacto)
    <p style="margin:0; font-size:0.74rem; line-height:1.5; color:{{ $corTexto }};">
        Suporte das operações financeiras (conta de pagamento, liquidação e saque) direto com o
        Asaas:
        @if($pspEmail)
            <a href="mailto:{{ $pspEmail }}" style="color:{{ $corLink }};">{{ $pspEmail }}</a>
        @endif
        @if($pspTelefone)
            · {{ $pspTelefone }}@if($pspTelefoneNota) ({{ $pspTelefoneNota }})@endif
        @endif
    </p>
@else
    <div style="font-size:0.84rem; line-height:1.6; color:{{ $corTexto }};">
        <p style="margin:0 0 8px; font-weight:800; color:{{ $corTitulo }};">
            <i class="ph ph-lifebuoy" style="vertical-align:-2px;"></i>
            Suporte do Asaas — operações financeiras
        </p>
        <p style="margin:0 0 10px;">
            Os serviços financeiros desta plataforma são prestados por <strong>{{ $psp }}</strong>,
            instituição de pagamento autorizada a funcionar pelo Banco Central do Brasil. Você
            <strong>pode acionar o Asaas diretamente</strong> sobre conta de pagamento, liquidação,
            transferências e saques:
        </p>
        <ul style="margin:0 0 10px 18px; padding:0;">
            @if($pspTelefone)
                <li>
                    <a href="tel:{{ $telLimpo }}" style="color:{{ $corLink }};">{{ $pspTelefone }}</a>
                    @if($pspTelefoneNota)
                        <span style="opacity:0.8;">— {{ $pspTelefoneNota }}</span>
                    @endif
                </li>
            @endif
            @if($pspEmail)
                <li><a href="mailto:{{ $pspEmail }}" style="color:{{ $corLink }};">{{ $pspEmail }}</a></li>
            @endif
        </ul>
        <p style="margin:0;">
            Dúvida sobre preço, agendamento, cancelamento ou qualquer coisa do produto é com a
            gente:
            <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}" style="color:{{ $corLink }};">{{ \App\Support\Empresa::get('email_suporte') }}</a>.
        </p>
    </div>
@endif
