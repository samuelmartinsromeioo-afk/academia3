<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\cadastro\Academia;
use App\Models\cadastro\Cliente;
use App\Models\cadastro\Personal;
use App\Models\cadastro\Plano;
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
            'nome'              => 'required|string|max:255',
            'cep'               => 'required|string|max:9',
            'rua'               => 'required|string|max:300',
            'bairro'            => 'required|string|max:200',
            'cidade'            => 'required|string|max:200',
            'estado'            => 'required|string|max:200',
            'complemento'       => 'nullable|string',
            'endereco'          => 'required|string|max:255',
            'valor_mensalidade' => 'required|numeric|min:0',
            'descricao'         => 'nullable|string|max:255',
            'email'             => 'required|email|unique:academias,email|max:255',
            'senha'             => 'required|string|min:8|confirmed',
            'cnpj'              => 'required|string|unique:academias,cnpj|max:18',
            'infraestrutura'    => 'required|string|max:255',
            'tipos_aulas'       => 'required|string|max:255',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $dados['senha'] = Hash::make($dados['senha']);
        Academia::create($dados);

        return redirect()->route('login.index')->with('sucesso', 'Academia cadastrada com sucesso!');
    }

    public function dashboard()
    {
        $academia_id = session('academia_id');

        if (!$academia_id) {
            return redirect()->route('login.index')->withErrors(['login' => 'Acesso negado. Faça o login.']);
        }

        $academia = Academia::with('fotos')->find($academia_id);

        if (!$academia) {
            session()->forget('academia_id');
            return redirect()->route('login.index')->withErrors(['login' => 'Conta de academia não encontrada.']);
        }

        $totalAlunos  = Cliente::where('academia_id', $academia_id)->count();
        $personals    = Personal::where('academia_id', $academia_id)->count();
        $planosAtivos = Cliente::where('academia_id', $academia_id)->where('plano_ativo', true)->count();
        $faturamento  = $totalAlunos * $academia->valor_mensalidade;

        $alunos = Cliente::where('academia_id', $academia_id)->latest()->take(5)->get();

        // Planos da academia
        $planos = Plano::where('academia_id', $academia_id)->orderBy('valor')->get();

        return view('academia.dashboard', compact(
            'academia',
            'totalAlunos',
            'planosAtivos',
            'faturamento',
            'personals',
            'alunos',
            'planos'
        ));
    }

    public function listarAlunos()
    {
        $academia_id = session('academia_id');
        if (!$academia_id) return redirect()->route('login.index');

        $todosAlunos = Cliente::where('academia_id', $academia_id)->orderBy('nome', 'asc')->get();
        return $this->dashboard();
    }

    public function update(Request $request, $id)
    {
        $academia = Academia::findOrFail($id);

        $dados = $request->validate([
            'nome'              => 'required|string|max:255',
            'cidade'            => 'required|string',
            'valor_mensalidade' => 'required|numeric',
            'descricao'         => 'nullable|string',
        ]);

        if ($request->filled('senha')) {
            $dados['senha'] = Hash::make($request->senha);
        }

        $academia->update($dados);

        return redirect()->back()->with('success', 'Perfil atualizado com sucesso!');
    }

    // ── PLANOS ──────────────────────────────────────────────

    public function storePlano(Request $request)
    {
        $request->validate([
            'nome'           => 'required|string|max:255',
            'valor'          => 'required|numeric|min:0',
            'duracao_meses'  => 'required|integer|min:1',
            'descricao'      => 'nullable|string|max:500',
        ]);

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
            'ativo'         => 'boolean',
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
        $plano = Plano::where('id', $id)
            ->where('academia_id', session('academia_id'))
            ->firstOrFail();

        $plano->delete();

        return redirect()->back()->with('success', 'Plano removido com sucesso!');
    }
}
