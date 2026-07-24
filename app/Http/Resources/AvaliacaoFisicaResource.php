<?php

namespace App\Http\Resources;

use App\Models\AvaliacaoFisica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AvaliacaoFisicaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'tipo_label' => AvaliacaoFisica::META[$this->tipo]['label'] ?? $this->tipo,
            'data_avaliacao' => $this->data_avaliacao?->toDateString(),
            'personal_id' => $this->personal_id,
            'academia_id' => $this->academia_id,
            'cliente' => $this->whenLoaded('cliente', fn () => $this->cliente ? [
                'id' => $this->cliente->id,
                'nome' => $this->cliente->nome,
            ] : null),
            // Anexos (URLs absolutas para o app)
            'foto_url' => $this->urlPublica($this->foto),
            'arquivo_url' => $this->urlPublica($this->arquivo),
            // Medidas gerais
            'peso' => $this->peso,
            'altura' => $this->altura,
            'imc' => $this->imc,
            'medidas' => $this->medidas,
            // Anamnese
            'objetivo_principal' => $this->objetivo_principal,
            'historico_atividade' => $this->historico_atividade,
            'lesoes' => $this->lesoes,
            'cirurgias' => $this->cirurgias,
            'medicamentos' => $this->medicamentos,
            'restricoes_medicas' => $this->restricoes_medicas,
            'habitos_sono' => $this->habitos_sono,
            'nivel_estresse' => $this->nivel_estresse,
            'alimentacao' => $this->alimentacao,
            // Antropométrica (circunferências)
            'circ_cintura' => $this->circ_cintura,
            'circ_abdomen' => $this->circ_abdomen,
            'circ_quadril' => $this->circ_quadril,
            'circ_torax' => $this->circ_torax,
            'circ_braco' => $this->circ_braco,
            'circ_coxa' => $this->circ_coxa,
            'circ_panturrilha' => $this->circ_panturrilha,
            // Dobras cutâneas
            'protocolo_dobras' => $this->protocolo_dobras,
            'dobra_triceps' => $this->dobra_triceps,
            'dobra_biceps' => $this->dobra_biceps,
            'dobra_subescapular' => $this->dobra_subescapular,
            'dobra_suprailiaca' => $this->dobra_suprailiaca,
            'dobra_abdominal' => $this->dobra_abdominal,
            'dobra_coxa_dc' => $this->dobra_coxa_dc,
            'dobra_peitoral' => $this->dobra_peitoral,
            'dobra_axilar_media' => $this->dobra_axilar_media,
            // Composição corporal
            'percentual_gordura' => $this->percentual_gordura,
            'massa_gorda' => $this->massa_gorda,
            'massa_magra' => $this->massa_magra,
            // Postural
            'foto_anterior_url' => $this->urlPublica($this->foto_anterior),
            'foto_posterior_url' => $this->urlPublica($this->foto_posterior),
            'foto_lateral_direita_url' => $this->urlPublica($this->foto_lateral_direita),
            'foto_lateral_esquerda_url' => $this->urlPublica($this->foto_lateral_esquerda),
            'postural_checklist' => $this->postural_checklist,
            // Neuromotora
            'equil_unipodal' => $this->equil_unipodal,
            'coordenacao_motora' => $this->coordenacao_motora,
            'mob_ombro' => $this->mob_ombro,
            'mob_quadril' => $this->mob_quadril,
            'mob_tornozelo' => $this->mob_tornozelo,
            'agach_profundidade' => $this->agach_profundidade,
            'agach_estabilidade' => $this->agach_estabilidade,
            'agach_simetria' => $this->agach_simetria,
            // Flexibilidade
            'flex_sentar_alcancar' => $this->flex_sentar_alcancar,
            'flex_ombros' => $this->flex_ombros,
            'flex_quadril' => $this->flex_quadril,
            // Cardiorrespiratória
            'teste_caminhada_dist' => $this->teste_caminhada_dist,
            'teste_cooper_dist' => $this->teste_cooper_dist,
            'teste_rockport_tempo' => $this->teste_rockport_tempo,
            'vo2max_estimado' => $this->vo2max_estimado,
            // Dinamômetro / força
            'forca' => $this->forca,
            'flexao_braco_reps' => $this->flexao_braco_reps,
            'prancha_tempo' => $this->prancha_tempo,
            'testes_submax' => $this->testes_submax,
            // Oxímetro
            'spo2' => $this->spo2,
            'bpm' => $this->bpm,
            // Pressão arterial
            'pressao_sistolica' => $this->pressao_sistolica,
            'pressao_diastolica' => $this->pressao_diastolica,
            // Funcional
            'func_agachamento' => $this->func_agachamento,
            'func_avanco' => $this->func_avanco,
            'func_stepup' => $this->func_stepup,
            'func_prancha' => $this->func_prancha,
            'func_mob_toracica' => $this->func_mob_toracica,
            // Dor
            'dor_lombar' => $this->dor_lombar,
            'dor_ombro' => $this->dor_ombro,
            'dor_joelho' => $this->dor_joelho,
            'dor_quadril' => $this->dor_quadril,
            'dor_cervical' => $this->dor_cervical,
            'observacoes' => $this->observacoes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /** URL absoluta de um arquivo do disco public (null se não houver). */
    private function urlPublica(?string $caminho): ?string
    {
        return $caminho ? Storage::disk('public')->url($caminho) : null;
    }
}
