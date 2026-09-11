<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class AnamneseResposta extends Model
{
    protected $table = 'nutri_anamneses';

    protected $fillable = ['paciente_id', 'modelo_id', 'respostas', 'origem', 'preenchida_em'];

    protected $casts = [
        'respostas' => 'array',
        'preenchida_em' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function modelo()
    {
        return $this->belongsTo(AnamneseModelo::class, 'modelo_id');
    }

    /**
     * Descarta respostas em branco antes de gravar. Com modelos de 70+ perguntas
     * a maioria volta vazia numa consulta — guardar tudo encheria o JSON de
     * strings vazias e poluiria o relatório do paciente.
     *
     * Usado tanto pelo formulário do nutricionista quanto pelo portal.
     */
    public static function limpar(array $respostas): array
    {
        $limpas = [];

        foreach ($respostas as $campo => $valor) {
            if (is_array($valor)) {
                $valor = array_values(array_filter($valor, fn ($v) => $v !== null && $v !== ''));
                if ($valor) {
                    $limpas[$campo] = $valor;
                }
            } elseif ($valor !== null && trim((string) $valor) !== '') {
                $limpas[$campo] = $valor;
            }
        }

        return $limpas;
    }
}
