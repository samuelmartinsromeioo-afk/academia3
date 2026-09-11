<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

/**
 * Meta comportamental prescrita ao paciente (hábito, não comida).
 */
class Meta extends Model
{
    protected $table = 'nutri_metas';

    protected $fillable = [
        'personal_id', 'paciente_id', 'titulo', 'descricao',
        'tipo', 'alvo', 'unidade', 'frequencia', 'ativo', 'ordem',
    ];

    protected $casts = [
        'alvo' => 'float',
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public const TIPOS = ['checkbox' => 'Fez / não fez', 'quantidade' => 'Registrar quantidade'];

    public const FREQUENCIAS = ['diaria' => 'Todo dia', 'semanal' => 'Toda semana'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function registros()
    {
        return $this->hasMany(MetaRegistro::class, 'meta_id');
    }

    /** O registro de um dia específico (ou null se o paciente ainda não marcou). */
    public function registroDe(string $data): ?MetaRegistro
    {
        return $this->registros->firstWhere(fn ($r) => $r->data->toDateString() === $data);
    }

    /** Rótulo do alvo para exibição: "2 L", "30 min", ou vazio no checkbox. */
    public function alvoLabel(): string
    {
        if ($this->tipo !== 'quantidade' || ! $this->alvo) {
            return '';
        }

        return rtrim(rtrim(number_format($this->alvo, 2, ',', '.'), '0'), ',').' '.$this->unidade;
    }

    /**
     * Percentual de dias cumpridos no período. Conta só os dias em que a meta
     * já existia — senão uma meta criada ontem apareceria com adesão péssima.
     */
    public function adesao(int $dias = 30): int
    {
        $inicio = max(0, min($dias, now()->diffInDays($this->created_at) + 1));
        if ($inicio <= 0) {
            return 0;
        }

        $feitos = $this->registros
            ->where('concluida', true)
            ->filter(fn ($r) => $r->data->gte(now()->subDays($dias)))
            ->count();

        return (int) round(($feitos / $inicio) * 100);
    }
}
