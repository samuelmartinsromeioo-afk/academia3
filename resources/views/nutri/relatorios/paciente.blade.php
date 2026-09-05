<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório — {{ $paciente->nome }}</title>
    <style>
        body { font-family:'Segoe UI',Arial,sans-serif; color:#111; margin:0; padding:32px; }
        .head { display:flex; justify-content:space-between; border-bottom:3px solid #0a0b0d; padding-bottom:14px; margin-bottom:20px; }
        h1 { font-size:1.4rem; margin:0; } h2 { font-size:1rem; border-bottom:1px solid #ddd; padding-bottom:6px; margin:22px 0 10px; }
        .nutri { text-align:right; font-size:.85rem; color:#444; }
        table { width:100%; border-collapse:collapse; font-size:.85rem; margin-bottom:8px; }
        th,td { padding:7px 12px; border-bottom:1px solid #eee; text-align:left; }
        th { background:#f5f5f5; font-size:.7rem; text-transform:uppercase; color:#666; }
        .grid2 { display:flex; gap:24px; flex-wrap:wrap; font-size:.9rem; }
        .print-btn { position:fixed; top:16px; right:16px; background:#0a0b0d; color:#d4ff00; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; }
        .kv b { display:block; color:#666; font-size:.72rem; }
        @media print { .print-btn { display:none; } body { padding:12px; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Imprimir / salvar PDF</button>
    <div class="head">
        <div><h1>Relatório de Evolução</h1><div>{{ $paciente->nome }}</div></div>
        <div class="nutri"><strong>{{ $nutri->nome }}</strong><br>Nutricionista @if($nutri->crn)· CRN {{ $nutri->crn }}@endif<br>{{ now()->format('d/m/Y') }}</div>
    </div>

    <div class="grid2 kv">
        <div><b>Objetivo</b>{{ $paciente->objetivo ?? '—' }}</div>
        <div><b>Idade</b>{{ $paciente->idade ? $paciente->idade.' anos' : '—' }}</div>
        <div><b>Sexo</b>{{ $paciente->sexo ?? '—' }}</div>
        <div><b>Altura</b>{{ $paciente->altura_cm ? $paciente->altura_cm.' cm' : '—' }}</div>
    </div>

    <h2>Evolução antropométrica</h2>
    @if ($paciente->antropometrias->count())
    <table>
        <thead><tr><th>Data</th><th>Peso</th><th>IMC</th><th>% Gordura</th><th>Cintura</th></tr></thead>
        <tbody>
        @foreach ($paciente->antropometrias->sortByDesc('data') as $a)
            <tr><td>{{ $a->data->format('d/m/Y') }}</td><td>{{ $a->peso ?? '—' }}</td><td>{{ $a->imc ?? '—' }}</td><td>{{ $a->percentual_gordura ?? '—' }}</td><td>{{ $a->circunferencias['cintura'] ?? '—' }}</td></tr>
        @endforeach
        </tbody>
    </table>
    @else <p>Sem avaliações registradas.</p> @endif

    <h2>Plano alimentar ativo</h2>
    @if ($planoAtivo)
        @php $t=$planoAtivo->totais(); @endphp
        <p><strong>{{ $planoAtivo->nome }}</strong> — {{ number_format($t['kcal'],0,',','.') }} kcal/dia
        (Carbo {{ number_format($t['carbo_g'],0) }}g · Proteína {{ number_format($t['proteina_g'],0) }}g · Gordura {{ number_format($t['gordura_g'],0) }}g)</p>
    @else <p>Nenhum plano ativo.</p> @endif

    <h2>Anamnese mais recente</h2>
    @php $an = $paciente->anamneses->first(); @endphp
    @if ($an)
        <table><tbody>
        @foreach ($an->respostas as $campo => $resp)
            <tr><th style="width:40%;">{{ $campo }}</th><td>{{ is_array($resp) ? implode(', ',$resp) : $resp }}</td></tr>
        @endforeach
        </tbody></table>
    @else <p>Sem anamnese registrada.</p> @endif
</body>
</html>
