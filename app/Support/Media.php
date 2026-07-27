<?php

namespace App\Support;

/**
 * Resolve a URL pública de mídia (vídeos de exercício). Quando MEDIA_URL está
 * configurado — um CDN/host estático que serve o conteúdo de storage/app/public
 * na raiz —, a mídia é entregue direto de lá, SEM passar pelo PHP. Isso é
 * essencial para escala: hoje cada vídeo em reprodução prende um worker do
 * backend (BinaryFileResponse). Sem MEDIA_URL, cai no endpoint de streaming do
 * próprio app, ótimo para desenvolvimento.
 *
 * As imagens já usam Storage::disk('public')->url(), cujo host também passa a
 * ser o CDN quando MEDIA_URL é definido (ver config/filesystems.php).
 */
class Media
{
    public static function videoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        // Caminho já é uma URL absoluta (ex.: vídeo hospedado externamente).
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $cdn = config('media.url');
        if ($cdn) {
            return rtrim($cdn, '/') . '/' . ltrim($path, '/');
        }

        // Fallback (dev): streaming pelo próprio backend, com suporte a Range (206).
        return url('/api/v1/media/exercicio-video/' . ltrim($path, '/'));
    }
}
