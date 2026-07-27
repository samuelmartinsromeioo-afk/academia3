<?php

namespace Database\Seeders;

use App\Models\Cadastro\Cliente;
use App\Models\Celebracao;
use App\Services\Celebracoes;
use App\Services\Retrospectivas;
use Illuminate\Database\Seeder;

/**
 * PREVIEW/TEMP: enfileira uma celebração de CADA tipo para a Ana, para revisar
 * visualmente os emblemas/animações no app. Não faz parte do fluxo de produção.
 */
class AnaCelebracoesPreviewSeeder extends Seeder
{
    public function run(): void
    {
        $ana = Cliente::where('email', 'ana@snrfit.com')->first();
        if (! $ana) {
            $this->command?->error('Ana não encontrada. Rode antes o TesteSnrFitSeeder.');
            return;
        }

        // Limpa a fila da Ana para um preview limpo.
        Celebracao::where('papel', 'cliente')->where('usuario_id', $ana->id)->delete();

        $push = fn ($notif) => Celebracoes::push('cliente', $ana->id, $notif);

        // Boas-vindas
        $push(Celebracoes::primeiroLogin($ana->nome));

        // Todas as 6 medalhas de sequência (cada uma com sua chama)
        foreach ([3, 7, 14, 30, 60, 100] as $marco) {
            $push(Celebracoes::sequenciaDias($marco));
        }

        // Evolução: perda de peso, progressão e recorde de carga, meta
        $push(Celebracoes::perdaPeso(6.0));
        $push(Celebracoes::progressaoCarga('Supino reto', 5.0, 65.0));
        $push(Celebracoes::recordeCarga('Agachamento', 90.0, 80.0));
        $push(Celebracoes::metaAtingida('Treinar 12x este mês'));

        // Retrospectivas (usam as medidas reais da Ana)
        $push(Retrospectivas::bimestral($ana->id));
        $push(Retrospectivas::semestral($ana->id));

        $lista = Celebracao::where('papel', 'cliente')->where('usuario_id', $ana->id)
            ->whereNull('visto_em')->orderBy('id')->get();
        $this->command?->info("Celebrações pendentes para Ana (id={$ana->id}): {$lista->count()}");
        foreach ($lista as $c) {
            $num = $c->dados_extras['sequencia_atual'] ?? '';
            $this->command?->line(" - {$c->tipo} {$num}");
        }
    }
}
