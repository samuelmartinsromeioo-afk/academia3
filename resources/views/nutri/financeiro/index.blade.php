@extends('layouts.nutri')
@section('titulo', 'Financeiro')

@section('conteudo')
    <div class="topbar">
        <div><h1>Financeiro</h1><div class="sub">Cobranças e recebimentos do consultório.</div></div>
        <button class="btn" onclick="document.getElementById('nova').style.display='block'"><i class="ph ph-plus"></i> Nova cobrança</button>
    </div>

    <div class="grid" style="grid-template-columns:repeat(2,1fr); margin-bottom:18px;">
        <div class="stat"><div class="n" style="color:var(--ok)">R$ {{ number_format($recebido,2,',','.') }}</div><div class="l">Total recebido</div></div>
        <div class="stat"><div class="n" style="color:var(--warn)">R$ {{ number_format($pendente,2,',','.') }}</div><div class="l">A receber</div></div>
    </div>

    <div id="nova" class="card" style="display:none; margin-bottom:18px;">
        <form method="POST" action="{{ route('nutri.cobrancas.store') }}">
            @csrf
            <div class="grid" style="grid-template-columns:1.4fr 2fr 1fr 1fr;">
                <div><label>Paciente</label>
                    <select name="paciente_id"><option value="">Avulsa</option>@foreach ($pacientes as $p)<option value="{{ $p->id }}">{{ $p->nome }}</option>@endforeach</select>
                </div>
                <div><label>Descrição</label><input name="descricao" required placeholder="Ex.: Consulta + plano alimentar"></div>
                <div><label>Valor (R$)</label><input type="number" step="0.01" name="valor" required></div>
                <div><label>Vencimento</label><input type="date" name="vencimento"></div>
            </div>
            <button class="btn" style="margin-top:12px;"><i class="ph ph-currency-dollar"></i> Criar cobrança</button>
            <span class="muted" style="font-size:.75rem; margin-left:10px;">Gera link de pagamento na sua subconta Asaas, se disponível.</span>
        </form>
    </div>

    <div class="card">
        @if ($cobrancas->count())
        <table>
            <thead><tr><th>Descrição</th><th>Paciente</th><th>Valor</th><th>Venc.</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($cobrancas as $c)
                <tr>
                    <td><strong>{{ $c->descricao }}</strong>@if($c->link_pagamento)<div><a href="{{ $c->link_pagamento }}" target="_blank" class="muted" style="font-size:.72rem;">Link de pagamento</a></div>@endif</td>
                    <td class="muted">{{ $c->paciente->nome ?? '—' }}</td>
                    <td>R$ {{ number_format($c->valor,2,',','.') }}</td>
                    <td class="muted">{{ optional($c->vencimento)->format('d/m/Y') ?? '—' }}</td>
                    <td><span class="badge {{ $c->status==='pago'?'badge-ok':'badge-warn' }}">{{ ucfirst($c->status) }}</span></td>
                    <td style="text-align:right; white-space:nowrap;">
                        @if ($c->status!=='pago')
                        <form method="POST" action="{{ route('nutri.cobrancas.pago',$c->id) }}" style="display:inline;">@csrf @method('PUT')<button class="btn btn-sm">Marcar pago</button></form>
                        @endif
                        <form method="POST" action="{{ route('nutri.cobrancas.destroy',$c->id) }}" style="display:inline;" onsubmit="return confirm('Remover?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:14px;">{{ $cobrancas->links() }}</div>
        @else
            <div class="empty"><i class="ph ph-currency-circle-dollar"></i>Nenhuma cobrança registrada.</div>
        @endif
    </div>
@endsection
