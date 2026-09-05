@extends('layouts.nutri')
@section('titulo', 'Antropometria')

@php
    // Circunferências únicas (tronco) e bilaterais (membros D/E), em cm.
    $circUnica = [
        'pescoco'  => 'Pescoço',
        'ombro'    => 'Ombro',
        'torax'    => 'Tórax',
        'cintura'  => 'Cintura',
        'abdomen'  => 'Abdômen',
        'quadril'  => 'Quadril',
    ];
    $circBilateral = [
        'braco'            => 'Braço relaxado',
        'braco_contraido'  => 'Braço contraído',
        'antebraco'        => 'Antebraço',
        'coxa'             => 'Coxa',
        'panturrilha'      => 'Panturrilha',
    ];
    // Dobras cutâneas (mm) — protocolo padrão medido no lado direito.
    $dobras = [
        'tricipital'   => 'Tricipital',
        'bicipital'    => 'Bicipital',
        'subescapular' => 'Subescapular',
        'peitoral'     => 'Peitoral',
        'axilar_media' => 'Axilar média',
        'suprailiaca'  => 'Supra-ilíaca',
        'abdominal'    => 'Abdominal',
        'coxa'         => 'Coxa',
        'panturrilha'  => 'Panturrilha',
    ];
@endphp

@section('estilos')
<style>
    .fieldset { border:1px solid var(--border); border-radius:12px; padding:14px; margin-top:14px; }
    .fieldset > .leg { font-size:.68rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:.5px; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
    .mgrid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px 14px; }
    .bil { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
    .bil small { color:var(--text-dim); font-size:.6rem; }
    .lbl-sm { font-size:.62rem !important; }
    details.med summary { cursor:pointer; color:var(--primary); font-size:.78rem; }
    details.med table { margin-top:8px; }
    details.med td { padding:5px 10px; border:none; }
    @media(max-width:1100px){ .mgrid{ grid-template-columns:repeat(2,1fr);} }
    @media(max-width:560px){ .mgrid{ grid-template-columns:1fr;} }
</style>
@endsection

@section('conteudo')
    <div class="topbar">
        <div><h1>Antropometria — {{ $paciente->nome }}</h1><div class="sub">Avaliação completa, evolução e histórico comparativo.</div></div>
        <a href="{{ route('nutri.pacientes.show',$paciente->id) }}" class="btn btn-ghost btn-sm"><i class="ph ph-arrow-left"></i> Voltar</a>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <h3 style="margin-bottom:14px;">Gráfico de evolução</h3>
        <canvas id="grafico" height="90"></canvas>
    </div>

    <form method="POST" action="{{ route('nutri.antropometria.store',$paciente->id) }}" class="card">
        <h3 style="margin-bottom:6px;">Nova avaliação</h3>

        @csrf

        <!-- Dados gerais -->
        <div class="fieldset">
            <div class="leg"><i class="ph ph-scales"></i> Dados gerais</div>
            <div class="mgrid">
                <div><label>Data</label><input type="date" name="data" value="{{ date('Y-m-d') }}" required></div>
                <div><label>Peso (kg)</label><input type="number" step="0.1" name="peso"></div>
                <div><label>Altura (cm)</label><input type="number" step="0.1" name="altura_cm" value="{{ $paciente->altura_cm }}"></div>
                <div><label>% Gordura</label><input type="number" step="0.1" name="percentual_gordura"></div>
                <div><label>Massa magra (kg)</label><input type="number" step="0.1" name="massa_magra"></div>
            </div>
        </div>

        <!-- Circunferências -->
        <div class="fieldset">
            <div class="leg"><i class="ph ph-ruler"></i> Circunferências (cm)</div>
            <div class="mgrid">
                @foreach ($circUnica as $key => $nome)
                    <div><label class="lbl-sm">{{ $nome }}</label><input type="number" step="0.1" name="circunferencias[{{ $key }}]"></div>
                @endforeach
            </div>
            <div class="mgrid" style="margin-top:12px;">
                @foreach ($circBilateral as $key => $nome)
                    <div>
                        <label class="lbl-sm">{{ $nome }} <span style="color:var(--text-dim);">(D / E)</span></label>
                        <div class="bil">
                            <input type="number" step="0.1" name="circunferencias[{{ $key }}_direito]" placeholder="Dir.">
                            <input type="number" step="0.1" name="circunferencias[{{ $key }}_esquerdo]" placeholder="Esq.">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Dobras cutâneas -->
        <div class="fieldset">
            <div class="leg"><i class="ph ph-caliper"></i> Dobras cutâneas (mm)</div>
            <div class="mgrid">
                @foreach ($dobras as $key => $nome)
                    <div><label class="lbl-sm">{{ $nome }}</label><input type="number" step="0.1" name="dobras[{{ $key }}]"></div>
                @endforeach
            </div>
        </div>

        <div style="margin-top:14px;"><label>Observações</label><textarea name="observacoes" rows="2"></textarea></div>
        <button class="btn" style="margin-top:14px;"><i class="ph ph-plus"></i> Registrar avaliação</button>
    </form>

    <div class="card" style="margin-top:18px;">
        <h3 style="margin-bottom:14px;">Histórico</h3>
        @if ($avaliacoes->count())
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Data</th><th>Peso</th><th>IMC</th><th>Classificação</th><th>%Gord.</th><th>M. magra</th><th>Medidas</th><th></th></tr></thead>
            <tbody>
            @foreach ($avaliacoes as $a)
                @php
                    $circ = collect($a->circunferencias ?? [])->filter(fn($v)=>$v!==null && $v!=='');
                    $dob  = collect($a->dobras ?? [])->filter(fn($v)=>$v!==null && $v!=='');
                    $rotulo = fn($k) => ucfirst(str_replace(['_direito','_esquerdo','_'],[' (D)',' (E)',' '],$k));
                @endphp
                <tr>
                    <td>{{ $a->data->format('d/m/Y') }}</td>
                    <td>{{ $a->peso ?? '—' }}</td>
                    <td>{{ $a->imc ?? '—' }}</td>
                    <td class="muted">{{ \App\Models\Nutri\Antropometria::classificarImc($a->imc) }}</td>
                    <td>{{ $a->percentual_gordura ?? '—' }}</td>
                    <td>{{ $a->massa_magra ?? '—' }}</td>
                    <td>
                        @if ($circ->count() || $dob->count())
                        <details class="med">
                            <summary>{{ $circ->count() + $dob->count() }} medida(s)</summary>
                            <table>
                                @if ($circ->count())
                                    <tr><td class="muted" style="font-size:.68rem; text-transform:uppercase;">Circunferências (cm)</td></tr>
                                    @foreach ($circ as $k => $v)<tr><td>{{ $rotulo($k) }}</td><td><strong>{{ $v }}</strong></td></tr>@endforeach
                                @endif
                                @if ($dob->count())
                                    <tr><td class="muted" style="font-size:.68rem; text-transform:uppercase; padding-top:8px;">Dobras (mm)</td></tr>
                                    @foreach ($dob as $k => $v)<tr><td>{{ $rotulo($k) }}</td><td><strong>{{ $v }}</strong></td></tr>@endforeach
                                @endif
                            </table>
                        </details>
                        @else <span class="muted">—</span> @endif
                    </td>
                    <td style="text-align:right;">
                        <form method="POST" action="{{ route('nutri.antropometria.destroy',$a->id) }}" onsubmit="return confirm('Remover avaliação?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @else
            <div class="empty"><i class="ph ph-ruler"></i>Sem avaliações ainda.</div>
        @endif
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
fetch("{{ route('nutri.antropometria.dados',$paciente->id) }}")
    .then(r=>r.json())
    .then(d=>{
        if(!d.labels.length){ document.getElementById('grafico').parentNode.innerHTML='<div class="empty"><i class="ph ph-chart-line"></i>Registre avaliações para ver a evolução.</div>'; return; }
        new Chart(document.getElementById('grafico'), {
            type:'line',
            data:{ labels:d.labels, datasets:[
                {label:'Peso (kg)', data:d.peso, borderColor:'#d4ff00', backgroundColor:'rgba(212,255,0,.1)', tension:.3},
                {label:'% Gordura', data:d.gordura, borderColor:'#ffaa00', tension:.3},
                {label:'Cintura (cm)', data:d.cintura, borderColor:'#00ff88', tension:.3},
            ]},
            options:{ plugins:{legend:{labels:{color:'#9ca3af'}}}, scales:{
                x:{ticks:{color:'#9ca3af'}, grid:{color:'rgba(255,255,255,.05)'}},
                y:{ticks:{color:'#9ca3af'}, grid:{color:'rgba(255,255,255,.05)'}} } }
        });
    });
</script>
@endsection
