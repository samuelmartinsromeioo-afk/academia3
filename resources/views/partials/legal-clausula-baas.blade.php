{{--
    Cláusula de Prestação de Serviços Financeiros — MODELO DO ASAAS.

    Este texto é o modelo de cláusula do "Playbook de BaaS para clientes Asaas"
    (slide 11), transcrito. Ele existe num partial, e não copiado dentro dos
    seis documentos legais, por um motivo que o próprio playbook dá: o tomador
    "não [pode] alterar nem criar documentos legais relacionados ao
    processamento financeiro de pagamentos". Seis cópias é a forma mais segura
    de o texto ser alterado sem ninguém perceber — basta alguém "melhorar" uma
    delas. Aqui ele é um só, e o teste `ConformidadeBaasAsaasTest` compara o que
    sai nos seis documentos com as frases do modelo.

    O QUE PODE SER SUBSTITUÍDO, e só isto: `[NOME DA TOMADORA]`, que no modelo é
    um espaço reservado, pela razão social do titular da plataforma. Nome da
    instituição, CNPJ e site saem de `config/empresa.php` (chaves `psp_*`), que
    já é a fonte única dos seis documentos.

    Sobre o nome do Asaas: o modelo escreve a razão social completa no primeiro
    parágrafo ("ASAAS GESTÃO FINANCEIRA INSTITUIÇÃO DE PAGAMENTOS S.A.") e a
    forma curta no terceiro. Aqui os dois usam `empresa.psp_razao_social` — a
    forma completa, que é a que nomeia a licença —, porque um documento que
    chama a mesma instituição por dois nomes diferentes convida à dúvida sobre
    serem duas pessoas jurídicas, que é exatamente o que a cláusula existe para
    dissipar. Nada do conteúdo normativo muda com isso.

    Parâmetro opcional:
      $titulo -> número/rótulo do título (ex.: '0.2.'). Sem ele, sai sem número,
                 para os documentos de perfil, que têm numeração própria.
--}}
@php
    $tituloClausula = $titulo ?? null;
    $pspNome = \App\Support\Empresa::get('psp_razao_social');
    $tomadora = \App\Support\Empresa::get('razao_social');
@endphp

<h2 id="clausula-servicos-financeiros">
    {{ $tituloClausula ? $tituloClausula.' ' : '' }}PRESTAÇÃO DE SERVIÇOS FINANCEIROS
</h2>

<div class="highlighted">
    <p style="margin-bottom:12px;">
        Os serviços financeiros e de pagamentos disponibilizados por meio da presente plataforma,
        incluindo abertura e manutenção de conta de pagamento, processamento de transações, emissão
        de boletos, transferências, pagamentos e demais movimentações de valores, são prestados pelo
        <strong>{{ $pspNome }}</strong>, instituição de pagamento autorizada a funcionar pelo
        <strong>Banco Central do Brasil</strong>.
    </p>

    <p style="margin-bottom:12px;">
        A <strong>{{ $tomadora }}</strong> atua exclusivamente como integradora tecnológica e
        distribuidora da experiência do produto, não sendo instituição financeira ou de pagamento,
        nem realizando intermediação financeira em nome próprio.
    </p>

    <p style="margin-bottom:12px;">
        O cliente declara ciência de que o relacionamento financeiro / de pagamentos e a
        responsabilidade regulatória pelos serviços acima descritos são do
        <strong>{{ $pspNome }}</strong>, nos termos da regulamentação vigente.
    </p>

    <div style="display:flex; justify-content:center; margin:4px 0 2px;">
        @include('partials.selo-asaas', ['altura' => 34, 'imprimivel' => true])
    </div>
</div>

{{-- O playbook exige que o cliente final saiba que pode acionar o Asaas: num
     documento legal, dizer quem responde sem dizer como falar com ele deixa o
     direito sem porta de entrada.

     Tema escuro porque a página legal é escura na TELA; o bloco `@media print`
     abaixo é que a prepara para a folha branca. Sem ele o texto sairia branco
     no branco: as cores do partial são inline, e estilo inline vence a regra
     de impressão do layout (`.content p { color: #333 }`). O canal de suporte
     tem de sair no papel — é parte da cláusula, não enfeite de tela. --}}
<div class="asaas-suporte-legal" style="margin:14px 0 6px;">
    @include('partials.asaas-suporte')
</div>
@once
    <style>
        @media print {
            .asaas-suporte-legal, .asaas-suporte-legal * { color: #333 !important; }
            .asaas-suporte-legal strong { color: #000 !important; }
            .asaas-suporte-legal a { color: #0b7285 !important; }
        }
    </style>
@endonce
