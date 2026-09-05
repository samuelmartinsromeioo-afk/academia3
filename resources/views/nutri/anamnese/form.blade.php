@extends('layouts.nutri')
@section('titulo', 'Anamnese')

@section('conteudo')
    <div class="topbar">
        <div><h1>Anamnese — {{ $paciente->nome }}</h1><div class="sub">Preencha ou escolha outro modelo.</div></div>
        <a href="{{ route('nutri.pacientes.show',$paciente->id) }}" class="btn btn-ghost btn-sm"><i class="ph ph-arrow-left"></i> Voltar</a>
    </div>

    <form method="GET" class="card" style="margin-bottom:18px; display:flex; gap:12px; align-items:flex-end;">
        <div style="flex:1;">
            <label>Modelo de anamnese</label>
            <select name="modelo" onchange="this.form.submit()">
                @foreach ($modelos as $m)
                    <option value="{{ $m->id }}" @selected($modelo && $modelo->id===$m->id)>{{ $m->nome }} ({{ \App\Models\Nutri\AnamneseModelo::PERFIS[$m->perfil] ?? $m->perfil }})</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('nutri.anamnese.modelos') }}" class="btn btn-ghost">Gerenciar modelos</a>
    </form>

    @if ($modelo)
    <form method="POST" action="{{ route('nutri.anamnese.salvar',$paciente->id) }}" class="card" style="max-width:820px;">
        @csrf
        <input type="hidden" name="modelo_id" value="{{ $modelo->id }}">
        @foreach ($modelo->campos as $campo)
            @php $label = $campo['label']; $tipo = $campo['tipo'] ?? 'texto'; @endphp
            <div style="margin-bottom:16px;">
                <label>{{ $label }}</label>
                @if ($tipo === 'textarea')
                    <textarea name="respostas[{{ $label }}]" rows="3"></textarea>
                @elseif ($tipo === 'numero')
                    <input type="number" step="any" name="respostas[{{ $label }}]">
                @elseif ($tipo === 'sim_nao')
                    <select name="respostas[{{ $label }}]"><option value="">—</option><option>Sim</option><option>Não</option></select>
                @elseif ($tipo === 'opcoes')
                    <select name="respostas[{{ $label }}]"><option value="">—</option>@foreach (($campo['opcoes'] ?? []) as $op)<option>{{ $op }}</option>@endforeach</select>
                @else
                    <input type="text" name="respostas[{{ $label }}]">
                @endif
            </div>
        @endforeach
        <button class="btn"><i class="ph ph-check"></i> Salvar anamnese</button>
    </form>
    @else
        <div class="card"><div class="empty"><i class="ph ph-clipboard-text"></i>Crie um modelo de anamnese primeiro.<br><a href="{{ route('nutri.anamnese.modelos') }}" class="muted">Criar modelo</a></div></div>
    @endif
@endsection
