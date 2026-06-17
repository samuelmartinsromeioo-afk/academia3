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
