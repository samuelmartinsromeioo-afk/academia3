<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recibo nº {{ str_pad($cobranca->id, 6, '0', STR_PAD_LEFT) }} — {{ $nutri->nome }}</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; color:#111; margin:0; padding:32px; }
        .folha { max-width:760px; margin:0 auto; border:1px solid #ddd; border-radius:12px; padding:34px; }
        .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #0a0b0d; padding-bottom:14px; margin-bottom:24px; }
        .head h1 { margin:0; font-size:1.5rem; letter-spacing:1px; }
        .head .num { font-size:.8rem; color:#666; }
        .emit { text-align:right; font-size:.85rem; color:#444; line-height:1.5; }
        .valor { background:#0a0b0d; color:#d4ff00; display:inline-block; padding:10px 20px; border-radius:8px; font-size:1.5rem; font-weight:700; margin-bottom:20px; }
        .corpo { font-size:1rem; line-height:1.9; text-align:justify; }
        .corpo strong { border-bottom:1px solid #999; padding-bottom:1px; }
        .rodape { margin-top:46px; text-align:center; }
        .assin { border-top:1px solid #111; width:340px; margin:0 auto; padding-top:8px; font-size:.85rem; }
        .infos { margin-top:26px; font-size:.78rem; color:#666; border-top:1px solid #eee; padding-top:12px; display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; }
        .print-btn { position:fixed; top:16px; right:16px; background:#0a0b0d; color:#d4ff00; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; }
        @media print { .print-btn { display:none; } body { padding:0; } .folha { border:none; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Imprimir / salvar PDF</button>

    <div class="folha">
        <div class="head">
            <div>
                <h1>RECIBO</h1>
                <div class="num">Nº {{ str_pad($cobranca->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
            @php
                // Montado em PHP porque diretiva Blade colada em palavra
                // ("Nutricionista@if") ou em outra diretiva ("@endif@endif") não
                // é reconhecida pelo compilador e vira texto literal.
                $registro = $nutri->crn ? ' · CRN '.$nutri->crn : '';
                $local = trim(($nutri->cidade ?? '').($nutri->estado ? '/'.$nutri->estado : ''), '/');
            @endphp
            <div class="emit">
                <strong>{{ $nutri->nome }}</strong><br>
                Nutricionista{{ $registro }}<br>
                @if ($nutri->cpf)CPF {{ $nutri->cpf }}<br>@endif
                {{ $local }}
            </div>
        </div>

        <div class="valor">R$ {{ number_format($cobranca->valor, 2, ',', '.') }}</div>

        <div class="corpo">
            Recebi de <strong>{{ $cobranca->paciente->nome ?? 'paciente não identificado' }}</strong>
            a importância de <strong>{{ $extenso }}</strong>,
            referente a <strong>{{ $cobranca->descricao }}</strong>,
            pela qual dou plena e geral quitação.
        </div>

        <div class="rodape">
            <div style="font-size:.9rem; margin-bottom:42px;">
                @if ($nutri->cidade){{ $nutri->cidade }}, @endif
                {{ optional($cobranca->pago_em)->translatedFormat('d \d\e F \d\e Y') ?? now()->translatedFormat('d \d\e F \d\e Y') }}
            </div>
            <div class="assin">
                {{ $nutri->nome }}<br>
                <span style="color:#666; font-size:.78rem;">Nutricionista{{ $nutri->crn ? ' — CRN '.$nutri->crn : '' }}</span>
            </div>
        </div>

        <div class="infos">
            <span>Pagamento confirmado em {{ optional($cobranca->pago_em)->format('d/m/Y H:i') ?? '—' }}</span>
            @if ($cobranca->asaas_payment_id)<span>Transação: {{ $cobranca->asaas_payment_id }}</span>@endif
            <span>Emitido pelo SNR FIT em {{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>
</body>
</html>
