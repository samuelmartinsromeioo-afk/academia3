<?php

namespace App\Services;

use App\Models\Cupom;
use App\Models\CupomUso;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Regras do cupom de indicação: validar o código digitado no cadastro,
 * registrar a indicação e emitir o código pessoal de cada usuário.
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
                // trazer quem conquista alunos. Os demais nascem `pendente` e
                // só liberam quando o indicado bate a meta (ver reavaliar()).
                $ehAluno = class_basename($usuario) === 'Cliente';

                $uso = CupomUso::create([
                    'cupom_id'     => $cupom->id,
                    'usuario_type' => $usuario->getMorphClass(),
                    'usuario_id'   => $usuario->getKey(),
                    'bonus_valor'  => $ehAluno ? 0 : $cupom->bonus_valor,
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

    /**
     * Libera as indicações pendentes cujo indicado já bateu a meta de alunos.
     *
     * Só anda para frente: uma vez liberado, o bônus não volta a travar se o
     * indicado perder alunos depois — quem indicou não controla a evasão do
     * outro, e um saldo que some do painel é pior que um critério rígido.
     *
     * @return int quantas indicações foram liberadas nesta passada
     */
    public function reavaliar(iterable $usos): int
    {
        $meta      = (int) config('indicacao.meta_alunos', 6);
        $liberados = 0;

        foreach ($usos as $uso) {
            if ($uso->status !== CupomUso::STATUS_PENDENTE) {
                continue;
            }

            $indicado = $uso->usuario;

            // Conta apagada: não há como comprovar a meta, fica pendente.
            if (! $indicado || ! method_exists($indicado, 'alunosPelaPlataforma')) {
                continue;
            }

            if ($indicado->alunosPelaPlataforma() >= $meta) {
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
                'bonus_valor' => (float) config('indicacao.bonus', 10),
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
