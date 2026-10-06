<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Mesociclo;
use App\Models\SolicitacaoFicha;

/**
 * "Este aluno é meu?" — a pergunta que todo controller do personal faz antes de
 * abrir dados de um cliente.
 *
 * Existia como oito cópias privadas de `podeVer()` espalhadas pelos controllers
 * (Anamnese, Meta, Mesociclo, Painel, Progresso, Template, e dois na API). Sete
 * eram idênticas e uma divergia, o que é o problema de fundo: uma regra de
 * autorização duplicada só precisa ser esquecida em UM lugar para virar
 * vazamento de dado de aluno de outro profissional.
 *
 * O conjunto abaixo é a UNIÃO de tudo que as oito versões aceitavam, para que
 * nenhuma tela perca acesso que já tinha, mais a solicitação paga.
 *
 * ATENÇÃO ao adotar: um método `podeVer()` declarado na própria classe VENCE o
 * do trait em silêncio, sem erro nenhum. Ao usar este trait num controller,
 * apague a cópia privada dele — senão parece adotado e não está.
 */
trait VinculoComAluno
{
    /**
     * Há vínculo entre este personal e este aluno?
     *
     * Cada ramo é um jeito legítimo de o vínculo existir:
     *
     *  - ficha montada para ele;
     *  - aula na agenda (não cancelada — aula desmarcada não mantém vínculo);
     *  - `clientes.personal_id`, o vínculo direto;
     *  - mesociclo/periodização montada para ele;
     *  - solicitação de ficha PAGA: é o aluno novo que comprou só a montagem do
     *    treino, sem agenda nem ficha ainda. Sem este ramo, a tela de
     *    Solicitações não conseguiria abrir a anamnese nem aplicar um template
     *    de quem acabou de pagar — exatamente o caso que ela existe para servir.
     *
     * Pedido NÃO pago não cria vínculo: qualquer um poderia abrir uma
     * solicitação sem pagar e expor os dados clínicos do aluno.
     */
    protected function podeVer($personalId, $clienteId): bool
    {
        if (! $personalId || ! $clienteId) {
            return false;
        }

        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $personalId)->exists()
            || Mesociclo::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || SolicitacaoFicha::where('personal_id', $personalId)
                ->where('cliente_id', $clienteId)
                ->where('payment_status', 'pago')
                ->exists();
    }
}
