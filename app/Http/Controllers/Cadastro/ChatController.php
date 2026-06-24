<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Personal;
use App\Models\Mensagem;
use App\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Chat in-app entre personal e aluno (role-aware via sessão).
 */
class ChatController extends Controller
{
    // Lista de conversas do usuário atual.
    public function index()
    {
        if (session('personal_id')) {
            $personalId = session('personal_id');
            $ids = FichaTreino::where('personal_id', $personalId)->pluck('cliente_id')
                ->merge(Agenda::where('personal_id', $personalId)->where('cancelado', false)->whereNotNull('cliente_id')->pluck('cliente_id'))
                ->merge(Mensagem::where('personal_id', $personalId)->pluck('cliente_id'))
                ->filter()->unique();

            $contatos = Cliente::whereIn('id', $ids)->orderBy('nome')->get()->map(function ($c) use ($personalId) {
                $c->ultima = Mensagem::where('personal_id', $personalId)->where('cliente_id', $c->id)->latest()->first();
                $c->nao_lidas = Mensagem::where('personal_id', $personalId)->where('cliente_id', $c->id)->where('remetente', 'cliente')->where('lida', false)->count();
                return $c;
            })->sortByDesc(fn ($c) => optional($c->ultima)->created_at)->values();

            return view('chat.lista', ['contatos' => $contatos, 'eu' => 'personal', 'voltar' => route('personal.dashboard')]);
        }

        if (session('cliente_id')) {
            $clienteId = session('cliente_id');
            $ids = FichaTreino::where('cliente_id', $clienteId)->pluck('personal_id')
                ->merge(Agenda::where('cliente_id', $clienteId)->where('cancelado', false)->whereNotNull('personal_id')->pluck('personal_id'))
                ->merge(Mensagem::where('cliente_id', $clienteId)->pluck('personal_id'))
                ->filter()->unique();

            $contatos = Personal::whereIn('id', $ids)->orderBy('nome')->get()->map(function ($p) use ($clienteId) {
                $p->ultima = Mensagem::where('cliente_id', $clienteId)->where('personal_id', $p->id)->latest()->first();
                $p->nao_lidas = Mensagem::where('cliente_id', $clienteId)->where('personal_id', $p->id)->where('remetente', 'personal')->where('lida', false)->count();
                return $p;
            })->sortByDesc(fn ($p) => optional($p->ultima)->created_at)->values();

            return view('chat.lista', ['contatos' => $contatos, 'eu' => 'cliente', 'voltar' => route('cliente.index')]);
        }

        return redirect()->route('login.index');
    }

    public function conversa($outroId)
    {
        $ctx = $this->ctx($outroId);
        if (! $ctx) {
            return redirect()->route('login.index');
        }
        if (! $this->relacionados($ctx)) {
            return redirect()->route('chat.index')->with('error', 'Conversa não disponível.');
        }

        // Marca como lidas as mensagens recebidas.
        Mensagem::where('personal_id', $ctx['personal_id'])->where('cliente_id', $ctx['cliente_id'])
            ->where('remetente', $ctx['eu'] === 'personal' ? 'cliente' : 'personal')
            ->where('lida', false)->update(['lida' => true]);

        $mensagens = Mensagem::where('personal_id', $ctx['personal_id'])->where('cliente_id', $ctx['cliente_id'])
            ->orderBy('created_at')->get();
        $outro = $ctx['eu'] === 'personal' ? Cliente::find($outroId) : Personal::find($outroId);

        return view('chat.conversa', ['mensagens' => $mensagens, 'outro' => $outro, 'eu' => $ctx['eu'], 'outroId' => $outroId]);
    }

    public function enviar(Request $request, $outroId)
    {
        $ctx = $this->ctx($outroId);
        if (! $ctx) {
            return redirect()->route('login.index');
        }
        if (! $this->relacionados($ctx)) {
            return redirect()->route('chat.index')->with('error', 'Conversa não disponível.');
        }

        $request->validate(['texto' => 'required|string|max:2000']);

        Mensagem::create([
            'personal_id' => $ctx['personal_id'],
            'cliente_id' => $ctx['cliente_id'],
            'remetente' => $ctx['eu'],
            'texto' => $request->texto,
        ]);

        // Aviso in-app para o destinatário.
        if ($ctx['eu'] === 'personal') {
            $nome = Personal::find($ctx['personal_id'])?->nome ?? 'Seu personal';
            Notificacao::para('cliente', $ctx['cliente_id'], "Nova mensagem de {$nome}", Str::limit($request->texto, 90), route('chat.conversa', $ctx['personal_id']));
        } else {
            $nome = Cliente::find($ctx['cliente_id'])?->nome ?? 'Aluno';
            Notificacao::para('personal', $ctx['personal_id'], "Nova mensagem de {$nome}", Str::limit($request->texto, 90), route('chat.conversa', $ctx['cliente_id']));
        }

        return redirect()->route('chat.conversa', $outroId);
    }

    public function mensagens($outroId)
    {
        $ctx = $this->ctx($outroId);
        if (! $ctx || ! $this->relacionados($ctx)) {
            return response()->json([]);
        }

        $msgs = Mensagem::where('personal_id', $ctx['personal_id'])->where('cliente_id', $ctx['cliente_id'])
            ->orderBy('created_at')->get()
            ->map(fn ($m) => [
                'mine' => $m->remetente === $ctx['eu'],
                'texto' => $m->texto,
                'hora' => $m->created_at->format('d/m H:i'),
            ]);

        return response()->json($msgs);
    }

    private function ctx($outroId): ?array
    {
        if (session('personal_id')) {
            return ['personal_id' => (int) session('personal_id'), 'cliente_id' => (int) $outroId, 'eu' => 'personal'];
        }
        if (session('cliente_id')) {
            return ['personal_id' => (int) $outroId, 'cliente_id' => (int) session('cliente_id'), 'eu' => 'cliente'];
        }
        return null;
    }

    private function relacionados(array $ctx): bool
    {
        return FichaTreino::where('personal_id', $ctx['personal_id'])->where('cliente_id', $ctx['cliente_id'])->exists()
            || Agenda::where('personal_id', $ctx['personal_id'])->where('cliente_id', $ctx['cliente_id'])->where('cancelado', false)->exists()
            || Mensagem::where('personal_id', $ctx['personal_id'])->where('cliente_id', $ctx['cliente_id'])->exists();
    }
}
