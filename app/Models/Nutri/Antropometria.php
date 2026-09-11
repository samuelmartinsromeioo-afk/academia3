<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class Antropometria extends Model
{
    protected $table = 'nutri_antropometria';

    protected $fillable = [
        'paciente_id', 'data', 'peso', 'altura_cm', 'imc', 'percentual_gordura',
        'massa_magra', 'circunferencias', 'dobras', 'observacoes',
        'foto_frente', 'foto_lado', 'foto_costas',
    ];

    /** Ângulos fixos da foto de evolução, na ordem em que aparecem na tela. */
    public const ANGULOS = ['foto_frente' => 'Frente', 'foto_lado' => 'Lado', 'foto_costas' => 'Costas'];

    /** Esta avaliação tem pelo menos uma foto? */
    public function temFoto(): bool
    {
        foreach (array_keys(self::ANGULOS) as $col) {
            if ($this->$col) {
                return true;
            }
        }

        return false;
    }

    protected $casts = [
        'data' => 'date',
        'peso' => 'float',
        'altura_cm' => 'float',
        'imc' => 'float',
        'percentual_gordura' => 'float',
        'massa_magra' => 'float',
        'circunferencias' => 'array',
        'dobras' => 'array',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /** Calcula IMC a partir de peso (kg) e altura (cm). */
    public static function calcularImc(?float $peso, ?float $alturaCm): ?float
    {
        if (! $peso || ! $alturaCm) {
            return null;
        }
        $m = $alturaCm / 100;

        return round($peso / ($m * $m), 2);
    }

    public static function classificarImc(?float $imc): string
    {
        if ($imc === null) {
            return '—';
        }

        return match (true) {
            $imc < 18.5 => 'Abaixo do peso',
            $imc < 25 => 'Peso normal',
            $imc < 30 => 'Sobrepeso',
            $imc < 35 => 'Obesidade I',
            $imc < 40 => 'Obesidade II',
            default => 'Obesidade III',
        };
    }
}
