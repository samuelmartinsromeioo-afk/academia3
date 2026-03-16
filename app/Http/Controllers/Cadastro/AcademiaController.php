<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\cadastro\Academia;
use App\Models\cadastro\Cliente; 
use App\Models\cadastro\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AcademiaController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        return view('cadastro.academia');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:300',
            'bairro' => 'required|string|max:200',
            'cidade' => 'required|string|max:200',
            'estado' => 'required|string|max:200',
            'complemento' => 'nullable|string',
            'endereco' => 'required|string|max:255',
            'valor_mensalidade' => 'required|numeric|min:0',
            'descricao' => 'nullable|string|max:255',
            'email' => 'required|email|unique:academias,email|max:255',
            'senha' => 'required|string|min:8|confirmed',
            'cnpj' => 'required|string|unique:academias,cnpj|max:18',
            'infraestrutura' => 'required|string|max:255',
            'tipos_aulas' => 'required|string|max:255'
        ]);

        $dados['senha'] = Hash::make($dados['senha']);

        Academia::create($dados);

        return redirect()->route('login.index')->with('sucesso', 'Academia cadastrada com sucesso!');
    }

    /**
     * Exibe o Painel Principal da Academia
     */
    public function dashboard()
    {
        // 1. Pega o ID de forma segura através da sessão do login
        $academia_id = session('academia_id');

        // Se não tiver sessão (proteção manual de rota)
        if (!$academia_id) {
            return redirect()->route('login.index')->withErrors(['login' => 'Acesso negado. Faça o login.']);
        }

        // 2. Busca a academia e lida com erro caso o ID não exista mais no banco
        $academia = Academia::find($academia_id);

        if (!$academia) {
            session()->forget('academia_id');
            return redirect()->route('login.index')->withErrors(['login' => 'Conta de academia não encontrada.']);
        }

        // 3. Cálculos e Consultas para a View

        // Total de alunos (Clientes vinculados a esta academia)
        $totalAlunos = Cliente::where('academia_id', $academia_id)->count();

        // Quantidade de personals vinculados
        $personals = Personal::where('academia_id', $academia_id)->count();

        // Faturamento (Multiplica alunos pelo valor da mensalidade da academia)
        $faturamento = $totalAlunos * $academia->valor_mensalidade;

        // Planos Ativos (Filtra clientes com a flag plano_ativo)
        // Nota: Garanta que essa coluna exista na sua tabela 'clientes'
        $planosAtivos = Cliente::where('academia_id', $academia_id)
            ->where('plano_ativo', true)
            ->count();

        // Busca os 5 últimos clientes cadastrados
        $alunos = Cliente::where('academia_id', $academia_id)
            ->latest()
            ->take(5)
            ->get();

        return view('academia.dashboard', compact(
            'academia',
            'totalAlunos',
            'planosAtivos',
            'faturamento',
            'personals',
            'alunos'
        ));
    }
   public function listarAlunos()
    {
        $academia_id = session('academia_id');

        if (!$academia_id) {
            return redirect()->route('login.index');
        }

        // Buscamos TODOS os alunos 
        $todosAlunos = Cliente::where('academia_id', $academia_id)->orderBy('nome', 'asc')->get();
        return $this->dashboard($todosAlunos);
    }
}
