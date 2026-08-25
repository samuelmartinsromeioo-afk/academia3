{{-- Meta Pixel Code --}}
{{-- Eventos podem vir de duas fontes:
       • flash de sessao  -> ->with('fb_event', ['event'=>'Purchase','params'=>[...]])
                             (aceita 1 evento assoc ou uma lista de eventos)
       • inline no @include -> @include('partials.meta-pixel', ['fbEvents' => [ ['event'=>'ViewContent','params'=>[...]] ]])
     O Pixel so dispara com consentimento (LGPD). Ver banner no fim do arquivo. --}}
@php
    $fbPixelId      = config('services.meta.pixel_id');
    $consentCookie  = config('services.meta.consent_cookie', 'snrfit_consent');
    $requireConsent = (bool) config('services.meta.require_consent', true);
    $fbConsent      = ! $requireConsent || request()->cookie($consentCookie) === 'granted';

    $fbFlash = session('fb_event');
    $fbList  = $fbFlash ? (isset($fbFlash['event']) ? [$fbFlash] : $fbFlash) : [];
    $fbList  = array_merge($fbList, $fbEvents ?? []);
@endphp
@if($fbPixelId && $fbConsent)
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', {!! json_encode($fbPixelId) !!});
fbq('track', 'PageView');
@foreach($fbList as $ev)
fbq('track', {!! json_encode($ev['event']) !!}, {!! json_encode($ev['params'] ?? (object) []) !!}@if(!empty($ev['event_id'])), {eventID: {!! json_encode($ev['event_id']) !!}}@endif);
@endforeach
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ urlencode($fbPixelId) }}&ev=PageView&noscript=1"/></noscript>
@endif

@if($fbPixelId && $requireConsent)
{{-- Banner de consentimento de cookies (LGPD). Injetado via JS para funcionar
     em qualquer layout; some apos o visitante decidir (aceitar/recusar). --}}
<script>
(function () {
    var COOKIE = {!! json_encode($consentCookie) !!};
    var POLITICA = {!! json_encode(route('lgpd.politica')) !!};

    function getCookie(name) {
        return document.cookie.split('; ').reduce(function (r, c) {
            var p = c.split('=');
            return p[0] === name ? decodeURIComponent(p[1]) : r;
        }, '');
    }
    if (getCookie(COOKIE)) return; // ja decidiu

    function setConsent(value) {
        document.cookie = COOKIE + '=' + value + '; path=/; max-age=31536000; SameSite=Lax';
    }

    function build() {
        if (document.getElementById('snrfit-consent')) return;

        var bar = document.createElement('div');
        bar.id = 'snrfit-consent';
        bar.setAttribute('role', 'dialog');
        bar.setAttribute('aria-label', 'Aviso de cookies');
        bar.style.cssText = 'position:fixed;left:16px;right:16px;bottom:16px;z-index:2147483647;' +
            'max-width:720px;margin:0 auto;background:#0a0b0d;color:#eef0f2;' +
            'border:1px solid rgba(212,255,0,0.35);border-radius:16px;padding:18px 20px;' +
            'box-shadow:0 20px 60px rgba(0,0,0,0.55);font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;' +
            'display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between;';

        var txt = document.createElement('p');
        txt.style.cssText = 'margin:0;flex:1 1 320px;font-size:0.86rem;line-height:1.5;color:#cfd3da;';
        txt.innerHTML = 'Usamos cookies para medir e melhorar sua experiencia, inclusive com ' +
            'ferramentas de marketing (Meta). Você pode aceitar ou recusar. ' +
            '<a href="' + POLITICA + '" style="color:#d4ff00;text-decoration:underline;">Política de Privacidade</a>.';

        var acts = document.createElement('div');
        acts.style.cssText = 'display:flex;gap:10px;flex:0 0 auto;';

        var btnBase = 'border:none;cursor:pointer;padding:11px 20px;border-radius:10px;font-weight:800;font-size:0.85rem;';

        var no = document.createElement('button');
        no.type = 'button';
        no.textContent = 'Recusar';
        no.style.cssText = btnBase + 'background:transparent;color:#cfd3da;border:1px solid rgba(255,255,255,0.22);';
        no.onclick = function () { setConsent('denied'); bar.remove(); };

        var yes = document.createElement('button');
        yes.type = 'button';
        yes.textContent = 'Aceitar';
        yes.style.cssText = btnBase + 'background:#d4ff00;color:#0a0b0d;';
        yes.onclick = function () { setConsent('granted'); bar.remove(); location.reload(); };

        acts.appendChild(no);
        acts.appendChild(yes);
        bar.appendChild(txt);
        bar.appendChild(acts);
        document.body.appendChild(bar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', build);
    } else {
        build();
    }
})();
</script>
@endif
{{-- End Meta Pixel Code --}}
