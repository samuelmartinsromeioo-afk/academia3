<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacidade e meus dados</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#F4BE16; --bg:#000; --card:#111317; --field:#1a1d23; --text:#fff; --muted:#9a9a9a; --red:#ff5252; --green:#00e676; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:var(--text); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; min-height:100vh; background-image:radial-gradient(circle at 50% -10%, rgba(244,190,22,0.1), transparent 50%); }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:15px; padding:15px 40px; background:rgba(0,0,0,0.6); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; backdrop-filter:blur(10px); }
        .back-btn { background:var(--card); border:1px solid var(--border); color:var(--primary); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .back-btn:hover { background:var(--primary); color:#000; }
        .top-bar .title { font-weight:800; font-size:0.95rem; display:flex; align-items:center; gap:8px; } .top-bar .title i { color:var(--primary); }
        .container { max-width:680px; margin:26px auto; padding:0 20px; }
        h1 { font-size:1.5rem; font-weight:900; color:var(--primary); margin-bottom:6px; display:flex; align-items:center; gap:10px; }
        .subtitle { color:var(--muted); font-size:0.9rem; margin-bottom:22px; }
        .panel { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:22px; margin-bottom:18px; }
        .panel h2 { font-size:1rem; margin-bottom:8px; display:flex; align-items:center; gap:8px; }
        .panel h2 i { color:var(--primary); }
        .panel p { color:var(--muted); font-size:0.88rem; line-height:1.6; margin-bottom:14px; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 18px; border:none; border-radius:10px; font-weight:900; font-size:0.85rem; cursor:pointer; }
        .btn-primary { background:var(--primary); color:#000; }
        .btn-ghost { background:var(--field); color:#fff; border:1px solid var(--border); }
        .danger { border-color:rgba(255,82,82,0.4); }
        .danger h2 i { color:var(--red); }
        .btn-danger { background:var(--red); color:#fff; }
        .field { display:flex; flex-direction:column; gap:6px; margin-bottom:12px; }
        .field label { font-size:0.66rem; text-transform:uppercase; color:var(--muted); font-weight:900; }
        .field input { padding:11px; background:var(--field); border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:9px; }
        .field input:focus { outline:none; border-color:var(--red); }
        .err { color:var(--red); font-size:0.82rem; margin-bottom:10px; }
        .chk { display:flex; align-items:flex-start; gap:8px; font-size:0.85rem; color:var(--text); margin-bottom:14px; }
        .chk input { margin-top:3px; accent-color:var(--red); }
    </style>
</head>

<body>
    <div class="top-bar">
        <a href="{{ $voltar }}" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <span class="title"><i class="fas fa-user-shield"></i> Privacidade e meus dados</span>
    </div>

    <div class="container">
        <h1><i class="fas fa-user-shield"></i> MEUS DADOS</h1>
        <p class="subtitle">Seus direitos sobre os seus dados pessoais, conforme a LGPD.</p>

        <div class="panel">
            <h2><i class="fas fa-file-shield"></i> Política de Privacidade</h2>
            <p>Entenda quais dados coletamos, por que, com quem compartilhamos e por quanto tempo guardamos.</p>
            <a href="{{ route('lgpd.politica') }}" target="_blank" class="btn btn-ghost"><i class="fas fa-up-right-from-square"></i> Ler política</a>
        </div>

        <div class="panel">
            <h2><i class="fas fa-download"></i> Exportar meus dados</h2>
            <p>Baixe uma cópia de todos os seus dados em formato JSON (acesso e portabilidade).</p>
            <a href="{{ route('lgpd.exportar') }}" class="btn btn-primary"><i class="fas fa-download"></i> Baixar meus dados</a>
        </div>

        <div class="panel danger">
            <h2><i class="fas fa-triangle-exclamation"></i> Excluir minha conta</h2>
            <p>
                Esta ação encerra sua conta, remove seus dados pessoais e sensíveis (saúde, fotos, medidas, mensagens) e
                não pode ser desfeita. Registros financeiros podem ser retidos pelo prazo exigido por lei, de forma desvinculada da sua identidade.
            </p>

            @if($errors->any())
                <div class="err"><i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('lgpd.excluir') }}" onsubmit="return confirm('Tem certeza? Esta ação é irreversível.')">
                @csrf
                <div class="field">
                    <label>Confirme sua senha</label>
                    <input type="password" name="senha" required>
                </div>
                <label class="chk">
                    <input type="checkbox" required>
                    Entendo que esta ação é permanente e que meus dados pessoais serão removidos.
                </label>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Excluir minha conta definitivamente</button>
            </form>
        </div>
    </div>
</body>

</html>
