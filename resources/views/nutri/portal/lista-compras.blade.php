@extends('nutri.portal.layout')
@section('titulo','Lista de compras')

@section('conteudo')
    <div class="card">
        <strong>Lista de compras</strong>
        <div class="muted" style="font-size:.8rem; margin:6px 0 12px;">Gerada a partir do seu plano ativo (quantidades por dia).</div>
        @if (count($itens))
            @foreach ($itens as $it)
                <label style="display:flex; gap:10px; align-items:center; padding:9px 0; border-bottom:1px solid var(--border); text-transform:none; color:#fff; font-weight:400;">
                    <input type="checkbox" style="width:auto;">
                    <span style="flex:1;">{{ $it['descricao'] }}</span>
                    <span class="muted" style="font-size:.8rem;">{{ number_format($it['total_g'],0,',','.') }} g/dia</span>
                </label>
            @endforeach
        @else
            <div class="muted" style="text-align:center; padding:20px;">Sem itens — seu plano ainda não tem alimentos.</div>
        @endif
    </div>
@endsection
