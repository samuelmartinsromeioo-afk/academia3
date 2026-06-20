<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\AcademiaAula;
use App\Models\Cadastro\AcademiaProfessor;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Filial;
use App\Models\Cadastro\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Cadastro\Plano;

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
            'tipos_aulas' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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

        $planos = Plano::where('academia_id', $academia_id)->orderBy('valor')->get();    

        return view('academia.dashboard', compact(
            'academia',
            'totalAlunos',
            'planosAtivos',
            'faturamento',
            'personals',
            'alunos',
            'planos',
        ));
    }
   public function listarAlunos()
    {
        $academia_id = session('academia_id');

        if (!$academia_id) {
            return redirect()->route('login.index');
        }

        $academia = Academia::find($academia_id);
        if (!$academia) {
            session()->forget('academia_id');
            return redirect()->route('login.index')->withErrors(['login' => 'Conta de academia não encontrada.']);
        }

        // Todos os alunos vinculados a esta academia
        $alunos = Cliente::where('academia_id', $academia_id)
            ->orderBy('nome', 'asc')
            ->get();

        return view('academia.alunos', compact('academia', 'alunos'));
    }

    /**
     * Atualiza o perfil da academia (nome, cidade, mensalidade, descrição e chave PIX).
     */
    public function update(Request $request, $id)
    {
        $academia_id = session('academia_id');
        if (!$academia_id || (int) $academia_id !== (int) $id) {
            return redirect()->route('login.index')->withErrors(['login' => 'Acesso negado.']);
        }

        $academia = Academia::findOrFail($id);

        $dados = $request->validate([
            'nome'              => 'required|string|max:255',
            'cidade'            => 'required|string|max:200',
            'valor_mensalidade' => 'nullable|numeric|min:0',
            'descricao'         => 'nullable|string|max:500',
            'chave_pix'         => 'nullable|string|max:255',
        ]);

        $academia->update($dados);

        return redirect()->route('academia.dashboard')->with('success', 'Perfil atualizado com sucesso!');
    }

        public function storePlano(Request $request)
    {
        $request->validate([
            'nome'          => 'required|string|max:255',
            'valor'         => 'required|numeric|min:0',
            'duracao_meses' => 'required|integer|min:1',
            'descricao'     => 'nullable|string|max:500',
        ]);

        $count = Plano::where('academia_id', session('academia_id'))->count();
        if ($count >= 5) {
            return redirect()->back()->with('error', 'Limite de 5 planos atingido. Exclua um plano antes de adicionar outro.');
        }

        Plano::create([
            'academia_id'   => session('academia_id'),
            'nome'          => $request->nome,
            'valor'         => $request->valor,
            'duracao_meses' => $request->duracao_meses,
            'descricao'     => $request->descricao,
            'ativo'         => true,
        ]);

        return redirect()->back()->with('success', 'Plano criado com sucesso!');
    }

    public function updatePlano(Request $request, $id)
    {
        $plano = Plano::where('id', $id)
            ->where('academia_id', session('academia_id'))
            ->firstOrFail();

        $request->validate([
            'nome'          => 'required|string|max:255',
            'valor'         => 'required|numeric|min:0',
            'duracao_meses' => 'required|integer|min:1',
            'descricao'     => 'nullable|string|max:500',
        ]);

        $plano->update([
            'nome'          => $request->nome,
            'valor'         => $request->valor,
            'duracao_meses' => $request->duracao_meses,
            'descricao'     => $request->descricao,
            'ativo'         => $request->has('ativo'),
        ]);

        return redirect()->back()->with('success', 'Plano atualizado com sucesso!');
    }

    public function destroyPlano($id)
    {
        Plano::where('id', $id)
            ->where('academia_id', session('academia_id'))
            ->firstOrFail()
            ->delete();

        return redirect()->back()->with('success', 'Plano removido com sucesso!');
    }

    public function filiais()
    {
        $academiaId = session('academia_id');
        if (!$academiaId) return redirect()->route('login.index');

        $academia = Academia::findOrFail($academiaId);
        $filiais  = Filial::where('academia_id', $academiaId)->orderBy('nome')->get();

        return view('academia.filiais', compact('academia', 'filiais'));
    }

    public function storeFilial(Request $request)
    {
        $academiaId = session('academia_id');
        if (!$academiaId) return redirect()->route('login.index');

        $request->validate([
            'nome'        => 'required|string|max:255',
            'cep'         => 'required|string|max:9',
            'rua'         => 'required|string|max:300',
            'bairro'      => 'required|string|max:200',
            'cidade'      => 'required|string|max:200',
            'estado'      => 'required|string|max:100',
            'complemento' => 'nullable|string|max:255',
            'telefone'    => 'nullable|string|max:20',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ]);

        Filial::create(array_merge(
            ['academia_id' => $academiaId],
            $request->only(['nome', 'cep', 'rua', 'bairro', 'cidade', 'estado', 'complemento', 'telefone', 'latitude', 'longitude'])
        ));

        return redirect()->back()->with('success', 'Filial adicionada com sucesso!');
    }

    public function updateFilial(Request $request, $id)
    {
        $filial = Filial::where('id', $id)
            ->where('academia_id', session('academia_id'))
            ->firstOrFail();

        $request->validate([
            'nome'        => 'required|string|max:255',
            'cep'         => 'required|string|max:9',
            'rua'         => 'required|string|max:300',
            'bairro'      => 'required|string|max:200',
            'cidade'      => 'required|string|max:200',
            'estado'      => 'required|string|max:100',
            'complemento' => 'nullable|string|max:255',
            'telefone'    => 'nullable|string|max:20',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ]);

        $filial->update($request->only(['nome', 'cep', 'rua', 'bairro', 'cidade', 'estado', 'complemento', 'telefone', 'latitude', 'longitude']));

        return redirect()->back()->with('success', 'Filial atualizada com sucesso!');
    }

    public function destroyFilial($id)
    {
        Filial::where('id', $id)
            ->where('academia_id', session('academia_id'))
            ->firstOrFail()
            ->delete();

        return redirect()->back()->with('success', 'Filial removida com sucesso!');
    }

    // ==========================================
    // GESTÃO: PROFISSIONAIS, AULAS E INFRAESTRUTURA
    // ==========================================

    private function academiaLogada(): ?Academia
    {
        $academiaId = session('academia_id');
        if (!$academiaId) {
            return null;
        }

        return Academia::find($academiaId);
    }

    public function gestao()
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $professores = AcademiaProfessor::where('academia_id', $academia->id)->orderBy('nome')->get();
        $aulas = AcademiaAula::with('professor')
            ->where('academia_id', $academia->id)
            ->orderBy('nome')
            ->get();

        return view('academia.gestao', compact('academia', 'professores', 'aulas'));
    }

    public function atualizarInfraestrutura(Request $request)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'infraestrutura' => 'nullable|string|max:2000',
        ]);

        $academia->update(['infraestrutura' => $request->infraestrutura]);

        return redirect()->back()->with('success', 'Infraestrutura atualizada!');
    }

    public function storeProfessor(Request $request)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome'   => 'required|string|max:120',
            'resumo' => 'nullable|string|max:2000',
        ]);

        AcademiaProfessor::create([
            'academia_id' => $academia->id,
            'nome'        => $request->nome,
            'resumo'      => $request->resumo,
            'ativo'       => true,
        ]);

        return redirect()->back()->with('success', 'Profissional cadastrado!');
    }

    public function updateProfessor(Request $request, $id)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome'   => 'required|string|max:120',
            'resumo' => 'nullable|string|max:2000',
        ]);

        $professor = AcademiaProfessor::where('academia_id', $academia->id)->findOrFail($id);
        $professor->update([
            'nome'   => $request->nome,
            'resumo' => $request->resumo,
        ]);

        return redirect()->back()->with('success', 'Profissional atualizado!');
    }

    public function destroyProfessor($id)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        AcademiaProfessor::where('academia_id', $academia->id)->findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Profissional removido!');
    }

    public function storeAula(Request $request)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome'         => 'required|string|max:120',
            'resumo'       => 'nullable|string|max:2000',
            'professor_id' => 'nullable|integer',
            'dia_semana'   => 'nullable|integer|min:0|max:6',
            'hora_inicio'  => 'nullable|date_format:H:i',
            'duracao_min'  => 'nullable|integer|min:5|max:600',
        ]);

        $professorId = null;
        if ($request->filled('professor_id')) {
            $professor = AcademiaProfessor::where('academia_id', $academia->id)->find($request->professor_id);
            $professorId = $professor?->id;
        }

        AcademiaAula::create([
            'academia_id'  => $academia->id,
            'nome'         => $request->nome,
            'resumo'       => $request->resumo,
            'professor_id' => $professorId,
            'dia_semana'   => $request->dia_semana,
            'hora_inicio'  => $request->hora_inicio,
            'duracao_min'  => $request->duracao_min,
            'ativo'        => true,
        ]);

        return redirect()->back()->with('success', 'Aula adicionada!');
    }

    public function updateAula(Request $request, $id)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome'         => 'required|string|max:120',
            'resumo'       => 'nullable|string|max:2000',
            'professor_id' => 'nullable|integer',
            'dia_semana'   => 'nullable|integer|min:0|max:6',
            'hora_inicio'  => 'nullable|date_format:H:i',
            'duracao_min'  => 'nullable|integer|min:5|max:600',
        ]);

        $aula = AcademiaAula::where('academia_id', $academia->id)->findOrFail($id);

        $professorId = null;
        if ($request->filled('professor_id')) {
            $professor = AcademiaProfessor::where('academia_id', $academia->id)->find($request->professor_id);
            $professorId = $professor?->id;
        }

        $aula->update([
            'nome'         => $request->nome,
            'resumo'       => $request->resumo,
            'professor_id' => $professorId,
            'dia_semana'   => $request->dia_semana,
            'hora_inicio'  => $request->hora_inicio,
            'duracao_min'  => $request->duracao_min,
        ]);

        return redirect()->back()->with('success', 'Aula atualizada!');
    }

    public function destroyAula($id)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        AcademiaAula::where('academia_id', $academia->id)->findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Aula removida!');
    }
}
