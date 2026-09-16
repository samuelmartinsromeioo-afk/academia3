<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Código de indicação. Todo cadastro ganha o seu (tipo `indicacao`, com dono);
 * o admin pode criar códigos de campanha sem dono (tipo `promocional`).
 */
class Cupom extends Model
{
    protected $table = 'cupons';

    public const TIPO_INDICACAO   = 'indicacao';
    public const TIPO_PROMOCIONAL = 'promocional';

    /** Bônus padrão creditado ao indicador por cada indicação confirmada. */
    public const BONUS_PADRAO = 10.00;

    protected $fillable = [
        'codigo',
        'tipo',
        'dono_type',
        'dono_id',
        'descricao',
        'bonus_valor',
        'ativo',
        'expira_em',
        'limite_usos',
        'usos',
    ];

    protected $casts = [
        'bonus_valor' => 'decimal:2',
        'ativo'       => 'boolean',
        'expira_em'   => 'date',
        'limite_usos' => 'integer',
        'usos'        => 'integer',
    ];

    public function dono()
    {
        return $this->morphTo();
    }

    public function usos()
    {
        return $this->hasMany(CupomUso::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Um cupom só vale se estiver ativo, dentro da validade e abaixo do limite
     * de usos. Usado tanto na checagem em tempo real quanto no submit.
     */
    public function estaValido(): bool
    {
        if (! $this->ativo) {
            return false;
        }

        if ($this->expira_em && $this->expira_em->isPast()) {
            return false;
        }

        if (! is_null($this->limite_usos) && $this->usos >= $this->limite_usos) {
            return false;
        }

        return true;
    }

    /** Motivo legível da recusa — não revela se o código existe ou não. */
    public function motivoInvalido(): ?string
    {
        if (! $this->ativo) {
            return 'Este cupom não está mais ativo.';
        }
        if ($this->expira_em && $this->expira_em->isPast()) {
            return 'Este cupom expirou.';
        }
        if (! is_null($this->limite_usos) && $this->usos >= $this->limite_usos) {
            return 'Este cupom atingiu o limite de usos.';
        }

        return null;
    }

    /** Nome de quem indicou, para exibir no formulário ("Indicado por …"). */
    public function nomeDono(): ?string
    {
        return $this->dono?->nome;
    }

    /**
     * Normaliza o que o usuário digitou: maiúsculas, sem acento e só
     * letras/números — assim "joão-a1b2 " e "JOAOA1B2" batem no mesmo código.
     */
    public static function normalizar(?string $codigo): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(Str::ascii((string) $codigo)));
    }

    /**
     * Gera um código único a partir do nome (ex.: "MARIA" → "MARIA7F3K").
     * Sem alfabeto ambíguo (0/O, 1/I) para o código ser ditado por telefone.
     */
    public static function gerarCodigoUnico(?string $base = null): string
    {
        $prefixo = substr(static::normalizar($base), 0, 6);
        $prefixo = strlen($prefixo) >= 3 ? $prefixo : 'SNR';
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $sufixo = '';
            for ($i = 0; $i < 4; $i++) {
                $sufixo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
            $codigo = $prefixo . $sufixo;
        } while (static::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
