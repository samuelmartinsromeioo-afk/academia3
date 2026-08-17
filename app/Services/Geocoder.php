<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Converte endereços textuais em coordenadas (latitude/longitude) usando o
 * Nominatim (OpenStreetMap) — gratuito. O serviço exige um User-Agent
 * identificável e limita o uso a ~1 requisição por segundo; o controle de
 * ritmo (sleep) é responsabilidade de quem chama em lote.
 */
class Geocoder
{
    /**
     * Geocodifica um endereço. Retorna [latitude, longitude] ou null quando não
     * encontra o endereço ou ocorre erro de rede.
     */
    public function geocodificar(string $endereco): ?array
    {
        $endereco = trim($endereco);
        if ($endereco === '') {
            return null;
        }

        try {
            $resp = Http::withHeaders([
                'User-Agent' => 'SNR FIT/1.0 (contato@snrfit.com.br)',
            ])
                ->timeout(15)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $endereco,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'br',
                    'addressdetails' => 0,
                ]);

            if (! $resp->successful()) {
                return null;
            }

            $data = $resp->json();
            if (empty($data[0]['lat']) || empty($data[0]['lon'])) {
                return null;
            }

            return [(float) $data[0]['lat'], (float) $data[0]['lon']];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
