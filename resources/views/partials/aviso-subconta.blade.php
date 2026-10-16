{{--
    Aviso de conta de recebimentos pendente, para o próprio recebedor.

    Existe porque o bloqueio da subconta era invisível para quem mais precisava
    saber: o profissional não vendia e não tinha como descobrir o motivo. O
    erro aparecia só do lado do ALUNO, no checkout, com o texto cru do Asaas —
    e o aluno, naturalmente, desistia em silêncio.

    Aqui vai o detalhe que o aluno não vê: qual parte do cadastro está pendente
    e o link para resolver. O público é outro, então a linguagem é outra.

    Espera `$conta` (personal, academia, studio ou loja). Não renderiza nada
    quando está tudo certo — banner permanente vira ruído e some da vista.
--}}
@php
    $avisoSub = $conta ?? null;
    $mostrarAviso = $avisoSub
        && method_exists($avisoSub, 'podeReceberPagamento')
        && ! $avisoSub->podeReceberPagamento();
    $detalheSub = $mostrarAviso ? $avisoSub->detalheSubconta() : null;
@endphp

@if($mostrarAviso)
    <div style="background:rgba(255,82,82,0.10); border:1px solid rgba(255,82,82,0.45);
                border-radius:14px; padding:18px 20px; margin-bottom:24px;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <i class="ph ph-warning-circle" style="color:#ff8a8a; font-size:1.2rem;"></i>
            <strong style="color:#ff8a8a; font-size:0.95rem;">
                Sua conta de recebimentos está pendente
            </strong>
        </div>

        <p style="font-size:0.88rem; line-height:1.6; color:#cfcfcf; margin-bottom:12px;">
            Enquanto o cadastro não for aprovado pelo processador de pagamentos,
            <strong>os alunos não conseguem contratar você</strong> — o botão de pagamento
            fica indisponível no app e no site. Isso não afeta nada do que você já recebeu.
        </p>

        @if(! empty($detalheSub['pendencias']))
            <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:6px; font-weight:700;">
                O que falta:
            </p>
            <ul style="font-size:0.84rem; line-height:1.7; color:#cfcfcf; margin:0 0 12px 18px;">
                @foreach($detalheSub['pendencias'] as $pendencia)
                    <li>{{ $pendencia }}</li>
                @endforeach
            </ul>
        @elseif(! empty($detalheSub['partes']))
            {{-- Sem lista de documentos, mas sabendo qual bloco reprovou: já
                 orienta melhor que "procure o suporte". --}}
            <ul style="font-size:0.84rem; line-height:1.7; color:#cfcfcf; margin:0 0 12px 18px;">
                @foreach($detalheSub['partes'] as $parte => $situacao)
                    @if($situacao && $situacao !== 'APPROVED')
                        <li>
                            {{ [
                                'commercialInfo' => 'Dados comerciais',
                                'bankAccountInfo' => 'Dados bancários',
                                'documentation' => 'Documentação',
                                'general' => 'Situação geral',
                            ][$parte] ?? $parte }}:
                            <strong>{{ $situacao }}</strong>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif

        @if(! empty($detalheSub['onboarding_url']))
            <a href="{{ $detalheSub['onboarding_url'] }}" target="_blank" rel="noopener"
               style="display:inline-flex; align-items:center; gap:8px; background:var(--primary);
                      color:#000; font-weight:900; font-size:0.82rem; padding:11px 18px;
                      border-radius:10px; text-decoration:none;">
                <i class="ph ph-arrow-square-out"></i> Enviar documentos e regularizar
            </a>
        @else
            {{-- Sem link ainda (subconta nunca sincronizada ou Asaas não devolveu):
                 não inventamos URL. O suporte resolve e o link aparece na próxima
                 sincronização. --}}
            <p style="font-size:0.82rem; color:var(--text-muted); margin:0;">
                Fale com o nosso suporte em
                <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}"
                   style="color:var(--primary);">{{ \App\Support\Empresa::get('email_suporte') }}</a>
                que a gente te ajuda a regularizar.
            </p>
        @endif

        @if(! empty($detalheSub['verificado_em']))
            <p style="font-size:0.74rem; color:var(--text-muted); margin:10px 0 0;">
                Situação verificada em
                {{ \Carbon\Carbon::parse($detalheSub['verificado_em'])->format('d/m/Y \à\s H:i') }}.
                Depois de enviar os documentos, a liberação pode levar algumas horas.
            </p>
        @endif
    </div>
@endif
