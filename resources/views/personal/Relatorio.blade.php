{{-- Relatório mensal do aluno. Visual e modos: layouts/documento. --}}
@extends('layouts.documento')

@php $mesRef = ucfirst(now()->locale('pt_BR')->isoFormat('MMMM/YYYY')); @endphp

@section('doc-titulo', 'Relatório Mensal')
@section('doc-sub', $cliente->nome.' · '.$mesRef)

@section('doc-emissor')
    <strong>{{ $personal->nome }}</strong><br>
    @if($personal->cref) CREF {{ $personal->cref }} @else Personal trainer @endif
    <div class="doc-dim doc-extra">
        @if($personal->whatsapp) {{ $personal->whatsapp }}<br> @endif
        @if($personal->email) {{ $personal->email }} @endif
    </div>
@endsection

@section('doc-destinatario')
    <table>
        <tr><th>Aluno</th><td>{{ $cliente->nome }}</td></tr>
        @if($cliente->idade)
            <tr><th>Nascimento</th><td>
                {{ \Carbon\Carbon::parse($cliente->idade)->format('d/m/Y') }}
                ({{ \Carbon\Carbon::parse($cliente->idade)->age }} anos)
            </td></tr>
        @endif
        @if($cliente->resumo_objetivo)<tr><th>Objetivo</th><td>{{ $cliente->resumo_objetivo }}</td></tr>@endif
        <tr><th>Período</th><td>{{ $mesRef }}</td></tr>
        <tr><th>Treinos planejados</th><td>{{ $planejados }}</td></tr>
    </table>
@endsection

@section('doc-corpo')
    @if(session('success'))
        <div class="doc-obs doc-nao-imprime">{{ session('success') }}</div>
    @endif

    <div class="doc-grade">
        <div class="doc-stat">
            <div class="rot">Aderência</div>
            <div class="val">{{ $aderencia }}%</div>
        </div>
        <div class="doc-stat">
            <div class="rot">Treinos no mês</div>
            <div class="val">{{ $realizados }}</div>
            <div class="doc-nota">de {{ $planejados }} planejados</div>
        </div>
        <div class="doc-stat">
            <div class="rot">Sequência</div>
            <div class="val">{{ $streak['atual'] }}</div>
            <div class="doc-nota">recorde {{ $streak['recorde'] }}</div>
        </div>
        <div class="doc-stat">
            <div class="rot">Esforço médio</div>
            <div class="val">{{ $rpeMedio ? $rpeMedio.'/10' : '—' }}</div>
        </div>
    </div>

    @if($pesoIni !== null && $pesoFim !== null)
        @php $delta = (float) $pesoFim - (float) $pesoIni; @endphp
        <div class="doc-secao">Peso corporal</div>
        <div class="doc-card">
            <table>
                <tr>
                    <td>{{ $pesoIni }} kg → {{ $pesoFim }} kg</td>
                    <td class="n"><strong>{{ $delta > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($delta, 2, ',', '.'), '0'), ',') }} kg</strong></td>
                </tr>
            </table>
        </div>
    @endif

    <div class="doc-secao">Recordes do mês</div>
    @if(count($recordes) === 0)
        <p class="doc-nota">Nenhum recorde batido neste mês.</p>
    @else
        <div class="doc-card">
            <table>
                <thead><tr><th>Exercício</th><th>Data</th><th class="n">Carga</th></tr></thead>
                <tbody>
                @foreach($recordes as $r)
                    <tr>
                        <td>{{ $r['exercicio'] }}</td>
                        <td>{{ $r['data']->format('d/m/Y') }}</td>
                        <td class="n"><strong>{{ rtrim(rtrim(number_format($r['peso'], 2, ',', '.'), '0'), ',') }} kg</strong></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="doc-nao-imprime">
        <a href="{{ route('fichas-treino.aluno', $cliente->id) }}" class="doc-btn doc-btn--alt">Voltar</a>
        <form method="POST" action="{{ route('relatorio.enviar', $cliente->id) }}">
            @csrf
            <button type="submit" class="doc-btn">Enviar ao aluno</button>
        </form>
    </div>
@endsection

@section('doc-legenda')
    <span class="doc-nota">
        <strong>Aderência</strong> = treinos realizados ÷ planejados no período.
        <strong>Sequência</strong> = dias consecutivos com treino concluído.
        <strong>Esforço médio</strong> = média do RPE informado pelo aluno (0 a 10).
        <strong>Recorde</strong> = maior carga registrada no exercício dentro do mês.
    </span>
@endsection

@section('doc-assinatura')
    <div class="nome">{{ $personal->nome }}</div>
    <div class="reg">@if($personal->cref) CREF {{ $personal->cref }} @else Personal trainer @endif</div>
@endsection

@section('doc-rodape', 'Relatório mensal · '.$cliente->nome.' · '.$mesRef)
