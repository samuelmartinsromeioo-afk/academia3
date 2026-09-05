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
            @foreach ($modelo->campos as $campo)
                @php $label=$campo['label']; $tipo=$campo['tipo']??'texto'; @endphp
                <div style="margin-bottom:14px;">
                    <label>{{ $label }}</label>
                    @if ($tipo==='textarea')
                        <textarea name="respostas[{{ $label }}]" rows="3"></textarea>
                    @elseif ($tipo==='numero')
                        <input type="number" step="any" name="respostas[{{ $label }}]">
                    @elseif ($tipo==='sim_nao')
                        <select name="respostas[{{ $label }}]"><option value="">—</option><option>Sim</option><option>Não</option></select>
                    @elseif ($tipo==='opcoes')
                        <select name="respostas[{{ $label }}]"><option value="">—</option>@foreach (($campo['opcoes']??[]) as $op)<option>{{ $op }}</option>@endforeach</select>
                    @else
                        <input type="text" name="respostas[{{ $label }}]">
                    @endif
                </div>
            @endforeach
            <button class="btn" style="width:100%; justify-content:center;">Enviar respostas</button>
        </form>
        @endif
    </div>
@endsection
