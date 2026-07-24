<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streaming de mídia pública (vídeos demonstrativos dos exercícios) com suporte
 * a HTTP Range (206 Partial Content).
 *
 * O `php artisan serve` (servidor embutido do PHP) entrega arquivos estáticos
 * SEM suporte a byte-range, e o AVPlayer do iOS EXIGE range para tocar MP4
 * progressivo — sem isso falha com "Operation Stopped". Servindo o arquivo via
 * BinaryFileResponse (que trata o header `Range` e responde 206), o vídeo toca
 * tanto no iOS quanto no Android. Rota pública: o player não envia token.
 */
class MediaController extends Controller
{
    // GET /api/v1/media/exercicio-video/{path}
    public function exercicioVideo(string $path)
    {
        // Bloqueia path traversal e restringe às pastas de mídia conhecidas.
        if (! preg_match('#^(exercicios|fichas/videos)/[A-Za-z0-9._-]+\.(mp4|mov|m4v|webm)$#i', $path)) {
            abort(404);
        }

        $full = Storage::disk('public')->path($path);
        if (! is_file($full)) {
            abort(404);
        }

        // BinaryFileResponse trata o header Range e devolve 206 quando pedido.
        $response = response()->file($full, [
            'Content-Type' => 'video/mp4',
            'Cache-Control' => 'public, max-age=86400',
        ]);
        $response->setAutoLastModified();
        $response->headers->set('Accept-Ranges', 'bytes');

        return $response;
    }
}
