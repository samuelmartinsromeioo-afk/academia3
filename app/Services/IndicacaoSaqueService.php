<?php

namespace App\Services;

use App\Models\CupomUso;
use App\Models\IndicacaoSaque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Saque do bônus de indicação, com pagamento automático por Pix via Asaas.
 *
 * Só é sacável o que já está `liberado` — ou seja, indicação cuja janela de 35
 * dias FECHOU e cujo indicado bateu a meta de alunos. O gate de data vive em
 * CupomUso::podeLiberar()/janelaFechada(); aqui nunca se olha para data, só para
 * o status, de modo que existe um único lugar capaz de liberar dinheiro.
 *
 * ── Como o dinheiro sai, e por que é difícil sair errado ───────────────────
 *
 * O bônus vem da comissão da plataforma, que fica na conta RAIZ do Asaas — não
 * numa subconta do usuário. Então o pagamento automático é um POST /transfers
 * autenticado com a access_token da plataforma: a operação mais sensível do
 * sistema. As defesas são em camadas, e cada uma sozinha já barra um pagamento a
 * mais:
 *
 *  1. O valor nunca vem do cliente. É somado no servidor a partir dos
 *     `cupom_usos` liberados e sem saque, dentro de uma transação que dá
 *     lockForUpdate neles e os amarra ao pedido (`saque_id`). Dois pedidos
 *     simultâneos: o segundo soma 0 e é recusado.
 *  2. Um pedido em aberto por conta (`emAberto` cobre `solicitado` e
 *     `processando`), então o saldo nunca é comprometido duas vezes.
 *  3. Teto por pedido (`saque_auto_teto`): acima dele NÃO há automação, vai para
 *     a fila do admin. Limita o estrago máximo de qualquer falha.
 *  4. Teto diário global (`saque_auto_teto_diario`): soma todos os automáticos do
 *     dia. Barra um bug que tentasse pagar muitos pedidos em sequência.
 *  5. Conferência de saldo antes de transferir, e `null` (não consegui consultar)
 *     aborta — nunca assume saldo.
 *  6. O automático é OPT-IN (`saque_automatico`, default false): deploy nenhum
 *     liga movimentação de dinheiro real.
 *  7. `externalReference` = `indicacao_saque:{id}` em toda transferência, e
 *     `indicacao_saques.asaas_transfer_id` é UNIQUE. Isso dá ao
 *     AsaasWebhookController como reconhecer a transferência e recusar
 *     (`REFUSED`) qualquer uma que não case com uma linha nossa no valor exato —
 *     uma segunda autorização, independente do caminho que criou o saque.
 *  8. A transferência só é criada DEPOIS do commit da transação que amarrou o
 *     saldo. Se o commit falhar, nada foi pedido ao Asaas.
 */
class IndicacaoSaqueService
{
    /** Erro de regra de negócio (saldo, pedido duplicado), para virar mensagem na tela. */
    public const ERRO_SALDO     = 'saldo';
    public const ERRO_DUPLICADO = 'duplicado';

    public function __construct(private AsaasService $asaas)
    {
    }

    /**
     * Cria o pedido de saque de TODO o saldo disponível do usuário e, quando as
     * condições permitem, já dispara o Pix.
     *
     * @return array{ok:bool, saque?:IndicacaoSaque, erro?:string, minimo?:float, saldo?:float}
     */
    public function solicitar(Model $usuario, string $pixChave, ?string $ip = null): array
    {
        $minimo = (float) config('indicacao.saque_minimo', 20.00);

        try {
            $saque = DB::transaction(function () use ($usuario, $pixChave, $ip, $minimo) {
                // Um pedido em aberto por vez: trava a faixa antes de somar, para
                // dois submits simultâneos não gerarem dois pedidos.
                $jaTemAberto = IndicacaoSaque::query()
                    ->doUsuario($usuario)
                    ->emAberto()
                    ->lockForUpdate()
                    ->exists();

                if ($jaTemAberto) {
                    return self::ERRO_DUPLICADO;
                }

                // Trava as indicações candidatas. Daqui em diante elas não podem
                // ser amarradas por outra requisição.
                $usos = $usuario->indicacoesFeitas()
                    ->sacaveis()
                    ->lockForUpdate()
                    ->get(['id', 'bonus_valor']);

                $valor = round((float) $usos->sum('bonus_valor'), 2);

                if ($valor < $minimo || $valor <= 0) {
                    return self::ERRO_SALDO;
                }

                // `status`/`metodo` ficam fora do fillable (nenhum request pode
                // movê-los), por isso são estampados aqui com forceFill: este
                // serviço é o único lugar que cria pedido. Explícito também porque
                // create() não traz o default do banco para o model em memória.
                $saque = (new IndicacaoSaque())->forceFill([
                    'usuario_type' => $usuario->getMorphClass(),
                    'usuario_id'   => $usuario->getKey(),
                    'valor'        => $valor,
                    'status'       => IndicacaoSaque::STATUS_SOLICITADO,
                    'metodo'       => IndicacaoSaque::METODO_MANUAL,
                    'pix_chave'    => $pixChave,
                    'pix_tipo'     => AsaasService::tipoChavePix($pixChave),
                    'ip'           => $ip,
                ]);
                $saque->save();

                // Amarra o que foi somado. Filtrar por saque_id nulo de novo é
                // cinto e suspensório: se outra transação tivesse passado à
                // frente, o update afetaria 0 linhas.
                $amarradas = CupomUso::whereIn('id', $usos->pluck('id'))
                    ->whereNull('saque_id')
                    ->update(['saque_id' => $saque->id]);

                if ($amarradas !== $usos->count()) {
                    // Corrida detectada: desfaz tudo em vez de pagar valor que
                    // não corresponde ao que ficou amarrado.
                    throw new \RuntimeException('corrida_no_saque');
                }

                Log::channel('security')->info('indicacao_saque_solicitado', [
                    'saque_id' => $saque->id,
                    'usuario'  => $usuario->getMorphClass() . '#' . $usuario->getKey(),
                    'valor'    => $valor,
                    'ip'       => $ip,
                ]);

                return $saque;
            });
        } catch (\Throwable $e) {
            Log::channel('security')->warning('indicacao_saque_falhou', [
                'usuario' => $usuario->getMorphClass() . '#' . $usuario->getKey(),
                'erro'    => $e->getMessage(),
                'ip'      => $ip,
            ]);

            return ['ok' => false, 'erro' => self::ERRO_SALDO, 'minimo' => $minimo, 'saldo' => 0.0];
        }

        if ($saque === self::ERRO_DUPLICADO) {
            return ['ok' => false, 'erro' => self::ERRO_DUPLICADO];
        }

        if ($saque === self::ERRO_SALDO) {
            return [
                'ok'     => false,
                'erro'   => self::ERRO_SALDO,
                'minimo' => $minimo,
                'saldo'  => $usuario->saldoDisponivel(),
            ];
        }

        // Fora da transação de propósito: a chamada ao Asaas só acontece depois do
        // commit que amarrou o saldo. Se o commit falhasse, nada teria sido pedido
        // ao Asaas; e uma chamada HTTP lenta não fica segurando locks de banco.
        $this->tentarPagamentoAutomatico($saque);

        return ['ok' => true, 'saque' => $saque->fresh()];
    }

    // ── Pagamento automático ─────────────────────────────────────────────

    /**
     * Decide se este pedido pode ser pago automaticamente. Devolve o motivo da
     * recusa (string) quando não pode — o motivo vai para o log, para o admin
     * saber por que o pedido caiu na fila dele.
     */
    private function motivoParaNaoAutomatizar(IndicacaoSaque $saque): ?string
    {
        if (! config('indicacao.saque_automatico', false)) {
            return 'automatico_desligado';
        }

        if ($saque->status !== IndicacaoSaque::STATUS_SOLICITADO || $saque->asaas_transfer_id) {
            return 'estado_invalido';
        }

        $valor = (float) $saque->valor;
        $teto  = (float) config('indicacao.saque_auto_teto', 300.00);

        if ($valor > $teto) {
            return 'acima_do_teto';
        }

        if (blank($saque->pix_chave)) {
            return 'sem_chave_pix';
        }

        // Teto diário: soma o que já foi pago/está processando hoje por automação.
        $hoje = (float) IndicacaoSaque::query()
            ->where('metodo', IndicacaoSaque::METODO_AUTOMATICO)
            ->whereIn('status', [
                IndicacaoSaque::STATUS_PROCESSANDO,
                IndicacaoSaque::STATUS_PAGO,
            ])
            ->whereDate('created_at', today())
            ->sum('valor');

        if ($hoje + $valor > (float) config('indicacao.saque_auto_teto_diario', 2000.00)) {
            return 'acima_do_teto_diario';
        }

        return null;
    }

    /**
     * Dispara o Pix se as condições permitirem. Nunca lança e nunca deixa o
     * pedido num estado pior: qualquer falha mantém o saque na fila do admin
     * (`solicitado`), que é o caminho seguro.
     */
    public function tentarPagamentoAutomatico(IndicacaoSaque $saque): bool
    {
        $motivo = $this->motivoParaNaoAutomatizar($saque);

        if ($motivo !== null) {
            Log::info('indicacao_saque_sem_automacao', [
                'saque_id' => $saque->id,
                'motivo'   => $motivo,
                'valor'    => (float) $saque->valor,
            ]);

            return false;
        }

        $valor = (float) $saque->valor;

        // Conferência de saldo. null = não consegui consultar: aborta. Nunca
        // transferimos sem saber se há saldo.
        $saldo = $this->asaas->saldoPlataforma();

        if ($saldo === null || $saldo < $valor) {
            Log::channel('security')->warning('indicacao_saque_automatico_abortado_por_saldo', [
                'saque_id' => $saque->id,
                'valor'    => $valor,
                'saldo'    => $saldo,
            ]);

            return false;
        }

        // Marca `processando` ANTES de chamar o Asaas, com guarda otimista no
        // status: se duas execuções corressem juntas, só uma consegue o update e
        // só ela chama o Asaas. Evita transferência duplicada na origem.
        $tomou = IndicacaoSaque::whereKey($saque->id)
            ->where('status', IndicacaoSaque::STATUS_SOLICITADO)
            ->whereNull('asaas_transfer_id')
            ->update([
                'status' => IndicacaoSaque::STATUS_PROCESSANDO,
                'metodo' => IndicacaoSaque::METODO_AUTOMATICO,
            ]);

        if ($tomou !== 1) {
            return false;
        }

        $saque->refresh();

        $res = $this->asaas->transferirPix(
            $valor,
            (string) $saque->pix_chave,
            (string) ($saque->pix_tipo ?: AsaasService::tipoChavePix((string) $saque->pix_chave)),
            'Bonus de indicacao SnrFit',
            $saque->referenciaExterna()
        );

        if (! $res['ok']) {
            // Indeterminado (timeout): a transferência PODE existir. Mantém
            // `processando` para a conciliação resolver pelo externalReference —
            // devolver para a fila aqui poderia gerar um segundo pagamento.
            if (! empty($res['indeterminado'])) {
                $saque->forceFill(['falha_motivo' => 'Comunicação indeterminada; em conciliação.'])->save();

                return false;
            }

            // Falha conhecida: volta para a fila do admin pagar à mão.
            $saque->forceFill([
                'status'       => IndicacaoSaque::STATUS_SOLICITADO,
                'metodo'       => IndicacaoSaque::METODO_MANUAL,
                'falha_motivo' => mb_substr((string) $res['erro'], 0, 255),
            ])->save();

            Log::channel('security')->warning('indicacao_saque_automatico_falhou', [
                'saque_id' => $saque->id,
                'valor'    => $valor,
                'erro'     => $res['erro'] ?? null,
            ]);

            return false;
        }

        $saque->forceFill([
            'asaas_transfer_id' => $res['id'],
            'asaas_status'      => $res['status'],
            'transferencia_em'  => now(),
            'receipt_url'       => $res['receipt'],
            'falha_motivo'      => $res['authorized'] ? null : 'Aguardando token SMS no painel do Asaas.',
        ])->save();

        Log::channel('security')->info('indicacao_saque_automatico_enviado', [
            'saque_id'    => $saque->id,
            'transfer_id' => $res['id'],
            'valor'       => $valor,
            'asaas_status' => $res['status'],
            'authorized'  => $res['authorized'],
        ]);

        // DONE já na criação (raro, mas possível): fecha de imediato.
        if (in_array($res['status'], IndicacaoSaque::ASAAS_CONCLUIDO, true)) {
            $this->concluirTransferencia($saque->fresh(), $res['status']);
        }

        return true;
    }

    // ── Autorização (webhook de validação de saque do Asaas) ─────────────

    /**
     * Decide se o Asaas pode liberar uma transferência. Chamado pelo webhook de
     * validação de saque, que acontece ~5 s depois da criação e é uma SEGUNDA
     * autorização, independente de quem criou a transferência.
     *
     * Fail-closed: aprova só o que casa com uma linha nossa em `processando`, no
     * valor EXATO. Qualquer divergência é recusada e logada — é esta função que
     * impede uma transferência de valor adulterado de sair, mesmo que ela tenha
     * sido criada com sucesso no Asaas.
     *
     * @param  array  $transfer  o objeto `transfer` do payload do webhook
     * @return array{aprovar:bool, motivo:string}
     */
    public function autorizarTransferencia(array $transfer): array
    {
        $ref   = $transfer['externalReference'] ?? null;
        $id    = IndicacaoSaque::idDaReferencia($ref);
        $valor = isset($transfer['value']) ? round((float) $transfer['value'], 2) : null;

        if ($id === null) {
            return ['aprovar' => false, 'motivo' => 'Referência externa não reconhecida.'];
        }

        $saque = IndicacaoSaque::find($id);

        if (! $saque) {
            return ['aprovar' => false, 'motivo' => 'Saque não encontrado.'];
        }

        // Só transferência que NÓS acabamos de criar está em `processando`. Um
        // pedido já pago, recusado ou na fila do admin não tem transferência
        // legítima em curso.
        if (! $saque->estaProcessando()) {
            return ['aprovar' => false, 'motivo' => 'Saque não está aguardando transferência.'];
        }

        if ($valor === null || abs($valor - (float) $saque->valor) > 0.001) {
            // O caso que a pergunta original queria impedir: valor diferente do
            // que a plataforma apurou.
            return ['aprovar' => false, 'motivo' => 'Valor divergente do saque registrado.'];
        }

        // O id da transferência tem de ser o que gravamos na criação.
        $transferId = $transfer['id'] ?? null;

        if ($transferId && $saque->asaas_transfer_id && $transferId !== $saque->asaas_transfer_id) {
            return ['aprovar' => false, 'motivo' => 'Transferência não corresponde ao saque.'];
        }

        // A chave de destino tem de ser a que o dono cadastrou no pedido.
        $chaveDestino = $transfer['pixAddressKey'] ?? null;

        if ($chaveDestino && ! hash_equals((string) $saque->pix_chave, (string) $chaveDestino)) {
            return ['aprovar' => false, 'motivo' => 'Chave Pix de destino divergente.'];
        }

        return ['aprovar' => true, 'motivo' => 'Saque de indicação conferido.'];
    }

    // ── Conciliação ──────────────────────────────────────────────────────

    /**
     * Aplica o desfecho de uma transferência (webhook de status ou conciliação).
     * Idempotente: reentrega do mesmo evento não mexe num saque já fechado.
     */
    public function concluirTransferencia(IndicacaoSaque $saque, string $asaasStatus, ?string $motivo = null): bool
    {
        if (! $saque->estaProcessando()) {
            return false;
        }

        if (in_array($asaasStatus, IndicacaoSaque::ASAAS_CONCLUIDO, true)) {
            $saque->forceFill([
                'status'        => IndicacaoSaque::STATUS_PAGO,
                'asaas_status'  => $asaasStatus,
                'processado_em' => now(),
            ])->save();

            Log::channel('security')->info('indicacao_saque_pago_automatico', [
                'saque_id'    => $saque->id,
                'transfer_id' => $saque->asaas_transfer_id,
                'valor'       => (float) $saque->valor,
            ]);

            return true;
        }

        if (in_array($asaasStatus, IndicacaoSaque::ASAAS_FRACASSADO, true)) {
            // Não saiu dinheiro: devolve o saldo para o indicador poder pedir de
            // novo (ou corrigir a chave Pix).
            DB::transaction(function () use ($saque, $asaasStatus, $motivo) {
                CupomUso::where('saque_id', $saque->id)->update(['saque_id' => null]);

                $saque->forceFill([
                    'status'        => IndicacaoSaque::STATUS_FALHOU,
                    'asaas_status'  => $asaasStatus,
                    'processado_em' => now(),
                    'falha_motivo'  => mb_substr($motivo ?: 'Transferência não concluída pelo Asaas.', 0, 255),
                ])->save();
            });

            Log::channel('security')->warning('indicacao_saque_transferencia_fracassou', [
                'saque_id'     => $saque->id,
                'transfer_id'  => $saque->asaas_transfer_id,
                'asaas_status' => $asaasStatus,
                'valor'        => (float) $saque->valor,
            ]);

            return true;
        }

        // PENDING / BANK_PROCESSING: ainda indefinido, só anota.
        $saque->forceFill(['asaas_status' => $asaasStatus])->save();

        return false;
    }

    /**
     * Consulta no Asaas os saques presos em `processando` e aplica o desfecho.
     * Rede de segurança para webhook perdido e para o caso indeterminado
     * (timeout na criação, em que a transferência pode existir sem id local).
     *
     * @return array{conciliados:int, pendentes:int}
     */
    public function conciliarPendentes(int $limite = 50): array
    {
        $conciliados = 0;
        $pendentes   = 0;

        $saques = IndicacaoSaque::processando()->orderBy('id')->limit($limite)->get();

        foreach ($saques as $saque) {
            if (! $saque->asaas_transfer_id) {
                // Timeout na criação: não sabemos o id. Deixa para inspeção
                // humana em vez de arriscar um segundo pagamento.
                $pendentes++;
                continue;
            }

            $dados = $this->asaas->consultarTransferencia($saque->asaas_transfer_id);

            if (! $dados || empty($dados['status'])) {
                $pendentes++;
                continue;
            }

            if ($this->concluirTransferencia($saque, (string) $dados['status'], $dados['failReason'] ?? null)) {
                $conciliados++;
            } else {
                $pendentes++;
            }
        }

        return ['conciliados' => $conciliados, 'pendentes' => $pendentes];
    }

    // ── Ações do admin (fallback manual) ─────────────────────────────────

    /**
     * Admin marca o pedido como pago (pagou por fora). Idempotente: um duplo
     * clique no botão não reescreve a data nem registra dois eventos.
     *
     * Só age em `solicitado`: um saque `processando` tem transferência real em
     * curso no Asaas e não pode ser fechado à mão, senão o desfecho do webhook
     * bateria num pedido já encerrado.
     */
    public function pagar(IndicacaoSaque $saque, int $adminId, ?string $observacao = null): bool
    {
        if (! $saque->estaEmAberto()) {
            return false;
        }

        $saque->forceFill([
            'status'        => IndicacaoSaque::STATUS_PAGO,
            'metodo'        => IndicacaoSaque::METODO_MANUAL,
            'admin_id'      => $adminId,
            'processado_em' => now(),
            'observacao'    => $observacao,
        ])->save();

        Log::channel('security')->info('indicacao_saque_pago', [
            'saque_id' => $saque->id,
            'admin_id' => $adminId,
            'valor'    => (float) $saque->valor,
        ]);

        return true;
    }

    /**
     * Admin recusa o pedido e o saldo volta a ficar disponível (as indicações são
     * desamarradas). O bônus em si não é cancelado: ele continua liberado.
     */
    public function recusar(IndicacaoSaque $saque, int $adminId, ?string $observacao = null): bool
    {
        if (! $saque->estaEmAberto()) {
            return false;
        }

        DB::transaction(function () use ($saque, $adminId, $observacao) {
            CupomUso::where('saque_id', $saque->id)->update(['saque_id' => null]);

            $saque->forceFill([
                'status'        => IndicacaoSaque::STATUS_RECUSADO,
                'admin_id'      => $adminId,
                'processado_em' => now(),
                'observacao'    => $observacao,
            ])->save();
        });

        Log::channel('security')->info('indicacao_saque_recusado', [
            'saque_id' => $saque->id,
            'admin_id' => $adminId,
            'valor'    => (float) $saque->valor,
        ]);

        return true;
    }

    /**
     * Admin dispara o Pix automático de um pedido que está na fila (tipicamente um
     * que passou do teto e ele conferiu). O valor continua sendo o do registro —
     * o admin autoriza, não digita quanto.
     */
    public function pagarViaAsaas(IndicacaoSaque $saque, int $adminId): array
    {
        if (! $saque->estaEmAberto()) {
            return ['ok' => false, 'erro' => 'Este saque já foi processado.'];
        }

        if (! config('indicacao.saque_automatico', false)) {
            return ['ok' => false, 'erro' => 'O pagamento automático está desligado (INDICACAO_SAQUE_AUTO).'];
        }

        $valor = (float) $saque->valor;
        $saldo = $this->asaas->saldoPlataforma();

        if ($saldo === null) {
            return ['ok' => false, 'erro' => 'Não foi possível consultar o saldo do Asaas agora.'];
        }

        if ($saldo < $valor) {
            return ['ok' => false, 'erro' => 'Saldo insuficiente na conta Asaas (R$ ' . number_format($saldo, 2, ',', '.') . ').'];
        }

        $tomou = IndicacaoSaque::whereKey($saque->id)
            ->where('status', IndicacaoSaque::STATUS_SOLICITADO)
            ->whereNull('asaas_transfer_id')
            ->update([
                'status'   => IndicacaoSaque::STATUS_PROCESSANDO,
                'metodo'   => IndicacaoSaque::METODO_AUTOMATICO,
                'admin_id' => $adminId,
            ]);

        if ($tomou !== 1) {
            return ['ok' => false, 'erro' => 'Este saque já foi processado.'];
        }

        $saque->refresh();

        $res = $this->asaas->transferirPix(
            $valor,
            (string) $saque->pix_chave,
            (string) ($saque->pix_tipo ?: AsaasService::tipoChavePix((string) $saque->pix_chave)),
            'Bonus de indicacao SnrFit',
            $saque->referenciaExterna()
        );

        if (! $res['ok']) {
            if (empty($res['indeterminado'])) {
                $saque->forceFill([
                    'status'       => IndicacaoSaque::STATUS_SOLICITADO,
                    'metodo'       => IndicacaoSaque::METODO_MANUAL,
                    'falha_motivo' => mb_substr((string) $res['erro'], 0, 255),
                ])->save();
            }

            Log::channel('security')->warning('indicacao_saque_asaas_admin_falhou', [
                'saque_id' => $saque->id,
                'admin_id' => $adminId,
                'erro'     => $res['erro'] ?? null,
            ]);

            return ['ok' => false, 'erro' => $res['erro'] ?? 'Falha ao transferir.'];
        }

        $saque->forceFill([
            'asaas_transfer_id' => $res['id'],
            'asaas_status'      => $res['status'],
            'transferencia_em'  => now(),
            'receipt_url'       => $res['receipt'],
            'falha_motivo'      => $res['authorized'] ? null : 'Aguardando token SMS no painel do Asaas.',
        ])->save();

        Log::channel('security')->info('indicacao_saque_asaas_admin_enviado', [
            'saque_id'    => $saque->id,
            'admin_id'    => $adminId,
            'transfer_id' => $res['id'],
            'valor'       => $valor,
        ]);

        if (in_array($res['status'], IndicacaoSaque::ASAAS_CONCLUIDO, true)) {
            $this->concluirTransferencia($saque->fresh(), $res['status']);
        }

        return ['ok' => true, 'saque' => $saque->fresh()];
    }
}
