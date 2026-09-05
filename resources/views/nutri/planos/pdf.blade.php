<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Plano alimentar — {{ $plano->paciente->nome ?? $plano->nome }}</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; color:#111; margin:0; padding:32px; }
        .head { display:flex; justify-content:space-between; border-bottom:3px solid #0a0b0d; padding-bottom:14px; margin-bottom:20px; }
        .head h1 { margin:0; font-size:1.4rem; }
        .head .nutri { text-align:right; font-size:.85rem; color:#444; }
        .meta { display:flex; gap:24px; margin-bottom:18px; font-size:.9rem; }
        .meal { border:1px solid #ddd; border-radius:10px; margin-bottom:14px; overflow:hidden; page-break-inside:avoid; }
        .meal h3 { margin:0; background:#0a0b0d; color:#d4ff00; padding:10px 14px; font-size:1rem; display:flex; justify-content:space-between; }
        table { width:100%; border-collapse:collapse; font-size:.85rem; }
        th,td { padding:8px 14px; border-bottom:1px solid #eee; text-align:left; }
        th { background:#f5f5f5; font-size:.7rem; text-transform:uppercase; color:#666; }
        td.n, th.n { text-align:right; }
        .tot { font-weight:700; background:#fafafa; }
        .day-tot { background:#d4ff00; color:#0a0b0d; padding:14px; border-radius:10px; font-weight:700; display:flex; gap:24px; margin-top:8px; }
        .obs { margin-top:16px; font-size:.85rem; color:#333; background:#f8f8f8; padding:12px; border-radius:8px; }
        .subs { color:#777; font-size:.75rem; }
        .print-btn { position:fixed; top:16px; right:16px; background:#0a0b0d; color:#d4ff00; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; }
        @media print { .print-btn { display:none; } body { padding:12px; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Imprimir / salvar PDF</button>

    @php $tot = $plano->totais(); @endphp
    <div class="head">
        <div>
            <h1>Plano Alimentar</h1>
            <div>{{ $plano->paciente->nome ?? 'Modelo' }} @if($plano->objetivo)· {{ $plano->objetivo }}@endif</div>
            @unless($plano->is_modelo)<div style="font-size:.85rem; color:#444;">Dias: {{ $plano->diasSemanaLabels() }}</div>@endunless
        </div>
        <div class="nutri">
            <strong>{{ $nutri->nome }}</strong><br>
            Nutricionista @if($nutri->crn) · CRN {{ $nutri->crn }} @endif<br>
            {{ now()->format('d/m/Y') }}
        </div>
    </div>

    @foreach ($plano->refeicoes as $ref)
        @php $rt = $ref->totais(); @endphp
        <div class="meal">
            <h3><span>{{ $ref->nome }} @if($ref->horario)<span style="color:#fff; font-weight:400;">· {{ $ref->horario }}</span>@endif</span><span style="color:#fff; font-weight:400;">{{ number_format($rt['kcal'],0,',','.') }} kcal</span></h3>
            <table>
                <thead><tr><th>Alimento</th><th class="n">Qtd</th><th class="n">Kcal</th><th class="n">Carbo</th><th class="n">Prot</th><th class="n">Gord</th></tr></thead>
                <tbody>
                @foreach ($ref->itens as $it)
                    <tr>
                        <td>{{ $it->descricao }}
                            @if($it->opcoes->count())
                                <div class="subs">Substituições: {{ $it->opcoes->map(fn($o) => $o->descricao.' ('.($o->medida ?: $o->quantidade_g.'g').')')->implode(' · ') }}</div>
                            @endif
                        </td>
                        <td class="n">{{ $it->medida ?: ($it->quantidade_g.' g') }}</td>
                        <td class="n">{{ number_format($it->kcal,0,',','.') }}</td>
                        <td class="n">{{ number_format($it->carbo_g,1,',','.') }}g</td>
                        <td class="n">{{ number_format($it->proteina_g,1,',','.') }}g</td>
                        <td class="n">{{ number_format($it->gordura_g,1,',','.') }}g</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($ref->observacoes)<div class="obs" style="margin:10px 14px;">{{ $ref->observacoes }}</div>@endif
        </div>
    @endforeach

    <div class="day-tot">
        <span>TOTAL DO DIA</span>
        <span>{{ number_format($tot['kcal'],0,',','.') }} kcal</span>
        <span>Carbo {{ number_format($tot['carbo_g'],0,',','.') }}g</span>
        <span>Proteína {{ number_format($tot['proteina_g'],0,',','.') }}g</span>
        <span>Gordura {{ number_format($tot['gordura_g'],0,',','.') }}g</span>
        @if($plano->kcal_meta)<span>Meta {{ number_format($plano->kcal_meta,0,',','.') }} kcal</span>@endif
    </div>

    @php $custoMensal = $plano->custoMensal(); $ufRef = $plano->paciente->uf ?? $nutri->estado ?? 'BR'; @endphp
    @if($custoMensal > 0)
    <div class="obs" style="margin-top:10px;">
        <strong>Custo estimado:</strong> ≈ R$ {{ number_format($custoMensal,2,',','.') }}/mês
        (R$ {{ number_format($custoMensal/30,2,',','.') }}/dia) · referência {{ $ufRef }}.
        <span style="color:#777;">Estimativa com base em preços médios regionais; pode variar conforme mercado e marcas.</span>
    </div>
    @endif

    @if ($plano->observacoes)<div class="obs">{{ $plano->observacoes }}</div>@endif
</body>
</html>
