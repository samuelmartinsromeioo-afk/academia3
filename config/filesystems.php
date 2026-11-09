<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('MEDIA_URL') ?: env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
         * Documentos de identidade enviados para a análise da subconta Asaas
         * (RG, CNH, selfie, contrato social).
         *
         * DISCO SEPARADO, E NUNCA O `public`. Todo upload do projeto até aqui
         * usa `->store(..., 'public')`, cuja raiz é `storage/app/public` e é
         * servida pela web pelo symlink `public/storage`. Isso é adequado para
         * foto de perfil e logo; para foto de RG/CNH seria **vazamento de
         * documento de identidade numa URL adivinhável**, sem autenticação
         * nenhuma — o tipo de dado que a LGPD trata como de risco alto e cuja
         * exposição não tem como ser desfeita depois.
         *
         * Note a AUSÊNCIA deliberada de `url` e de `visibility`: sem `url` o
         * disco não sabe gerar link público, então nem por engano uma view
         * consegue imprimir o caminho do documento de alguém. Se algum dia
         * precisar exibi-lo, sirva por rota autenticada que confere o dono —
         * nunca acrescentando `url` aqui.
         *
         * Fora de `storage/app/public` também por isto: `php artisan
         * storage:link` não o alcança.
         */
        'documentos' => [
            'driver' => 'local',
            'root' => storage_path('app/documentos-subconta'),
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
