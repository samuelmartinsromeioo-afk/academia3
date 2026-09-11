@extends('nutri.portal.layout')
@section('titulo','Questionário')

@section('conteudo')
    <div class="card">
        <strong>Questionário pré-consulta</strong>
        <div class="muted" style="font-size:.82rem; margin:6px 0 14px;">Responder antes ajuda a aproveitar melhor sua consulta.</div>

        @if (!$modelo)
            <div class="muted" style="text-align:center; padding:20px;">Seu nutricionista ainda não configurou um questionário.</div>
        @else
        <form method="POST" action="{{ route('portal.anamnese.salvar',$token) }}">
            @csrf
            <input type="hidden" name="modelo_id" value="{{ $modelo->id }}">

            {{-- No celular, seção vira accordion: o paciente responde um bloco por
                 vez em vez de encarar 60 perguntas de uma vez. A primeira já abre. --}}
            @foreach ($modelo->camposPorSecao() as $secao => $campos)
                <details @if($loop->first) open @endif
                         style="border-bottom:1px solid var(--border); padding:4px 0;">
                    <summary style="cursor:pointer; font-weight:700; font-size:.9rem; padding:12px 0; list-style:none; display:flex; justify-content:space-between; align-items:center;">
                        {{ $secao }}
                        <span class="muted" style="font-weight:400; font-size:.72rem;">{{ count($campos) }}</span>
                    </summary>
                    <div style="padding-bottom:10px;">
                        @foreach ($campos as $campo)
                            @include('nutri.anamnese._campo', ['campo' => $campo, 'valor' => null])
                        @endforeach
                    </div>
                </details>
            @endforeach

            <button class="btn" style="width:100%; justify-content:center; margin-top:16px;">Enviar respostas</button>
        </form>
        @endif
    </div>
@endsection
