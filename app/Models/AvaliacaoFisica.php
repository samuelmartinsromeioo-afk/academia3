<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvaliacaoFisica extends Model
{
    protected $table = 'avaliacoes_fisicas';

    public const TIPOS = ['antes_depois', 'dinamometro', 'oximetro', 'pressao_arterial', 'bioimpedancia', 'completa'];

    protected $fillable = [
        'personal_id',
        'cliente_id',
        'tipo',
        'data_avaliacao',
        'foto',
        'arquivo',
        'peso',
        'medidas',
        'forca',
        'spo2',
        'bpm',
        'pressao_sistolica',
        'pressao_diastolica',
        'observacoes',
        // Módulo 1 - Anamnese
        'objetivo_principal',
        'historico_atividade',
        'lesoes',
        'cirurgias',
        'medicamentos',
        'restricoes_medicas',
        'habitos_sono',
        'nivel_estresse',
        'alimentacao',
        // Módulo 2 - Antropométrica
        'altura',
        'imc',
        'circ_cintura',
        'circ_abdomen',
        'circ_quadril',
        'circ_torax',
        'circ_braco',
        'circ_coxa',
        'circ_panturrilha',
        // Módulo 3 - Dobras Cutâneas
        'protocolo_dobras',
        'dobra_triceps',
        'dobra_biceps',
        'dobra_subescapular',
        'dobra_suprailiaca',
        'dobra_abdominal',
        'dobra_coxa_dc',
        'dobra_peitoral',
        'dobra_axilar_media',
        'percentual_gordura',
        'massa_gorda',
        'massa_magra',
        // Módulo 4 - Postural
        'foto_anterior',
        'foto_posterior',
        'foto_lateral_direita',
        'foto_lateral_esquerda',
        'postural_checklist',
        // Módulo 5 - Neuromotora
        'equil_unipodal',
        'coordenacao_motora',
        'mob_ombro',
        'mob_quadril',
        'mob_tornozelo',
        'agach_profundidade',
        'agach_estabilidade',
        'agach_simetria',
        // Módulo 6 - Flexibilidade
        'flex_sentar_alcancar',
        'flex_ombros',
        'flex_quadril',
        // Módulo 7 - Cardiorrespiratória
        'teste_caminhada_dist',
        'teste_cooper_dist',
        'teste_rockport_tempo',
        'vo2max_estimado',
        // Módulo 8 - Força
        'flexao_braco_reps',
        'prancha_tempo',
        'testes_submax',
        // Módulo 9 - Funcional
        'func_agachamento',
        'func_avanco',
        'func_stepup',
        'func_prancha',
        'func_mob_toracica',
        // Módulo 10 - Dor
        'dor_lombar',
        'dor_ombro',
        'dor_joelho',
        'dor_quadril',
        'dor_cervical',
    ];

    protected $casts = [
        'data_avaliacao'    => 'date',
        'postural_checklist' => 'array',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'cliente_id');
    }
}
