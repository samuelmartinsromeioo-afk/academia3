<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Studio;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Academia;
use App\Models\Admin;
use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

        // Notifica o personal da aprovação (e-mail + WhatsApp)
        $primeiroNome = explode(' ', trim($personal->nome))[0];
        \App\Services\NotificacaoService::personal(
            $personal,
            'Cadastro aprovado — bem-vindo(a) ao SnrFit! 🎉',
            "🎉 *Parabéns, {$primeiroNome}!*\n\n".
            "Seu cadastro como personal foi *aprovado* na SnrFit.\n".
            "Você já pode acessar a plataforma com seu e-mail e senha para gerenciar agenda, alunos, pacotes e muito mais.\n\n".
            "Bons treinos! 💪",
            'cadastro_aprovado_personal',
            [$primeiroNome]
        );

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

        // Notifica o personal da rejeição (e-mail + WhatsApp)
        $primeiroNome = explode(' ', trim($personal->nome))[0];
        \App\Services\NotificacaoService::personal(
            $personal,
            'Sobre o seu cadastro no SnrFit',
            "Olá, {$primeiroNome}.\n\n".
            "Analisamos o seu cadastro como personal e, por ora, ele *não pôde ser aprovado*.\n\n".
            "*Motivo:* {$request->motivo}\n\n".
            "Você pode ajustar as informações e enviar novamente. Qualquer dúvida, estamos à disposição.",
            'cadastro_rejeitado_personal',
            [$primeiroNome, $request->motivo]
        );

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
        \App\Models\Cadastro\Pacote::where('personal_id', $id)->delete();

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

    // ✅ CRIAR SUBCONTA ASAAS PARA PERSONAL EXISTENTE
    public function criarSubcontaAsaas($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $personal = Personal::findOrFail($id);

        if ($personal->asaas_wallet_id) {
            return redirect()->back()->with('success', "Personal '{$personal->nome}' já possui conta Asaas configurada.");
        }

        try {
            $payload = [
                'name'          => $personal->nome,
                'email'         => $personal->email,
                'cpfCnpj'       => preg_replace('/\D/', '', $personal->cpf),
                'birthDate'     => $personal->idade,
                'address'       => $personal->rua,
                'addressNumber' => 'S/N',
                'province'      => $personal->bairro,
                'postalCode'    => preg_replace('/\D/', '', $personal->cep),
                'complement'    => $personal->complemento,
                'personType'    => 'FISICA',
            ];

            if ($personal->whatsapp) {
                $payload['mobilePhone'] = preg_replace('/\D/', '', $personal->whatsapp);
            }

            $res  = Http::withHeaders([
                'access_token' => config('services.asaas.key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.asaas.url') . '/accounts', $payload);

            $data = $res->json();

            if ($res->successful() && !empty($data['walletId'])) {
                // A apiKey da subconta só é retornada UMA vez, aqui na criação,
                // e é irrecuperável depois. Persistimos imediatamente, com a key
                // criptografada (Crypt::encryptString) — nunca em texto puro.
                $personal->asaas_account_id = $data['id']       ?? null;
                $personal->asaas_wallet_id  = $data['walletId'] ?? null;

                $semKey = empty($data['apiKey']);
                if (! $semKey) {
                    $personal->asaas_api_key = Crypt::encryptString($data['apiKey']);
                } else {
                    // Não expõe o conteúdo; apenas sinaliza que a key precisará
                    // ser gerada manualmente (fluxo de subconta legada).
                    Log::warning("Admin: subconta criada SEM apiKey no retorno — gerar manualmente depois", [
                        'personal_id'      => $personal->id,
                        'asaas_account_id' => $data['id'] ?? null,
                    ]);
                }

                $personal->save();

                Log::info("Admin: subconta Asaas criada para personal", [
                    'personal_id' => $personal->id,
                    'wallet_id'   => $data['walletId'],
                    'tem_api_key' => ! $semKey,
                ]);

                $msg = "Conta Asaas criada com sucesso para '{$personal->nome}'! Split de pagamentos ativado.";
                if ($semKey) {
                    $msg .= " Atenção: a chave de API não veio no retorno — gere-a pelo fluxo de subconta legada antes de liberar saques.";
                }

                return redirect()->back()->with('success', $msg);
            }

            $errMsg = $data['errors'][0]['description'] ?? 'Resposta inesperada da Asaas.';
            Log::warning("Admin: falha ao criar subconta Asaas", ['personal_id' => $personal->id, 'response' => $data]);
            return redirect()->back()->with('error', "Falha ao criar conta Asaas: {$errMsg}");

        } catch (\Exception $e) {
            Log::error("Admin: exceção ao criar subconta Asaas", ['personal_id' => $personal->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', "Erro ao criar conta Asaas: {$e->getMessage()}");
        }
    }

    /**
     * Gera (recupera) a apiKey de uma subconta JÁ EXISTENTE, cujo asaas_account_id
     * já está salvo no banco. A apiKey original do Asaas só é retornada uma única
     * vez, na criação, e é irrecuperável depois — então, para subcontas legadas
     * sem a key salva (ou com key perdida), criamos uma nova chave de API para a
     * subconta a partir da CONTA RAIZ. A nova key é salva criptografada na hora.
     *
     * ── PRÉ-REQUISITOS MANUAIS NO PAINEL ASAAS (sem eles a chamada FALHA) ──
     *   1) Integrações → "Gerenciamento de Chaves de API de Subcontas" →
     *      "Habilitar acesso". Essa liberação dura APENAS 2 HORAS e depois é
     *      revogada automaticamente.
     *   2) Whitelist de IP: o IP do servidor (Hetzner) precisa estar cadastrado
     *      na whitelist do Asaas; caso contrário a chamada retorna HTTP 403.
     *
     * Autenticação: API key da CONTA RAIZ (config('services.asaas.key')) — nunca
     * a key da subconta.
     *
     * Observação: o campo exato da nova key no retorno deve ser confirmado contra
     * uma chamada real (lemos os candidatos conhecidos de forma defensiva).
     */
    public function gerarKeySubcontaExistente($personalId)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $personal = Personal::findOrFail($personalId);

        if (! $personal->asaas_account_id) {
            return redirect()->back()->with('error', "Personal '{$personal->nome}' não possui subconta Asaas (asaas_account_id vazio). Crie a subconta primeiro.");
        }

        try {
            $res = Http::withHeaders([
                'access_token' => config('services.asaas.key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.asaas.url') . "/accounts/{$personal->asaas_account_id}/accessTokens", [
                'name' => 'SNRFIT saque ' . now()->format('Y-m-d H:i'),
            ]);

            // 403 = acesso aos endpoints de subconta desabilitado (dura 2h) ou IP fora da whitelist.
            if ($res->status() === 403) {
                Log::warning("Admin: 403 ao gerar key de subconta", ['personal_id' => $personal->id]);
                return redirect()->back()->with('error', 'Acesso negado (403). Verifique se o "Gerenciamento de Chaves de API de Subcontas" está habilitado no painel Asaas (dura 2h) e se o IP do servidor está na whitelist.');
            }

            $data = $res->json();

            // O nome do campo da nova key pode variar conforme a versão da API.
            $novaKey = $data['apiKey'] ?? $data['accessToken'] ?? $data['access_token'] ?? null;

            if ($res->successful() && ! empty($novaKey)) {
                $personal->asaas_api_key = Crypt::encryptString($novaKey);
                $personal->save();
                unset($novaKey, $data); // descarta a key em texto puro o quanto antes

                Log::info("Admin: nova apiKey de subconta gerada e salva (criptografada)", [
                    'personal_id'      => $personal->id,
                    'asaas_account_id' => $personal->asaas_account_id,
                ]);

                return redirect()->back()->with('success', "Nova chave de API gerada e salva com segurança para '{$personal->nome}'. Saques liberados.");
            }

            $errMsg = $data['errors'][0]['description'] ?? 'Resposta inesperada do Asaas ao gerar a chave (confira o campo da key no retorno).';
            Log::warning("Admin: falha ao gerar key de subconta", ['personal_id' => $personal->id, 'status' => $res->status(), 'response' => $data]);
            return redirect()->back()->with('error', "Falha ao gerar chave de API: {$errMsg}");

        } catch (\Exception $e) {
            Log::error("Admin: exceção ao gerar key de subconta", ['personal_id' => $personal->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', "Erro ao gerar chave de API: {$e->getMessage()}");
        }
    }

    // ==========================================
    // GESTÃO DE STUDIOS
    // ==========================================

    public function listarStudios(Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $filtro = $request->query('status', 'todos');
        $busca = $request->query('busca', '');

        $query = Studio::query();

        if ($filtro !== 'todos') {
            $query->where('status', $filtro);
        }

        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'LIKE', "%$busca%")
                  ->orWhere('email', 'LIKE', "%$busca%")
                  ->orWhere('cnpj', 'LIKE', "%$busca%");
            });
        }

        $studios = $query->orderBy('nome')->get();

        return view('admin.studios.lista', [
            'studios' => $studios,
            'filtro' => $filtro,
            'busca' => $busca,
        ]);
    }

    public function verDetalhesStudio($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $studio = Studio::withCount('clientes')->findOrFail($id);

        $faturamentoMes = \App\Models\Payment::where('studio_id', $id)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount_total');

        return view('admin.studios.detalhes', [
            'studio' => $studio,
            'faturamentoMes' => $faturamentoMes,
        ]);
    }

    public function aprovarStudio($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $studio = Studio::findOrFail($id);
        $studio->update([
            'status' => 'aprovado',
            'data_aprovacao' => now(),
            'motivo_rejeicao' => null,
        ]);

        return redirect()->back()->with('success', "Studio '{$studio->nome}' aprovado com sucesso! ✅");
    }

    public function rejeitarStudio($id, Request $request)
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

        $studio = Studio::findOrFail($id);
        $studio->update([
            'status' => 'rejeitado',
            'motivo_rejeicao' => $request->motivo,
        ]);

        return redirect()->back()->with('success', "Studio '{$studio->nome}' rejeitado.");
    }

    // ===================== ACADEMIAS =====================
    public function listarAcademias(Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $filtro = $request->query('status', 'todos');
        $busca  = $request->query('busca', '');

        $query = Academia::query();
        if ($filtro !== 'todos') {
            $query->where('status', $filtro);
        }
        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'LIKE', "%$busca%")
                  ->orWhere('email', 'LIKE', "%$busca%")
                  ->orWhere('cnpj', 'LIKE', "%$busca%");
            });
        }

        $academias = $query->orderBy('nome')->get();

        return view('admin.academias.lista', [
            'academias' => $academias,
            'filtro'    => $filtro,
            'busca'     => $busca,
        ]);
    }

    public function verDetalhesAcademia($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $academia = Academia::findOrFail($id);

        return view('admin.academias.detalhes', ['academia' => $academia]);
    }

    public function aprovarAcademia($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $academia = Academia::findOrFail($id);
        $academia->update([
            'status'          => 'aprovado',
            'data_aprovacao'  => now(),
            'motivo_rejeicao' => null,
        ]);

        return redirect()->back()->with('success', "Academia '{$academia->nome}' aprovada com sucesso! ✅");
    }

    public function rejeitarAcademia($id, Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'motivo' => 'required|string|min:10|max:500',
        ], [
            'motivo.required' => 'Você deve informar o motivo da rejeição',
            'motivo.min'      => 'O motivo deve ter pelo menos 10 caracteres',
        ]);

        $academia = Academia::findOrFail($id);
        $academia->update([
            'status'          => 'rejeitado',
            'motivo_rejeicao' => $request->motivo,
        ]);

        return redirect()->back()->with('success', "Academia '{$academia->nome}' rejeitada.");
    }

    // ===================== LOJAS: aprovar / rejeitar =====================
    public function aprovarLoja($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $loja = Loja::findOrFail($id);
        $loja->update([
            'status'          => 'aprovado',
            'data_aprovacao'  => now(),
            'motivo_rejeicao' => null,
        ]);

        return redirect()->back()->with('success', "Loja '{$loja->nome}' aprovada com sucesso! ✅");
    }

    public function rejeitarLoja($id, Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'motivo' => 'required|string|min:10|max:500',
        ], [
            'motivo.required' => 'Você deve informar o motivo da rejeição',
            'motivo.min'      => 'O motivo deve ter pelo menos 10 caracteres',
        ]);

        $loja = Loja::findOrFail($id);
        $loja->update([
            'status'          => 'rejeitado',
            'motivo_rejeicao' => $request->motivo,
        ]);

        return redirect()->back()->with('success', "Loja '{$loja->nome}' rejeitada.");
    }

    public function deletarStudio($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $studio = Studio::findOrFail($id);
        $nome = $studio->nome;

        Agenda::where('studio_id', $id)->delete();
        Cliente::where('studio_id', $id)->update([
            'studio_id' => null,
            'studio_plano_id' => null,
            'studio_plano_ativo' => false,
        ]);
        $studio->fotos()->delete();
        $studio->delete();

        Log::info("Studio '{$nome}' (ID: {$id}) foi deletado pelo admin");

        return redirect()->route('admin.studios.lista')
            ->with('success', "Studio '{$nome}' e todos seus dados foram deletados permanentemente.");
    }

    // ✅ CRIAR SUBCONTA ASAAS PARA STUDIO (PESSOA JURÍDICA)
    public function criarSubcontaAsaasStudio($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $studio = Studio::findOrFail($id);

        if ($studio->asaas_wallet_id) {
            return redirect()->back()->with('success', "Studio '{$studio->nome}' já possui conta Asaas configurada.");
        }

        try {
            // Subconta PJ: a Asaas exige companyType e incomeValue (e dispensa birthDate)
            $payload = [
                'name'          => $studio->nome,
                'email'         => $studio->email,
                'cpfCnpj'       => preg_replace('/\D/', '', $studio->cnpj),
                'personType'    => 'JURIDICA',
                'companyType'   => 'LIMITED',
                'incomeValue'   => 10000,
                'address'       => $studio->rua,
                'addressNumber' => 'S/N',
                'province'      => $studio->bairro,
                'postalCode'    => preg_replace('/\D/', '', $studio->cep),
                'complement'    => $studio->complemento,
            ];

            if ($studio->whatsapp) {
                $payload['mobilePhone'] = preg_replace('/\D/', '', $studio->whatsapp);
            }

            $res = Http::withHeaders([
                'access_token' => config('services.asaas.key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.asaas.url') . '/accounts', $payload);

            $data = $res->json();

            if ($res->successful() && !empty($data['walletId'])) {
                $studio->update([
                    'asaas_account_id' => $data['id']       ?? null,
                    'asaas_wallet_id'  => $data['walletId'] ?? null,
                    'asaas_api_key'    => $data['apiKey']   ?? null,
                ]);

                Log::info("Admin: subconta Asaas criada para studio", [
                    'studio_id' => $studio->id,
                    'wallet_id' => $data['walletId'],
                ]);

                return redirect()->back()->with('success', "Conta Asaas criada com sucesso para '{$studio->nome}'! Split de pagamentos ativado.");
            }

            $errMsg = $data['errors'][0]['description'] ?? 'Resposta inesperada da Asaas.';
            Log::warning("Admin: falha ao criar subconta Asaas do studio", ['studio_id' => $studio->id, 'response' => $data]);
            return redirect()->back()->with('error', "Falha ao criar conta Asaas: {$errMsg}");

        } catch (\Exception $e) {
            Log::error("Admin: exceção ao criar subconta Asaas do studio", ['studio_id' => $studio->id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', "Erro ao criar conta Asaas: {$e->getMessage()}");
        }
    }

    // ==========================================
    // LOJAS DE SUPLEMENTOS
    // ==========================================

    public function listarLojas(Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $filtro = $request->query('status', 'todos');
        $busca = $request->query('busca', '');

        $query = Loja::withCount('produtos');

        if ($filtro !== 'todos') {
            $query->where('status', $filtro);
        }

        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'LIKE', "%$busca%")
                  ->orWhere('email', 'LIKE', "%$busca%")
                  ->orWhere('cnpj', 'LIKE', "%$busca%");
            });
        }

        $lojas = $query->orderBy('nome')->get();

        return view('admin.lojas.lista', [
            'lojas'  => $lojas,
            'filtro' => $filtro,
            'busca'  => $busca,
        ]);
    }

    public function verDetalhesLoja($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $loja = Loja::with(['produtos' => fn($q) => $q->orderBy('nome')])->findOrFail($id);

        return view('admin.lojas.detalhes', [
            'loja' => $loja,
        ]);
    }

    public function bloquearLoja($id, Request $request)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'motivo' => 'required|string|min:10|max:500',
        ], [
            'motivo.required' => 'Você deve informar o motivo do bloqueio.',
            'motivo.min'      => 'O motivo deve ter pelo menos 10 caracteres.',
        ]);

        $loja = Loja::findOrFail($id);
        $loja->update([
            'status'          => 'rejeitado',
            'motivo_rejeicao' => $request->motivo,
        ]);

        return redirect()->back()->with('success', "Loja '{$loja->nome}' bloqueada.");
    }

    public function reativarLoja($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $loja = Loja::findOrFail($id);
        $loja->update([
            'status'          => 'aprovado',
            'data_aprovacao'  => now(),
            'motivo_rejeicao' => null,
        ]);

        return redirect()->back()->with('success', "Loja '{$loja->nome}' reativada com sucesso! ✅");
    }

    public function deletarLoja($id)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $loja = Loja::findOrFail($id);
        $nome = $loja->nome;

        // Remove as imagens dos produtos e o logo do disco antes de apagar.
        foreach ($loja->produtos as $produto) {
            if ($produto->imagem) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($produto->imagem);
            }
        }
        if ($loja->logo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($loja->logo);
        }

        // produtos têm cascadeOnDelete, mas removemos explicitamente por clareza.
        $loja->produtos()->delete();
        $loja->delete();

        Log::info("Loja '{$nome}' (ID: {$id}) foi deletada pelo admin");

        return redirect()->route('admin.lojas.lista')
            ->with('success', "Loja '{$nome}' e todos os seus produtos foram deletados permanentemente.");
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
                $pacote = \App\Models\Cadastro\Pacote::where('personal_id', $personalId)
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