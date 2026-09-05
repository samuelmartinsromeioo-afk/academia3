@extends('layouts.nutri')
@section('titulo', 'Roadmap')

@section('conteudo')
    <div class="topbar">
        <div><h1>Roadmap público</h1><div class="sub">{{ config('textos.profissional.diferenciais.escuta') }}</div></div>
    </div>

    <div class="grid" style="grid-template-columns:1fr 2fr; align-items:start;">
        <form method="POST" action="{{ route('nutri.roadmap.store') }}" class="card">
            <h3 style="margin-bottom:12px;">Sugerir funcionalidade</h3>
            @csrf
            <div style="margin-bottom:10px;"><label>Título</label><input name="titulo" required></div>
            <div><label>Descrição</label><textarea name="descricao" rows="4" placeholder="O que resolveria e por quê"></textarea></div>
            <button class="btn" style="margin-top:12px;"><i class="ph ph-paper-plane-tilt"></i> Enviar sugestão</button>
        </form>

        <div>
            <div style="display:flex; gap:8px; margin-bottom:12px;">
                <a href="?ordem=votos" class="btn btn-sm {{ $ordem==='votos'?'':'btn-ghost' }}">Mais votadas</a>
                <a href="?ordem=recentes" class="btn btn-sm {{ $ordem==='recentes'?'':'btn-ghost' }}">Recentes</a>
            </div>
            @forelse ($sugestoes as $s)
                <div class="card" style="margin-bottom:12px; display:flex; gap:14px;">
                    <form method="POST" action="{{ route('nutri.roadmap.votar',$s->id) }}" style="text-align:center;">@csrf
                        <button class="btn btn-sm {{ in_array($s->id,$meusVotos)?'':'btn-ghost' }}" style="flex-direction:column; padding:8px 12px;">
                            <i class="ph ph-caret-up"></i> {{ $s->votos_count }}
                        </button>
                    </form>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>{{ $s->titulo }}</strong>
                            <span class="badge {{ $s->status==='entregue'?'badge-ok':($s->status==='recusado'?'badge-dim':'badge-warn') }}">{{ \App\Models\Nutri\Sugestao::STATUS[$s->status] ?? $s->status }}</span>
                        </div>
                        @if($s->descricao)<div class="muted" style="font-size:.85rem; margin-top:6px;">{{ $s->descricao }}</div>@endif
                        <div class="muted" style="font-size:.72rem; margin-top:8px;">por {{ $s->autor->nome ?? 'Nutricionista' }} · {{ $s->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <div class="card"><div class="empty"><i class="ph ph-megaphone-simple"></i>Seja o primeiro a sugerir!</div></div>
            @endforelse
        </div>
    </div>
@endsection
