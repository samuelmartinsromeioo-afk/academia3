<?php

namespace App\Console\Commands;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Services\Geocoder;
use Illuminate\Console\Command;

/**
 * Preenche latitude/longitude de academias, personais, studios e lojas a partir
 * do endereço cadastrado, para viabilizar a ordenação por proximidade na busca
 * do app. Processa apenas registros sem coordenadas (a menos que --force) e
 * respeita o limite de ~1 req/s do Nominatim. Ideal rodar no agendador para
 * cobrir cadastros novos.
 */
class GeocodificarCadastros extends Command
{
    protected $signature = 'geo:preencher
        {--limit=50 : Máximo de registros por tipo em cada execução}
        {--force : Recalcula mesmo quem já possui coordenadas}';

    protected $description = 'Geocodifica endereços (academias, personais, studios, lojas) em latitude/longitude via Nominatim/OSM';

    public function handle(Geocoder $geocoder): int
    {
        $modelos = [
            'academias' => Academia::class,
            'personais' => Personal::class,
            'studios' => Studio::class,
            'lojas' => Loja::class,
        ];

        $limite = max(1, (int) $this->option('limit'));
        $force = (bool) $this->option('force');
        $total = 0;

        foreach ($modelos as $rotulo => $classe) {
            $query = $classe::query();
            if (! $force) {
                $query->where(function ($q) {
                    $q->whereNull('latitude')->orWhereNull('longitude');
                });
            }

            foreach ($query->limit($limite)->get() as $r) {
                $endereco = $this->montarEndereco($r);
                if ($endereco === '') {
                    continue;
                }

                $coord = $geocoder->geocodificar($endereco);
                sleep(1); // Nominatim: máximo de ~1 requisição por segundo.

                if (! $coord) {
                    $this->warn("[{$rotulo} #{$r->id}] não localizado: {$endereco}");
                    continue;
                }

                $r->latitude = $coord[0];
                $r->longitude = $coord[1];
                $r->save();
                $total++;
                $this->info("[{$rotulo} #{$r->id}] {$coord[0]}, {$coord[1]}");
            }
        }

        $this->info("Concluído. {$total} cadastro(s) geocodificado(s).");

        return self::SUCCESS;
    }

    /** Monta o texto de endereço enviado ao geocodificador. */
    private function montarEndereco($r): string
    {
        $partes = array_filter([
            $r->endereco ?? null,
            $r->bairro ?? null,
            $r->cidade ?? null,
            $r->estado ?? null,
            'Brasil',
        ]);

        return trim(implode(', ', $partes));
    }
}
