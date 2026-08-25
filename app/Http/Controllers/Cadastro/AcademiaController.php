<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Services\MetaConversionsService;
use App\Http\Controllers\Concerns\EscopoAcademia;
use App\Models\Anamnese;
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
    use EscopoAcademia;

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
            'quantidade_alunos' => 'required|integer|min:0|max:100000',
            'descricao' => 'nullable|string|max:255',
            'email' => 'required|email|unique:academias,email|max:255',
            'senha' => 'required|string|min:8|confirmed',
            'cnpj' => 'required|string|unique:academias,cnpj|max:18',
            'infraestrutura' => 'required|string|max:255',
            'tipos_aulas' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $dados['senha']  = Hash::make($dados['senha']);
        $dados['status'] = 'pendente'; // precisa de aprovação do administrador

        $academia = Academia::create($dados);

        $fb = app(MetaConversionsService::class);
        return redirect()->route('cadastro.sucesso')
            ->with('cad_tipo', 'academia')
            ->with('fb_event', $fb->track(
                'CompleteRegistration',
                ['content_name' => 'Academia', 'status' => 'pendente'],
                $fb->userDataFromModel($academia),
                null,
                null,
                route('cadastro.sucesso')
            ));
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
            session()->forget(['academia_id', 'filial_id']);
            return redirect()->route('login.index')->withErrors(['login' => 'Conta de academia não encontrada.']);
        }

        $valorMensalidade = $academia->valor_mensalidade ?? 0;

        // 3. Métricas ESCOPADAS: subconta vê só a sua filial; principal vê tudo.
        $totalAlunos  = $this->clientesVisiveis()->count();
        $planosAtivos = $this->clientesVisiveis()->where('plano_ativo', true)->count();
        $faturamento  = $totalAlunos * $valorMensalidade;
        $personals    = Personal::where('academia_id', $academia_id)->count();
        $alunos       = $this->clientesVisiveis()->latest()->take(5)->get();
        $planos       = Plano::where('academia_id', $academia_id)->orderBy('valor')->get();

        // Contexto da filial logada (para o cabeçalho do painel).
        $filialAtual = $this->ehSubcontaFilial() ? Filial::find($this->filialId()) : null;

        // Quebra por filial — só a conta principal vê, para comparar as unidades.
        $porFilial = collect();
        if ($this->ehAcademiaPrincipal()) {
            $linha = function ($nome, $query) use ($valorMensalidade) {
                $alunosCount = (clone $query)->count();
                return [
                    'nome'         => $nome,
                    'alunos'       => $alunosCount,
                    'planosAtivos' => (clone $query)->where('plano_ativo', true)->count(),
                    'faturamento'  => $alunosCount * $valorMensalidade,
                ];
            };

            $porFilial->push($linha('Matriz (sem filial)', Cliente::where('academia_id', $academia_id)->whereNull('filial_id')));
            foreach (Filial::where('academia_id', $academia_id)->orderBy('nome')->get() as $f) {
                $porFilial->push($linha($f->nome, Cliente::where('academia_id', $academia_id)->where('filial_id', $f->id)));
            }
        }

        return view('academia.dashboard', compact(
            'academia',
            'totalAlunos',
            'planosAtivos',
            'faturamento',
            'personals',
            'alunos',
            'planos',
            'filialAtual',
            'porFilial',
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
            session()->forget(['academia_id', 'filial_id']);
            return redirect()->route('login.index')->withErrors(['login' => 'Conta de academia não encontrada.']);
        }

        // Alunos visíveis para a sessão (subconta = só a filial; principal = todos).
        $alunos = $this->clientesVisiveis()
            ->with('filial:id,nome')
            ->orderBy('nome', 'asc')
            ->get();

        $filialAtual = $this->ehSubcontaFilial() ? Filial::find($this->filialId()) : null;

        // Conta principal: agrupa por filial (chave 0 = Matriz) para comparar unidades.
        $alunosPorFilial = $this->ehAcademiaPrincipal()
            ? $alunos->groupBy(fn ($a) => $a->filial_id ?? 0)
            : collect();

        return view('academia.alunos', compact('academia', 'alunos', 'filialAtual', 'alunosPorFilial'));
    }

    // ==========================================
    // CADASTRO DE ALUNOS PELA ACADEMIA (+ anamnese)
    // ==========================================

    /** Senha padrão atribuída no cadastro feito pela academia. */
    private const SENHA_PADRAO_ALUNO = '123456';

    /** Formulário de cadastro de um novo aluno pela academia. */
    public function criarAluno()
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $planos = Plano::where('academia_id', $academia->id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        // Principal escolhe a filial do aluno; a subconta já tem a sua fixa.
        $filiais = $this->ehAcademiaPrincipal()
            ? Filial::where('academia_id', $academia->id)->orderBy('nome')->get()
            : collect();
        $filialAtual = $this->ehSubcontaFilial() ? Filial::find($this->filialId()) : null;

        return view('academia.aluno-criar', compact('academia', 'planos', 'filiais', 'filialAtual'));
    }

    /** Cria o aluno vinculado à academia com senha padrão e segue para a anamnese. */
    public function storeAluno(Request $request)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $dados = $request->validate([
            'nome'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:clientes,email',
            'whatsapp'         => 'nullable|string|max:20',
            'idade'            => 'nullable|date|before:today',
            'sexo'             => 'required|in:masculino,feminino,outro',
            'altura'           => 'nullable|numeric|min:0|max:300',
            'peso'             => 'nullable|numeric|min:0|max:600',
            'plano'            => 'nullable|string|max:255',
            'resumo_objetivo'  => 'nullable|string|max:1000',
            'condicao_clinica' => 'nullable|string|max:1000',
        ], [
            'email.unique' => 'Já existe um aluno cadastrado com este e-mail.',
        ]);

        $dados['academia_id'] = $academia->id;
        $dados['senha']       = Hash::make(self::SENHA_PADRAO_ALUNO);
        $dados['plano_ativo'] = $request->filled('plano');

        // Vínculo de filial: subconta usa a sua; principal escolhe (validando posse).
        if ($this->ehSubcontaFilial()) {
            $dados['filial_id'] = $this->filialId();
        } else {
            $filialId = $request->input('filial_id');
            $dados['filial_id'] = ($filialId && Filial::where('id', $filialId)->where('academia_id', $academia->id)->exists())
                ? (int) $filialId
                : null;
        }

        $cliente = Cliente::create($dados);

        return redirect()
            ->route('academia.alunos.anamnese', $cliente->id)
            ->with('success', 'Aluno cadastrado! Senha padrão: ' . self::SENHA_PADRAO_ALUNO . ' — o aluno troca no primeiro acesso. Agora preencha a anamnese.');
    }

    /** Formulário de anamnese do aluno, preenchido pela academia logo após o cadastro. */
    public function anamneseForm($clienteId)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $cliente = $this->clienteAcessivel($clienteId);
        if (!$cliente) {
            return redirect()->route('academia.alunos')->with('error', 'Aluno não encontrado.');
        }
        $anamnese = Anamnese::firstOrNew(['cliente_id' => $cliente->id]);

        return view('academia.aluno-anamnese', compact('academia', 'cliente', 'anamnese'));
    }

    /** Salva (cria ou atualiza) a anamnese do aluno vinculado à academia. */
    public function salvarAnamnese(Request $request, $clienteId)
    {
        $academia = $this->academiaLogada();
        if (!$academia) {
            return redirect()->route('login.index');
        }

        $cliente = $this->clienteAcessivel($clienteId);
        if (!$cliente) {
            return redirect()->route('academia.alunos')->with('error', 'Aluno não encontrado.');
        }

        $request->validate([
            'objetivo_principal'    => 'nullable|string|max:255',
            'nivel_atividade'       => 'nullable|in:sedentario,leve,moderado,intenso',
            'historico_lesoes'      => 'nullable|string',
            'restricoes_medicas'    => 'nullable|string',
            'doencas_preexistentes' => 'nullable|string',
            'medicamentos'          => 'nullable|string',
            'cirurgias'             => 'nullable|string',
            'parq_observacoes'      => 'nullable|string',
            'observacoes'           => 'nullable|string',
        ]);

        Anamnese::updateOrCreate(
            ['cliente_id' => $cliente->id],
            [
                'objetivo_principal'    => $request->objetivo_principal,
                'nivel_atividade'       => $request->nivel_atividade,
                'historico_lesoes'      => $request->historico_lesoes,
                'restricoes_medicas'    => $request->restricoes_medicas,
                'doencas_preexistentes' => $request->doencas_preexistentes,
                'medicamentos'          => $request->medicamentos,
                'cirurgias'             => $request->cirurgias,
                'parq_1'                => $request->boolean('parq_1'),
                'parq_2'                => $request->boolean('parq_2'),
                'parq_3'                => $request->boolean('parq_3'),
                'parq_4'                => $request->boolean('parq_4'),
                'parq_5'                => $request->boolean('parq_5'),
                'parq_6'                => $request->boolean('parq_6'),
                'parq_7'                => $request->boolean('parq_7'),
                'parq_observacoes'      => $request->parq_observacoes,
                'observacoes'           => $request->observacoes,
                'preenchida_em'         => now(),
            ]
        );

        return redirect()
            ->route('academia.alunos')
            ->with('success', 'Anamnese de ' . $cliente->nome . ' salva com sucesso! 💪');
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
        if ($this->ehSubcontaFilial()) {
            return redirect()->route('academia.dashboard')->with('error', 'Apenas a conta principal gerencia as filiais.');
        }

        $academia = Academia::findOrFail($academiaId);
        $filiais  = Filial::where('academia_id', $academiaId)->withCount('clientes')->orderBy('nome')->get();

        return view('academia.filiais', compact('academia', 'filiais'));
    }

    public function storeFilial(Request $request)
    {
        $academiaId = session('academia_id');
        if (!$academiaId) return redirect()->route('login.index');
        if ($this->ehSubcontaFilial()) {
            return redirect()->route('academia.dashboard')->with('error', 'Apenas a conta principal pode criar filiais.');
        }

        $request->validate([
            'nome'        => 'required|string|max:255',
            'senha'       => 'required|string|min:6|max:255',
            'cep'         => 'required|string|max:9',
            'rua'         => 'required|string|max:300',
            'bairro'      => 'required|string|max:200',
            'cidade'      => 'required|string|max:200',
            'estado'      => 'required|string|max:100',
            'complemento' => 'nullable|string|max:255',
            'telefone'    => 'nullable|string|max:20',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ], [
            'senha.required' => 'Defina uma senha para a subconta desta filial.',
            'senha.min'      => 'A senha da subconta deve ter ao menos 6 caracteres.',
        ]);

        Filial::create(array_merge(
            ['academia_id' => $academiaId, 'senha' => Hash::make($request->senha)],
            $request->only(['nome', 'cep', 'rua', 'bairro', 'cidade', 'estado', 'complemento', 'telefone', 'latitude', 'longitude'])
        ));

        return redirect()->back()->with('success', 'Filial criada! A subconta loga com o mesmo e-mail/CNPJ da academia e a senha que você definiu.');
    }

    public function updateFilial(Request $request, $id)
    {
        if ($this->ehSubcontaFilial()) {
            return redirect()->route('academia.dashboard')->with('error', 'Apenas a conta principal pode editar filiais.');
        }

        $filial = Filial::where('id', $id)
            ->where('academia_id', session('academia_id'))
            ->firstOrFail();

        $request->validate([
            'nome'        => 'required|string|max:255',
            'senha'       => 'nullable|string|min:6|max:255',
            'cep'         => 'required|string|max:9',
            'rua'         => 'required|string|max:300',
            'bairro'      => 'required|string|max:200',
            'cidade'      => 'required|string|max:200',
            'estado'      => 'required|string|max:100',
            'complemento' => 'nullable|string|max:255',
            'telefone'    => 'nullable|string|max:20',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ], [
            'senha.min' => 'A senha da subconta deve ter ao menos 6 caracteres.',
        ]);

        $dados = $request->only(['nome', 'cep', 'rua', 'bairro', 'cidade', 'estado', 'complemento', 'telefone', 'latitude', 'longitude']);
        if ($request->filled('senha')) {
            $dados['senha'] = Hash::make($request->senha); // redefine a senha da subconta
        }

        $filial->update($dados);

        return redirect()->back()->with('success', 'Filial atualizada com sucesso!');
    }

    public function destroyFilial($id)
    {
        if ($this->ehSubcontaFilial()) {
            return redirect()->route('academia.dashboard')->with('error', 'Apenas a conta principal pode remover filiais.');
        }

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
