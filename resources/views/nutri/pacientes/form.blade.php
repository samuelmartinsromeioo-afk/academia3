@extends('layouts.nutri')
@section('titulo', $paciente->exists ? 'Editar paciente' : 'Novo paciente')

@section('conteudo')
    <div class="topbar"><h1>{{ $paciente->exists ? 'Editar paciente' : 'Novo paciente' }}</h1></div>

    <form method="POST" action="{{ $paciente->exists ? route('nutri.pacientes.update',$paciente->id) : route('nutri.pacientes.store') }}" class="card" style="max-width:760px;">
        @csrf
        @if ($paciente->exists) @method('PUT') @endif

        <div class="grid" style="grid-template-columns:1fr 1fr;">
            <div style="grid-column:span 2;">
                <label>Nome completo *</label>
                <input type="text" name="nome" value="{{ old('nome',$paciente->nome) }}" required>
            </div>
            <div><label>E-mail</label><input type="email" name="email" value="{{ old('email',$paciente->email) }}"></div>
            <div><label>WhatsApp</label><input type="text" name="whatsapp" value="{{ old('whatsapp',$paciente->whatsapp) }}" placeholder="(11) 90000-0000"></div>
            <div><label>Data de nascimento</label><input type="date" name="data_nascimento" value="{{ old('data_nascimento',optional($paciente->data_nascimento)->format('Y-m-d')) }}"></div>
            <div>
                <label>Sexo</label>
                <select name="sexo">
                    <option value="">—</option>
                    @foreach (['Feminino','Masculino','Outro'] as $s)
                        <option value="{{ $s }}" @selected(old('sexo',$paciente->sexo)===$s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Objetivo</label>
                <select name="objetivo">
                    <option value="">—</option>
                    @foreach ($objetivos as $obj)
                        <option value="{{ $obj }}" @selected(old('objetivo',$paciente->objetivo)===$obj)>{{ $obj }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Estado (UF)</label>
                <select name="uf">
                    <option value="">—</option>
                    @foreach (\App\Support\PrecoRegional::ufs() as $u)
                        <option value="{{ $u }}" @selected(old('uf',$paciente->uf)===$u)>{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Altura (cm)</label><input type="number" step="0.1" name="altura_cm" value="{{ old('altura_cm',$paciente->altura_cm) }}"></div>
            <div style="grid-column:span 2;">
                <label>Observações</label>
                <textarea name="observacoes" rows="3">{{ old('observacoes',$paciente->observacoes) }}</textarea>
            </div>
        </div>

        <div style="margin-top:20px; display:flex; gap:10px;">
            <button class="btn"><i class="ph ph-check"></i> Salvar</button>
            <a href="{{ $paciente->exists ? route('nutri.pacientes.show',$paciente->id) : route('nutri.pacientes') }}" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
@endsection
