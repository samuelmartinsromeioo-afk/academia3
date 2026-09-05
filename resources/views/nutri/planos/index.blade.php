@extends('layouts.nutri')
@section('titulo', 'Planos alimentares')

@section('conteudo')
    <div class="topbar">
        <div><h1>Planos alimentares</h1><div class="sub">Planos dos pacientes e seus modelos reutilizáveis.</div></div>
        <button class="btn" onclick="document.getElementById('novoPlano').style.display='block'"><i class="ph ph-plus"></i> Novo plano</button>
    </div>

    <div id="novoPlano" class="card" style="display:none; margin-bottom:18px;">
        <form method="POST" action="{{ route('nutri.planos.store') }}">
            @csrf
            <div class="grid" style="grid-template-columns:2fr 1fr auto; align-items:flex-end;">
                <div><label>Nome do plano</label><input name="nome" required placeholder="Ex.: Plano de emagrecimento"></div>
                <div><label>Meta kcal (opcional)</label><input type="number" name="kcal_meta"></div>
                <label style="display:flex; gap:8px; align-items:center; color:var(--text-main); text-transform:none;">
                    <input type="checkbox" name="is_modelo" value="1" style="width:auto;"> Salvar como modelo
                </label>
            </div>
            <button class="btn" style="margin-top:12px;"><i class="ph ph-arrow-right"></i> Criar e abrir editor</button>
        </form>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <h3 style="margin-bottom:14px;">Planos de pacientes</h3>
        @if ($planos->count())
        <table>
            <thead><tr><th>Plano</th><th>Paciente</th><th>Versão</th><th>Atualizado</th><th></th></tr></thead>
            <tbody>
            @foreach ($planos as $p)
                <tr>
                    <td><a href="{{ route('nutri.planos.editor',$p->id) }}"><strong>{{ $p->nome }}</strong></a> @if($p->ativo)<span class="badge badge-ok">Ativo</span>@endif</td>
                    <td class="muted">{{ $p->paciente->nome ?? '—' }}</td>
                    <td>v{{ $p->versao }}</td>
                    <td class="muted">{{ $p->updated_at?->diffForHumans() }}</td>
                    <td style="text-align:right;"><a href="{{ route('nutri.planos.editor',$p->id) }}" class="btn btn-ghost btn-sm">Editar</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:14px;">{{ $planos->links() }}</div>
        @else
            <div class="empty"><i class="ph ph-fork-knife"></i>Nenhum plano ainda.</div>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px;">Modelos reutilizáveis</h3>
        @forelse ($modelos as $m)
            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border);">
                <div><strong>{{ $m->nome }}</strong> <span class="muted" style="font-size:.75rem;">{{ $m->objetivo }}</span></div>
                <a href="{{ route('nutri.planos.editor',$m->id) }}" class="btn btn-ghost btn-sm">Abrir</a>
            </div>
        @empty
            <div class="empty" style="padding:20px;"><i class="ph ph-copy"></i>Nenhum modelo. Salve um plano como modelo no editor.</div>
        @endforelse
    </div>
@endsection
