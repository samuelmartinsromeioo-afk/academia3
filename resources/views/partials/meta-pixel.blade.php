{{-- Meta Pixel Code --}}
{{-- Eventos podem vir de duas fontes:
       • flash de sessao  -> ->with('fb_event', ['event'=>'Purchase','params'=>[...]])
                             (aceita 1 evento assoc ou uma lista de eventos)
       • inline no @include -> @include('partials.meta-pixel', ['fbEvents' => [ ['event'=>'ViewContent','params'=>[...]] ]])
     Sempre dispara PageView. --}}
@php
    $fbPixelId = config('services.meta.pixel_id');
    $fbFlash = session('fb_event');
    $fbList  = $fbFlash ? (isset($fbFlash['event']) ? [$fbFlash] : $fbFlash) : [];
    $fbList  = array_merge($fbList, $fbEvents ?? []);
@endphp
@if($fbPixelId)
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
{{-- End Meta Pixel Code --}}
