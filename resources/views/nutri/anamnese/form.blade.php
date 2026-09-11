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
    @php $secoes = $modelo->camposPorSecao(); @endphp

    @if ($anterior)
        <div class="card" style="margin-bottom:18px; border-color:rgba(212,255,0,.25);">
            <div style="display:flex; gap:10px; align-items:flex-start;">
                <i class="ph ph-clock-counter-clockwise" style="color:var(--primary); font-size:1.2rem;"></i>
                <div style="font-size:.85rem;">
                    Formulário pré-preenchido com a anamnese de
                    <strong>{{ $anterior->preenchida_em->format('d/m/Y') }}</strong>.
                    Ajuste o que mudou — o histórico anterior é preservado, esta vira um novo registro.
                    <a href="{{ route('nutri.anamnese.form',[$paciente->id,'modelo'=>$modelo->id,'limpar'=>1]) }}" class="muted">Começar em branco</a>
                </div>
            </div>
        </div>
    @endif

    {{-- Índice das seções: com 60+ perguntas, sem isso não se acha nada. --}}
    @if (count($secoes) > 1)
        <div class="card" style="margin-bottom:18px; display:flex; flex-wrap:wrap; gap:8px;">
            @foreach (array_keys($secoes) as $i => $secao)
                <a href="#secao-{{ $i }}" class="btn btn-ghost btn-sm">{{ $secao }}</a>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('nutri.anamnese.salvar',$paciente->id) }}" style="max-width:820px;">
        @csrf
        <input type="hidden" name="modelo_id" value="{{ $modelo->id }}">

        @foreach ($secoes as $secao => $campos)
            <div class="card" style="margin-bottom:18px;" id="secao-{{ $loop->index }}">
                <h3 style="margin-bottom:16px;">{{ $secao }}</h3>
                @foreach ($campos as $campo)
                    @include('nutri.anamnese._campo', ['campo' => $campo, 'valor' => $respostas[$campo['label']] ?? null])
                @endforeach
            </div>
        @endforeach

        <button class="btn"><i class="ph ph-check"></i> Salvar anamnese</button>
    </form>
    @else
        <div class="card"><div class="empty"><i class="ph ph-clipboard-text"></i>Crie um modelo de anamnese primeiro.<br><a href="{{ route('nutri.anamnese.modelos') }}" class="muted">Criar modelo</a></div></div>
    @endif
@endsection
