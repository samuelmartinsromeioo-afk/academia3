@extends('nutri.portal.layout')
@section('titulo','Orientações')

@section('estilos')
    .ori summary { cursor:pointer; font-weight:600; font-size:.92rem; padding:14px 0; list-style:none; display:flex; justify-content:space-between; gap:10px; align-items:center; }
    .ori summary::-webkit-details-marker { display:none; }
    .ori summary i { color:var(--dim); transition:transform .2s; }
    .ori[open] summary i { transform:rotate(180deg); }
    .ori { border-bottom:1px solid var(--border); }
    .ori:last-child { border-bottom:none; }
    .ori .txt { color:var(--dim); font-size:.86rem; line-height:1.6; white-space:pre-line; padding-bottom:16px; }
    .cat { font-size:.62rem; text-transform:uppercase; letter-spacing:.5px; color:var(--primary); font-weight:700; }
@endsection

@section('conteudo')
    <div class="card">
        <div style="font-weight:700; margin-bottom:4px;">Orientações do seu nutricionista</div>
        <div class="muted" style="font-size:.78rem; margin-bottom:6px;">Toque para abrir cada uma.</div>

        @forelse ($orientacoes as $o)
            <details class="ori">
                <summary>
                    <span>
                        @if ($o->categoria)<span class="cat">{{ $o->categoria }}</span><br>@endif
                        {{ $o->titulo }}
                    </span>
                    <i class="ph ph-caret-down"></i>
                </summary>
                <div class="txt">{{ $o->conteudo }}</div>
            </details>
        @empty
            <div class="muted" style="text-align:center; padding:28px 10px; font-size:.88rem;">
                <i class="ph ph-book-open" style="font-size:2rem; display:block; margin-bottom:8px;"></i>
                Nenhuma orientação liberada ainda.
            </div>
        @endforelse
    </div>
@endsection
