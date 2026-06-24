{{-- Notificações do navegador enquanto o app está aberto + atualização do badge do sino. --}}
<script>
(function () {
    const URL_COUNT = @json(route('notificacoes.nao-lidas'));
    const URL_LISTA = @json(route('notificacoes.index'));
    const ICON = @json(asset('SnrFit.png'));
    let ultimo = parseInt(localStorage.getItem('snrfit_notif_count') || '0');

    // Pede permissão de notificação no primeiro toque/clique do usuário.
    if ('Notification' in window && Notification.permission === 'default') {
        const pede = () => { try { Notification.requestPermission(); } catch (e) {} };
        document.addEventListener('click', pede, { once: true });
    }

    function atualizarBadges(count) {
        document.querySelectorAll('[data-notif-badge]').forEach(b => {
            b.textContent = count > 99 ? '99+' : count;
            b.style.display = count > 0 ? 'inline-flex' : 'none';
        });
    }

    async function checar() {
        try {
            const r = await fetch(URL_COUNT, { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            const count = data.count || 0;

            if (count > ultimo && 'Notification' in window && Notification.permission === 'granted') {
                const n = new Notification('SnrFit', {
                    body: 'Você tem ' + count + ' notificação(ões) nova(s).',
                    icon: ICON,
                });
                n.onclick = () => { window.focus(); window.location.href = URL_LISTA; };
            }

            ultimo = count;
            localStorage.setItem('snrfit_notif_count', String(count));
            atualizarBadges(count);
        } catch (e) { /* silencioso */ }
    }

    checar();
    setInterval(checar, 30000);
})();
</script>
