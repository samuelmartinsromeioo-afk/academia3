<?php

namespace App\Models\Nutri;

use App\Models\Cadastro\Personal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Paciente extends Model
{
    protected $table = 'nutri_pacientes';

    protected $fillable = [
        'personal_id', 'cliente_id', 'nome', 'email', 'whatsapp',
        'data_nascimento', 'sexo', 'objetivo', 'uf', 'altura_cm', 'observacoes',
        'orcamento_mensal', 'ativo', 'portal_token',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'altura_cm' => 'float',
        'orcamento_mensal' => 'float',
        'ativo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Paciente $p) {
            if (empty($p->portal_token)) {
                $p->portal_token = Str::random(48);
            }
        });
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function antropometrias()
    {
        return $this->hasMany(Antropometria::class, 'paciente_id')->orderBy('data');
    }

    public function anamneses()
    {
        return $this->hasMany(AnamneseResposta::class, 'paciente_id')->latest();
    }

    public function planos()
    {
        return $this->hasMany(PlanoAlimentar::class, 'paciente_id')->latest();
    }

    public function consultas()
    {
        return $this->hasMany(Consulta::class, 'paciente_id')->orderByDesc('data_hora');
    }

    public function cobrancas()
    {
        return $this->hasMany(Cobranca::class, 'paciente_id')->latest();
    }

    public function checkins()
    {
        return $this->hasMany(Checkin::class, 'paciente_id')->orderByDesc('data');
    }

    /** Metas comportamentais prescritas (hábitos entre consultas). */
    public function metas()
    {
        return $this->hasMany(Meta::class, 'paciente_id')->orderBy('ordem')->orderBy('id');
    }

    public function metasAtivas()
    {
        return $this->metas()->where('ativo', true);
    }

    /** Orientações da biblioteca do nutricionista anexadas a este paciente. */
    public function orientacoes()
    {
        return $this->belongsToMany(Orientacao::class, 'nutri_paciente_orientacoes', 'paciente_id', 'orientacao_id')
            ->withTimestamps()
            ->orderBy('titulo');
    }

    public function getIdadeAttribute(): ?int
    {
        return $this->data_nascimento?->age;
    }

    /** Todas as fichas (planos) ativas do paciente — pode haver mais de uma. */
    public function planosAtivos()
    {
        return $this->planos()->where('ativo', true)->where('is_modelo', false);
    }

    /**
     * Cota mensal de cada ficha: orçamento dividido igualmente pelo nº de fichas
     * ativas (a soma das cotas = orçamento). Null se não há orçamento definido.
     */
    public function cotaMensalPorFicha(?int $nFichas = null): ?float
    {
        if (! $this->orcamento_mensal) {
            return null;
        }
        $n = $nFichas ?? $this->planosAtivos()->count();

        return round($this->orcamento_mensal / max(1, $n), 2);
    }

    /**
     * Ficha ativa que vale para um dia da semana (0=Dom … 6=Sáb).
     * Prioriza uma ficha atribuída àquele dia; se nenhuma for específica,
     * usa uma ficha "para todos os dias".
     */
    public function planoDoDia(int $dia): ?PlanoAlimentar
    {
        return self::escolherPlanoDoDia($this->planosAtivos()->get(), $dia);
    }

    /** Regra de escolha da ficha do dia a partir de uma coleção já carregada. */
    public static function escolherPlanoDoDia($ativos, int $dia): ?PlanoAlimentar
    {
        $especifica = $ativos->first(fn ($p) => ! empty($p->dias_semana) && $p->aplicaNoDia($dia));
        if ($especifica) {
            return $especifica;
        }

        return $ativos->first(fn ($p) => empty($p->dias_semana));
    }

    /** Ficha "principal" para telas de resumo: a de hoje ou, na falta, a mais recente. */
    public function planoAtivo(): ?PlanoAlimentar
    {
        $ativos = $this->planosAtivos()->latest()->get();

        return self::escolherPlanoDoDia($ativos, now()->dayOfWeek) ?? $ativos->first();
    }

    // ==========================================================
    // Acompanhamento / ausência ("mais de um mês sem retorno")
    // ==========================================================

    /** Considera-se "retorno" qualquer contato há menos disto. */
    public const DIAS_AUSENCIA = 30;

    /**
     * Pré-carrega a data da última interação de cada paciente (consulta concluída,
     * check-in ou avaliação) para evitar N+1 ao listar. Usado com `estaAusente()`.
     */
    public function scopeComUltimaInteracao($query)
    {
        return $query
            ->withMax(['consultas as ult_consulta' => fn ($q) => $q->where('status', 'concluida')], 'data_hora')
            ->withMax('checkins as ult_checkin', 'data')
            ->withMax('antropometrias as ult_antropo', 'data');
    }

    /**
     * Pacientes ausentes: cadastrados há mais de um mês e SEM nenhum retorno
     * (consulta concluída, check-in ou avaliação) no último mês.
     */
    public function scopeAusentes($query, ?Carbon $referencia = null)
    {
        $limite = ($referencia ?? now())->copy()->subDays(self::DIAS_AUSENCIA);

        return $query
            ->where('created_at', '<', $limite)
            ->whereDoesntHave('consultas', fn ($q) => $q->where('status', 'concluida')->where('data_hora', '>=', $limite))
            ->whereDoesntHave('checkins', fn ($q) => $q->where('data', '>=', $limite))
            ->whereDoesntHave('antropometrias', fn ($q) => $q->where('data', '>=', $limite));
    }

    /** Data do último "retorno" do paciente (usa agregados pré-carregados se houver). */
    public function ultimaInteracaoEm(): ?Carbon
    {
        $datas = [];

        // Se veio de scopeComUltimaInteracao, os agregados já estão no modelo.
        $preAgregado = array_key_exists('ult_consulta', $this->attributes)
            || array_key_exists('ult_checkin', $this->attributes)
            || array_key_exists('ult_antropo', $this->attributes);

        if ($preAgregado) {
            foreach (['ult_consulta', 'ult_checkin', 'ult_antropo'] as $k) {
                if (! empty($this->attributes[$k])) {
                    $datas[] = Carbon::parse($this->attributes[$k]);
                }
            }
        } else {
            $c = $this->consultas()->where('status', 'concluida')->max('data_hora');
            $ck = $this->checkins()->max('data');
            $an = $this->antropometrias()->max('data');
            foreach ([$c, $ck, $an] as $d) {
                if ($d) {
                    $datas[] = Carbon::parse($d);
                }
            }
        }

        if ($this->created_at) {
            $datas[] = $this->created_at;
        }

        return empty($datas) ? null : collect($datas)->sortDesc()->first();
    }

    /** Dias corridos desde o último retorno (null se nunca houve interação). */
    public function diasSemRetorno(): ?int
    {
        $ultima = $this->ultimaInteracaoEm();

        return $ultima ? $ultima->diffInDays(now()) : null;
    }

    /** Está ausente há mais de um mês sem retorno? */
    public function estaAusente(): bool
    {
        $ultima = $this->ultimaInteracaoEm();

        return $ultima ? $ultima->lt(now()->copy()->subDays(self::DIAS_AUSENCIA)) : false;
    }
}
