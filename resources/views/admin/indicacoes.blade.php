@extends('layouts.dashboard')
@section('titulo','Indicações')

@section('conteudo')
    <div style="margin-bottom:18px;">
        <h2 style="margin:0;">Programa de indicação</h2>
        <div class="muted" style="font-size:.85rem;">
            Créditos gerados para quem indicou. A transferência é feita fora do sistema —
            aqui você registra que foi paga.
        </div>
    </div>

    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:18px;">
        <div class="card">
            <div class="muted" style="font-size:.72rem;">Total a pagar</div>
            <div style="font-size:1.6rem; font-weight:800; color:var(--primary);">R$ {{ number_format($totalAReceber, 2, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="muted" style="font-size:.72rem;">Já pago</div>
            <div style="font-size:1.6rem; font-weight:800;">R$ {{ number_format($totalPago, 2, ',', '.') }}</div>
        </div>
    </div>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
        @foreach (['a_receber' => 'A receber', 'pago' => 'Pagos', 'cancelado' => 'Cancelados', 'todos' => 'Todos'] as $k => $v)
            <a href="{{ route('admin.indicacoes', ['status' => $k]) }}" class="btn btn-ghost btn-sm"
               @if($status === $k) style="border-color:var(--primary); color:var(--primary);" @endif>{{ $v }}</a>
        @endforeach
    </div>

    <div class="card">
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Data</th><th>Indicador</th><th>Indicado</th>
                        <th class="n">Comissão</th><th class="n">Crédito</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($creditos as $c)
                    @php
                        $tipos = config('indicacao.tipos');
                        $indicador = ($tipos[$c->indicador_tipo] ?? null)?->find($c->indicador_id);
                        $indicado = $c->indicado();
                    @endphp
                    <tr>
                        <td class="muted">{{ $c->created_at->format('d/m/Y') }}</td>
                        <td>
                            {{ $indicador->nome ?? '—' }}
                            <div class="muted" style="font-size:.7rem;">{{ config('indicacao.rotulos')[$c->indicador_tipo] ?? $c->indicador_tipo }}</div>
                        </td>
                        <td>
                            {{ $indicado->nome ?? '—' }}
                            <div class="muted" style="font-size:.7rem;">{{ config('indicacao.rotulos')[$c->indicado_tipo] ?? $c->indicado_tipo }}</div>
                        </td>
                        <td class="n muted">R$ {{ number_format($c->base_company_fee, 2, ',', '.') }}</td>
                        <td class="n"><strong>R$ {{ number_format($c->valor, 2, ',', '.') }}</strong></td>
                        <td>
                            <span class="badge {{ $c->status === 'pago' ? 'badge-ok' : ($c->status === 'cancelado' ? 'badge-dim' : 'badge-warn') }}">
                                {{ \App\Models\IndicacaoCredito::STATUS[$c->status] ?? $c->status }}
                            </span>
                            @if ($c->observacao)<div class="muted" style="font-size:.68rem;">{{ $c->observacao }}</div>@endif
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            @if ($c->status === 'a_receber')
                                <form method="POST" action="{{ route('admin.indicacoes.pago', $c->id) }}" style="display:inline;"
                                      onsubmit="return confirm('Confirmar que o valor já foi transferido ao indicador?')">
                                    @csrf @method('PUT')
                                    <button class="btn btn-sm"><i class="ph ph-check"></i> Marcar pago</button>
                                </form>
                                <form method="POST" action="{{ route('admin.indicacoes.cancelar', $c->id) }}" style="display:inline;"
                                      onsubmit="this.observacao.value = prompt('Motivo do cancelamento:') || ''; return this.observacao.value !== '';">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="observacao">
                                    <button class="btn btn-danger btn-sm"><i class="ph ph-x"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty" style="padding:26px;"><i class="ph ph-gift"></i>Nenhum crédito nesta situação.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px;">{{ $creditos->links() }}</div>
    </div>
@endsection
