@extends('layouts.nutri')
@section('titulo', 'Pacientes')

@section('conteudo')
    <div class="topbar">
        <div><h1>Pacientes</h1><div class="sub">{{ $pacientes->total() }} paciente(s)</div></div>
        <a href="{{ route('nutri.pacientes.create') }}" class="btn"><i class="ph ph-plus"></i> Novo paciente</a>
    </div>

    <form method="GET" class="card" style="display:flex; gap:12px; margin-bottom:18px; align-items:flex-end; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
            <label>Buscar</label>
            <input type="text" name="q" value="{{ $busca }}" placeholder="Nome, e-mail ou WhatsApp">
        </div>
        <div style="min-width:180px;">
            <label>Objetivo</label>
            <select name="objetivo">
                <option value="">Todos</option>
                @foreach ($objetivos as $obj)
                    <option value="{{ $obj }}" @selected($objetivo===$obj)>{{ $obj }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:160px;">
            <label>Situação</label>
            <select name="situacao">
                <option value="">Todos</option>
                <option value="ausentes" @selected($situacao==='ausentes')>Ausentes ({{ $totalAusentes }})</option>
            </select>
        </div>
        <button class="btn btn-ghost"><i class="ph ph-magnifying-glass"></i> Filtrar</button>
    </form>

    @if ($situacao !== 'ausentes' && $totalAusentes > 0)
        <a href="{{ route('nutri.pacientes', ['situacao' => 'ausentes']) }}" class="flash flash-err" style="text-decoration:none; background:rgba(255,170,0,.08); border-color:rgba(255,170,0,.3); color:var(--warn);">
            <i class="ph ph-user-minus"></i> {{ $totalAusentes }} paciente(s) há mais de um mês sem retorno. Ver ausentes →
        </a>
    @endif

    <div class="card">
        @if ($pacientes->count())
        <table>
            <thead><tr><th>Nome</th><th>Objetivo</th><th>Contato</th><th>Idade</th><th>Situação</th><th></th></tr></thead>
            <tbody>
            @foreach ($pacientes as $p)
                <tr>
                    <td><a href="{{ route('nutri.pacientes.show',$p->id) }}"><strong>{{ $p->nome }}</strong></a></td>
                    <td class="muted">{{ $p->objetivo ?? '—' }}</td>
                    <td class="muted">{{ $p->whatsapp ?? $p->email ?? '—' }}</td>
                    <td class="muted">{{ $p->idade ? $p->idade.' anos' : '—' }}</td>
                    <td>
                        @if ($p->estaAusente())
                            <span class="badge badge-warn" title="Sem retorno há mais de um mês">Ausente · {{ $p->diasSemRetorno() }}d</span>
                        @else
                            <span class="badge badge-ok">Ativo</span>
                        @endif
                    </td>
                    <td style="text-align:right;"><a href="{{ route('nutri.pacientes.show',$p->id) }}" class="btn btn-ghost btn-sm">Abrir ficha</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:16px;">{{ $pacientes->links() }}</div>
        @else
            <div class="empty"><i class="ph ph-users-three"></i>Nenhum paciente encontrado.</div>
        @endif
    </div>
@endsection
