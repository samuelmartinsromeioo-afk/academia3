<?php

namespace App\Http\Controllers\Api;

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
 * Notificações in-app e chat personal↔aluno pela API — espelha
 * Cadastro\NotificacaoController e Cadastro\ChatController.
 */
class ChatController extends Controller
{
    // ===================== NOTIFICAÇÕES =====================

    // GET /api/v1/notificacoes
    public function notificacoes(Request $request)
    {
        $d = $this->dest($request);
        if (! $d) {
            return response()->json(['notificacoes' => []]);
        }

        $notificacoes = Notificacao::where('destinatario_tipo', $d['tipo'])
            ->where('destinatario_id', $d['id'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'titulo' => $n->titulo,
                'texto' => $n->texto,
                'lida' => (bool) $n->lida,
                'criada_em' => $n->created_at?->toDateTimeString(),
            ]);

        return response()->json(['notificacoes' => $notificacoes]);
    }

    // GET /api/v1/notificacoes/nao-lidas
    public function naoLidas(Request $request)
    {
        $d = $this->dest($request);
        if (! $d) {
            return response()->json(['count' => 0]);
        }

        $count = Notificacao::where('destinatario_tipo', $d['tipo'])
            ->where('destinatario_id', $d['id'])
            ->where('lida', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // POST /api/v1/notificacoes/{id}/lida
    public function marcarLida(Request $request, $id)
    {
        $d = $this->dest($request);
        $n = Notificacao::find($id);

        if ($d && $n && $n->destinatario_tipo === $d['tipo'] && $n->destinatario_id == $d['id']) {
            $n->update(['lida' => true]);
        }

        return response()->json(['success' => true]);
    }

    // POST /api/v1/notificacoes/marcar-todas
    public function marcarTodas(Request $request)
    {
        $d = $this->dest($request);
        if ($d) {
            Notificacao::where('destinatario_tipo', $d['tipo'])
                ->where('destinatario_id', $d['id'])
                ->where('lida', false)
                ->update(['lida' => true]);
        }

        return response()->json(['success' => true, 'message' => 'Tudo marcado como lido.']);
    }

    // ===================== CHAT =====================

    // GET /api/v1/chat — lista de conversas
    public function conversas(Request $request)
    {
        $user = $request->user();

        if ($user instanceof Personal) {
            $ids = FichaTreino::where('personal_id', $user->id)->pluck('cliente_id')
                ->merge(Agenda::where('personal_id', $user->id)->where('cancelado', false)->whereNotNull('cliente_id')->pluck('cliente_id'))
                ->merge(Mensagem::where('personal_id', $user->id)->pluck('cliente_id'))
                ->filter()->unique();

            $contatos = Cliente::whereIn('id', $ids)->orderBy('nome')->get()->map(function ($c) use ($user) {
                $ultima = Mensagem::where('personal_id', $user->id)->where('cliente_id', $c->id)->latest()->first();
                return [
                    'id' => $c->id,
                    'nome' => $c->nome,
                    'ultima_mensagem' => $ultima?->texto ? Str::limit($ultima->texto, 60) : null,
                    'ultima_em' => $ultima?->created_at?->toDateTimeString(),
                    'nao_lidas' => Mensagem::where('personal_id', $user->id)->where('cliente_id', $c->id)
                        ->where('remetente', 'cliente')->where('lida', false)->count(),
                ];
            })->sortByDesc('ultima_em')->values();

            return response()->json(['eu' => 'personal', 'contatos' => $contatos]);
        }

        if ($user instanceof Cliente) {
            $ids = FichaTreino::where('cliente_id', $user->id)->pluck('personal_id')
                ->merge(Agenda::where('cliente_id', $user->id)->where('cancelado', false)->whereNotNull('personal_id')->pluck('personal_id'))
                ->merge(Mensagem::where('cliente_id', $user->id)->pluck('personal_id'))
                ->filter()->unique();

            $contatos = Personal::whereIn('id', $ids)->orderBy('nome')->get()->map(function ($p) use ($user) {
                $ultima = Mensagem::where('cliente_id', $user->id)->where('personal_id', $p->id)->latest()->first();
                return [
                    'id' => $p->id,
                    'nome' => $p->nome,
                    'ultima_mensagem' => $ultima?->texto ? Str::limit($ultima->texto, 60) : null,
                    'ultima_em' => $ultima?->created_at?->toDateTimeString(),
                    'nao_lidas' => Mensagem::where('cliente_id', $user->id)->where('personal_id', $p->id)
                        ->where('remetente', 'personal')->where('lida', false)->count(),
                ];
            })->sortByDesc('ultima_em')->values();

            return response()->json(['eu' => 'cliente', 'contatos' => $contatos]);
        }

        return response()->json(['error' => 'O chat está disponível para personal e aluno.'], 403);
    }

    // GET /api/v1/chat/{outroId} — mensagens da conversa (marca recebidas como lidas)
    public function mensagens(Request $request, $outroId)
    {
        $ctx = $this->ctx($request, $outroId);
        if (! $ctx) {
            return response()->json(['error' => 'O chat está disponível para personal e aluno.'], 403);
        }
        if (! $this->relacionados($ctx)) {
            return response()->json(['error' => 'Conversa não disponível.'], 403);
        }

        Mensagem::where('personal_id', $ctx['personal_id'])->where('cliente_id', $ctx['cliente_id'])
            ->where('remetente', $ctx['eu'] === 'personal' ? 'cliente' : 'personal')
            ->where('lida', false)->update(['lida' => true]);

        $outro = $ctx['eu'] === 'personal'
            ? Cliente::select('id', 'nome')->find($ctx['cliente_id'])
            : Personal::select('id', 'nome')->find($ctx['personal_id']);

        $mensagens = Mensagem::where('personal_id', $ctx['personal_id'])->where('cliente_id', $ctx['cliente_id'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'minha' => $m->remetente === $ctx['eu'],
                'texto' => $m->texto,
                'hora' => $m->created_at->format('d/m H:i'),
            ]);

        return response()->json(['outro' => $outro, 'mensagens' => $mensagens]);
    }

    // POST /api/v1/chat/{outroId} — envia mensagem
    public function enviar(Request $request, $outroId)
    {
        $ctx = $this->ctx($request, $outroId);
        if (! $ctx) {
            return response()->json(['error' => 'O chat está disponível para personal e aluno.'], 403);
        }
        if (! $this->relacionados($ctx)) {
            return response()->json(['error' => 'Conversa não disponível.'], 403);
        }

        $request->validate(['texto' => 'required|string|max:2000']);

        Mensagem::create([
            'personal_id' => $ctx['personal_id'],
            'cliente_id' => $ctx['cliente_id'],
            'remetente' => $ctx['eu'],
            'texto' => $request->texto,
        ]);

        // Aviso in-app para o destinatário (mesmo padrão do web).
        if ($ctx['eu'] === 'personal') {
            $nome = Personal::find($ctx['personal_id'])?->nome ?? 'Seu personal';
            Notificacao::para('cliente', $ctx['cliente_id'], "Nova mensagem de {$nome}", Str::limit($request->texto, 90), route('chat.conversa', $ctx['personal_id']));
        } else {
            $nome = Cliente::find($ctx['cliente_id'])?->nome ?? 'Aluno';
            Notificacao::para('personal', $ctx['personal_id'], "Nova mensagem de {$nome}", Str::limit($request->texto, 90), route('chat.conversa', $ctx['cliente_id']));
        }

        return response()->json(['success' => true], 201);
    }

    // ===================== HELPERS =====================

    private function dest(Request $request): ?array
    {
        $user = $request->user();
        if ($user instanceof Personal) {
            return ['tipo' => 'personal', 'id' => $user->id];
        }
        if ($user instanceof Cliente) {
            return ['tipo' => 'cliente', 'id' => $user->id];
        }
        return null;
    }

    private function ctx(Request $request, $outroId): ?array
    {
        $user = $request->user();
        if ($user instanceof Personal) {
            return ['personal_id' => $user->id, 'cliente_id' => (int) $outroId, 'eu' => 'personal'];
        }
        if ($user instanceof Cliente) {
            return ['personal_id' => (int) $outroId, 'cliente_id' => $user->id, 'eu' => 'cliente'];
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
