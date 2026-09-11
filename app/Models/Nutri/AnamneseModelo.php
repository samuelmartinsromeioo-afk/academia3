<?php

namespace App\Models\Nutri;

use Illuminate\Database\Eloquent\Model;

class AnamneseModelo extends Model
{
    protected $table = 'nutri_anamnese_modelos';

    protected $fillable = ['personal_id', 'nome', 'perfil', 'campos', 'is_padrao', 'uso_portal'];

    protected $casts = [
        'campos' => 'array',
        'is_padrao' => 'boolean',
        'uso_portal' => 'boolean',
    ];

    /** Modelos-semente por perfil (usados ao criar o primeiro modelo do nutri). */
    public const PERFIS = [
        'geral' => 'Geral',
        'clinica' => 'Clínica',
        'esportiva' => 'Esportiva',
        'materno_infantil' => 'Materno-Infantil',
    ];

    /** Tipos de campo aceitos no schema de `campos`. */
    public const TIPOS = [
        'texto' => 'Texto curto',
        'textarea' => 'Texto longo',
        'numero' => 'Número',
        'data' => 'Data',
        'sim_nao' => 'Sim / Não',
        'opcoes' => 'Escolha única',
        'multipla' => 'Múltipla escolha',
        'escala' => 'Escala 0–10',
    ];

    public const SECAO_PADRAO = 'Geral';

    /**
     * Campos agrupados pela `secao` declarada, preservando a ordem em que
     * aparecem no modelo. Uma anamnese completa passa de 60 perguntas — sem
     * agrupamento o formulário vira uma parede ilegível.
     *
     * @return array<string, array<int, array>>
     */
    public function camposPorSecao(): array
    {
        $secoes = [];
        foreach ($this->campos ?? [] as $campo) {
            $secao = trim($campo['secao'] ?? '') ?: self::SECAO_PADRAO;
            $secoes[$secao][] = $campo;
        }

        return $secoes;
    }

    /** Um campo aceita múltiplos valores (e portanto grava array)? */
    public static function ehMultiplo(array $campo): bool
    {
        return ($campo['tipo'] ?? 'texto') === 'multipla';
    }
}
