<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pedido de saque do bônus de indicação.
 *
 * Dois caminhos de pagamento, decididos em IndicacaoSaqueService::solicitar():
 *  • `automatico` — Pix disparado na hora pelo Asaas (POST /transfers na conta da
 *    plataforma). Só para pedido ATÉ o teto de `config('indicacao.saque_auto_teto')`
 *    e dentro do teto diário, e só com `config('indicacao.saque_automatico')` ligado.
 *  • `manual` — tudo o que passa do teto, ou quando o automático está desligado /
 *    falhou: cai na fila do admin, que paga por fora e marca aqui.
 *
 * Ciclo de vida:
 *   solicitado  → na fila do admin (acima do teto, automático desligado, ou falha)
 *   processando → transferência criada no Asaas, aguardando DONE
 *   pago        → dinheiro saiu (DONE no Asaas, ou o admin marcou à mão)
 *   recusado    → admin negou; o saldo volta ao indicador
 *   falhou      → Asaas cancelou/recusou; o saldo volta ao indicador e ele pode pedir de novo
 *
 * As indicações que compõem o pedido ficam amarradas nele por
 * `cupom_usos.saque_id` — é o que impede sacar o mesmo bônus duas vezes. Recusar
 * ou falhar desamarra e o saldo volta a ficar disponível.
 *
 * `valor` NUNCA vem do formulário: é somado no servidor a partir dos bônus
 * liberados e sem saque (ver IndicacaoSaqueService::solicitar).
 */
class IndicacaoSaque extends Model
{
    protected $table = 'indicacao_saques';

    public const STATUS_SOLICITADO  = 'solicitado';
    public const STATUS_PROCESSANDO = 'processando';
    public const STATUS_PAGO        = 'pago';
    public const STATUS_RECUSADO    = 'recusado';
    public const STATUS_FALHOU      = 'falhou';

    public const METODO_AUTOMATICO = 'automatico';
    public const METODO_MANUAL     = 'manual';

    /** Prefixo do externalReference no Asaas — é por ele que o webhook nos reconhece. */
    public const REF_PREFIXO = 'indicacao_saque:';

    /**
     * Status do Asaas que significam "dinheiro saiu" e "não vai sair".
     * (PENDING/BANK_PROCESSING ficam de fora: ainda indefinidos.)
     */
    public const ASAAS_CONCLUIDO = ['DONE'];

    public const ASAAS_FRACASSADO = ['CANCELLED', 'FAILED'];

    /**
     * `status`, `metodo`, `admin_id`, `processado_em` e tudo o que diz respeito à
     * transferência ficam FORA do fillable de propósito: só os serviços os movem,
     * e nunca por atribuição em massa de request.
     */
    protected $fillable = [
        'usuario_type',
        'usuario_id',
        'valor',
        'pix_chave',
        'pix_tipo',
        'ip',
    ];

    protected $casts = [
        'valor'            => 'decimal:2',
        'processado_em'    => 'datetime',
        'transferencia_em' => 'datetime',
        // Chave Pix é dado pessoal: cifrada em repouso (LGPD / OWASP A02).
        'pix_chave'        => 'encrypted',
    ];

    /** O externalReference desta linha no Asaas. */
    public function referenciaExterna(): string
    {
        return self::REF_PREFIXO . $this->getKey();
    }

    /** Extrai o id de um externalReference do Asaas. Null se não é nosso. */
    public static function idDaReferencia(?string $ref): ?int
    {
        if (! $ref || ! str_starts_with($ref, self::REF_PREFIXO)) {
            return null;
        }

        $id = substr($ref, strlen(self::REF_PREFIXO));

        return ctype_digit($id) ? (int) $id : null;
    }

    public function usuario()
    {
        return $this->morphTo();
    }

    /** As indicações que este pedido consome. */
    public function usos()
    {
        return $this->hasMany(CupomUso::class, 'saque_id');
    }

    /**
     * Pedidos que ainda podem virar dinheiro — na fila do admin OU já no Asaas.
     * É o que bloqueia um segundo pedido simultâneo: enquanto houver um destes, o
     * saldo está comprometido.
     */
    public function scopeEmAberto($query)
    {
        return $query->whereIn('status', [self::STATUS_SOLICITADO, self::STATUS_PROCESSANDO]);
    }

    /** Só os que esperam decisão humana (a fila do admin). */
    public function scopeNaFilaDoAdmin($query)
    {
        return $query->where('status', self::STATUS_SOLICITADO);
    }

    /** Transferências criadas e ainda indefinidas — alvo da conciliação. */
    public function scopeProcessando($query)
    {
        return $query->where('status', self::STATUS_PROCESSANDO);
    }

    public function scopePagos($query)
    {
        return $query->where('status', self::STATUS_PAGO);
    }

    /** Pedidos de um usuário específico (morph manual: não há relação inversa). */
    public function scopeDoUsuario($query, $usuario)
    {
        return $query->where('usuario_type', $usuario->getMorphClass())
            ->where('usuario_id', $usuario->getKey());
    }

    /** Ainda esperando decisão do admin — é o único estado que ele pode mover. */
    public function estaEmAberto(): bool
    {
        return $this->status === self::STATUS_SOLICITADO;
    }

    /** Transferência no Asaas, aguardando desfecho. Ninguém mexe à mão aqui. */
    public function estaProcessando(): bool
    {
        return $this->status === self::STATUS_PROCESSANDO;
    }

    public function foiAutomatico(): bool
    {
        return $this->metodo === self::METODO_AUTOMATICO;
    }

    public function situacao(): string
    {
        return match ($this->status) {
            self::STATUS_PAGO        => 'Pago',
            self::STATUS_RECUSADO    => 'Recusado',
            self::STATUS_FALHOU      => 'Falhou',
            self::STATUS_PROCESSANDO => 'Pix em processamento',
            default                  => 'Em análise',
        };
    }

    /**
     * Chave Pix mascarada para telas de listagem — mostra só as pontas, o
     * suficiente para o dono reconhecer sem expor o dado inteiro em print,
     * ombro ou log.
     */
    public function pixMascarada(): string
    {
        $chave = (string) $this->pix_chave;

        if ($chave === '') {
            return '—';
        }

        if (mb_strlen($chave) <= 6) {
            return str_repeat('•', mb_strlen($chave));
        }

        return mb_substr($chave, 0, 3) . str_repeat('•', 4) . mb_substr($chave, -3);
    }
}
