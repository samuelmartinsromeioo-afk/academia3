{{-- Ficha de nutrição para impressão. Visual e modos: layouts/documento. --}}
@extends('layouts.documento')

@section('doc-titulo', 'Plano Alimentar')

{{-- Só o nome: objetivo e dias ficam na ficha de dados abaixo, e repetir aqui
     deixava a mesma informação em três lugares da mesma página. --}}
@section('doc-sub', $plano->paciente->nome ?? 'Modelo')

@section('doc-emissor')
    <strong>{{ $nutri->nome }}</strong><br>
    Nutricionista @if($nutri->crn) · CRN {{ $nutri->crn }} @endif
    <div class="doc-dim doc-extra">
        @if($nutri->whatsapp) {{ $nutri->whatsapp }}<br> @endif
        @if($nutri->email) {{ $nutri->email }} @endif
    </div>
@endsection

@section('doc-destinatario')
    <table>
        <tr><th>Paciente</th><td>{{ $plano->paciente->nome ?? 'Modelo (sem paciente)' }}</td></tr>
        @if($plano->paciente?->data_nascimento)
            <tr><th>Nascimento</th><td>
                {{ $plano->paciente->data_nascimento->format('d/m/Y') }}
                ({{ $plano->paciente->data_nascimento->age }} anos)
            </td></tr>
        @endif
        @if($plano->paciente?->objetivo)<tr><th>Objetivo do paciente</th><td>{{ $plano->paciente->objetivo }}</td></tr>@endif
        @if($plano->objetivo)<tr><th>Objetivo do plano</th><td>{{ $plano->objetivo }}</td></tr>@endif
        {{-- A meta diária aparece na linha de totais, ao lado do que foi
             somado — é lá que ela serve para comparar. --}}
        @unless($plano->is_modelo)
            <tr><th>Dias da semana</th><td>{{ $plano->diasSemanaLabels() }}</td></tr>
        @endunless
        {{-- `observacoes` do paciente é onde ficam restrições e alergias. --}}
        @if($plano->paciente?->observacoes)
            <tr><th>Observações do paciente</th><td>{{ $plano->paciente->observacoes }}</td></tr>
        @endif
    </table>
@endsection

@section('doc-corpo')
    @php $tot = $plano->totais(); @endphp

    @foreach ($plano->refeicoes as $ref)
        @php $rt = $ref->totais(); @endphp
        <div class="doc-card">
            <h3>
                <span>{{ $ref->nome }}@if($ref->horario) · {{ $ref->horario }}@endif</span>
                <span>{{ number_format($rt['kcal'], 0, ',', '.') }} kcal</span>
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Alimento</th>
                        <th class="n">Qtd</th>
                        <th class="n">Kcal</th>
                        <th class="n">Carbo</th>
                        <th class="n">Prot</th>
                        <th class="n">Gord</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($ref->itens as $it)
                    <tr>
                        <td>
                            {{ $it->descricao }}
                            @if($it->opcoes->count())
                                {{-- Substituições só na folha: na versão enxuta
                                     elas dobram a altura da tabela. --}}
                                <div class="doc-nota doc-extra">
                                    Substituições: {{ $it->opcoes->map(fn ($o) => $o->descricao.' ('.($o->medida ?: $o->quantidade_g.'g').')')->implode(' · ') }}
                                </div>
                            @endif
                            {{-- Preparo e alergênicos vivem no ALIMENTO do
                                 catálogo, não no item do plano. --}}
                            @if($it->alimento?->preparo)
                                <div class="doc-nota doc-extra">Preparo: {{ $it->alimento->preparo }}</div>
                            @endif
                            @if($it->alimento?->contem)
                                <div class="doc-nota doc-extra">Contém: {{ $it->alimento->contem }}</div>
                            @endif
                        </td>
                        <td class="n">{{ $it->medida ?: ($it->quantidade_g.' g') }}</td>
                        <td class="n">{{ number_format($it->kcal, 0, ',', '.') }}</td>
                        <td class="n">{{ number_format($it->carbo_g, 1, ',', '.') }}g</td>
                        <td class="n">{{ number_format($it->proteina_g, 1, ',', '.') }}g</td>
                        <td class="n">{{ number_format($it->gordura_g, 1, ',', '.') }}g</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($ref->observacoes)
                <div class="doc-obs" style="margin:10px 13px;">{{ $ref->observacoes }}</div>
            @endif
        </div>
    @endforeach

    <div class="doc-faixa">
        <span>TOTAL DO DIA</span>
        <span>{{ number_format($tot['kcal'], 0, ',', '.') }} kcal</span>
        <span>Carbo {{ number_format($tot['carbo_g'], 0, ',', '.') }}g</span>
        <span>Proteína {{ number_format($tot['proteina_g'], 0, ',', '.') }}g</span>
        <span>Gordura {{ number_format($tot['gordura_g'], 0, ',', '.') }}g</span>
        @if($plano->kcal_meta)<span>Meta {{ number_format($plano->kcal_meta, 0, ',', '.') }} kcal</span>@endif
    </div>

    @php
        $custoDia = $plano->custoDiario();
        $custoMensal = $plano->custoMensal();
        $ufRef = $plano->paciente->uf ?? $nutri->estado ?? 'BR';
    @endphp
    @if($custoMensal > 0)
        <div class="doc-obs">
            <strong>Custo estimado:</strong>
            R$ {{ number_format($custoDia, 2, ',', '.') }}/dia
            ≈ R$ {{ number_format($custoMensal, 2, ',', '.') }}/mês ({{ $plano->diasNoMes() }} dia(s) no mês) · referência {{ $ufRef }}.
            <span class="doc-nota">Estimativa com base em preços médios regionais; pode variar conforme mercado e marcas.</span>
        </div>
    @endif

    @if ($plano->observacoes)
        <div class="doc-obs">{{ $plano->observacoes }}</div>
    @endif
@endsection

@section('doc-legenda')
    <span class="doc-nota">
        Kcal, Carbo, Prot e Gord são por porção indicada em Qtd.
        “Substituições” são equivalentes em calorias — use uma por refeição, não some.
        Totais do dia consideram apenas os itens principais.
    </span>
@endsection

@section('doc-assinatura')
    <div class="nome">{{ $nutri->nome }}</div>
    <div class="reg">Nutricionista @if($nutri->crn) · CRN {{ $nutri->crn }} @endif</div>
@endsection

@section('doc-rodape')
    Plano alimentar · {{ $plano->paciente->nome ?? $plano->nome }}
@endsection
