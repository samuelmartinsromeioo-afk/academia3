@extends('nutri.portal.layout')
@section('titulo','Meu plano')

@section('conteudo')
    @php $temVariasFichas = ($ativos ?? collect())->contains(fn($p) => !empty($p->dias_semana)); @endphp

    @if ($temVariasFichas)
        {{-- Seletor de dia: cada dia mostra a ficha correspondente --}}
        <div class="card" style="padding:12px;">
            <div class="muted" style="font-size:.72rem; margin-bottom:8px;">Sua ficha muda conforme o dia. Toque no dia:</div>
            <div style="display:flex; gap:6px; overflow-x:auto; padding-bottom:4px;">
                @foreach (\App\Models\Nutri\PlanoAlimentar::DIAS_SEMANA as $num => $lbl)
                    @php $temFicha = \App\Models\Nutri\Paciente::escolherPlanoDoDia($ativos, $num) !== null; @endphp
                    <a href="{{ route('portal.plano', $token) }}?dia={{ $num }}"
                       style="flex:0 0 auto; padding:8px 12px; border-radius:20px; font-size:.8rem; font-weight:700; text-align:center;
                              {{ $num === $dia ? 'background:var(--primary); color:#000;' : ($temFicha ? 'background:rgba(255,255,255,.06); color:#fff;' : 'background:transparent; color:var(--text-dim);') }}">
                        {{ $lbl }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if (!$plano)
        <div class="card"><div class="muted" style="text-align:center; padding:20px;">Nenhum plano ativo para este dia.</div></div>
    @else
        @php $t = $plano->totais(); @endphp
        <div class="card">
            @if ($temVariasFichas)
                <div class="muted" style="font-size:.72rem;"><i class="ph ph-calendar-dots"></i> {{ $plano->diasSemanaLabels() }}</div>
            @endif
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
