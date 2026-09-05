@extends('nutri.portal.layout')
@section('titulo','Início')

@section('conteudo')
    @if ($plano)
        @php $t = $plano->totais(); @endphp
        <div class="card">
            <div class="muted" style="font-size:.75rem;">Seu plano atual</div>
            <strong style="font-size:1.1rem;">{{ $plano->nome }}</strong>
            <div style="display:flex; gap:16px; margin-top:12px; flex-wrap:wrap;">
                <div><strong style="color:var(--primary); font-size:1.2rem;">{{ number_format($t['kcal'],0,',','.') }}</strong><div class="muted" style="font-size:.7rem;">kcal/dia</div></div>
                <div><strong>{{ number_format($t['carbo_g'],0) }}g</strong><div class="muted" style="font-size:.7rem;">carbo</div></div>
                <div><strong>{{ number_format($t['proteina_g'],0) }}g</strong><div class="muted" style="font-size:.7rem;">proteína</div></div>
                <div><strong>{{ number_format($t['gordura_g'],0) }}g</strong><div class="muted" style="font-size:.7rem;">gordura</div></div>
            </div>
            <a href="{{ route('portal.plano',$token) }}" class="btn" style="margin-top:14px; width:100%; justify-content:center;">Ver plano completo</a>
        </div>
    @else
        <div class="card"><div class="muted" style="text-align:center; padding:20px;">Seu nutricionista ainda não publicou um plano. Você será avisado!</div></div>
    @endif

    <!-- Check-in rápido -->
    <div class="card">
        <strong>Check-in de hoje</strong>
        <div class="muted" style="font-size:.8rem; margin:6px 0 12px;">Um retorno rápido ajuda seu nutri a te acompanhar.</div>
        <form method="POST" action="{{ route('portal.checkin',$token) }}">
            @csrf
            <input type="hidden" name="data" value="{{ date('Y-m-d') }}">
            <div style="display:flex; gap:10px;">
                <div style="flex:1;"><label>Peso (kg)</label><input type="number" step="0.1" name="peso"></div>
                <div style="flex:1;"><label>Adesão (%)</label><input type="number" min="0" max="100" name="adesao" placeholder="0-100"></div>
            </div>
            <div style="margin-top:10px;"><label>Como foi seu dia?</label><textarea name="comentario" rows="2"></textarea></div>
            <button class="btn" style="margin-top:12px; width:100%; justify-content:center;">Enviar check-in</button>
        </form>
    </div>

    <a href="{{ route('portal.anamnese',$token) }}" class="btn btn-ghost" style="width:100%; justify-content:center;"><i class="ph ph-clipboard-text"></i> Responder questionário pré-consulta</a>
@endsection
