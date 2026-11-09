{{--
    Envio/reenvio de documento da subconta, pelo painel do próprio recebedor.

    Existe porque o cadastro resolve só o PRIMEIRO envio. O Asaas pode recusar o
    documento dias depois (foto ilegível, documento vencido) e, sem reenvio por
    aqui, a pessoa voltaria a depender do painel dele — que é exatamente o que
    esta funcionalidade veio evitar.

    Espera `$conta` (personal, academia, studio ou loja). Incluído por
    `partials/aviso-subconta`, que já só aparece quando há algo pendente.
--}}
@php
    $docsConta = \App\Models\DocumentoSubconta::query()
        ->where('recebedor_type', $conta->getMorphClass())
        ->where('recebedor_id', $conta->id)
        ->orderByDesc('id')
        ->get();

    // Um documento por tipo é o que interessa: o Asaas substitui o arquivo
    // anterior do mesmo grupo, então mostrar o histórico inteiro confundiria
    // ("enviei 3 RGs?"). `orderByDesc('id')` acima garante que o mais novo vence.
    $docsAtuais = $docsConta->unique('tipo');

    $rotulosStatus = [
        \App\Models\DocumentoSubconta::STATUS_PENDENTE => ['Aguardando envio', '#f4be16'],
        \App\Models\DocumentoSubconta::STATUS_ENVIADO => ['Em análise no Asaas', '#7cff00'],
        \App\Models\DocumentoSubconta::STATUS_APROVADO => ['Aprovado', '#7cff00'],
        \App\Models\DocumentoSubconta::STATUS_RECUSADO => ['Recusado — envie outro', '#ff8a8a'],
        \App\Models\DocumentoSubconta::STATUS_SO_LINK => ['Só pelo link do Asaas', '#f4be16'],
    ];
@endphp

<div style="margin-top:16px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.08);">

    @if(session('ok_documento'))
        <p style="font-size:0.84rem; line-height:1.55; color:#7cff00; margin:0 0 12px;">
            <i class="ph ph-check-circle"></i> {{ session('ok_documento') }}
        </p>
    @endif

    @if(session('erro_documento'))
        <p style="font-size:0.84rem; line-height:1.55; color:#ff8a8a; margin:0 0 12px;">
            <i class="ph ph-warning-circle"></i> {{ session('erro_documento') }}
        </p>
    @endif

    @if($errors->has('documento') || $errors->has('tipo'))
        <p style="font-size:0.84rem; line-height:1.55; color:#ff8a8a; margin:0 0 12px;">
            <i class="ph ph-warning-circle"></i>
            {{ $errors->first('documento') ?: $errors->first('tipo') }}
        </p>
    @endif

    @if($docsAtuais->isNotEmpty())
        <p style="font-size:0.82rem; color:var(--text-muted); margin:0 0 6px; font-weight:700;">
            Documentos que você já enviou:
        </p>
        <ul style="list-style:none; margin:0 0 14px; padding:0; font-size:0.84rem;">
            @foreach($docsAtuais as $doc)
                @php
                    [$rotulo, $cor] = $rotulosStatus[$doc->status] ?? ['Enviado', '#cfcfcf'];
                @endphp
                <li style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding:6px 0;
                           border-bottom:1px solid rgba(255,255,255,0.05);">
                    <i class="ph ph-file-text" style="color:var(--text-muted);"></i>
                    <span style="color:#cfcfcf;">{{ $doc->rotulo() }}</span>
                    <span style="color:{{ $cor }}; font-weight:700; font-size:0.78rem;">{{ $rotulo }}</span>

                    {{-- O motivo da recusa é a informação mais acionável que o
                         Asaas devolve, e ficava só no banco. --}}
                    @if($doc->status === \App\Models\DocumentoSubconta::STATUS_RECUSADO && $doc->motivo_recusa)
                        <span style="flex-basis:100%; font-size:0.78rem; color:#ff8a8a;">
                            Motivo: {{ $doc->motivo_recusa }}
                        </span>
                    @endif

                    {{-- Só-link não é falha do arquivo: é o Asaas recusando API
                         neste grupo. Mostrar o link em vez de pedir outro
                         arquivo evita o laço de reenviar o que nunca entra. --}}
                    @if($doc->status === \App\Models\DocumentoSubconta::STATUS_SO_LINK && $doc->onboarding_url)
                        <a href="{{ $doc->onboarding_url }}" target="_blank" rel="noopener"
                           style="flex-basis:100%; font-size:0.78rem; color:var(--primary);">
                            Enviar este documento no site do Asaas
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('documentos.subconta.store') }}" method="POST" enctype="multipart/form-data"
          style="display:flex; flex-direction:column; gap:10px;">
        @csrf

        <p style="font-size:0.82rem; color:var(--text-muted); margin:0; font-weight:700;">
            Enviar documento pelo site:
        </p>

        <select name="tipo" required
                style="background:#16181d; border:1px solid rgba(255,255,255,0.12); color:#fff;
                       border-radius:10px; padding:10px 12px; font-size:0.84rem; font-family:inherit;">
            <option value="{{ \App\Models\DocumentoSubconta::TIPO_IDENTIFICACAO }}">
                Documento de identidade (RG ou CNH)
            </option>
            <option value="{{ \App\Models\DocumentoSubconta::TIPO_SELFIE }}">
                Selfie segurando o documento
            </option>
            {{-- Contrato social e MEI só fazem sentido para PJ. Personal é
                 pessoa física e não tem nenhum dos dois: oferecê-los a ele
                 seria convidar a enviar documento que o Asaas vai recusar. --}}
            @if(! ($conta instanceof \App\Models\Cadastro\Personal))
                <option value="{{ \App\Models\DocumentoSubconta::TIPO_CONTRATO_SOCIAL }}">
                    Contrato social
                </option>
                <option value="{{ \App\Models\DocumentoSubconta::TIPO_CERTIFICADO_MEI }}">
                    Certificado MEI
                </option>
            @endif
        </select>

        <input type="file" name="documento" required accept="image/jpeg,image/png,application/pdf"
               style="background:#16181d; border:1px solid rgba(255,255,255,0.12); color:#cfcfcf;
                      border-radius:10px; padding:10px 12px; font-size:0.82rem; font-family:inherit;">

        {{-- O `accept` acima e esta frase repetem de propósito a regra de
             `DocumentoSubcontaService::regraValidacao()`: HEIC é o padrão da
             câmera do iPhone e a análise do Asaas não o aceita. Dizer antes
             evita a pessoa descobrir isso só na recusa, dias depois. --}}
        <p style="font-size:0.74rem; color:var(--text-muted); margin:0; line-height:1.5;">
            JPG, PNG ou PDF, até 10 MB. Precisa estar legível e sem cortes.
            Foto de iPhone em HEIC não é aceita — use Ajustes &gt; Câmera &gt;
            Formatos &gt; "Mais compatível", ou envie um print.
        </p>

        <button type="submit"
                style="background:var(--primary); color:#000; font-weight:900; font-size:0.82rem;
                       border:none; padding:11px 18px; border-radius:10px; cursor:pointer;
                       align-self:flex-start;">
            <i class="ph ph-upload-simple"></i> Enviar documento
        </button>
    </form>
</div>
