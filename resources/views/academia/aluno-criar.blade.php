<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Aluno — {{ $academia->nome }}</title>
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
        input[type=text], input[type=email], input[type=date], input[type=number], select, textarea { width: 100%; padding: 11px; background: var(--field); border: 1px solid rgba(255,255,255,0.1); color: var(--text-main); border-radius: 9px; font-size: 0.92rem; font-family: inherit; margin-bottom: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); }
        textarea { resize: vertical; min-height: 70px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .grid-2 > div { min-width: 0; }

        .info-box { background:rgba(212,255,0,0.08); border:1px solid rgba(212,255,0,0.35); border-radius:12px; padding:14px 16px; margin-bottom:18px; font-size:0.82rem; color:#cfcfcf; line-height:1.5; }
        .info-box i { color: var(--primary); }

        .btn-salvar { width: 100%; padding: 16px; border: none; border-radius: 14px; background: var(--primary); color: #000; font-weight: 900; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.2s; }
        .btn-salvar:hover { filter: brightness(1.1); }

        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>

<body class="ed-page">
    <div class="top-bar">
        <a href="{{ route('academia.alunos') }}" class="back-btn" title="Voltar"><i class="ph ph-arrow-left"></i></a>
        <span class="title"><i class="ph ph-user-plus"></i> Cadastrar aluno</span>
    </div>

    <div class="container">
        <div class="ed-eyebrow"><i class="ph ph-user-plus"></i> Novo aluno</div>
        <h1 class="ed-h"><span class="ed-mark">Cadastrar aluno</span></h1>
        <p class="subtitle">O aluno é vinculado a {{ $academia->nome }} e já entra no site. Após salvar, você preenche a anamnese dele.</p>

        @if($errors->any())
            <div class="alert alert-error"><i class="ph ph-warning-circle"></i> {{ $errors->first() }}</div>
        @endif

        <div class="info-box">
            <i class="ph ph-key"></i>
            A senha inicial do aluno será <strong>123456</strong>. Ele acessa o site com o e-mail informado e essa senha,
            e troca a senha no próprio perfil quando entrar.
        </div>

        <form method="POST" action="{{ route('academia.alunos.store') }}">
            @csrf

            <div class="panel">
                <div class="panel-title"><i class="ph ph-identification-card"></i> Dados do aluno</div>

                <label class="lbl">Nome completo *</label>
                <input type="text" name="nome" value="{{ old('nome') }}" placeholder="Nome do aluno" required>

                <label class="lbl">E-mail (login) *</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="email@exemplo.com" required>

                <div class="grid-2">
                    <div>
                        <label class="lbl">WhatsApp</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="(11) 99999-9999" maxlength="15"
                            oninput="this.value=this.value.replace(/\D/g,'').replace(/(\d{2})(\d)/,'($1) $2').replace(/(\d{4,5})(\d{4})/,'$1-$2').replace(/(-\d{4})\d+?$/,'$1')">

                    </div>
                    <div>
                        <label class="lbl">Data de nascimento</label>
                        <input type="date" name="idade" value="{{ old('idade') }}">
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label class="lbl">Sexo *</label>
                        @php $sx = old('sexo', 'masculino'); @endphp
                        <select name="sexo" required>
                            <option value="masculino" {{ $sx === 'masculino' ? 'selected' : '' }}>Masculino</option>
                            <option value="feminino" {{ $sx === 'feminino' ? 'selected' : '' }}>Feminino</option>
                            <option value="outro" {{ $sx === 'outro' ? 'selected' : '' }}>Outro</option>
                        </select>
                    </div>
                    <div>
                        <label class="lbl">Plano</label>
                        @php $pl = old('plano'); @endphp
                        <select name="plano">
                            <option value="">Sem plano</option>
                            @foreach($planos as $plano)
                                <option value="{{ $plano->nome }}" {{ $pl === $plano->nome ? 'selected' : '' }}>{{ $plano->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($filiais->isNotEmpty())
                    <label class="lbl">Filial</label>
                    @php $filOld = old('filial_id'); @endphp
                    <select name="filial_id">
                        <option value="">Matriz (sem filial)</option>
                        @foreach($filiais as $f)
                            <option value="{{ $f->id }}" {{ (string) $filOld === (string) $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                        @endforeach
                    </select>
                @elseif($filialAtual)
                    <label class="lbl">Filial</label>
                    <input type="text" value="{{ $filialAtual->nome }}" disabled>
                    <p style="color:var(--text-muted); font-size:0.74rem; margin:-8px 0 14px;">O aluno será vinculado a esta filial automaticamente.</p>
                @endif

                <div class="grid-2">
                    <div>
                        <label class="lbl">Altura (cm)</label>
                        <input type="number" step="0.01" name="altura" value="{{ old('altura') }}" placeholder="175">
                    </div>
                    <div>
                        <label class="lbl">Peso (kg)</label>
                        <input type="number" step="0.01" name="peso" value="{{ old('peso') }}" placeholder="73.5">
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title"><i class="ph ph-target"></i> Objetivo &amp; saúde</div>

                <label class="lbl">Objetivo</label>
                <textarea name="resumo_objetivo" placeholder="Ex: emagrecimento, hipertrofia, saúde...">{{ old('resumo_objetivo') }}</textarea>

                <label class="lbl">Condição clínica</label>
                <textarea name="condicao_clinica" placeholder="Observações de saúde relevantes...">{{ old('condicao_clinica') }}</textarea>
            </div>

            <button type="submit" class="btn-salvar"><i class="ph ph-check-circle"></i> Cadastrar e preencher anamnese</button>
        </form>
    </div>
</body>

</html>
