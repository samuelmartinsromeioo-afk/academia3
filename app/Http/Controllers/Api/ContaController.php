<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ExportacaoDadosMail;
use App\Models\Consentimento;
use App\Services\ExclusaoDeConta;
use App\Services\ExportacaoDeDados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Direitos do titular pela API mobile — o espelho de `Cadastro\LgpdController`.
 *
 * A Apple exige que apps que criam cadastro ofereçam a exclusão da conta dentro
 * do próprio app (Perfil → Configurações → Privacidade e meus dados → Excluir
 * minha conta). O papel é detectado pelo token Sanctum (tokenable polimórfico),
 * então todo endpoint daqui serve aos cinco perfis.
 *
 * A exportação (`exportar`) foi acrescentada porque a Política de Privacidade e
 * os Termos do Aluno afirmavam que o titular acessa, exporta e exclui os dados
 * "no app e no site", e no app só existia a exclusão: a promessa valia pela
 * metade, justamente na parte que o art. 18, II e V da LGPD garante. A coleta é
 * a MESMA do site (`ExportacaoDeDados`) — duas coletas divergiriam, e aí o
 * pacote entregue dependeria de onde o titular clicou.
 */
class ContaController extends Controller
{
    /**
     * GET /api/v1/conta/exportar — acesso e portabilidade (art. 18, II e V).
     *
     * Devolve JSON com `Content-Disposition: attachment` igual ao site, para o
     * app poder abrir/compartilhar o arquivo sem precisar remontar nada.
     */
    public function exportar(Request $request, ExportacaoDeDados $exportacao)
    {
        return response($exportacao->json($request->user()))
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$exportacao->nomeArquivo().'"');
    }

    /**
     * POST /api/v1/conta/exportar/email — manda o pacote por e-mail, anexado.
     *
     * É o caminho principal da portabilidade NO APP, e a razão é prática: no
     * site a exportação é um download, mas salvar e compartilhar arquivo no
     * app exigiria `expo-file-system`/`expo-sharing`, que não são dependências
     * do projeto — entrariam com build nativo novo e revisão de loja só para
     * entregar um arquivo. O e-mail vai para o endereço que o próprio titular
     * usa para entrar na conta, que é o mesmo caminho de Google e Apple.
     *
     * Conta sem e-mail válido recebe 422 em vez de um "enviado" falso: dizer
     * que mandou sem ter mandado é pior do que recusar.
     */
    public function exportarPorEmail(Request $request, ExportacaoDeDados $exportacao)
    {
        $user = $request->user();
        $email = $user->email ?? null;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'error' => 'Sua conta não tem um e-mail válido cadastrado. '
                    .'Atualize o e-mail no perfil para receber a cópia dos seus dados.',
            ], 422);
        }

        try {
            Mail::to($email)->send(new ExportacaoDadosMail(
                $user->nome ?? 'Titular',
                $exportacao->json($user),
                $exportacao->nomeArquivo(),
            ));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar exportação de dados por e-mail', [
                'conta' => class_basename($user),
                'id' => $user->getKey(),
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos '
                    .'ou baixe seus dados pelo site, em Privacidade e meus dados.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'email' => $email,
            'message' => "Enviamos a cópia dos seus dados para {$email}, em anexo.",
        ]);
    }

    /**
     * GET /api/v1/conta/consentimentos — estado atual de cada consentimento.
     *
     * Serve a tela "Privacidade e meus dados" do app. O de marketing é exibido
     * como informação, não como interruptor: o consentimento de medição de
     * anúncios é dado no banner de cookies do site, e o app não tem Pixel nem
     * banner — oferecer um botão de "ativar" ali criaria rastreamento a partir
     * de dado coletado no app, que é exatamente o que o rótulo da App Store
     * declara não existir.
     */
    public function consentimentos(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'saude' => Consentimento::concedido($user, Consentimento::TIPO_SAUDE),
            'marketing' => Consentimento::concedido($user, Consentimento::TIPO_MARKETING),
        ]);
    }

    // DELETE /api/v1/conta  { senha }
    public function destroy(Request $request, ExclusaoDeConta $exclusao)
    {
        $request->validate(
            ['senha' => 'required|string'],
            ['senha.required' => 'Confirme sua senha para excluir a conta.']
        );

        $user = $request->user();

        if (! Hash::check($request->senha, $user->senha)) {
            return response()->json(['error' => 'Senha incorreta.'], 422);
        }

        // Encerra as cobranças, confirma por escrito e anonimiza, nessa ordem —
        // ver o cabeçalho de `ExclusaoDeConta::executar`. Nada a chamar antes.
        $exclusao->executar($user);

        // Revoga TODOS os tokens da conta (encerra a sessão em todos os aparelhos).
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sua conta foi encerrada, as assinaturas mensais foram canceladas e seus '
                .'dados pessoais foram removidos. Enviamos a confirmação para o seu e-mail.',
        ]);
    }
}
