<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

class TreinoConcluido extends Model
{
    protected $table = 'treinos_concluidos';
    
    protected $fillable = [
        'ficha_id',
        'cliente_id',
        'data_treino',
        'concluido',
        'observacoes',
        'rpe',
        'sensacao',
    ];

    protected $casts = [
        'data_treino' => 'date',
        'concluido' => 'boolean',
        'rpe' => 'integer',
    ];

    /** Sensações possíveis no feedback pós-treino: chave => [emoji, rótulo]. */
    public const SENSACOES = [
        'otimo'   => ['😀', 'Ótimo'],
        'bem'     => ['🙂', 'Bem'],
        'cansado' => ['😓', 'Cansado'],
        'exausto' => ['🥵', 'Exausto'],
        'dor'     => ['🤕', 'Com dor'],
    ];

    public function sensacaoEmoji(): ?string
    {
        return self::SENSACOES[$this->sensacao][0] ?? null;
    }

    public function sensacaoLabel(): ?string
    {
        return self::SENSACOES[$this->sensacao][1] ?? null;
    }

    public function ficha()
    {
        return $this->belongsTo(FichaTreino::class, 'ficha_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function registros()
    {
        return $this->hasMany(RegistroExercicio::class, 'treino_concluido_id');
    }
}