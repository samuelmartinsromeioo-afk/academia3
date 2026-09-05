@extends('nutri.portal.layout')
@section('titulo','Meu plano')

@section('conteudo')
    @if (!$plano)
        <div class="card"><div class="muted" style="text-align:center; padding:20px;">Nenhum plano ativo no momento.</div></div>
    @else
        @php $t = $plano->totais(); @endphp
        <div class="card">
            <strong style="font-size:1.1rem;">{{ $plano->nome }}</strong>
            @if($plano->observacoes)<div class="muted" style="font-size:.85rem; margin-top:6px;">{{ $plano->observacoes }}</div>@endif
            <div style="display:flex; gap:16px; margin-top:12px; flex-wrap:wrap;">
                <div><strong style="color:var(--primary);">{{ number_format($t['kcal'],0,',','.') }}</strong> <span class="muted" style="font-size:.75rem;">kcal</span></div>
                <div><strong>{{ number_format($t['carbo_g'],0) }}g</strong> <span class="muted" style="font-size:.75rem;">C</span></div>
                <div><strong>{{ number_format($t['proteina_g'],0) }}g</strong> <span class="muted" style="font-size:.75rem;">P</span></div>
                <div><strong>{{ number_format($t['gordura_g'],0) }}g</strong> <span class="muted" style="font-size:.75rem;">G</span></div>
            </div>
        </div>

        @foreach ($plano->refeicoes as $ref)
            @php $rt = $ref->totais(); @endphp
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong>{{ $ref->nome }} @if($ref->horario)<span class="muted" style="font-weight:400;">· {{ $ref->horario }}</span>@endif</strong>
                    <span class="muted" style="font-size:.8rem;">{{ number_format($rt['kcal'],0) }} kcal</span>
                </div>
                <div style="margin-top:10px;">
                    @foreach ($ref->itens as $it)
                        <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);">
                            <div style="flex:1;">
                                {{ $it->descricao }}
                                <div class="muted" style="font-size:.72rem;">
                                    {{ $it->medida ?: ($it->quantidade_g.' g') }} · {{ number_format($it->kcal,0) }} kcal
                                </div>
                                @if($it->opcoes->count())
                                    <div style="margin-top:6px; padding:8px 10px; background:rgba(212,255,0,.06); border-radius:8px;">
                                        <div style="font-size:.68rem; color:var(--primary); font-weight:700; text-transform:uppercase; letter-spacing:.5px;">Pode trocar por</div>
                                        @foreach ($it->opcoes as $op)
                                            <div style="font-size:.75rem; margin-top:3px;">
                                                ↔ {{ $op->descricao }}
                                                <span class="muted">— {{ $op->medida ?: ($op->quantidade_g.' g') }}@if($op->kcal > 0) · {{ number_format($op->kcal,0) }} kcal @endif</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($ref->observacoes)<div class="muted" style="font-size:.8rem; margin-top:8px;">{{ $ref->observacoes }}</div>@endif
            </div>
        @endforeach

        <a href="{{ route('portal.lista-compras',$token) }}" class="btn" style="width:100%; justify-content:center;"><i class="ph ph-shopping-cart"></i> Gerar lista de compras</a>
    @endif
@endsection
