{{-- Recibo para impressão. Visual e modos: layouts/documento. --}}
@extends('layouts.documento')

@php
    // Montado em PHP porque diretiva Blade colada em palavra
    // ("Nutricionista@if") ou em outra diretiva ("@endif@endif") não é
    // reconhecida pelo compilador e vira texto literal.
    $registro = $nutri->crn ? ' · CRN '.$nutri->crn : '';
    $local = trim(($nutri->cidade ?? '').($nutri->estado ? '/'.$nutri->estado : ''), '/');
    $numero = str_pad($cobranca->id, 6, '0', STR_PAD_LEFT);
    $dataRecibo = optional($cobranca->pago_em)->translatedFormat('d \d\e F \d\e Y')
        ?? now()->translatedFormat('d \d\e F \d\e Y');
@endphp

@section('doc-titulo', 'Recibo')
@section('doc-sub', 'Nº '.$numero)

@section('doc-emissor')
    <strong>{{ $nutri->nome }}</strong><br>
    Nutricionista{{ $registro }}
    <div class="doc-dim doc-extra">
        @if($nutri->cpf) CPF {{ $nutri->cpf }}<br> @endif
        @if($local) {{ $local }}<br> @endif
        @if($nutri->whatsapp) {{ $nutri->whatsapp }} @endif
    </div>
@endsection

@section('doc-destinatario')
    <table>
        <tr><th>Pagador</th><td>{{ $cobranca->paciente->nome ?? 'Paciente não identificado' }}</td></tr>
        @if($cobranca->paciente?->whatsapp)
            <tr><th>Contato</th><td>{{ $cobranca->paciente->whatsapp }}</td></tr>
        @endif
        <tr><th>Referente a</th><td>{{ $cobranca->descricao }}</td></tr>
        <tr><th>Valor</th><td>R$ {{ number_format($cobranca->valor, 2, ',', '.') }} ({{ $extenso }})</td></tr>
        <tr><th>Pago em</th><td>{{ optional($cobranca->pago_em)->format('d/m/Y H:i') ?? '—' }}</td></tr>
        @if($cobranca->asaas_payment_id)
            <tr><th>Transação</th><td>{{ $cobranca->asaas_payment_id }}</td></tr>
        @endif
    </table>
@endsection

@section('doc-corpo')
    <div class="doc-faixa" style="font-size:1.3rem;">
        R$ {{ number_format($cobranca->valor, 2, ',', '.') }}
    </div>

    <div class="doc-obs" style="font-size:0.95rem; line-height:1.9; text-align:justify;">
        Recebi de <strong>{{ $cobranca->paciente->nome ?? 'paciente não identificado' }}</strong>
        a importância de <strong>{{ $extenso }}</strong>,
        referente a <strong>{{ $cobranca->descricao }}</strong>,
        pela qual dou plena e geral quitação.
    </div>

    <div style="text-align:center; margin-top:26px; font-size:0.9rem;">
        @if($nutri->cidade){{ $nutri->cidade }}, @endif{{ $dataRecibo }}
    </div>
@endsection

@section('doc-assinatura')
    <div class="nome">{{ $nutri->nome }}</div>
    <div class="reg">Nutricionista{{ $nutri->crn ? ' — CRN '.$nutri->crn : '' }}</div>
@endsection

@section('doc-rodape', 'Recibo nº '.$numero.' · '.$nutri->nome)
