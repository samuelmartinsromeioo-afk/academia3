<?php

namespace App\Models;

use App\Models\Cadastro\Cliente;
use Illuminate\Database\Eloquent\Model;

/**
 * Anamnese digital do aluno (Feature 5): PAR-Q, lesões, objetivos e restrições.
 */
class Anamnese extends Model
{
    protected $table = 'anamneses';

    protected $fillable = [
        'cliente_id',
        'objetivo_principal',
        'nivel_atividade',
        'historico_lesoes',
        'restricoes_medicas',
        'doencas_preexistentes',
        'medicamentos',
        'cirurgias',
        'parq_1', 'parq_2', 'parq_3', 'parq_4', 'parq_5', 'parq_6', 'parq_7',
        'parq_observacoes',
        'observacoes',
        'preenchida_em',
    ];

    protected $casts = [
        'parq_1' => 'boolean',
        'parq_2' => 'boolean',
        'parq_3' => 'boolean',
        'parq_4' => 'boolean',
        'parq_5' => 'boolean',
        'parq_6' => 'boolean',
        'parq_7' => 'boolean',
        'preenchida_em' => 'datetime',
    ];

    /** Perguntas padrão do PAR-Q, na ordem parq_1..parq_7. */
    public const PERGUNTAS_PARQ = [
        'parq_1' => 'Algum médico já disse que você possui problema cardíaco e que só deveria fazer atividade física sob supervisão?',
        'parq_2' => 'Você sente dor no peito ao praticar atividade física?',
        'parq_3' => 'No último mês, você sentiu dor no peito sem estar praticando atividade física?',
        'parq_4' => 'Você perde o equilíbrio por tontura ou já desmaiou?',
        'parq_5' => 'Você tem algum problema ósseo ou articular que poderia piorar com atividade física?',
        'parq_6' => 'Você toma algum medicamento para pressão arterial ou problema cardíaco?',
        'parq_7' => 'Você conhece alguma outra razão pela qual não deveria praticar atividade física?',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /** Há ao menos uma resposta "sim" no PAR-Q (sinal de alerta). */
    public function temAlertaParq(): bool
    {
        foreach (array_keys(self::PERGUNTAS_PARQ) as $campo) {
            if ($this->{$campo}) {
                return true;
            }
        }
        return false;
    }
}
