<?php

namespace App\Http\Controllers;

use App\Models\TermoAceite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Tela de reaceite dos Termos de Uso, exibida quando `config('termos.versao')`
 * avança e a conta ainda não registrou aceite dessa versão.
 *
 * O bloqueio em si é do middleware VerificaAceiteTermos; aqui ficam a tela e o
 * registro. Duas rotas, ambas em `config('termos.rotas_livres')` — se não
 * estivessem, o middleware redirecionaria a própria tela de aceite para si
 * mesma, num laço infinito.
 */
class TermoAceiteController extends Controller
{
    /** Sessão => model, igual ao resto do app. */
    private const PERFIS = [
        'personal_id' => \App\Models\Cadastro\Personal::class,
        'cliente_id'  => \App\Models\Cadastro\Cliente::class,
        'academia_id' => \App\Models\Cadastro\Academia::class,
        'studio_id'   => \App\Models\Cadastro\Studio::class,
        'loja_id'     => \App\Models\Cadastro\Loja::class,
    ];

    public function mostrar(Request $request)
    {
        $usuario = $this->usuarioLogado($request);

        if (! $usuario) {
            return redirect()->route('login.index');
        }

        // Já aceitou (outra aba, duplo submit): não prende a pessoa na tela.
        if (! $usuario->precisaAceitarTermos()) {
            return redirect()->to($this->destino($request, $usuario));
        }

        return view('legal.aceite', [
            'usuario'      => $usuario,
            'versao'       => (string) config('termos.versao'),
            'versaoAnterior' => $usuario->versaoTermosAceita(),
            'resumo'       => (array) config('termos.resumo', []),
            'linkTermos'   => route('termos'),
            'linkPerfil'   => $this->termosDoPerfil($usuario),
            'perfilLabel'  => $this->perfilLabel($usuario),
        ]);
    }

    public function registrar(Request $request)
    {
        $usuario = $this->usuarioLogado($request);

        if (! $usuario) {
            return redirect()->route('login.index');
        }

        // O checkbox é obrigatório: sem ele não há manifestação de vontade, que é
        // o que dá validade ao aceite.
        $request->validate(
            ['aceito' => ['accepted']],
            ['aceito.accepted' => 'Marque a caixa de confirmação para continuar.']
        );

        $ok = $usuario->registrarAceiteTermos(
            $request->ip(),
            (string) $request->userAgent(),
            TermoAceite::ORIGEM_REACEITE
        );

        if (! $ok) {
            return back()->withErrors([
                'aceito' => 'Não conseguimos registrar seu aceite agora. Tente novamente em instantes.',
            ]);
        }

        // A09 — o aceite é um ato jurídico; fica na trilha de auditoria.
        Log::channel('security')->info('termos_aceitos', [
            'usuario' => $usuario->getMorphClass() . '#' . $usuario->getKey(),
            'versao'  => (string) config('termos.versao'),
            'ip'      => $request->ip(),
        ]);

        return redirect()->to($this->destino($request, $usuario))
            ->with('sucesso', 'Obrigado! Seu aceite foi registrado.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function usuarioLogado(Request $request)
    {
        foreach (self::PERFIS as $chave => $model) {
            if ($id = $request->session()->get($chave)) {
                return $model::find($id);
            }
        }

        return null;
    }

    /**
     * Para onde mandar a pessoa depois de aceitar: a página que ela tentou abrir
     * (guardada pelo middleware) ou o painel do perfil dela.
     *
     * Só aceita destino do próprio host — um `fullUrl` guardado em sessão não
     * deveria ser externo, mas validar evita que isso vire open redirect se a
     * sessão for manipulada.
     */
    private function destino(Request $request, $usuario): string
    {
        $destino = $request->session()->pull('termos_destino');

        if ($destino && str_starts_with($destino, $request->getSchemeAndHttpHost() . '/')) {
            return $destino;
        }

        return $this->rotaDashboard($usuario);
    }

    private function rotaDashboard($usuario): string
    {
        return match (class_basename($usuario)) {
            'Personal' => $usuario->isNutricionista() ? route('nutri.painel') : route('personal.dashboard'),
            'Cliente'  => route('cliente.index'),
            'Academia' => route('academia.dashboard'),
            'Studio'   => route('studio.dashboard'),
            'Loja'     => route('loja.dashboard'),
            default    => route('login.index'),
        };
    }

    /** Link do documento específico do perfil, que também integra os Termos. */
    private function termosDoPerfil($usuario): ?string
    {
        return match (class_basename($usuario)) {
            'Personal' => route('termos.personal'),
            'Cliente'  => route('termos.aluno'),
            'Academia' => route('termos.academia'),
            'Studio'   => route('termos.studio'),
            'Loja'     => route('termos.loja'),
            default    => null,
        };
    }

    private function perfilLabel($usuario): string
    {
        return match (class_basename($usuario)) {
            'Personal' => $usuario->isNutricionista() ? 'Nutricionista' : 'Personal Trainer',
            'Cliente'  => 'Aluno',
            'Academia' => 'Academia',
            'Studio'   => 'Studio',
            'Loja'     => 'Loja',
            default    => 'Conta',
        };
    }
}
