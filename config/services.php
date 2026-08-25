<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    'whatsapp' => [
        'token'           => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        // Ligue (=true) depois de criar/aprovar os templates na Meta. Enquanto
        // false, as notificações proativas saem como texto livre (janela 24h).
        'use_templates'   => env('WHATSAPP_USE_TEMPLATES', false),
    ],

    'stripe' => [
        'key'            => env('STRIPE_PUBLIC_KEY'),
        'secret'         => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
    ],

    'asaas' => [
        'key'            => env('ASAAS_API_KEY'),
        'url'            => env('ASAAS_URL', 'https://sandbox.asaas.com/api/v3'),
        'webhook_token'  => env('ASAAS_WEBHOOK_TOKEN'),
    ],

    // Meta (Facebook) Pixel + API de Conversões (server-side).
    'meta' => [
        'pixel_id'        => env('META_PIXEL_ID', '28125781433700453'),
        'capi_token'      => env('META_CAPI_TOKEN'),
        'api_version'     => env('META_API_VERSION', 'v21.0'),
        // Preencha com o código do "Testar eventos" para depurar; deixe vazio em produção.
        'test_event_code' => env('META_CAPI_TEST_CODE'),
    ],

];
