{{-- Relatório de evolução do paciente. Visual e modos: layouts/documento. --}}
@extends('layouts.documento')

@section('doc-titulo', 'Relatório de Evolução')
@section('doc-sub', $paciente->nome)

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
        <tr><th>Paciente</th><td>{{ $paciente->nome }}</td></tr>
        @if($paciente->data_nascimento)
            <tr><th>Nascimento</th><td>
                {{ $paciente->data_nascimento->format('d/m/Y') }} ({{ $paciente->data_nascimento->age }} anos)
            </td></tr>
        @endif
        @if($paciente->sexo)<tr><th>Sexo</th><td>{{ $paciente->sexo }}</td></tr>@endif
        @if($paciente->altura_cm)<tr><th>Altura</th><td>{{ $paciente->altura_cm }} cm</td></tr>@endif
        @if($paciente->objetivo)<tr><th>Objetivo</th><td>{{ $paciente->objetivo }}</td></tr>@endif
        @if($paciente->observacoes)<tr><th>Observações</th><td>{{ $paciente->observacoes }}</td></tr>@endif
    </table>
@endsection

@section('doc-corpo')
    {{-- Objetivo, idade, sexo e altura ficam só no bloco do paciente, acima.
         Havia uma cópia aqui porque antes esse bloco era exclusivo da folha
         branca e a versão escura precisava do resumo; com um modo só, as duas
         apareciam juntas. --}}
    <div class="doc-secao">Evolução antropométrica</div>
    @if ($paciente->antropometrias->count())
        <div class="doc-card">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th class="n">Peso</th>
                        <th class="n">IMC</th>
                        <th class="n">% Gordura</th>
                        <th class="n">Cintura</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($paciente->antropometrias->sortByDesc('data') as $a)
                    <tr>
                        <td>{{ $a->data->format('d/m/Y') }}</td>
                        <td class="n">{{ $a->peso ?? '—' }}</td>
                        <td class="n">{{ $a->imc ?? '—' }}</td>
                        <td class="n">{{ $a->percentual_gordura ?? '—' }}</td>
                        <td class="n">{{ $a->circunferencias['cintura'] ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="doc-nota">Sem avaliações registradas.</p>
    @endif

    <div class="doc-secao">Plano alimentar ativo</div>
    @if ($planoAtivo)
        @php $t = $planoAtivo->totais(); @endphp
        <div class="doc-card">
            <table>
                <tr>
                    <td><strong>{{ $planoAtivo->nome }}</strong></td>
                    <td class="n">{{ number_format($t['kcal'], 0, ',', '.') }} kcal/dia</td>
                </tr>
                <tr>
                    <td class="doc-nota">Macros</td>
                    <td class="n doc-nota">
                        Carbo {{ number_format($t['carbo_g'], 0) }}g ·
                        Proteína {{ number_format($t['proteina_g'], 0) }}g ·
                        Gordura {{ number_format($t['gordura_g'], 0) }}g
                    </td>
                </tr>
            </table>
        </div>
    @else
        <p class="doc-nota">Nenhum plano ativo.</p>
    @endif

    <div class="doc-secao">Anamnese mais recente</div>
    @php $an = $paciente->anamneses->first(); @endphp
    @if ($an)
        <div class="doc-card">
            <table>
                <tbody>
                @foreach ($an->respostas as $campo => $resp)
                    <tr>
                        <th style="width:40%;">{{ $campo }}</th>
                        <td>{{ is_array($resp) ? implode(', ', $resp) : $resp }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="doc-nota">Sem anamnese registrada.</p>
    @endif
@endsection

@section('doc-legenda')
    <span class="doc-nota">
        <strong>IMC</strong> = peso ÷ altura². <strong>% Gordura</strong> conforme o
        protocolo usado na avaliação. <strong>Cintura</strong> em cm, na menor
        circunferência. Linhas em ordem da avaliação mais recente para a mais antiga.
    </span>
@endsection

@section('doc-assinatura')
    <div class="nome">{{ $nutri->nome }}</div>
    <div class="reg">Nutricionista @if($nutri->crn) · CRN {{ $nutri->crn }} @endif</div>
@endsection

@section('doc-rodape', 'Relatório de evolução · '.$paciente->nome)
