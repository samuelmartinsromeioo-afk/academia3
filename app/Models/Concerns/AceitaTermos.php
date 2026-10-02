<?php

namespace App\Models\Concerns;

use App\Models\TermoAceite;
use Illuminate\Database\QueryException;

/**
 * Dá a um cadastro (personal, cliente, academia, studio, loja) o controle de
 * aceite dos Termos de Uso por versão.
 *
 * A versão vigente vem de `config('termos.versao')`. Quem não tem linha em
 * `termo_aceites` para essa versão cai na tela de reaceite (ver o middleware
 * VerificaAceiteTermos).
 */
trait AceitaTermos
{
    /**
     * Histórico de aceites desta conta, do mais recente para o mais antigo.
     *
     * O desempate por `id` não é decorativo: dois aceites podem cair no MESMO
     * segundo (reaceite imediato após o cadastro, por exemplo) e, só com
     * `aceito_em`, a ordem fica indefinida e `versaoTermosAceita()` pode devolver
     * a versão antiga.
     */
    public function aceitesTermos()
    {
        return TermoAceite::query()->doUsuario($this)
            ->orderByDesc('aceito_em')
            ->orderByDesc('id');
    }

    /** Esta conta já aceitou a versão informada (ou a vigente)? */
    public function aceitouTermos(?string $versao = null): bool
    {
        $versao = $versao ?: (string) config('termos.versao');

        return TermoAceite::query()->doUsuario($this)->naVersao($versao)->exists();
    }

    /** Precisa passar pela tela de reaceite antes de seguir usando a plataforma? */
    public function precisaAceitarTermos(): bool
    {
        return ! $this->aceitouTermos();
    }

    /** A versão mais recente que esta conta aceitou (null se nunca aceitou). */
    public function versaoTermosAceita(): ?string
    {
        return $this->aceitesTermos()->value('versao');
    }

    /**
     * Registra o aceite da versão vigente. Idempotente: o unique
     * (conta + versão) impede linha duplicada se o formulário for reenviado, e a
     * violação é absorvida em vez de virar erro 500 na cara do usuário.
     *
     * Nunca lança: um aceite é o passo que destrava o acesso, e falhar aqui com
     * exceção prenderia a pessoa na tela. A falha é logada e o acesso segue
     * bloqueado, que é o lado seguro.
     */
    public function registrarAceiteTermos(
        ?string $ip = null,
        ?string $userAgent = null,
        string $origem = TermoAceite::ORIGEM_REACEITE,
        ?string $versao = null
    ): bool {
        $versao = $versao ?: (string) config('termos.versao');

        try {
            TermoAceite::create([
                'usuario_type' => $this->getMorphClass(),
                'usuario_id'   => $this->getKey(),
                'versao'       => $versao,
                'aceito_em'    => now(),
                'ip'           => $ip,
                'user_agent'   => $userAgent ? mb_substr($userAgent, 0, 255) : null,
                'origem'       => $origem,
            ]);

            return true;
        } catch (QueryException $e) {
            // Unique violation = já aceitou esta versão; qualquer outra falha
            // não pode derrubar a requisição.
            if ($this->aceitouTermos($versao)) {
                return true;
            }

            \Illuminate\Support\Facades\Log::warning('Falha ao registrar aceite de termos', [
                'usuario' => $this->getMorphClass() . '#' . $this->getKey(),
                'versao'  => $versao,
                'erro'    => $e->getMessage(),
            ]);

            return false;
        }
    }
}
