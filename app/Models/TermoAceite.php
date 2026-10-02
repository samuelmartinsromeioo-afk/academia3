<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Aceite dos Termos de Uso por uma conta, numa versão específica.
 *
 * Append-only: cada aceite é uma linha nova, nunca um UPDATE. É o que permite
 * provar depois QUAL texto a pessoa aceitou e QUANDO — o ônus da prova do
 * consentimento é do controlador (LGPD, art. 8º, §1º).
 */
class TermoAceite extends Model
{
    protected $table = 'termo_aceites';

    public const ORIGEM_CADASTRO = 'cadastro';
    public const ORIGEM_REACEITE = 'reaceite';

    protected $fillable = [
        'usuario_type',
        'usuario_id',
        'versao',
        'aceito_em',
        'ip',
        'user_agent',
        'origem',
    ];

    protected $casts = [
        'aceito_em' => 'datetime',
    ];

    public function usuario()
    {
        return $this->morphTo();
    }

    public function scopeNaVersao($query, string $versao)
    {
        return $query->where('versao', $versao);
    }

    /** Aceites de uma conta específica (morph manual: não há relação inversa). */
    public function scopeDoUsuario($query, $usuario)
    {
        return $query->where('usuario_type', $usuario->getMorphClass())
            ->where('usuario_id', $usuario->getKey());
    }
}
