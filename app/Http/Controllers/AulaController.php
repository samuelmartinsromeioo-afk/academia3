<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AulaController extends Controller
{
  
    public function concluirAula(Request $request, $id)
    {
        try {
            $aula = Agenda::findOrFail($id);

            if ($aula->personal_id !== session('personal_id')) {
                return response()->json(['sucesso' => false, 'erro' => 'Não autorizado'], 403);
            }
            $personal = Personal::find($aula->personal_id);
            $cliente = Cliente::find($aula->cliente_id);

            if (!$personal || !$cliente) {
                return response()->json([
                    'sucesso' => false,
                    'erro' => 'Personal ou cliente não encontrado'
                ], 404);
            }

            // ✅ Atualizar status da aula
            $aula->update(['concluida' => true]);

            // ✅ Notificar o personal (WhatsApp + e-mail)
            $this->notificarPersonalWhatsApp(
                $cliente->nome,
                $aula->tipo_aula ?? 'aula',
                $personal,
                $aula
            );

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Aula concluída e personal notificado!',
                'aula_id' => $aula->id
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Aula não encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erro ao concluir aula: ' . $e->getMessage());
            
            return response()->json([
                'sucesso' => false,
                'erro' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notificar personal via WhatsApp usando Twilio
     * 
     * @param string $nomeCliente
     * @param string $tipoAula (pacote, avulsa, bloqueio)
     * @param string $whatsappPersonal (número do personal)
     * @param Agenda $aula
     */
    private function notificarPersonalWhatsApp($nomeCliente, $tipoAula, $personal, $aula)
    {
        if (!$personal) {
            Log::warning("Personal não encontrado para notificar - ID: {$aula->personal_id}");
            return;
        }

        $mensagem = $this->formatarMensagem($nomeCliente, $tipoAula, $aula);

        $data = $aula->data instanceof \Carbon\Carbon
            ? $aula->data->format('d/m/Y')
            : \Carbon\Carbon::parse($aula->data)->format('d/m/Y');

        \App\Services\NotificacaoService::personal(
            $personal,
            'Aula concluída — SnrFit',
            $mensagem,
            'aula_concluida_personal',
            [$nomeCliente, $data, "{$aula->hora_inicio} - {$aula->hora_fim}"]
        );
    }

    /**
     * Formatar mensagem conforme o tipo de aula
     */
    private function formatarMensagem($nomeCliente, $tipoAula, $aula)
    {
        $data = $aula->data instanceof \Carbon\Carbon ? 
            $aula->data->format('d/m/Y') : 
            \Carbon\Carbon::parse($aula->data)->format('d/m/Y');

        $mensagemBase = "✅ Aula Concluída!\n\n";
        $mensagemBase .= "👤 Cliente: {$nomeCliente}\n";
        $mensagemBase .= "📅 Data: {$data}\n";
        $mensagemBase .= "⏰ Horário: {$aula->hora_inicio} - {$aula->hora_fim}\n";

        // Adicionar informações específicas conforme tipo
        switch ($tipoAula) {
            case 'pacote':
                $mensagemBase .= "📦 Tipo: Aula de Pacote\n";
                if ($aula->frequencia_pacote) {
                    $mensagemBase .= "🔄 Frequência: {$aula->frequencia_pacote}x semana\n";
                }
                break;

            case 'avulsa':
                $mensagemBase .= "🎯 Tipo: Aula Avulsa\n";
                break;

            default:
                $mensagemBase .= "📚 Tipo: {$tipoAula}\n";
        }

        $mensagemBase .= "\nAcesse seu app para mais detalhes.";

        return $mensagemBase;
    }
}