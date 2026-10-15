{{--
    Banner de consentimento de cookies (LGPD, art. 7º, I).

    SEPARADO de `partials/meta-pixel` de propósito. Os dois viviam juntos, e
    isso criava um impasse: as páginas legais (Termos, Política, "Privacidade e
    meus dados") deliberadamente NÃO incluem o Pixel — e eram justamente as
    páginas onde a pessoa vai para mudar de ideia sobre o Pixel. Incluir o
    partial inteiro ali ligaria o rastreamento na página de privacidade; não
    incluir deixava o botão "Preferências de cookies" sem nada para chamar.

    Este arquivo gerencia só a ESCOLHA, nunca carrega o Pixel. Pode entrar em
    qualquer página, inclusive nas legais.

    Duas correções em relação à versão que vivia dentro do meta-pixel, as duas
    exigidas pela própria Política:

    1. A escolha vai ao SERVIDOR (`POST lgpd.cookies`), não fica só no JS. O
       cookie resolve o browser, mas não existe em webhook nem no app — e é o
       registro em `consentimentos` que `MetaConversionsService::trackServer`
       consulta antes de mandar qualquer coisa à Meta.
    2. O script é SEMPRE carregado, mesmo com a escolha já feita, para expor
       `window.snrfitCookies.abrir()`. Antes ele dava `return` no início quando
       o cookie existia, de modo que a única forma de mudar de ideia era limpar
       os cookies do navegador — que era literalmente o que a Política mandava
       fazer. Consentimento que não se revoga pelo mesmo caminho em que foi dado
       não é consentimento livre (art. 8º da LGPD).
--}}
@php
    $bcPixelId = config('services.meta.pixel_id');
    $bcCookie = config('services.meta.consent_cookie', 'snrfit_consent');
    $bcRequireConsent = (bool) config('services.meta.require_consent', true);
@endphp

@if($bcPixelId && $bcRequireConsent)
<script>
(function () {
    if (window.snrfitCookies) return; // já carregado por outro include na página

    var COOKIE = {!! json_encode($bcCookie) !!};
    var POLITICA = {!! json_encode(route('lgpd.politica')) !!};
    var ENDPOINT = {!! json_encode(route('lgpd.cookies')) !!};
    var TOKEN = {!! json_encode(csrf_token()) !!};

    function getCookie(name) {
        return document.cookie.split('; ').reduce(function (r, c) {
            var p = c.split('=');
            return p[0] === name ? decodeURIComponent(p[1]) : r;
        }, '');
    }

    function setConsent(value) {
        // Grava local primeiro: a decisão do usuário não pode depender da rede.
        // O servidor reescreve o mesmo cookie com os atributos da aplicação
        // (Secure em produção) e registra a prova em `consentimentos`.
        document.cookie = COOKIE + '=' + value + '; path=/; max-age=31536000; SameSite=Lax';

        return fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ escolha: value })
        }).catch(function () { /* o cookie local já vale; não trava a tela */ });
    }

    function build() {
        if (document.getElementById('snrfit-consent')) return;

        var escolhaAtual = getCookie(COOKIE);

        var bar = document.createElement('div');
        bar.id = 'snrfit-consent';
        bar.setAttribute('role', 'dialog');
        bar.setAttribute('aria-label', 'Preferências de cookies');
        bar.style.cssText = 'position:fixed;left:16px;right:16px;bottom:16px;z-index:2147483647;' +
            'max-width:720px;margin:0 auto;background:#0a0b0d;color:#eef0f2;' +
            'border:1px solid rgba(124,255,0,0.35);border-radius:16px;padding:18px 20px;' +
            'box-shadow:0 20px 60px rgba(0,0,0,0.55);font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;' +
            'display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between;';

        var txt = document.createElement('p');
        txt.style.cssText = 'margin:0;flex:1 1 320px;font-size:0.86rem;line-height:1.5;color:#cfd3da;';
        txt.innerHTML = 'Cookies essenciais mantêm você autenticado e não dependem de escolha. ' +
            'Os de <strong>medição de anúncios</strong> (Meta Pixel e API de Conversões) só são ' +
            'ativados se você aceitar — e recusar não afeta o uso da plataforma. ' +
            '<a href="' + POLITICA + '" style="color:#7cff00;text-decoration:underline;">Política de Privacidade</a>.' +
            (escolhaAtual
                ? '<br><span style="color:#9aa0a6;font-size:0.8rem;">Sua escolha atual: <strong>' +
                  (escolhaAtual === 'granted' ? 'aceito' : 'recusado') + '</strong>.</span>'
                : '');

        var acts = document.createElement('div');
        acts.style.cssText = 'display:flex;gap:10px;flex:0 0 auto;';

        var btnBase = 'border:none;cursor:pointer;padding:11px 20px;border-radius:10px;font-weight:800;font-size:0.85rem;';

        var no = document.createElement('button');
        no.type = 'button';
        no.textContent = 'Recusar';
        no.style.cssText = btnBase + 'background:transparent;color:#cfd3da;border:1px solid rgba(255,255,255,0.22);';
        no.onclick = function () {
            setConsent('denied');
            bar.remove();
            // Recarrega ao SAIR de "aceito": o Pixel já estava carregado nesta
            // aba, e remover só a barra deixaria o script da Meta vivo depois de
            // a pessoa ter dito não.
            if (escolhaAtual === 'granted') location.reload();
        };

        var yes = document.createElement('button');
        yes.type = 'button';
        yes.textContent = 'Aceitar';
        yes.style.cssText = btnBase + 'background:#7cff00;color:#0a0b0d;';
        yes.onclick = function () { setConsent('granted'); bar.remove(); location.reload(); };

        acts.appendChild(no);
        acts.appendChild(yes);
        bar.appendChild(txt);
        bar.appendChild(acts);
        document.body.appendChild(bar);
    }

    // Reabrir para mudar de ideia, de qualquer página. É o que o link
    // "Preferências de cookies" do rodapé dos documentos legais e o painel de
    // "Privacidade e meus dados" chamam.
    window.snrfitCookies = {
        abrir: build,
        escolha: function () { return getCookie(COOKIE) || null; }
    };

    if (getCookie(COOKIE)) return; // já decidiu: não reaparece sozinho

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', build);
    } else {
        build();
    }
})();
</script>
@endif
