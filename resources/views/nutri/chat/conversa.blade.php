@extends('layouts.nutri')
@section('titulo', 'Chat')

@section('estilos')
<style>
    .msgs { display:flex; flex-direction:column; gap:10px; max-height:55vh; overflow-y:auto; padding:6px; }
    .msg { max-width:70%; padding:10px 14px; border-radius:14px; font-size:.88rem; }
    .msg .t { font-size:.65rem; color:var(--text-dim); margin-top:4px; }
    .me { align-self:flex-end; background:var(--primary); color:#000; }
    .them { align-self:flex-start; background:var(--card-2); }
</style>
@endsection

@section('conteudo')
    <div class="topbar">
        <div><h1>Chat — {{ $paciente->nome }}</h1><div class="sub">Mensagens ficam registradas na plataforma (sem expor seu telefone).</div></div>
        <a href="{{ route('nutri.pacientes.show',$paciente->id) }}" class="btn btn-ghost btn-sm"><i class="ph ph-arrow-left"></i> Ficha</a>
    </div>

    <div class="card">
        <div class="msgs" id="msgs">
            @forelse ($mensagens as $m)
                <div class="msg {{ $m->remetente==='nutri'?'me':'them' }}">{{ $m->texto }}<div class="t">{{ $m->created_at->format('d/m H:i') }}</div></div>
            @empty
                <div class="empty"><i class="ph ph-chat-circle"></i>Nenhuma mensagem ainda.</div>
            @endforelse
        </div>
        <form method="POST" action="{{ route('nutri.chat.enviar',$paciente->id) }}" style="display:flex; gap:10px; margin-top:14px;">
            @csrf
            <input name="texto" placeholder="Escreva uma mensagem…" required autocomplete="off">
            <button class="btn"><i class="ph ph-paper-plane-right"></i></button>
        </form>
    </div>
@endsection

@section('scripts')
<script>const m=document.getElementById('msgs'); m.scrollTop=m.scrollHeight;</script>
@endsection
