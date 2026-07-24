<?php

namespace App\Support;

/**
 * Mapa nome do exercício => caminho relativo do vídeo demonstrativo no disco público
 * (ex.: "exercicios/bench-press__...mp4"), carregado de resources/data/exercicios_videos.json.
 *
 * Fonte única usada tanto na criação de ficha quanto na periodização, para casar
 * exercícios (por nome) com os vídeos da biblioteca SNR.
 */
class VideosExercicios
{
    private static ?array $mapa = null;

    /** Retorna o mapa completo nome => caminho do vídeo (cacheado em memória). */
    public static function map(): array
    {
        if (self::$mapa !== null) {
            return self::$mapa;
        }

        $caminho = resource_path('data/exercicios_videos.json');
        if (! is_file($caminho)) {
            return self::$mapa = [];
        }

        $dados = json_decode(file_get_contents($caminho), true);

        return self::$mapa = is_array($dados) ? $dados : [];
    }

    /**
     * Caminho do vídeo para um exercício pelo nome, ou null se não houver.
     *
     * Casamento tolerante (para nomes livres, ex.: "Supino reto" digitado antes do catálogo):
     *  1) exato;
     *  2) normalizado (ignora acentos/maiúsculas/espaços extras);
     *  3) por prefixo — o nome guardado é o começo de um nome do catálogo
     *     (ex.: "Rosca direta" → "Rosca Direta com Barra").
     */
    public static function para(?string $nome): ?string
    {
        if ($nome === null || trim($nome) === '') {
            return null;
        }

        $mapa = self::map();

        // 1) exato
        if (isset($mapa[trim($nome)])) {
            return $mapa[trim($nome)];
        }

        // 2) normalizado exato
        $n = self::normalizar($nome);
        $norm = self::mapaNormalizado();
        if (isset($norm[$n])) {
            return $norm[$n];
        }

        // 3) por prefixo (evita ruído exigindo ao menos 4 caracteres)
        if (mb_strlen($n) >= 4) {
            foreach ($norm as $chave => $path) {
                if (str_starts_with($chave, $n . ' ')) {
                    return $path;
                }
            }
        }

        return null;
    }

    /** Mapa nome-normalizado => caminho do vídeo (cacheado). */
    private static function mapaNormalizado(): array
    {
        static $norm = null;
        if ($norm !== null) {
            return $norm;
        }
        $norm = [];
        foreach (self::map() as $nome => $path) {
            // primeira ocorrência vence (ordem do JSON), mantém determinístico
            $norm[self::normalizar($nome)] ??= $path;
        }

        return $norm;
    }

    /** Minúsculas, sem acentos, espaços colapsados. */
    private static function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, [
            'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n',
        ]);

        return preg_replace('/\s+/', ' ', $s);
    }
}
