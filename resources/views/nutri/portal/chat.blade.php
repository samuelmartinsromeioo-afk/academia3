@extends('nutri.portal.layout')
@section('titulo','Chat')

@section('estilos')
<style>
    .msgs { display:flex; flex-direction:column; gap:10px; max-height:60vh; overflow-y:auto; }
    .msg { max-width:78%; padding:10px 14px; border-radius:14px; font-size:.9rem; }
    .me { align-self:flex-end; background:var(--primary); color:#000; }
    .them { align-self:flex-start; background:var(--card2); }
    .msg .t { font-size:.62rem; color:var(--dim); margin-top:3px; }
</style>
@endsection

@section('conteudo')
    <div class="card">
        <strong>Fale com seu nutricionista</strong>
        <div class="msgs" id="msgs" style="margin-top:12px;">
            @forelse ($mensagens as $m)
                <div class="msg {{ $m->remetente==='paciente'?'me':'them' }}">{{ $m->texto }}<div class="t">{{ $m->created_at->format('d/m H:i') }}</div></div>
            @empty
                <div class="muted" style="text-align:center; padding:20px;">Ainda não há mensagens.</div>
            @endforelse
        </div>
        <form method="POST" action="{{ route('portal.chat.enviar',$token) }}" style="display:flex; gap:8px; margin-top:12px;">
            @csrf
            <input name="texto" placeholder="Mensagem…" required autocomplete="off">
            <button class="btn"><i class="ph ph-paper-plane-right"></i></button>
        </form>
    </div>
@endsection

@section('scripts')<script>const m=document.getElementById('msgs'); m.scrollTop=m.scrollHeight;</script>@endsection
