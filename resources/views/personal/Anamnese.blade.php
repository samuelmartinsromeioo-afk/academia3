<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anamnese · {{ $cliente->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">
    <style>
        :root {
            --primary: var(--snr-lime); --bg-dark: var(--snr-bg); --card-bg: var(--snr-surface);
            --text-main: var(--snr-text); --text-muted: var(--snr-dim); --green: var(--snr-success); --red: var(--snr-error);
            --border: rgba(255, 255, 255, 0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-dark); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: var(--text-main); min-height: 100vh; background-image: radial-gradient(circle at 12% -10%, rgba(212, 255, 0, 0.10), transparent 45%); }
        a { color: inherit; text-decoration: none; }
        .top-bar { display: flex; align-items: center; gap: 15px; padding: 15px 40px; background: rgba(0,0,0,0.6); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .back-btn { background: var(--card-bg); border: 1px solid var(--border); color: var(--primary); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: 0.3s; }
        .back-btn:hover { background: var(--primary); color: #000; }
        .top-bar .title { font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .top-bar .title i { color: var(--primary); }
        .container { max-width: 760px; margin: 28px auto; padding: 0 20px; }
        h1 { font-size: 1.5rem; font-weight: 900; color: var(--primary); margin-bottom: 4px; display: flex; align-items: center; gap: 10px; }
        .subtitle { color: var(--text-muted); font-size: 0.82rem; margin-bottom: 22px; }

        .panel { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 22px; margin-bottom: 18px; }
        .panel-title { font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary); font-weight: 900; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

        .field { margin-bottom: 14px; }
        .field:last-child { margin-bottom: 0; }
        .field .k { font-size: 0.64rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 900; margin-bottom: 4px; }
        .field .v { font-size: 0.92rem; line-height: 1.5; white-space: pre-line; }
        .field .v.vazio { color: var(--text-muted); font-style: italic; }

        .parq-item { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.86rem; }
        .parq-item:last-child { border-bottom: none; }
        .resp { font-size: 0.72rem; font-weight: 900; padding: 4px 12px; border-radius: 20px; flex-shrink: 0; }
        .resp.sim { background: rgba(255,82,82,0.18); color: var(--red); border: 1px solid var(--red); }
        .resp.nao { background: rgba(0,230,118,0.12); color: var(--green); border: 1px solid rgba(0,230,118,0.4); }

        .alerta-parq { background: rgba(255,82,82,0.1); border: 1px solid var(--red); color: var(--red); border-radius: 12px; padding: 14px; margin-bottom: 18px; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; }

        .empty { text-align: center; padding: 70px 20px; color: var(--text-muted); background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; }
        .empty i { font-size: 3rem; color: var(--primary); margin-bottom: 16px; display: block; opacity: 0.8; }
        .empty p { font-size: 1.05rem; color: var(--text-main); }

        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ url()->previous() }}" class="back-btn" title="Voltar"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-first-aid"></i> Anamnese</span>
    </div>

    <div class="container">
        <div class="ed-eyebrow"><i class="ph ph-first-aid"></i> Anamnese do aluno</div><h1 class="ed-h">{{ strtoupper($cliente->nome) }}</h1>

        @if(!$anamnese)
            <p class="subtitle">Anamnese do aluno.</p>
            <div class="empty">
                <i class="ph ph-file-x"></i>
                <p>Este aluno ainda não preencheu a anamnese.</p>
                <small>Peça para ele completar pelo menu "Anamnese" no app.</small>
            </div>
        @else
            <p class="subtitle">
                Preenchida em {{ $anamnese->preenchida_em?->format('d/m/Y H:i') ?? '—' }}
            </p>

            @if($anamnese->temAlertaParq())
                <div class="alerta-parq">
                    <i class="ph ph-warning"></i>
                    Atenção: há resposta(s) "Sim" no PAR-Q. Recomenda-se avaliação médica antes de intensificar os treinos.
                </div>
            @endif

            @php
                $campo = function ($valor) {
                    $valor = trim((string) $valor);
                    return $valor !== ''
                        ? '<span class="v">' . e($valor) . '</span>'
                        : '<span class="v vazio">Não informado</span>';
                };
            @endphp

            <div class="panel">
                <div class="panel-title"><i class="ph ph-target"></i> Objetivos</div>
                <div class="field"><div class="k">Objetivo principal</div>{!! $campo($anamnese->objetivo_principal) !!}</div>
                <div class="field"><div class="k">Nível de atividade</div>{!! $campo($anamnese->nivel_atividade ? ucfirst($anamnese->nivel_atividade) : '') !!}</div>
            </div>

            <div class="panel">
                <div class="panel-title"><i class="ph ph-notepad"></i> Saúde &amp; histórico</div>
                <div class="field"><div class="k">Histórico de lesões</div>{!! $campo($anamnese->historico_lesoes) !!}</div>
                <div class="field"><div class="k">Restrições médicas</div>{!! $campo($anamnese->restricoes_medicas) !!}</div>
                <div class="field"><div class="k">Doenças pré-existentes</div>{!! $campo($anamnese->doencas_preexistentes) !!}</div>
                <div class="field"><div class="k">Medicamentos</div>{!! $campo($anamnese->medicamentos) !!}</div>
                <div class="field"><div class="k">Cirurgias</div>{!! $campo($anamnese->cirurgias) !!}</div>
            </div>

            <div class="panel">
                <div class="panel-title"><i class="ph ph-heartbeat"></i> PAR-Q</div>
                @foreach(\App\Models\Anamnese::PERGUNTAS_PARQ as $c => $pergunta)
                    <div class="parq-item">
                        <span>{{ $pergunta }}</span>
                        @if($anamnese->{$c})
                            <span class="resp sim">SIM</span>
                        @else
                            <span class="resp nao">NÃO</span>
                        @endif
                    </div>
                @endforeach
                @if(trim((string) $anamnese->parq_observacoes) !== '')
                    <div class="field" style="margin-top:14px;"><div class="k">Observações do PAR-Q</div><span class="v">{{ $anamnese->parq_observacoes }}</span></div>
                @endif
            </div>

            @if(trim((string) $anamnese->observacoes) !== '')
                <div class="panel">
                    <div class="panel-title"><i class="ph ph-chat-circle"></i> Observações</div>
                    <div class="field"><span class="v">{{ $anamnese->observacoes }}</span></div>
                </div>
            @endif
        @endif
    </div>
</body>

</html>
