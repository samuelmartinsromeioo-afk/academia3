<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\MensagemNutri;
use Illuminate\Http\Request;

/**
 * Chat interno nutricionista ↔ paciente (sem expor telefone pessoal).
 * O lado do paciente vive no portal (PortalController).
 */
class ChatController extends Controller
{
    use ResolveNutri;

    public function conversa(int $pacienteId)
    {
        $nutri = $this->nutri();
        $paciente = $this->pacienteDoNutri($pacienteId);

        MensagemNutri::where('paciente_id', $paciente->id)
            ->where('remetente', 'paciente')
            ->whereNull('lida_em')
            ->update(['lida_em' => now()]);

        $mensagens = MensagemNutri::where('paciente_id', $paciente->id)->orderBy('created_at')->get();

        return view('nutri.chat.conversa', compact('nutri', 'paciente', 'mensagens'));
    }

    public function enviar(int $pacienteId, Request $request)
    {
        $paciente = $this->pacienteDoNutri($pacienteId);
        $dados = $request->validate(['texto' => 'required|string|max:2000']);

        MensagemNutri::create([
            'paciente_id' => $paciente->id,
            'remetente' => 'nutri',
            'texto' => $dados['texto'],
        ]);

        // Espelha em WhatsApp/e-mail quando houver contato (reusa canal existente).
        if ($paciente->whatsapp || $paciente->email) {
            \App\Services\NotificacaoService::enviar(
                $paciente->whatsapp,
                $paciente->email,
                $paciente->nome,
                'Nova mensagem do seu nutricionista — SnrFit',
                $dados['texto']
            );
        }

        return back();
    }

    public function mensagens(int $pacienteId)
    {
        $paciente = $this->pacienteDoNutri($pacienteId);

        return response()->json(
            MensagemNutri::where('paciente_id', $paciente->id)->orderBy('created_at')->get()
        );
    }
}
