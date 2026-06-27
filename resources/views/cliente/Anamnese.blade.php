<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anamnese</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="{{ asset('css/snrfit-brand.css') }}">
    <style>
        :root {
            --primary: var(--snr-lime); --bg-dark: var(--snr-bg); --card-bg: var(--snr-surface); --field: var(--snr-surface-2);
            --text-main: var(--snr-text); --text-muted: var(--snr-dim); --green: var(--snr-success); --red: var(--snr-error);
            --border: rgba(255, 255, 255, 0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-dark); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: var(--text-main); min-height: 100vh; background-image: radial-gradient(circle at 50% -10%, rgba(212, 255, 0, 0.12), transparent 50%); }
        a { color: inherit; text-decoration: none; }
        .top-bar { display: flex; align-items: center; gap: 15px; padding: 15px 40px; background: rgba(0,0,0,0.6); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .back-btn { background: var(--card-bg); border: 1px solid var(--border); color: var(--primary); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: 0.3s; }
        .back-btn:hover { background: var(--primary); color: #000; }
        .top-bar .title { font-weight: 800; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .top-bar .title i { color: var(--primary); }
        .container { max-width: 760px; margin: 28px auto; padding: 0 20px; }
        h1 { font-size: 1.6rem; font-weight: 900; color: var(--primary); margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
        .subtitle { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 22px; }

        .alert { padding: 14px; border-radius: 12px; margin-bottom: 18px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: rgba(255,82,82,0.12); color: var(--red); border: 1px solid var(--red); }

        .panel { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 22px; margin-bottom: 18px; }
        .panel-title { font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary); font-weight: 900; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

        label.lbl { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 900; display: block; margin-bottom: 5px; }
        input[type=text], select, textarea { width: 100%; padding: 11px; background: var(--field); border: 1px solid rgba(255,255,255,0.1); color: var(--text-main); border-radius: 9px; font-size: 0.92rem; font-family: inherit; margin-bottom: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); }
        textarea { resize: vertical; min-height: 70px; }

        .parq-item { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .parq-item:last-child { border-bottom: none; }
        .parq-item .q { flex: 1; font-size: 0.88rem; line-height: 1.4; }
        .switch { position: relative; display: inline-block; width: 92px; flex-shrink: 0; }
        .switch input { display: none; }
        .switch .track { display: flex; background: var(--field); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; overflow: hidden; cursor: pointer; font-size: 0.74rem; font-weight: 800; text-align: center; }
        .switch .track span { flex: 1; padding: 8px 0; color: var(--text-muted); transition: 0.15s; }
        .switch input:not(:checked) ~ .track .nao { background: rgba(0,230,118,0.15); color: var(--green); }
        .switch input:checked ~ .track .sim { background: rgba(255,82,82,0.18); color: var(--red); }

        .parq-aviso { font-size: 0.72rem; color: var(--text-muted); margin-top: 12px; line-height: 1.5; }

        .btn-salvar { width: 100%; padding: 16px; border: none; border-radius: 14px; background: var(--primary); color: #000; font-weight: 900; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.2s; }
        .btn-salvar:hover { filter: brightness(1.1); }

        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('cliente.index') }}" class="back-btn" title="Voltar"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-first-aid"></i> Anamnese</span>
    </div>

    <div class="container">
        <div class="ed-eyebrow"><i class="ph ph-first-aid"></i> AvaliaÃ§Ã£o</div><h1 class="ed-h"><span class="ed-mark">Anamnese</span></h1>
        <p class="subtitle">Essas informações ajudam seu personal a montar um treino seguro e eficiente para você.</p>

        @if($errors->any())
            <div class="alert alert-error"><i class="ph ph-warning-circle"></i> {{ $errors->first() }}</div>
        @endif

        <div style="background:rgba(212,255,0,0.08); border:1px solid rgba(212,255,0,0.35); border-radius:12px; padding:14px 16px; margin-bottom:18px; font-size:0.82rem; color:#cfcfcf; line-height:1.5;">
            <i class="ph ph-shield-check" style="color:var(--primary);"></i>
            São <strong>dados sensíveis de saúde</strong>. Ao salvar, você <strong>consente</strong> com o uso dessas informações pelo seu personal para montar um treino seguro, conforme a
            <a href="{{ route('lgpd.politica') }}" target="_blank" style="color:var(--primary);">Política de Privacidade</a>. Você pode editá-las ou removê-las quando quiser.
        </div>

        <form method="POST" action="{{ route('anamnese.salvar') }}">
            @csrf

            <div class="panel">
                <div class="panel-title"><i class="ph ph-target"></i> Objetivos</div>
                <label class="lbl">Objetivo principal</label>
                <input type="text" name="objetivo_principal" value="{{ old('objetivo_principal', $anamnese->objetivo_principal) }}" placeholder="Ex: emagrecimento, hipertrofia, saúde...">

                <label class="lbl">Nível de atividade atual</label>
                @php $niv = old('nivel_atividade', $anamnese->nivel_atividade); @endphp
                <select name="nivel_atividade">
                    <option value="">Selecione...</option>
                    <option value="sedentario" {{ $niv === 'sedentario' ? 'selected' : '' }}>Sedentário</option>
                    <option value="leve" {{ $niv === 'leve' ? 'selected' : '' }}>Leve (1-2x/semana)</option>
                    <option value="moderado" {{ $niv === 'moderado' ? 'selected' : '' }}>Moderado (3-4x/semana)</option>
                    <option value="intenso" {{ $niv === 'intenso' ? 'selected' : '' }}>Intenso (5x+/semana)</option>
                </select>
            </div>

            <div class="panel">
                <div class="panel-title"><i class="ph ph-notepad"></i> Saúde &amp; histórico</div>
                <label class="lbl">Histórico de lesões</label>
                <textarea name="historico_lesoes" placeholder="Lesões atuais ou passadas...">{{ old('historico_lesoes', $anamnese->historico_lesoes) }}</textarea>

                <label class="lbl">Restrições médicas</label>
                <textarea name="restricoes_medicas" placeholder="Movimentos/exercícios que deve evitar...">{{ old('restricoes_medicas', $anamnese->restricoes_medicas) }}</textarea>

                <label class="lbl">Doenças pré-existentes</label>
                <textarea name="doencas_preexistentes" placeholder="Diabetes, hipertensão, asma...">{{ old('doencas_preexistentes', $anamnese->doencas_preexistentes) }}</textarea>

                <label class="lbl">Medicamentos em uso</label>
                <textarea name="medicamentos" placeholder="Medicamentos contínuos...">{{ old('medicamentos', $anamnese->medicamentos) }}</textarea>

                <label class="lbl">Cirurgias</label>
                <textarea name="cirurgias" placeholder="Cirurgias realizadas e quando...">{{ old('cirurgias', $anamnese->cirurgias) }}</textarea>
            </div>

            <div class="panel">
                <div class="panel-title"><i class="ph ph-heartbeat"></i> PAR-Q · Prontidão para atividade física</div>
                @foreach(\App\Models\Anamnese::PERGUNTAS_PARQ as $campo => $pergunta)
                    <div class="parq-item">
                        <div class="q">{{ $pergunta }}</div>
                        <label class="switch">
                            <input type="checkbox" name="{{ $campo }}" value="1" {{ old($campo, $anamnese->{$campo}) ? 'checked' : '' }}>
                            <span class="track"><span class="nao">Não</span><span class="sim">Sim</span></span>
                        </label>
                    </div>
                @endforeach
                <label class="lbl" style="margin-top:16px;">Se respondeu "Sim" a alguma, explique</label>
                <textarea name="parq_observacoes" placeholder="Detalhe as respostas marcadas como Sim...">{{ old('parq_observacoes', $anamnese->parq_observacoes) }}</textarea>
                <p class="parq-aviso"><i class="ph ph-info"></i> Respostas "Sim" no PAR-Q indicam que é recomendável avaliação médica antes de iniciar ou intensificar a atividade física.</p>
            </div>

            <div class="panel">
                <div class="panel-title"><i class="ph ph-chat-circle"></i> Observações</div>
                <textarea name="observacoes" placeholder="Algo mais que seu personal deva saber?">{{ old('observacoes', $anamnese->observacoes) }}</textarea>
            </div>

            <button type="submit" class="btn-salvar"><i class="ph ph-floppy-disk"></i> Salvar anamnese</button>
        </form>
    </div>
</body>

</html>
