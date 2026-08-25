<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Cookies do Meta Pixel — gravados pelo browser sem criptografia Laravel.
        // Precisam ficar de fora para a API de Conversões ler fbp/fbc (match quality).
        '_fbp',
        '_fbc',
    ];
}
