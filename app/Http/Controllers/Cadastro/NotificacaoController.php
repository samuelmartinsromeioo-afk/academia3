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

        $voltar = $d['tipo'] === 'personal' ? route('personal.dashboard') : route('cliente.index');

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
            if ($n->url) {
                return redirect($n->url);
            }
        }

        return redirect()->route('notificacoes.index');
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

    private function dest(): ?array
    {
        if (session('personal_id')) {
            return ['tipo' => 'personal', 'id' => session('personal_id')];
        }
        if (session('cliente_id')) {
            return ['tipo' => 'cliente', 'id' => session('cliente_id')];
        }
        return null;
    }
}
