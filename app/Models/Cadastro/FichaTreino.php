<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Model;

class FichaTreino extends Model
{
    protected $table = 'fichas_treino';

    /**
     * Níveis da ficha — os MESMOS que o aluno escolhe em
     * `solicitacoes_ficha.nivel_experiencia`.
     *
     * `intermediario` entrou porque o aluno sempre pôde escolhê-lo no pedido,
     * mas a ficha só aceitava dois valores: a resposta dele era descartada e o
     * personal reescolhia o nível na mão. Fonte única — use nas validações em
     * vez de repetir `in:iniciante,avancado`.
     */
    public const NIVEIS = ['iniciante', 'intermediario', 'avancado'];

    /** Rótulos com acento, para exibir. */
    public const NIVEIS_LABEL = [
        'iniciante' => 'Iniciante',
        'intermediario' => 'Intermediário',
        'avancado' => 'Avançado',
    ];

    /** Regra de validação do nível, para não duplicar a lista. */
    public static function regraNivel(bool $obrigatorio = true): string
    {
        return ($obrigatorio ? 'required' : 'nullable').'|in:'.implode(',', self::NIVEIS);
    }

    /**
     * Este nível usa divisão (A/B, A/B/C…)?
     *
     * Iniciante treina o corpo todo na mesma ficha; de intermediário para cima
     * o treino é dividido. Antes a condição era `nivel === 'avancado'`, repetida
     * em seis lugares — com três níveis, uma cópia esquecida tiraria a divisão
     * do intermediário em uma tela só e ninguém notaria.
     */
    public static function nivelTemDivisao(?string $nivel): bool
    {
        return in_array($nivel, ['intermediario', 'avancado'], true);
    }

    /** Rótulo legível do nível desta ficha. */
    public function nivelLabel(): string
    {
        return self::NIVEIS_LABEL[$this->nivel] ?? self::NIVEIS_LABEL['iniciante'];
    }
    
    protected $fillable = [
        'personal_id',
        'academia_id',
        'academia_professor_id',
        'cliente_id',
        'dia_semana',
        'nome_treino',
        'observacoes',
        'ativo',
        'nivel',
        'divisao',
    ];

    // ✅ RELAÇÕES
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function academia()
    {
        return $this->belongsTo(Academia::class, 'academia_id');
    }

    /** Professor da academia que criou a ficha (fichas da academia). */
    public function professorAcademia()
    {
        return $this->belongsTo(AcademiaProfessor::class, 'academia_professor_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function exercicios()
    {
        return $this->hasMany(ExercicioFicha::class, 'ficha_id')->orderBy('ordem');
    }

    public function treinos_concluidos()
    {
        return $this->hasMany(TreinoConcluido::class, 'ficha_id');
    }

    // ✅ METODOS ÚTEIS
    public function getDiaSemanaNome()
    {
        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        return $dias[$this->dia_semana] ?? 'Desconhecido';
    }

    public function foi_concluido_hoje()
    {
        return $this->treinos_concluidos()
            ->where('data_treino', now()->format('Y-m-d'))
            ->where('concluido', true)
            ->exists();
    }

    public function treino_de_hoje()
    {
        return $this->treinos_concluidos()
            ->where('data_treino', now()->format('Y-m-d'))
            ->first();
    }
}