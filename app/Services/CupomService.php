<?php

namespace App\Services;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\IndicacaoCredito;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Regras do cupom de indicação: validar o código digitado no cadastro, registrar
 * a indicação, emitir o código pessoal de cada usuário e APURAR o revenue share.
 *
 * O bônus é `config('indicacao.percentual')` (10%) de tudo o que o indicado
 * faturar na plataforma durante `config('indicacao.janela_dias')` (35) dias
 * contados da APROVAÇÃO dele, e só pode ser sacado depois que essa janela fecha —
 * com a meta de alunos também batida.
 *
 * A apuração é por VARREDURA, não por gancho no fluxo de pagamento. Isso é
 * deliberado: a receita entra por muitos caminhos (pagarSucesso, webhook do
 * Asaas, renovação de assinatura, consulta de nutri) e um gancho esquecido em
 * qualquer um deles perderia dinheiro de alguém silenciosamente. A varredura é
 * idempotente por construção (`indicacao_creditos.origem` é unique), se
 * autocorrige e não encosta no caminho crítico do pagamento.
 */
class CupomService
{
    /** Busca um cupom pelo código digitado (normalizado). Não filtra validade. */
    public function buscar(?string $codigo): ?Cupom
    {
        $codigo = Cupom::normalizar($codigo);

        if ($codigo === '') {
            return null;
        }

        return Cupom::where('codigo', $codigo)->first();
    }

    /**
     * Regra de validação do campo `cupom` nos formulários de cadastro.
     * Um código errado barra o submit com mensagem clara em vez de sumir
     * silenciosamente — o usuário corrige ou apaga o campo.
     */
    public function regraValidacao(): array
    {
        return ['nullable', 'string', 'max:40', function ($attribute, $value, $fail) {
            if (blank($value)) {
                return;
            }

            $cupom = $this->buscar($value);

            if (! $cupom || ! $cupom->estaValido()) {
                $fail(config('indicacao.invalido'));
            }
        }];
    }

    /**
     * Registra a indicação depois que o cadastro foi criado.
     * Nunca lança: uma falha aqui não pode derrubar um cadastro já persistido.
     */
    public function registrarIndicacao(?string $codigo, Model $usuario, ?string $ip = null): ?CupomUso
    {
        $cupom = $this->buscar($codigo);

        if (! $cupom || ! $cupom->estaValido() || $this->ehAutoIndicacao($cupom, $usuario)) {
            return null;
        }

        try {
            return DB::transaction(function () use ($cupom, $usuario, $ip) {
                // Trava a linha para que o incremento de `usos` e a checagem de
                // `limite_usos` não corram em paralelo com outro cadastro.
                $cupom = Cupom::whereKey($cupom->getKey())->lockForUpdate()->first();

                if (! $cupom || ! $cupom->estaValido()) {
                    return null;
                }

                // Aluno indicado entra no histórico sem bônus — o prêmio é por
                // trazer quem fatura. Os demais nascem `pendente` com valor 0: a
                // janela só abre na aprovação e o valor cresce com a apuração.
                $ehAluno = class_basename($usuario) === 'Cliente';

                $uso = CupomUso::create([
                    'cupom_id'     => $cupom->id,
                    'usuario_type' => $usuario->getMorphClass(),
                    'usuario_id'   => $usuario->getKey(),
                    'bonus_valor'  => 0,
                    'status'       => $ehAluno ? CupomUso::STATUS_SEM_BONUS : CupomUso::STATUS_PENDENTE,
                    'ip'           => $ip,
                ]);

                $cupom->increment('usos');

                return $uso;
            });
        } catch (QueryException $e) {
            // Unique violation = conta já indicada; qualquer outra falha não
            // pode impedir o cadastro de concluir.
            Log::warning('Falha ao registrar indicação', [
                'cupom'   => $cupom->codigo,
                'usuario' => $usuario->getMorphClass() . '#' . $usuario->getKey(),
                'erro'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ── Janela ───────────────────────────────────────────────────────────

    /**
     * Abre a janela de apuração a partir da data de aprovação do indicado.
     *
     * Idempotente de propósito: só grava quando `janela_inicio` está nulo. Isso
     * importa porque `data_aprovacao` é reescrita em fluxos de reativação
     * (AdminController::reativarLoja, por exemplo) — sem essa guarda, bloquear e
     * reativar uma conta reiniciaria o relógio e daria 35 dias novos de bônus.
     *
     * @return bool se a janela passou a existir nesta chamada
     */
    public function iniciarJanela(CupomUso $uso): bool
    {
        if ($uso->janelaIniciada() || ! $uso->geraBonus()) {
            return false;
        }

        $indicado = $uso->usuario;

        if (! $indicado) {
            return false;
        }

        $aprovacao = $this->aprovadoEm($indicado);

        // Ainda pendente de aprovação: o indicado não pode faturar, então o
        // relógio não faria sentido.
        if (! $aprovacao) {
            return false;
        }

        $fim = $aprovacao->copy()->addDays((int) config('indicacao.janela_dias', 35));

        $uso->update([
            'janela_inicio' => $aprovacao,
            'janela_fim'    => $fim,
        ]);

        return true;
    }

    /**
     * Quando o indicado foi aprovado. Null = ainda não aprovado (ou rejeitado).
     * `data_aprovacao` não é cast para datetime em todos os cinco models, então a
     * conversão é feita aqui em vez de confiar no cast.
     */
    private function aprovadoEm(Model $indicado): ?Carbon
    {
        $status = $indicado->status ?? null;

        if ($status !== null && $status !== 'aprovado') {
            return null;
        }

        $data = $indicado->data_aprovacao ?? null;

        if (blank($data)) {
            return null;
        }

        try {
            return Carbon::parse($data);
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── Apuração ─────────────────────────────────────────────────────────

    /**
     * Varre a receita do indicado dentro da janela e grava os créditos que ainda
     * faltam, devolvendo o acumulado da indicação.
     *
     * Escreve com firstOrCreate sobre `origem` (unique): rodar duas vezes, ou em
     * paralelo com o cron, não duplica crédito. Nunca lança — o painel e o cron
     * chamam isto em laço e uma indicação problemática não pode derrubar as outras.
     */
    public function apurar(CupomUso $uso): float
    {
        try {
            if (! $uso->geraBonus() || ! $uso->janelaIniciada()) {
                return (float) $uso->bonus_valor;
            }

            // Janela fechada e já apurada depois do fechamento: o valor congelou,
            // não há o que reler. Mantém o cron barato.
            if ($uso->janelaFechada() && $uso->apurado_em && $uso->apurado_em->gt($uso->janela_fim)) {
                return (float) $uso->bonus_valor;
            }

            $indicado = $uso->usuario;

            if (! $indicado) {
                return (float) $uso->bonus_valor;
            }

            $percentual = $this->percentual();

            foreach ($this->receitasNaJanela($uso, $indicado) as $receita) {
                $valor = round($receita['base'] * $percentual, 2);

                if ($valor <= 0) {
                    continue;
                }

                IndicacaoCredito::firstOrCreate(
                    ['origem' => $receita['origem']],
                    [
                        'cupom_uso_id' => $uso->id,
                        'base_valor'   => $receita['base'],
                        'percentual'   => $percentual,
                        'valor'        => $valor,
                        'ocorreu_em'   => $receita['em'],
                    ]
                );
            }

            $acumulado = round((float) $uso->creditos()->sum('valor'), 2);

            $uso->update([
                'bonus_valor' => $acumulado,
                'apurado_em'  => now(),
            ]);

            return $acumulado;
        } catch (\Throwable $e) {
            Log::warning('Indicação: falha ao apurar', [
                'cupom_uso_id' => $uso->id,
                'erro'         => $e->getMessage(),
            ]);

            return (float) $uso->bonus_valor;
        }
    }

    /**
     * As receitas do indicado dentro da janela, normalizadas em
     * ['origem' => chave idempotente, 'base' => bruto, 'em' => quando].
     *
     * A base é o FATURAMENTO BRUTO (`payments.amount_total`), não a comissão da
     * plataforma — ver a nota em config/indicacao.php sobre o que isso custa.
     *
     * @return array<int, array{origem:string, base:float, em:mixed}>
     */
    private function receitasNaJanela(CupomUso $uso, Model $indicado): array
    {
        $coluna = match (class_basename($indicado)) {
            'Personal' => 'trainer_id',
            'Academia' => 'academia_id',
            'Studio'   => 'studio_id',
            'Loja'     => 'loja_id',
            default    => null,
        };

        if ($coluna === null) {
            return [];
        }

        $inicio = $uso->janela_inicio;
        $fim    = $uso->janela_fim;

        $receitas = [];

        // `paid_at` fica nulo em algumas linhas antigas; COALESCE com created_at
        // evita perder receita legítima. Bindings nomeados — nada concatenado.
        $pagamentos = Payment::query()
            ->where($coluna, $indicado->getKey())
            ->whereIn('status', Cupom::STATUS_PAGAMENTO_VALIDO)
            ->whereRaw('COALESCE(paid_at, created_at) BETWEEN ? AND ?', [$inicio, $fim])
            ->get(['id', 'amount_total', 'paid_at', 'created_at']);

        foreach ($pagamentos as $pagamento) {
            $receitas[] = [
                'origem' => 'payment:' . $pagamento->id,
                'base'   => (float) $pagamento->amount_total,
                'em'     => $pagamento->paid_at ?? $pagamento->created_at,
            ];
        }

        // Consulta do nutricionista corre por nutri_cobrancas, fora de payments.
        if (method_exists($indicado, 'isNutricionista') && $indicado->isNutricionista()) {
            $cobrancas = \App\Models\Nutri\Cobranca::query()
                ->where('personal_id', $indicado->getKey())
                ->where('status', 'pago')
                ->whereBetween('pago_em', [$inicio, $fim])
                ->get(['id', 'valor', 'pago_em']);

            foreach ($cobrancas as $cobranca) {
                $receitas[] = [
                    'origem' => 'nutri_cobranca:' . $cobranca->id,
                    'base'   => (float) $cobranca->valor,
                    'em'     => $cobranca->pago_em,
                ];
            }
        }

        return $receitas;
    }

    /** Percentual vigente, preso em [0, 1] para um config errado não virar bônus absurdo. */
    private function percentual(): float
    {
        return max(0.0, min(1.0, (float) config('indicacao.percentual', 0.10)));
    }

    // ── Liberação ────────────────────────────────────────────────────────

    /**
     * Roda o ciclo completo nas indicações informadas: abre a janela de quem foi
     * aprovado, apura o acumulado e libera para saque o que já pode.
     *
     * Só anda para frente: uma vez liberado, o bônus não volta a travar se o
     * indicado perder alunos depois — quem indicou não controla a evasão do
     * outro, e um saldo que some do painel é pior que um critério rígido.
     *
     * @return int quantas indicações foram liberadas nesta passada
     */
    public function reavaliar(iterable $usos): int
    {
        $liberados = 0;

        foreach ($usos as $uso) {
            if ($uso->status !== CupomUso::STATUS_PENDENTE) {
                continue;
            }

            // Conta apagada: não há como apurar nem comprovar a meta.
            if (! $uso->usuario) {
                continue;
            }

            $this->iniciarJanela($uso);
            $this->apurar($uso);

            // podeLiberar() exige janela fechada E meta de alunos batida.
            // Sem valor apurado não há o que liberar: fica pendente (se a meta
            // vier depois com a janela já fechada, o valor segue sendo 0 mesmo).
            if ((float) $uso->bonus_valor > 0 && $uso->podeLiberar()) {
                $uso->update([
                    'status'      => CupomUso::STATUS_LIBERADO,
                    'liberado_em' => now(),
                ]);
                $liberados++;
            }
        }

        return $liberados;
    }

    /** Reavalia as indicações pendentes feitas por um indicador específico. */
    public function reavaliarDoIndicador(Model $dono): int
    {
        return $this->reavaliar(
            $dono->indicacoesFeitas()->pendentes()->with('usuario')->get()
        );
    }

    /** O cupom pessoal de um usuário, criado no primeiro acesso. */
    public function cupomDe(Model $dono): Cupom
    {
        $existente = Cupom::where('dono_type', $dono->getMorphClass())
            ->where('dono_id', $dono->getKey())
            ->first();

        if ($existente) {
            return $existente;
        }

        try {
            return Cupom::create([
                'codigo'      => Cupom::gerarCodigoUnico($dono->nome ?? null),
                'tipo'        => Cupom::TIPO_INDICACAO,
                'dono_type'   => $dono->getMorphClass(),
                'dono_id'     => $dono->getKey(),
                'descricao'   => 'Indicação de ' . ($dono->nome ?? 'usuário'),
                // Cupom de indicação não tem valor fixo: o bônus é percentual.
                'bonus_valor' => Cupom::BONUS_INDICACAO_FIXO,
                'ativo'       => true,
            ]);
        } catch (QueryException $e) {
            // Corrida entre duas requisições do mesmo usuário: relê o vencedor.
            $cupom = Cupom::where('dono_type', $dono->getMorphClass())
                ->where('dono_id', $dono->getKey())
                ->first();

            if (! $cupom) {
                throw $e;
            }

            return $cupom;
        }
    }

    /**
     * Bloqueia usar o próprio código — mesma conta ou mesmo e-mail do dono
     * (tentativa de se auto-indicar criando um segundo cadastro).
     */
    private function ehAutoIndicacao(Cupom $cupom, Model $usuario): bool
    {
        if ($cupom->dono_type === $usuario->getMorphClass()
            && (string) $cupom->dono_id === (string) $usuario->getKey()) {
            return true;
        }

        $emailDono = $cupom->dono?->email;

        return $emailDono
            && $usuario->email
            && strcasecmp($emailDono, $usuario->email) === 0;
    }
}
