<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\cadastro\Personal;
use App\Models\cadastro\Cliente;
use App\Models\Admin;
use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminController extends Controller
{
    // ✅ DASHBOARD - Visão geral do sistema
    public function dashboard()
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        // Contar personals por status
        $totalPersonals = Personal::count();
        $personalsPendentes = Personal::where('status', 'pendente')->count();
        $personalsAprovados = Personal::where('status', 'aprovado')->count();
        $personalsRejeitados = Personal::where('status', 'rejeitado')->count();

        // Total de clientes
        $totalClientes = Cliente::count();

        // Calcular lucro total
        $lucroTotal = $this->calcularLucroTotalMes();

        // Últimos personals pendentes
        $ultimosPendentes = Personal::where('status', 'pendente')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'totalPersonals' => $totalPersonals,
            'personalsPendentes' => $personalsPendentes,
            'personalsAprovados' => $personalsAprovados,
            'personalsRejeitados' => $personalsRejeitados,
            'totalClientes' => $totalClientes,
            'lucroTotal' => $lucroTotal,
            'ultimosPendentes' => $ultimosPendentes,
        ]);
    }

    // ✅ LISTAR TODOS OS PERSONALS COM FILTRO
    public function listarPersonals(Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $filtro = $request->query('status', 'todos');
        $busca = $request->query('busca', '');
        $ordenacao = $request->query('ordem', 'nome');

        $query = Personal::query();

        // Filtrar por status
        if ($filtro !== 'todos') {
            $query->where('status', $filtro);
        }

        // Filtrar por busca (nome ou email)
        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'LIKE', "%$busca%")
                  ->orWhere('email', 'LIKE', "%$busca%");
            });
        }

        // Ordenação
        switch ($ordenacao) {
            case 'data_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'data_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'lucro':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('nome', 'asc');
        }

        $personalsRaw = $query->get();

        // Calcular lucro com foreach
        $personalsComLucro = [];
        foreach ($personalsRaw as $personal) {
            $personal->lucro_mes = $this->calcularLucroPersonal($personal->id);
            $personalsComLucro[] = $personal;
        }

        // Se ordenar por lucro, reordenar manualmente
        if ($ordenacao === 'lucro') {
            usort($personalsComLucro, function ($a, $b) {
                return $b->lucro_mes <=> $a->lucro_mes;
            });
        }

        return view('admin.personals.lista', [
            'personalsComLucro' => $personalsComLucro,
            'filtro' => $filtro,
            'busca' => $busca,
            'ordenacao' => $ordenacao,
        ]);
    }

    // ✅ VER DETALHES DO PERSONAL
    public function verDetalhes($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $personal = Personal::findOrFail($id);

        // Calcular financeiro detalhado
        $financeiro = $this->calcularFinanceiroPersonal($id);

        // Últimas aulas
        $ultimasAulas = Agenda::where('personal_id', $id)
            ->where('cancelado', false)
            ->orderBy('data', 'desc')
            ->limit(10)
            ->get();

        // Número de clientes únicos
        $totalClientes = Agenda::where('personal_id', $id)
            ->where('cancelado', false)
            ->distinct('cliente_id')
            ->count('cliente_id');

        // Avaliação média
        $mediaAvaliacao = 0;
        if (method_exists($personal, 'avaliacoes') && $personal->avaliacoes) {
            $mediaAvaliacao = $personal->avaliacoes->avg('nota') ?? 0;
        }

        return view('admin.personals.detalhes', [
            'personal' => $personal,
            'financeiro' => $financeiro,
            'ultimasAulas' => $ultimasAulas,
            'totalClientes' => $totalClientes,
            'mediaAvaliacao' => $mediaAvaliacao,
        ]);
    }

    // ✅ APROVAR PERSONAL
   public function aprovar($id)
{
    Log::info("========== INICIANDO APROVAÇÃO ==========");
    Log::info("ID: " . $id);
    Log::info("Admin ID: " . session('admin_id'));
    
    if (!session('admin_id')) {
        Log::error("Sem sessão admin!");
        return redirect()->route('admin.login');
    }
 
    try {
        $personal = Personal::findOrFail($id);
        Log::info("Personal encontrado: " . $personal->nome);
        Log::info("Status antes: " . $personal->status);
        
        $resultado = $personal->update([
            'status' => 'aprovado',
            'data_aprovacao' => now(),
            'motivo_rejeicao' => null
        ]);
        
        Log::info("Update retornou: " . ($resultado ? 'TRUE' : 'FALSE'));
        
        $personal->refresh();
        Log::info("Status depois de refresh: " . $personal->status);
        Log::info("✅ Atualizado com sucesso!");
        
        return redirect()->back()->with('success', "Personal '{$personal->nome}' aprovado com sucesso! ✅");
    } catch (\Exception $e) {
        Log::error("❌ Erro ao aprovar: " . $e->getMessage());
        Log::error("Stack: " . $e->getTraceAsString());
        return redirect()->back()->with('error', "Erro: " . $e->getMessage());
    }
}

    // ✅ REJEITAR PERSONAL
    public function rejeitar($id, Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'motivo' => 'required|string|min:10|max:500'
        ], [
            'motivo.required' => 'Você deve informar o motivo da rejeição',
            'motivo.min' => 'O motivo deve ter pelo menos 10 caracteres',
        ]);

        $personal = Personal::findOrFail($id);
        $personal->update([
            'status' => 'rejeitado',
            'motivo_rejeicao' => $request->motivo
        ]);

        return redirect()->back()->with('success', "Personal '{$personal->nome}' rejeitado. Email de notificação enviado.");
    }

    // ✅ DELETAR PERSONAL
    public function deletar($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $personal = Personal::findOrFail($id);
        $nome = $personal->nome;

        // Deletar todas as aulas/agendas associadas
        Agenda::where('personal_id', $id)->delete();

        // Deletar fotos
        if ($personal->foto) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($personal->foto);
        }
        if ($personal->certificado) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($personal->certificado);
        }

        // Deletar pacotes
        \App\Models\cadastro\Pacote::where('personal_id', $id)->delete();

        // Deletar fotos adicionais
        if (method_exists($personal, 'fotos')) {
            $personal->fotos()->delete();
        }

        // Deletar personal
        $personal->delete();

        Log::info("Personal '{$nome}' (ID: {$id}) foi deletado pelo admin");

        return redirect()->route('admin.personals.lista')
            ->with('success', "Personal '{$nome}' e todos seus dados foram deletados permanentemente.");
    }

    // ✅ RELATÓRIO FINANCEIRO
    public function relatorioFinanceiro(Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $mes = $request->query('mes', now()->month);
        $ano = $request->query('ano', now()->year);

        $personalsRaw = Personal::where('status', 'aprovado')->get();

        $dados = [];
        $totalGeral = 0;

        foreach ($personalsRaw as $personal) {
            $financeiro = $this->calcularFinanceiroPersonal($personal->id, $mes, $ano);
            $total = $financeiro['pacotes'] + $financeiro['avulsas'];
            
            $dados[] = [
                'personal' => $personal,
                'pacotes' => $financeiro['pacotes'],
                'avulsas' => $financeiro['avulsas'],
                'total' => $total,
                'comissao' => $total * 0.1,
            ];

            $totalGeral += $total;
        }

        // Ordenar por lucro decrescente
        usort($dados, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return view('admin.relatorio-financeiro', [
            'dados' => $dados,
            'totalGeral' => $totalGeral,
            'mes' => $mes,
            'ano' => $ano,
        ]);
    }

    public function logout()
    {
        session()->forget(['admin_id', 'admin_nome']);
        return redirect()->route('login.index')->with('sucesso', 'Você saiu da conta.');
    }

    // ==========================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ==========================================

    /**
     * Calcula o lucro de um personal em um mês específico
     */
    private function calcularLucroPersonal($personalId, $mes = null, $ano = null)
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        $financeiro = $this->calcularFinanceiroPersonal($personalId, $mes, $ano);
        return $financeiro['pacotes'] + $financeiro['avulsas'];
    }

    /**
     * Calcula financeiro detalhado (pacotes + avulsas)
     */
    private function calcularFinanceiroPersonal($personalId, $mes = null, $ano = null)
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        // ✅ AULAS DE PACOTE
        $faturamentoPacotes = 0;
        $pacotesProcessados = [];

        $aulasPackote = Agenda::where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('tipo_aula', 'pacote')
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        foreach ($aulasPackote as $agenda) {
            if ($agenda->frequencia_pacote && !isset($pacotesProcessados[$agenda->frequencia_pacote])) {
                $pacote = \App\Models\cadastro\Pacote::where('personal_id', $personalId)
                    ->where('frequencia', $agenda->frequencia_pacote)
                    ->first();

                if ($pacote) {
                    $pacotesProcessados[$agenda->frequencia_pacote] = true;
                    $faturamentoPacotes += $pacote->valor_mensal;
                }
            }
        }

        // ✅ AULAS AVULSAS
        $faturamentoAvulsas = 0;
        $aulasAvulsas = Agenda::where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('tipo_aula', 'avulsa')
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        $personal = Personal::find($personalId);

        foreach ($aulasAvulsas as $agenda) {
            $duracao = Carbon::parse($agenda->hora_inicio)
                ->diffInMinutes(Carbon::parse($agenda->hora_fim)) / 60;
            $faturamentoAvulsas += ($duracao * ($personal->valor_secao ?? 0));
        }

        return [
            'pacotes' => $faturamentoPacotes,
            'avulsas' => $faturamentoAvulsas,
        ];
    }

    /**
     * Calcula lucro total de todos os personals aprovados no mês
     */
    private function calcularLucroTotalMes($mes = null, $ano = null)
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        $personals = Personal::where('status', 'aprovado')->pluck('id');
        $totalGeral = 0;

        foreach ($personals as $personalId) {
            $financeiro = $this->calcularFinanceiroPersonal($personalId, $mes, $ano);
            $totalGeral += ($financeiro['pacotes'] + $financeiro['avulsas']);
        }

        return $totalGeral;
    }

}