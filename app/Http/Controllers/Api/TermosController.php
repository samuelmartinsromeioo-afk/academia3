<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TermoAceite;
use Illuminate\Http\Request;

/**
 * Reaceite dos Termos de Uso pelo APP.
 *
 * Por que isto precisa existir separado do web: `VerificaAceiteTermos` barra
 * apenas requisições GET que aceitam HTML. Essa isenção de JSON/AJAX é
 * deliberada (um 302 no meio de um POST ou de um fetch quebraria o fluxo sem
 * ganho nenhum, já que toda sessão do navegador começa por um GET HTML) — mas
 * o app NUNCA faz um GET HTML. Consequência: subir `config('termos.versao')`
 * colocava todo mundo do site atrás da parede e deixava o app passando reto.
 *
 * A trava do app é do cliente, a partir de `termos.precisa_aceitar` que vem no
 * login e no /me. Isso é aceitável porque aqui o aceite é um registro de
 * consentimento, não um controle de acesso a dado de terceiro: contornar a
 * tela no app não dá acesso a nada que o token já não dê. O que importa é que
 * o REGISTRO do aceite seja íntegro e provável, e isso é servidor.
 */
class TermosController extends Controller
{
    /**
     * GET /api/v1/termos — versão vigente, o que mudou e o estado desta conta.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'versao_vigente' => (string) config('termos.versao'),
            'vigente_desde' => config('termos.vigente_desde'),
            'versao_aceita' => $user->versaoTermosAceita(),
            'precisa_aceitar' => $user->precisaAceitarTermos(),
            /*
             * O resumo do config carrega <strong> para as views Blade. O app é
             * React Native e não tem como renderizar HTML num <Text>, então as
             * tags saem aqui — mostrar "<strong>Pix</strong>" cru ao usuário
             * seria pior que o negrito faltando. `html_entity_decode` cobre
             * entidades que a cópia possa ter (&amp;, &oacute;).
             */
            'resumo' => array_values(array_map(
                fn ($item) => html_entity_decode(strip_tags((string) $item), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                (array) config('termos.resumo', [])
            )),
        ]);
    }

    /**
     * POST /api/v1/termos/aceitar — registra o aceite da versão vigente.
     *
     * A versão aceita é sempre a do servidor, nunca a que o app mandar: deixar
     * o cliente declarar a versão permitiria registrar aceite de uma versão
     * antiga (ou inexistente) e corromper a prova de consentimento (LGPD art.
     * 8º, §1º põe o ônus da prova no controlador).
     *
     * `registrarAceiteTermos` é idempotente e não lança — reenviar o formulário
     * não duplica linha, e a tabela é append-only (uma correção é uma linha
     * nova, nunca um UPDATE), para o histórico de consentimento continuar
     * auditável.
     */
    public function aceitar(Request $request)
    {
        $user = $request->user();

        $ok = $user->registrarAceiteTermos(
            $request->ip(),
            (string) $request->userAgent(),
            TermoAceite::ORIGEM_REACEITE
        );

        if (! $ok) {
            // Falha ao gravar: responde erro em vez de "aceito". Deixar o app
            // seguir sem o registro é o lado inseguro — o acesso continuaria,
            // mas sem prova de consentimento.
            return response()->json([
                'error' => 'Não foi possível registrar seu aceite agora. Tente novamente.',
            ], 500);
        }

        return response()->json([
            'aceito' => true,
            'versao_aceita' => (string) config('termos.versao'),
            'precisa_aceitar' => false,
        ]);
    }
}
