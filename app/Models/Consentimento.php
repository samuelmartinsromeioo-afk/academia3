<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Consentimento do titular para uma finalidade específica.
 *
 * Append-only: conceder e revogar são as duas linhas do mesmo livro, e o estado
 * atual é a ÚLTIMA linha de cada (conta, tipo). Ver o comentário da migration
 * `2026_10_14_000001_create_consentimentos_table` para o porquê.
 *
 * As duas finalidades existentes têm bases legais diferentes e não se
 * confundem:
 *
 *  - SAUDE: art. 11, I — consentimento específico e destacado para dado
 *    sensível. É a base legal da anamnese. Sem ele, não se grava.
 *  - MARKETING: art. 7º, I — medição de anúncios (Meta Pixel e API de
 *    Conversões). É opcional e recusá-lo não afeta o uso da Plataforma.
 *
 * Um não vale pelo outro, e é por isso que `tipo` é obrigatório em toda
 * consulta: um `concedido($conta)` sem finalidade seria a mesma armadilha do
 * "consentimento genérico" que a LGPD veda.
 */
class Consentimento extends Model
{
    protected $table = 'consentimentos';

    /** Anamnese e dados de saúde (art. 11, I). */
    public const TIPO_SAUDE = 'saude';

    /** Cookies e medição de anúncios (art. 7º, I). */
    public const TIPO_MARKETING = 'marketing';

    public const ORIGEM_SITE = 'site';

    public const ORIGEM_APP = 'app';

    public const ORIGEM_CADASTRO = 'cadastro';

    /**
     * Declarado por academia/studio ao preencher a anamnese do aluno no painel
     * deles. Ver `declarado_por_*` e o cabeçalho da migration
     * `2026_10_14_000002_add_declarante_to_consentimentos`.
     */
    public const ORIGEM_ESTABELECIMENTO = 'estabelecimento';

    protected $fillable = [
        'usuario_type',
        'usuario_id',
        'tipo',
        'concedido',
        'registrado_em',
        'ip',
        'user_agent',
        'origem',
        'declarado_por_type',
        'declarado_por_id',
    ];

    protected $casts = [
        'concedido' => 'boolean',
        'registrado_em' => 'datetime',
    ];

    public function usuario()
    {
        return $this->morphTo();
    }

    /**
     * Registra um ato de consentimento (concessão ou revogação).
     *
     * ATENÇÃO: isto NÃO é best-effort, ao contrário dos avisos. Para dado
     * sensível o registro É a base legal — gravar a anamnese e perder o
     * consentimento deixa a Plataforma tratando saúde sem fundamento. Quem
     * chama deve registrar ANTES de gravar o dado, dentro da mesma transação,
     * para que uma falha aqui impeça a gravação em vez de criar o órfão.
     */
    public static function registrar(
        $usuario,
        string $tipo,
        bool $concedido,
        ?Request $request = null,
        string $origem = self::ORIGEM_SITE,
        $declaradoPor = null
    ): self {
        return self::create([
            'usuario_type' => $usuario->getMorphClass(),
            'usuario_id' => $usuario->getKey(),
            'tipo' => $tipo,
            'concedido' => $concedido,
            'registrado_em' => now(),
            'ip' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
            'origem' => $origem,
            // Terceiro que AFIRMOU ter colhido o consentimento do titular
            // (academia/studio preenchendo a anamnese do aluno). O
            // consentimento continua sendo do titular — quem revoga é ele.
            'declarado_por_type' => $declaradoPor?->getMorphClass(),
            'declarado_por_id' => $declaradoPor?->getKey(),
        ]);
    }

    /** O terceiro que declarou o consentimento, quando houve um. */
    public function declaradoPor()
    {
        return $this->morphTo('declarado_por');
    }

    /**
     * O último ato desta conta nesta finalidade, ou null se nunca houve.
     *
     * O desempate por `id` é load-bearing, igual ao de `AceitaTermos`: dois
     * atos podem cair no mesmo segundo (conceder e revogar num duplo-clique) e
     * sem ele a ordenação devolveria o mais antigo, invertendo a resposta.
     */
    public static function ultimo($usuario, string $tipo): ?self
    {
        return self::where('usuario_type', $usuario->getMorphClass())
            ->where('usuario_id', $usuario->getKey())
            ->where('tipo', $tipo)
            ->orderByDesc('registrado_em')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Esta conta consentiu com esta finalidade AGORA?
     *
     * Ausência de registro é NÃO, nunca sim: o silêncio não consente (art. 8º
     * da LGPD). Isso faz toda conta criada antes desta tabela começar sem
     * consentimento de marketing — que é a resposta correta, não um defeito de
     * migração.
     */
    public static function concedido($usuario, string $tipo): bool
    {
        return (bool) self::ultimo($usuario, $tipo)?->concedido;
    }

    /** Atos de uma conta (morph manual: não há relação inversa). */
    public function scopeDoUsuario($query, $usuario)
    {
        return $query->where('usuario_type', $usuario->getMorphClass())
            ->where('usuario_id', $usuario->getKey());
    }
}
