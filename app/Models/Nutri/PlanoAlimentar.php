<?php

namespace App\Models\Nutri;

use App\Models\Cadastro\Personal;
use Illuminate\Database\Eloquent\Model;

class PlanoAlimentar extends Model
{
    protected $table = 'nutri_planos';

    protected $fillable = [
        'personal_id', 'paciente_id', 'nome', 'is_modelo', 'objetivo',
        'kcal_meta', 'observacoes', 'ativo', 'versao',
    ];

    protected $casts = [
        'is_modelo' => 'boolean',
        'ativo' => 'boolean',
        'kcal_meta' => 'float',
        'versao' => 'integer',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function refeicoes()
    {
        return $this->hasMany(PlanoRefeicao::class, 'plano_id')->orderBy('ordem');
    }

    public function versoes()
    {
        return $this->hasMany(PlanoVersao::class, 'plano_id')->orderByDesc('versao');
    }

    /** Totais do dia (soma de todas as refeições/itens). */
    public function totais(): array
    {
        $t = ['kcal' => 0, 'carbo_g' => 0, 'proteina_g' => 0, 'gordura_g' => 0];
        foreach ($this->refeicoes as $ref) {
            foreach ($ref->itens as $item) {
                $t['kcal'] += $item->kcal;
                $t['carbo_g'] += $item->carbo_g;
                $t['proteina_g'] += $item->proteina_g;
                $t['gordura_g'] += $item->gordura_g;
            }
        }

        return array_map(fn ($v) => round($v, 1), $t);
    }

    /** Índice regional de preço a aplicar (UF do paciente ou do profissional). */
    public function ufIndice(): float
    {
        $uf = $this->paciente->uf ?? $this->personal->estado ?? null;

        return \App\Support\PrecoRegional::indice($uf);
    }

    /** Custo estimado do dia (R$), ajustado pela UF. Itens manuais não entram. */
    public function custoDiario(?float $ufIndice = null): float
    {
        $ufIndice ??= $this->ufIndice();
        $this->loadMissing('refeicoes.itens.alimento');

        $total = 0;
        foreach ($this->refeicoes as $ref) {
            foreach ($ref->itens as $item) {
                if ($item->alimento) {
                    $total += $item->alimento->custoPara((float) $item->quantidade_g, $ufIndice);
                }
            }
        }

        return round($total, 2);
    }

    /** Custo estimado mensal (R$) — o "mínimo para manter" a dieta. */
    public function custoMensal(?float $ufIndice = null): float
    {
        return round($this->custoDiario($ufIndice) * (int) config('precos.dias_mes', 30), 2);
    }

    /** Snapshot completo (para versionamento e portabilidade). */
    public function snapshot(): array
    {
        $this->loadMissing('refeicoes.itens.opcoes');

        return [
            'plano' => $this->only(['id', 'nome', 'objetivo', 'kcal_meta', 'observacoes', 'versao']),
            'refeicoes' => $this->refeicoes->map(fn ($r) => [
                'nome' => $r->nome, 'horario' => $r->horario, 'ordem' => $r->ordem,
                'observacoes' => $r->observacoes,
                'itens' => $r->itens->map(fn ($i) => array_merge(
                    $i->only([
                        'descricao', 'quantidade_g', 'medida', 'kcal', 'carbo_g',
                        'proteina_g', 'gordura_g', 'ordem',
                    ]),
                    // Substituições em formato estruturado (restauráveis pelo service).
                    ['substituicoes' => $i->opcoes->map(fn ($s) => [
                        'alimento_id' => $s->alimento_id, 'descricao' => $s->descricao,
                        'quantidade_g' => $s->quantidade_g, 'medida' => $s->medida,
                        'kcal' => $s->kcal, 'carbo_g' => $s->carbo_g,
                        'proteina_g' => $s->proteina_g, 'gordura_g' => $s->gordura_g,
                    ])->toArray()],
                ))->toArray(),
            ])->toArray(),
            'totais' => $this->totais(),
        ];
    }
}
