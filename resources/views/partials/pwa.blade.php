{{-- PWA: manifest, ícones, meta mobile e registro do service worker. Incluído no <head>. --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#0a0b0d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="SnrFit">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

<style>
    /* Ajustes base de responsividade mobile (PWA) */
    html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
    @supports (padding: max(0px)) {
        body {
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }
    }
    * { -webkit-tap-highlight-color: transparent; }
    img, video, canvas, svg { max-width: 100%; height: auto; }
    @media (max-width: 600px) {
        html, body { overflow-x: hidden; }
        /* Alvos de toque mais confortáveis no celular */
        button, .btn, .btn-primary, a.btn, input[type="submit"] { min-height: 44px; }
        /* Evita zoom automático do iOS ao focar campos */
        input, select, textarea { font-size: 16px; }
        /* Tabelas: rolagem horizontal no celular, sem cortar o conteúdo */
        table { display: block; width: 100%; max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table thead th, table tbody td { white-space: nowrap; }

        /* Tabelas marcadas com .resp-cards viram cards empilhados no celular */
        table.resp-cards { display: block; overflow: visible; }
        table.resp-cards thead { display: none; }
        table.resp-cards tbody, table.resp-cards tr, table.resp-cards td { display: block; width: 100%; }
        table.resp-cards tr { margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.10); border-radius: 12px; padding: 6px 8px; background: rgba(255,255,255,0.02); }
        table.resp-cards td { border: none; padding: 7px 8px; white-space: normal; text-align: left !important; }
        table.resp-cards td::before { content: attr(data-label); display: block; font-weight: 800; font-size: 0.58rem; text-transform: uppercase; letter-spacing: 0.5px; color: #a0a0a0; margin-bottom: 3px; }
        table.resp-cards td[data-label=""]::before, table.resp-cards td:not([data-label])::before { content: none; }
    }
</style>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '/' }).catch(function (e) {
                console.warn('SW registration failed:', e);
            });
        });
    }
</script>
