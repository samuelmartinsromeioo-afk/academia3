@extends('nutri.portal.layout')
@section('titulo','Minhas metas')

@section('estilos')
    .meta { display:flex; gap:12px; align-items:flex-start; padding:14px 0; border-bottom:1px solid var(--border); }
    .meta:last-child { border-bottom:none; }
    .meta input[type=checkbox] { width:24px; height:24px; accent-color:var(--primary); flex-shrink:0; margin-top:2px; }
    .meta .tit { font-weight:600; font-size:.95rem; }
    .meta .desc { color:var(--dim); font-size:.78rem; margin-top:3px; }
    .meta .qtd { display:flex; gap:6px; align-items:center; margin-top:8px; }
    .meta .qtd input { width:90px; padding:8px; }
    .dia-nav { display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:12px; }
    .dia-nav a { color:var(--dim); text-decoration:none; font-size:1.3rem; }
    .streak { font-size:.7rem; color:var(--primary); font-weight:700; }
@endsection

@section('conteudo')
    @php
        $hoje = \Carbon\Carbon::parse($dia);
        $anterior = $hoje->copy()->subDay()->toDateString();
        $proximo = $hoje->copy()->addDay();
    @endphp

    <div class="card">
        <div class="dia-nav">
            <a href="{{ route('portal.metas',[$token,'data'=>$anterior]) }}"><i class="ph ph-caret-left"></i></a>
            <div style="text-align:center;">
                <div style="font-weight:700;">{{ $hoje->isToday() ? 'Hoje' : $hoje->format('d/m') }}</div>
                <div class="muted" style="font-size:.72rem;">{{ ucfirst($hoje->locale('pt_BR')->dayName) }}</div>
            </div>
            {{-- Sem link para o futuro: não dá para marcar um dia que não aconteceu. --}}
            @if ($proximo->lte(now()))
                <a href="{{ route('portal.metas',[$token,'data'=>$proximo->toDateString()]) }}"><i class="ph ph-caret-right"></i></a>
            @else
                <span style="width:1.3rem;"></span>
            @endif
        </div>

        @if ($metas->isEmpty())
            <div class="muted" style="text-align:center; padding:28px 10px; font-size:.88rem;">
                <i class="ph ph-target" style="font-size:2rem; display:block; margin-bottom:8px;"></i>
                Seu nutricionista ainda não definiu metas para você.
            </div>
        @else
            <form method="POST" action="{{ route('portal.metas.salvar',$token) }}">
                @csrf
                <input type="hidden" name="data" value="{{ $dia }}">

                @foreach ($metas as $meta)
                    @php $reg = $meta->registroDe($dia); @endphp
                    <div class="meta">
                        <input type="checkbox" name="marcadas[]" value="{{ $meta->id }}"
                               id="m{{ $meta->id }}" @checked($reg && $reg->concluida)>
                        <div style="flex:1;">
                            <label for="m{{ $meta->id }}" style="color:#fff; text-transform:none; font-size:.95rem; letter-spacing:0; margin:0; cursor:pointer;">
                                <span class="tit">{{ $meta->titulo }}</span>
                                @if ($meta->alvoLabel())
                                    <span class="streak">· {{ $meta->alvoLabel() }}</span>
                                @endif
                            </label>
                            @if ($meta->descricao)
                                <div class="desc">{{ $meta->descricao }}</div>
                            @endif
                            @if ($meta->tipo === 'quantidade')
                                <div class="qtd">
                                    <input type="number" step="0.1" min="0" name="valores[{{ $meta->id }}]"
                                           value="{{ $reg->valor ?? '' }}" placeholder="0">
                                    <span class="muted" style="font-size:.8rem;">{{ $meta->unidade }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <button class="btn" style="width:100%; margin-top:16px; justify-content:center;">
                    <i class="ph ph-check"></i> Salvar {{ $hoje->isToday() ? 'o dia de hoje' : 'dia '.$hoje->format('d/m') }}
                </button>
            </form>
        @endif
    </div>

    @if ($metas->isNotEmpty())
        <div class="card">
            <div style="font-size:.8rem; font-weight:700; margin-bottom:10px;">Seus últimos 30 dias</div>
            @foreach ($metas as $meta)
                <div style="display:flex; justify-content:space-between; padding:7px 0; font-size:.85rem;">
                    <span class="muted">{{ $meta->titulo }}</span>
                    <strong style="color:var(--primary);">{{ $meta->adesao() }}%</strong>
                </div>
            @endforeach
        </div>
    @endif
@endsection
