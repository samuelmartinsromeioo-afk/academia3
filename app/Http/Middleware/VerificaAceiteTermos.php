<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia a navegação de quem ainda não aceitou a versão vigente dos Termos.
 *
 * Roda no grupo `web`, para pegar o app inteiro sem precisar anotar dezenas de
 * rotas uma a uma. Três decisões que mantêm isso seguro:
 *
 *  1. Só intercepta GET que pede HTML. POST, PUT, DELETE, AJAX e qualquer coisa
 *     que espere JSON passam direto. Um redirect 302 no meio de um submit ou de
 *     um fetch quebraria o fluxo sem o usuário entender por quê — e não é
 *     necessário: toda sessão começa por um GET de página, então a parede
 *     aparece no primeiro acesso de qualquer forma.
 *  2. Rotas livres em config('termos.rotas_livres') — a própria tela de aceite,
 *     o logout, o login e os documentos legais. Sem isso o usuário não
 *     conseguiria ler o que precisa aceitar, nem sair: seria um laço fechado.
 *  3. Admin não é alcançado. O admin não é usuário da plataforma no sentido dos
 *     Termos e não tem conta nos cinco perfis; bloqueá-lo travaria a operação.
 */
class VerificaAceiteTermos
{
    /** Sessão => model, na mesma ordem usada no resto do app. */
    private const PERFIS = [
        'personal_id' => \App\Models\Cadastro\Personal::class,
        'cliente_id'  => \App\Models\Cadastro\Cliente::class,
        'academia_id' => \App\Models\Cadastro\Academia::class,
        'studio_id'   => \App\Models\Cadastro\Studio::class,
        'loja_id'     => \App\Models\Cadastro\Loja::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->deveVerificar($request)) {
            return $next($request);
        }

        $usuario = $this->usuarioLogado($request);

        if (! $usuario || ! $usuario->precisaAceitarTermos()) {
            return $next($request);
        }

        // Guarda para onde a pessoa estava indo, para devolvê-la ali depois de
        // aceitar em vez de jogá-la num dashboard genérico.
        $request->session()->put('termos_destino', $request->fullUrl());

        return redirect()->route('termos.aceite');
    }

    /** Esta requisição é candidata ao bloqueio? */
    private function deveVerificar(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        // AJAX/JSON: devolver um redirect de HTML aqui só geraria erro estranho
        // no console. A navegação normal já cobre o bloqueio.
        if ($request->ajax() || $request->wantsJson() || $request->isJson()) {
            return false;
        }

        if (! $request->acceptsHtml()) {
            return false;
        }

        $rota = $request->route()?->getName();

        // Rota sem nome não é página de navegação do app (asset, fallback);
        // deixa passar em vez de arriscar bloquear algo inesperado.
        if (! $rota) {
            return false;
        }

        return ! in_array($rota, (array) config('termos.rotas_livres', []), true);
    }

    /** Resolve a conta logada em qualquer um dos cinco perfis (admin fica fora). */
    private function usuarioLogado(Request $request)
    {
        foreach (self::PERFIS as $chave => $model) {
            if ($id = $request->session()->get($chave)) {
                return $model::find($id);
            }
        }

        return null;
    }
}
