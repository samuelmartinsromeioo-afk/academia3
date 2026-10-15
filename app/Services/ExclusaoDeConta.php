<?php

namespace App\Services;

use App\Models\Anamnese;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\Studio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\CupomUso;
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use App\Models\Mensagem;
use App\Models\Meta;
use App\Models\Notificacao;
use App\Models\PushToken;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Exclusão de conta (direito de eliminação — LGPD / requisito da App Store).
 *
 * Para evitar quebra de integridade referencial e respeitar a retenção legal
 * de registros financeiros/fiscais, a exclusão ANONIMIZA o perfil e APAGA os
 * dados pessoais e sensíveis (saúde, fotos, medidas, mensagens). O e-mail é
 * trocado por um placeholder e a senha por um valor aleatório, de modo que a
 * conta não pode mais ser acessada.
 *
 * Usado tanto pelo painel web (LgpdController) quanto pela API mobile
 * (Api\ContaController), a partir do mesmo modelo autenticado.
 */
class ExclusaoDeConta
{
    /**
     * Confirma a exclusão ao titular, por e-mail e WhatsApp.
     *
     * Chamado de dentro de `executar()`, entre o cancelamento das cobranças e a
     * anonimização — a única janela em que existem ao mesmo tempo o contato do
     * titular (que a anonimização destrói, trocando o e-mail por
     * `removido_{id}@snrfit.local` e o WhatsApp por null) e a contagem das
     * recorrências encerradas.
     *
     * Nenhuma das duas versões anteriores (site e app) avisava nada: o usuário
     * via um alerta na tela, fechava o app e não ficava com nenhum vestígio de
     * que pediu e obteve a exclusão.
     *
     * Usa `NotificacaoService::enviar()`, e não `conta()`, de propósito: `conta`
     * também grava um aviso in-app e dispara push, e os dois iriam para uma
     * conta que está deixando de existir — o aviso in-app seria apagado pela
     * própria exclusão segundos depois.
     *
     * Público porque é chamado nos testes e pode ser reexecutado pelo suporte;
     * best-effort porque a exclusão é direito do titular (art. 18, VI) e não
     * pode falhar porque o servidor de e-mail está fora do ar.
     */
    public static function confirmarAoTitular(Model $titular): void
    {
        /*
         * A frase sobre as assinaturas é o comprovante que mais importa aqui:
         * é a única prova que o titular guarda de que a cobrança mensal parou,
         * e é exatamente o que ele vai precisar apresentar se aparecer uma
         * cobrança depois. Por isso ela é contada ANTES da anonimização, com
         * o número real de recorrências encerradas, em vez de uma promessa
         * genérica.
         */
        $assinaturas = $titular instanceof Cliente
            ? Subscription::where('user_id', $titular->getKey())
                ->where('status', 'canceled')
                ->whereDate('updated_at', today())
                ->count()
            : 0;

        $sobreAssinaturas = $assinaturas > 0
            ? ($assinaturas === 1
                ? "*A sua assinatura mensal foi cancelada* e não haverá novas cobranças.\n\n"
                : "*As suas {$assinaturas} assinaturas mensais foram canceladas* e não haverá novas cobranças.\n\n")
            : "Não havia assinatura mensal ativa na sua conta, então não há cobrança futura a cancelar.\n\n";

        $texto = 'Sua conta na SNR FIT foi encerrada a seu pedido e os seus dados pessoais '
            ."foram removidos.\n\n"
            .$sobreAssinaturas
            .'Permanecem guardados, de forma desvinculada da sua identidade e apenas pelo '
            .'prazo que a lei exige: os registros financeiros das transações já realizadas '
            .'(prazo fiscal e prescricional) e os registros de acesso à aplicação (6 meses, '
            ."art. 15 do Marco Civil da Internet).\n\n"
            ."*Guarde esta mensagem*: ela é o seu comprovante do encerramento.\n\n"
            .'Se você não solicitou este encerramento, responda a esta mensagem imediatamente.';

        try {
            NotificacaoService::enviar(
                $titular->whatsapp ?? null,
                $titular->email ?? null,
                $titular->nome ?? 'Titular',
                'Conta encerrada — SNR FIT',
                $texto
            );
        } catch (\Throwable $e) {
            Log::warning('Falha ao confirmar exclusão de conta ao titular', [
                'conta' => class_basename($titular),
                'id' => $titular->getKey(),
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Exclui a conta do titular: encerra cobranças, confirma por escrito e
     * anonimiza — NESTA ordem, e é por isso que é um método só.
     *
     * A ordem não é detalhe, é o que faz cada passo funcionar:
     *
     *  1. **Encerrar cobranças primeiro.** Depois da anonimização não há como
     *     saber de quem era a assinatura.
     *  2. **Confirmar depois disso, e antes de anonimizar.** Só aqui existem
     *     simultaneamente o e-mail do titular (que a anonimização destrói) e o
     *     número de recorrências já canceladas (que o passo 1 produziu). Em
     *     qualquer outra posição a confirmação sai incompleta ou não sai.
     *  3. **Anonimizar por último.**
     *
     * Os três passos viviam espalhados: o chamador tinha de lembrar de invocar
     * a confirmação antes de `executar()`, e o cancelamento não existia. Um
     * método que depende de o chamador acertar a ordem é um defeito esperando
     * o próximo chamador — agora não há ordem a lembrar.
     */
    public function executar(Model $titular): void
    {
        $this->encerrarCobrancas($titular);

        self::confirmarAoTitular($titular);

        match (true) {
            $titular instanceof Personal => $this->anonimizarPersonal($titular),
            $titular instanceof Academia => $this->anonimizarAcademia($titular),
            $titular instanceof Studio => $this->anonimizarStudio($titular),
            $titular instanceof Loja => $this->anonimizarLoja($titular),
            $titular instanceof Cliente => $this->anonimizarCliente($titular),
            default => null,
        };
    }

    /**
     * Encerra o que ainda cobraria dinheiro depois do fim da conta.
     *
     * Este método é a correção do defeito mais caro desta auditoria: a exclusão
     * **não tocava em `subscriptions`**. O aluno excluía a conta, a recorrência
     * seguia viva no Asaas, e ele continuava sendo cobrado todo mês — sem conta
     * para cancelar, sem e-mail para reclamar (já anonimizado) e sem nada no
     * sistema ligando a cobrança a uma pessoa. Cobrança indevida continuada, e
     * silenciosa das duas pontas.
     *
     * A regra tem dois lados, e eles são deliberadamente diferentes:
     *
     *  - **Titular é ALUNO** → cancelamos as assinaturas DELE. É dinheiro dele
     *    saindo por um serviço que ele acabou de dispensar; não há terceiro
     *    afetado e deixar rodando não tem defesa possível.
     *
     *  - **Titular é PROFISSIONAL ou ESTABELECIMENTO** → NÃO cancelamos as
     *    assinaturas dos alunos dele. Aquele dinheiro é de terceiros, e
     *    cancelar por conta própria encerraria na hora o acesso que o aluno
     *    pagou, sem que ele tenha pedido nada. O que a cláusula 16.3 promete, e
     *    o que fazemos aqui, é **avisar cada aluno afetado** para que ele
     *    decida — cancelar, pedir reembolso proporcional ou migrar.
     *
     * Tudo best-effort e logado: a exclusão é direito do titular (art. 18, VI,
     * da LGPD) e não pode falhar porque o Asaas está fora do ar. Se um
     * cancelamento não passar, a assinatura fica registrada no log para
     * conciliação manual — melhor um alerta do que travar o direito.
     */
    private function encerrarCobrancas(Model $titular): void
    {
        try {
            if ($titular instanceof Cliente) {
                $this->cancelarAssinaturasDoAluno($titular);

                return;
            }

            $coluna = match (true) {
                $titular instanceof Personal => 'trainer_id',
                $titular instanceof Academia => 'academia_id',
                $titular instanceof Studio => 'studio_id',
                // Loja não tem recorrência: vende pedido, não assinatura.
                default => null,
            };

            if ($coluna) {
                $this->avisarAlunosDoProfissional($titular, $coluna);
            }
        } catch (\Throwable $e) {
            Log::error('Exclusão de conta: falha ao encerrar cobranças', [
                'conta' => class_basename($titular),
                'id' => $titular->getKey(),
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /** Cancela, no Asaas e localmente, as recorrências do próprio aluno. */
    private function cancelarAssinaturasDoAluno(Cliente $cliente): void
    {
        $ativas = Subscription::where('user_id', $cliente->id)
            ->where('status', '!=', 'canceled')
            ->get();

        if ($ativas->isEmpty()) {
            return;
        }

        // Reaproveita o cancelamento real (Asaas + estado local + recálculo de
        // acesso) em vez de escrever um segundo. `avisar: false` porque a
        // confirmação da exclusão já informa que as recorrências foram
        // encerradas — ver `confirmarAoTitular`.
        $pagamentos = app(\App\Http\Controllers\PaymentController::class);

        foreach ($ativas as $sub) {
            try {
                $pagamentos->cancelarAssinaturaDe($sub, 'exclusao_de_conta', avisar: false);
            } catch (\Throwable $e) {
                Log::error('Exclusão de conta: assinatura NÃO cancelada — conciliar à mão', [
                    'subscription_id' => $sub->id,
                    'asaas_subscription_id' => $sub->asaas_subscription_id,
                    'cliente_id' => $cliente->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Avisa os alunos com plano vigente que o profissional saiu (cláusula 16.3).
     *
     * Não cancela nada: a decisão é do aluno, que é quem paga. O texto diz o
     * que houve, o que continua valendo e quais são as saídas — sem isso o
     * aluno descobriria pela aula que ninguém dá.
     */
    private function avisarAlunosDoProfissional(Model $titular, string $coluna): void
    {
        $afetadas = Subscription::where($coluna, $titular->getKey())
            ->where('status', '!=', 'canceled')
            ->get();

        $nome = $titular->nome ?: 'O profissional';

        foreach ($afetadas as $sub) {
            try {
                $cliente = Cliente::find($sub->user_id);
                if (! $cliente) {
                    continue;
                }

                $ate = $sub->acesso_ate
                    ? \Carbon\Carbon::parse($sub->acesso_ate)->format('d/m/Y')
                    : null;

                NotificacaoService::cliente(
                    $cliente,
                    'Importante: encerramento de conta afeta o seu plano — SNR FIT',
                    "*{$nome}* encerrou a conta na SNR FIT, e isso afeta um plano seu que está vigente.\n\n"
                    .($ate ? "O seu acesso pago segue até *{$ate}*.\n\n" : '')
                    ."*Não cancelamos nada por você* — a decisão é sua. Você pode:\n"
                    ."• cancelar a assinatura agora, em Meus pagamentos › Minhas assinaturas;\n"
                    ."• pedir o reembolso proporcional do período não usufruído, falando com o suporte;\n"
                    ."• escolher outro profissional ou estabelecimento na plataforma.\n\n"
                    .'Se precisar de ajuda, responda a esta mensagem.'
                );
            } catch (\Throwable $e) {
                Log::warning('Exclusão de conta: falha ao avisar aluno afetado', [
                    'subscription_id' => $sub->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        if ($afetadas->isNotEmpty()) {
            Log::info('Exclusão de conta: alunos com plano vigente avisados', [
                'conta' => class_basename($titular),
                'id' => $titular->getKey(),
                'assinaturas_afetadas' => $afetadas->count(),
            ]);
        }
    }

    /**
     * Apaga os canais de aviso da conta: avisos in-app e tokens de aparelho.
     *
     * Extraído porque estava **faltando em três dos cinco perfis**. Cliente e
     * personal apagavam `notificacoes`; academia, studio e loja não — e os três
     * recebem aviso de plano vendido e de pedido pago, então sobravam avisos
     * com conteúdo de negócio depois da "exclusão".
     *
     * `push_tokens` não era apagado para **nenhum** dos cinco. O token é o
     * identificador do aparelho da pessoa (é o que a Apple chama de Device ID):
     * mantê-lo após a eliminação contradiz a própria exclusão e deixa um
     * identificador vivo apontando para uma conta anonimizada.
     */
    private function limparCanaisDeAviso(string $tipo, int $id): void
    {
        Notificacao::where('destinatario_tipo', $tipo)->where('destinatario_id', $id)->delete();
        PushToken::where('destinatario_tipo', $tipo)->where('destinatario_id', $id)->delete();
    }

    /**
     * Cancela o bônus de indicação ainda não pago (cláusula 9.10 dos Termos).
     *
     * A cláusula afirma que o encerramento "implica a perda do bônus ainda não
     * pago, inclusive do já liberado e não solicitado". Nada em código fazia
     * isso: o saldo continuava marcado como `liberado` e sacável, contra o que
     * o contrato diz — divergência que favorecia a conta excluída e, pior,
     * deixava dinheiro pendente atrelado a um titular que não existe mais.
     *
     * Só mexe no que NÃO está em saque: `saque_id` preenchido significa que o
     * dinheiro saiu ou está em trânsito no Asaas, e reescrever o livro embaixo
     * de uma transferência em andamento é pior que a inconsistência — é a mesma
     * regra que a re-apuração da indicação já seguia.
     */
    private function cancelarBonusDeIndicacao(Model $titular): void
    {
        try {
            $afetados = CupomUso::where('usuario_type', $titular->getMorphClass())
                ->where('usuario_id', $titular->getKey())
                ->whereNull('saque_id')
                ->whereIn('status', [CupomUso::STATUS_PENDENTE, CupomUso::STATUS_LIBERADO])
                ->update(['status' => CupomUso::STATUS_CANCELADO]);

            if ($afetados > 0) {
                Log::info('Exclusão de conta: bônus de indicação cancelado (cláusula 9.10)', [
                    'conta' => class_basename($titular),
                    'id' => $titular->getKey(),
                    'indicacoes_canceladas' => $afetados,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Exclusão de conta: falha ao cancelar bônus de indicação', [
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function anonimizarCliente(Cliente $c): void
    {
        Anamnese::where('cliente_id', $c->id)->delete();
        foreach (FotoProgresso::where('cliente_id', $c->id)->get() as $f) {
            $this->apagar($f->caminho);
        }
        FotoProgresso::where('cliente_id', $c->id)->delete();
        MedidaCorporal::where('cliente_id', $c->id)->delete();
        Meta::where('cliente_id', $c->id)->delete();
        RegistroExercicio::where('cliente_id', $c->id)->delete();
        TreinoConcluido::where('cliente_id', $c->id)->delete();
        Mensagem::where('cliente_id', $c->id)->delete();
        $this->limparCanaisDeAviso('cliente', (int) $c->id);
        $this->cancelarBonusDeIndicacao($c);
        $this->apagar($c->foto);

        $c->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_'.$c->id.'@snrfit.local',
            'whatsapp' => null, 'foto' => null, 'cep' => null, 'rua' => null, 'bairro' => null,
            'cidade' => null, 'estado' => null, 'complemento' => null,
            'resumo_objetivo' => null, 'condicao_clinica' => null, 'altura' => null, 'peso' => null,
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function anonimizarPersonal(Personal $p): void
    {
        foreach (($p->fotos ?? collect()) as $foto) {
            $this->apagar($foto->path);
            $foto->delete();
        }
        Mensagem::where('personal_id', $p->id)->delete();
        $this->limparCanaisDeAviso('personal', (int) $p->id);
        $this->cancelarBonusDeIndicacao($p);
        $this->apagar($p->foto);

        $p->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_'.$p->id.'@snrfit.local',
            'cpf' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '',
            'complemento' => '', 'foto' => '', 'whatsapp' => null,
            'chave_pix' => null, 'cref' => null,
            'asaas_account_id' => null, 'asaas_wallet_id' => null, 'asaas_api_key' => null, 'stripe_account_id' => null,
            'status' => 'rejeitado',
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function anonimizarAcademia(Academia $a): void
    {
        foreach (($a->fotos ?? collect()) as $foto) {
            $this->apagar($foto->path);
            $foto->delete();
        }
        $this->limparCanaisDeAviso('academia', (int) $a->id);
        $this->cancelarBonusDeIndicacao($a);
        $a->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_'.$a->id.'@snrfit.local',
            'cnpj' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '',
            'complemento' => '', 'endereco' => '',
            'latitude' => null, 'longitude' => null, 'chave_pix' => null,
            'asaas_account_id' => null, 'asaas_wallet_id' => null, 'asaas_api_key' => null,
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function anonimizarStudio(Studio $s): void
    {
        foreach (($s->fotos ?? collect()) as $foto) {
            $this->apagar($foto->path);
            $foto->delete();
        }
        $this->limparCanaisDeAviso('studio', (int) $s->id);
        $this->cancelarBonusDeIndicacao($s);
        $s->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_'.$s->id.'@snrfit.local',
            'cnpj' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '', 'endereco' => '',
            'whatsapp' => null, 'latitude' => null, 'longitude' => null, 'chave_pix' => null,
            'asaas_account_id' => null, 'asaas_wallet_id' => null, 'asaas_api_key' => null,
            'status' => 'rejeitado',
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function anonimizarLoja(Loja $l): void
    {
        $this->limparCanaisDeAviso('loja', (int) $l->id);
        $this->cancelarBonusDeIndicacao($l);
        $this->apagar($l->logo);
        $l->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_'.$l->id.'@snrfit.local',
            'cnpj' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '', 'endereco' => '',
            'whatsapp' => null, 'logo' => null, 'latitude' => null, 'longitude' => null, 'chave_pix' => null,
            'asaas_account_id' => null, 'asaas_wallet_id' => null, 'asaas_api_key' => null,
            'status' => 'rejeitado',
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function apagar(?string $caminho): void
    {
        if ($caminho) {
            Storage::disk('public')->delete($caminho);
        }
    }
}
