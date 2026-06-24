<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Conversa</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#F4BE16; --bg-dark:#000; --card-bg:#111317; --field:#1a1d23; --text-main:#fff; --text-muted:#9a9a9a; --border:rgba(255,255,255,0.08); }
        * { margin:0; padding:0; box-sizing:border-box; }
        html,body { height:100%; }
        body { background:var(--bg-dark); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:var(--text-main); display:flex; flex-direction:column; height:100vh; }
        a { color:inherit; text-decoration:none; }
        .top-bar { display:flex; align-items:center; gap:14px; padding:12px 20px; background:rgba(0,0,0,0.7); border-bottom:1px solid var(--border); flex-shrink:0; }
        .back-btn { background:var(--card-bg); border:1px solid var(--border); color:var(--primary); width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; }
        .top-bar img { width:38px; height:38px; border-radius:50%; }
        .top-bar .nome { font-weight:800; }
        .thread { flex:1; overflow-y:auto; padding:18px 16px; display:flex; flex-direction:column; gap:8px; max-width:680px; width:100%; margin:0 auto; }
        .bubble { max-width:78%; padding:10px 14px; border-radius:16px; font-size:0.92rem; line-height:1.4; white-space:pre-wrap; word-wrap:break-word; }
        .bubble .h { display:block; font-size:0.62rem; opacity:0.6; margin-top:4px; }
        .mine { align-self:flex-end; background:var(--primary); color:#000; border-bottom-right-radius:5px; }
        .theirs { align-self:flex-start; background:var(--card-bg); border:1px solid var(--border); border-bottom-left-radius:5px; }
        .composer { flex-shrink:0; border-top:1px solid var(--border); background:rgba(0,0,0,0.6); padding:12px 16px; }
        .composer form { display:flex; gap:10px; max-width:680px; margin:0 auto; }
        .composer input { flex:1; padding:12px 14px; background:var(--field); border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:24px; font-size:0.95rem; font-family:inherit; }
        .composer input:focus { outline:none; border-color:var(--primary); }
        .composer button { width:46px; height:46px; border-radius:50%; border:none; background:var(--primary); color:#000; font-size:1rem; cursor:pointer; flex-shrink:0; }
        .vazio { text-align:center; color:var(--text-muted); margin:auto; font-size:0.88rem; }
    </style>
</head>

<body>
    <div class="top-bar">
        <a href="{{ route('chat.index') }}" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <img src="https://ui-avatars.com/api/?name={{ urlencode($outro->nome ?? 'Contato') }}&background=F4BE16&color=000" alt="">
        <div class="nome">{{ $outro->nome ?? 'Contato' }}</div>
    </div>

    <div class="thread" id="thread"></div>

    <div class="composer">
        <form id="formMsg" onsubmit="return enviar(event)">
            <input type="text" id="texto" placeholder="Mensagem..." autocomplete="off" maxlength="2000" required>
            <button type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>

    <script>
        const URL_MSGS = @json(route('chat.mensagens', $outroId));
        const URL_ENVIAR = @json(route('chat.enviar', $outroId));
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        const thread = document.getElementById('thread');
        let ultimoCount = -1;

        function esc(s){ return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

        async function carregar() {
            try {
                const r = await fetch(URL_MSGS, { headers:{'Accept':'application/json'} });
                const msgs = await r.json();
                if (msgs.length === ultimoCount) return;
                const estavaNoFim = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 80;
                if (msgs.length === 0) { thread.innerHTML = '<div class="vazio">Comece a conversa! 👋</div>'; ultimoCount = 0; return; }
                thread.innerHTML = msgs.map(m => `<div class="bubble ${m.mine ? 'mine':'theirs'}">${esc(m.texto)}<span class="h">${m.hora}</span></div>`).join('');
                ultimoCount = msgs.length;
                if (estavaNoFim) thread.scrollTop = thread.scrollHeight;
            } catch(e) {}
        }

        async function enviar(ev) {
            ev.preventDefault();
            const inp = document.getElementById('texto');
            const txt = inp.value.trim();
            if (!txt) return false;
            inp.value = '';
            const fd = new FormData(); fd.append('texto', txt); fd.append('_token', CSRF);
            try {
                await fetch(URL_ENVIAR, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd });
            } catch(e) {}
            await carregar();
            thread.scrollTop = thread.scrollHeight;
            return false;
        }

        carregar().then(() => thread.scrollTop = thread.scrollHeight);
        setInterval(carregar, 5000);
    </script>
</body>

</html>
