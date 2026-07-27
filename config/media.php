<?php

return [
    /*
    |--------------------------------------------------------------------------
    | URL pública da mídia (CDN)
    |--------------------------------------------------------------------------
    |
    | Base de um CDN / host estático que serve o conteúdo de storage/app/public
    | na raiz (ex.: https://cdn.snrfit.com.br). Quando definida, vídeos de
    | exercício e imagens são entregues por ela, sem passar pelo PHP — o que é
    | essencial para escala (cada vídeo deixa de prender um worker do backend).
    |
    | Vazio = servir pelo próprio backend (bom para desenvolvimento).
    |
    */
    'url' => env('MEDIA_URL') ?: null,
];
