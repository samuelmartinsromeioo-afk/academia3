{{-- Define a saudação (Bom dia / Boa tarde / Boa noite) pelo relógio do
     navegador do usuário — independente do timezone do servidor (UTC).
     Use junto de um elemento com id="snrSaud". --}}
<script>
    (function () {
        var h = new Date().getHours();
        var s = h < 12 ? 'Bom dia' : (h < 18 ? 'Boa tarde' : 'Boa noite');
        document.querySelectorAll('#snrSaud, [data-snr-saud]').forEach(function (el) {
            el.textContent = el.hasAttribute('data-snr-upper') ? s.toUpperCase() : s;
        });
    })();
</script>
