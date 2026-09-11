<?php

namespace App\Services;

use App\Models\IndicacaoCredito;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Programa de indicação entre profissionais.
 *
 * Regra: quem indica recebe `config('indicacao.percentual')` do que a PLATAFORMA
 * arrecadar do indicado durante `janela_dias`, contados da aprovação do indicado.
 * A base é `payments.company_fee` — a comissão da plataforma, não o bruto do
 * indicado. Se o indicado gerar R$ 2.500 de comissão na janela, o indicador
 * recebe R$ 250.
 *
 * Todo o serviço é best-effort: uma falha aqui nunca pode derrubar o pagamento
 * que a originou.
 */
class IndicacaoService
{
    /** Caracteres do código: sem 0/O/1/I para não confundir quem digita. */
    private const ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    // ── Código ──────────────────────────────────────────────────────────────

    /**
     * Código do profissional, gerado na primeira vez que é pedido. Deriva do
     * nome para ser reconhecível ("JOAO4K2P") e recebe sufixo aleatório.
     */
    public function codigoDe(Model $profissional): string
    {
        if (! empty($profissional->codigo_indicacao)) {
            return $profissional->codigo_indicacao;
        }

        $base = $this->prefixoDoNome($profissional->nome ?? '');

        do {
            $codigo = $base.$this->sufixo(4);
        } while ($this->buscarPorCodigo($codigo) !== null);

        $profissional->forceFill(['codigo_indicacao' => $codigo])->save();

        return $codigo;
    }

    private function prefixoDoNome(string $nome): string
    {
        $limpo = preg_replace('/[^A-Z]/', '', mb_strtoupper($this->semAcento($nome)));

        return substr($limpo ?: 'SNR', 0, 4) ?: 'SNR';
    }

    private function semAcento(string $s): string
    {
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    }

    private function sufixo(int $n): string
    {
        $out = '';
        for ($i = 0; $i < $n; $i++) {
            $out .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return $out;
    }

    /**
     * Encontra o dono de um código nas três tabelas de profissional.
     *
     * @return array{tipo:string, model:Model}|null
     */
    public function buscarPorCodigo(?string $codigo): ?array
    {
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '') {
            return null;
        }

        foreach (config('indicacao.tipos') as $tipo => $classe) {
            $model = $classe::where('codigo_indicacao', $codigo)->first();
            if ($model) {
                return ['tipo' => $tipo, 'model' => $model];
            }
        }

        return null;
    }

    // ── Vínculo ─────────────────────────────────────────────────────────────

    /**
     * Vincula um profissional recém-cadastrado a quem o indicou. Devolve o nome
     * do indicador quando o vínculo é criado, ou null se o código não existe.
     *
     * Silencioso de propósito: código inválido não pode barrar um cadastro.
     */
    public function vincular(Model $novo, string $tipoNovo, ?string $codigo): ?string
    {
        $indicador = $this->buscarPorCodigo($codigo);
        if (! $indicador) {
            return null;
        }

        // Ninguém indica a si mesmo.
        if ($indicador['tipo'] === $tipoNovo && (int) $indicador['model']->id === (int) $novo->id) {
            return null;
        }

        $novo->forceFill([
            'indicado_por_tipo' => $indicador['tipo'],
            'indicado_por_id' => $indicador['model']->id,
        ])->save();

        return $indicador['model']->nome ?? null;
    }

    /**
     * Começa a contar a janela. Chamado na aprovação do cadastro — antes disso o
     * profissional não pode faturar, então o relógio não faria sentido.
     * Idempotente: reaprovar não reinicia o prazo.
     */
    public function iniciarJanela(Model $profissional): void
    {
        if (empty($profissional->indicado_por_id) || ! empty($profissional->indicacao_inicio)) {
            return;
        }

        $profissional->forceFill(['indicacao_inicio' => now()])->save();
    }

    /** A janela do indicado ainda está aberta? */
    public function janelaAberta(Model $profissional): bool
    {
        if (empty($profissional->indicacao_inicio)) {
            return false;
        }

        return now()->lte(
            $profissional->indicacao_inicio->copy()->addDays((int) config('indicacao.janela_dias'))
        );
    }

    // ── Crédito ─────────────────────────────────────────────────────────────

    /**
     * Credita o indicador a partir de um pagamento confirmado do indicado.
     * Chamado no funil único de confirmação (handleSuccessfulPayment).
     */
    public function creditarPorPagamento(Payment $payment): ?IndicacaoCredito
    {
        try {
            $fee = (float) $payment->company_fee;
            if ($fee <= 0) {
                return null;
            }

            [$tipo, $profissional] = $this->profissionalDoPagamento($payment);
            if (! $profissional || empty($profissional->indicado_por_id)) {
                return null;
            }

            if (! $this->janelaAberta($profissional)) {
                return null;
            }

            $percentual = (float) config('indicacao.percentual');
            $valor = round($fee * $percentual, 2);
            if ($valor <= 0) {
                return null;
            }

            // firstOrCreate na chave única do pagamento: o webhook do Asaas
            // reenvia a confirmação, e sem isso o indicador receberia duas vezes.
            return IndicacaoCredito::firstOrCreate(
                ['payment_id' => $payment->id],
                [
                    'indicador_tipo' => $profissional->indicado_por_tipo,
                    'indicador_id' => $profissional->indicado_por_id,
                    'indicado_tipo' => $tipo,
                    'indicado_id' => $profissional->id,
                    'base_company_fee' => $fee,
                    'valor' => $valor,
                    'percentual' => $percentual,
                    'status' => 'a_receber',
                ]
            );
        } catch (\Throwable $e) {
            // Nunca derruba o pagamento por causa da indicação.
            Log::warning('IndicacaoService: falha ao creditar', [
                'payment_id' => $payment->id ?? null,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Descobre de qual profissional é a receita do pagamento.
     *
     * @return array{0:?string, 1:?Model}
     */
    private function profissionalDoPagamento(Payment $payment): array
    {
        $mapa = [
            'personal' => ['coluna' => 'trainer_id'],
            'academia' => ['coluna' => 'academia_id'],
            'studio' => ['coluna' => 'studio_id'],
        ];

        foreach ($mapa as $tipo => $cfg) {
            $id = $payment->{$cfg['coluna']} ?? null;
            if (! $id) {
                continue;
            }
            $classe = config('indicacao.tipos')[$tipo] ?? null;
            $model = $classe ? $classe::find($id) : null;
            if ($model) {
                return [$tipo, $model];
            }
        }

        return [null, null];
    }

    // ── Consulta ────────────────────────────────────────────────────────────

    /** Resumo para o painel do profissional. */
    public function resumo(Model $profissional, string $tipo): array
    {
        $creditos = IndicacaoCredito::doIndicador($tipo, $profissional->id);

        $indicados = [];
        foreach (config('indicacao.tipos') as $t => $classe) {
            $lista = $classe::where('indicado_por_tipo', $tipo)
                ->where('indicado_por_id', $profissional->id)
                ->get();
            foreach ($lista as $ind) {
                $indicados[] = [
                    'tipo' => $t,
                    'model' => $ind,
                    'janela_aberta' => $this->janelaAberta($ind),
                    'fim_janela' => $ind->indicacao_inicio
                        ? $ind->indicacao_inicio->copy()->addDays((int) config('indicacao.janela_dias'))
                        : null,
                    'gerado' => IndicacaoCredito::where('indicado_tipo', $t)
                        ->where('indicado_id', $ind->id)->sum('valor'),
                ];
            }
        }

        return [
            'codigo' => $this->codigoDe($profissional),
            'indicados' => $indicados,
            'a_receber' => (float) (clone $creditos)->where('status', 'a_receber')->sum('valor'),
            'recebido' => (float) (clone $creditos)->where('status', 'pago')->sum('valor'),
            'extrato' => (clone $creditos)->latest('id')->limit(50)->get(),
        ];
    }
}
