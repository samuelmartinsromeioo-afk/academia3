<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Notificacao;

/**
 * Central de notificações in-app (sino), para personal e cliente.
 */
class NotificacaoController extends Controller
{
    public function index()
    {
        $d = $this->dest();
        if (! $d) {
            return redirect()->route('login.index');
        }

        $notificacoes = Notificacao::where('destinatario_tipo', $d['tipo'])
            ->where('destinatario_id', $d['id'])
            ->orderByDesc('created_at')->limit(100)->get();

        // Um destino por papel: com o ternário de antes, academia/studio/loja
        // voltariam para o painel do ALUNO (`cliente.index`).
        $voltar = match ($d['tipo']) {
            'personal' => route('personal.dashboard'),
            'academia' => route('academia.dashboard'),
            'studio'   => route('studio.dashboard'),
            'loja'     => route('loja.dashboard'),
            default    => route('cliente.index'),
        };

        return view('notificacoes.index', compact('notificacoes', 'voltar'));
    }

    public function naoLidas()
    {
        $d = $this->dest();
        if (! $d) {
            return response()->json(['count' => 0]);
        }

        $count = Notificacao::where('destinatario_tipo', $d['tipo'])
            ->where('destinatario_id', $d['id'])->where('lida', false)->count();

        return response()->json(['count' => $count]);
    }

    public function marcarLida($id)
    {
        $d = $this->dest();
        if (! $d) {
            return redirect()->route('login.index');
        }

        $n = Notificacao::find($id);
        if ($n && $n->destinatario_tipo === $d['tipo'] && $n->destinatario_id == $d['id']) {
            $n->update(['lida' => true]);

            // A01 — só redireciona para dentro da própria aplicação. Sem isso uma
            // notificação com url externa viraria open redirect (link de phishing
            // saindo de um domínio legítimo).
            if ($n->url && $this->urlInterna($n->url)) {
                return redirect($n->url);
            }
        }

        return redirect()->route('notificacoes.index');
    }

    /** Aceita caminho relativo ou URL absoluta no mesmo host da aplicação. */
    private function urlInterna(string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && $host === parse_url(config('app.url'), PHP_URL_HOST);
    }

    public function marcarTodas()
    {
        $d = $this->dest();
        if (! $d) {
            return redirect()->route('login.index');
        }

        Notificacao::where('destinatario_tipo', $d['tipo'])
            ->where('destinatario_id', $d['id'])->where('lida', false)->update(['lida' => true]);

        return redirect()->route('notificacoes.index')->with('success', 'Tudo marcado como lido.');
    }

    /**
     * Caixa de avisos do usuário da sessão — nos CINCO papéis.
     *
     * Tratava só personal e cliente; academia, studio e loja caíam no null e
     * eram redirecionados para o login mesmo estando logados. Agora que os
     * eventos de venda avisam essas contas (plano contratado, pedido pago), a
     * leitura tem de existir — senão o aviso é gravado e ninguém lê.
     *
     * A ordem segue a do login (LoginController): personal → cliente →
     * academia → studio → loja.
     */
    private function dest(): ?array
    {
        foreach (['personal', 'cliente', 'academia', 'studio', 'loja'] as $tipo) {
            if (session("{$tipo}_id")) {
                return ['tipo' => $tipo, 'id' => (int) session("{$tipo}_id")];
            }
        }

        return null;
    }
}
