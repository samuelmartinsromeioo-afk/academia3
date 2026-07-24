<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Anamnese;
use App\Models\Cadastro\AcademiaAula;
use App\Models\Cadastro\AcademiaProfessor;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Filial;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Plano;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Painel da ACADEMIA pela API — espelha Cadastro\AcademiaController e
 * AcademiaSolicitacaoController. Tokens de subconta de filial (ability
 * "filial:{id}") enxergam apenas os alunos da própria filial, como no web.
 */
class AcademiaController extends Controller
{
    use ResolvesApiUser;

    private const SENHA_PADRAO_ALUNO = '123456';

    // ===================== DASHBOARD =====================

    // GET /api/v1/academia/dashboard
    public function dashboard(Request $request)
    {
        $academia = $this->academiaAutenticada($request);
        $filialId = $this->filialDoToken($request);

        $valorMensalidade = $academia->valor_mensalidade ?? 0;

        $visiveis = $this->clientesVisiveis($academia->id, $filialId);
        $totalAlunos = (clone $visiveis)->count();
        $planosAtivos = (clone $visiveis)->where('plano_ativo', true)->count();

        $porFilial = [];
        if ($filialId === null) {
            $linha = function ($nome, $query) use ($valorMensalidade) {
                $alunosCount = (clone $query)->count();
                return [
                    'nome' => $nome,
                    'alunos' => $alunosCount,
                    'planos_ativos' => (clone $query)->where('plano_ativo', true)->count(),
                    'faturamento' => $alunosCount * $valorMensalidade,
                ];
            };

            $porFilial[] = $linha('Matriz (sem filial)', Cliente::where('academia_id', $academia->id)->whereNull('filial_id'));
            foreach (Filial::where('academia_id', $academia->id)->orderBy('nome')->get() as $f) {
                $porFilial[] = $linha($f->nome, Cliente::where('academia_id', $academia->id)->where('filial_id', $f->id));
            }
        }

        return response()->json([
            'academia' => [
                'id' => $academia->id,
                'nome' => $academia->nome,
                'descricao' => $academia->descricao,
                'valor_mensalidade' => (float) $valorMensalidade,
                'cidade' => $academia->cidade,
            ],
            'filial_atual' => $filialId ? Filial::select('id', 'nome')->find($filialId) : null,
            'total_alunos' => $totalAlunos,
            'planos_ativos' => $planosAtivos,
            'faturamento' => $totalAlunos * $valorMensalidade,
            'personals' => Personal::where('academia_id', $academia->id)->count(),
            'por_filial' => $porFilial,
        ]);
    }

    // PUT /api/v1/academia/perfil
    public function atualizarPerfil(Request $request)
    {
        $academia = $this->academiaAutenticada($request);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cidade' => 'required|string|max:200',
            'valor_mensalidade' => 'nullable|numeric|min:0',
            'descricao' => 'nullable|string|max:500',
            'chave_pix' => 'nullable|string|max:255',
        ]);

        $academia->update($dados);

        return response()->json(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
    }

    // POST /api/v1/academia/infraestrutura
    public function atualizarInfraestrutura(Request $request)
    {
        $academia = $this->academiaAutenticada($request);

        $request->validate(['infraestrutura' => 'nullable|string|max:2000']);
        $academia->update(['infraestrutura' => $request->infraestrutura]);

        return response()->json(['success' => true, 'message' => 'Infraestrutura atualizada!']);
    }

    // ===================== ALUNOS =====================

    // GET /api/v1/academia/alunos
    public function alunos(Request $request)
    {
        $academia = $this->academiaAutenticada($request);
        $filialId = $this->filialDoToken($request);

        $alunos = $this->clientesVisiveis($academia->id, $filialId)
            ->with('filial:id,nome')
            ->orderBy('nome')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'nome' => $a->nome,
                'email' => $a->email,
                'whatsapp' => $a->whatsapp,
                'plano' => $a->plano,
                'plano_ativo' => (bool) $a->plano_ativo,
                'filial' => $a->filial?->nome,
            ]);

        return response()->json(['alunos' => $alunos]);
    }

    // POST /api/v1/academia/alunos — cadastro de aluno pela academia
    public function criarAluno(Request $request)
    {
        $academia = $this->academiaAutenticada($request);
        $filialId = $this->filialDoToken($request);

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clientes,email',
            'whatsapp' => 'nullable|string|max:20',
            'idade' => 'nullable|date|before:today',
            'sexo' => 'required|in:masculino,feminino,outro',
            'altura' => 'nullable|numeric|min:0|max:300',
            'peso' => 'nullable|numeric|min:0|max:600',
            'plano' => 'nullable|string|max:255',
            'resumo_objetivo' => 'nullable|string|max:1000',
            'condicao_clinica' => 'nullable|string|max:1000',
        ], [
            'email.unique' => 'Já existe um aluno cadastrado com este e-mail.',
        ]);

        $dados['academia_id'] = $academia->id;
        $dados['senha'] = Hash::make(self::SENHA_PADRAO_ALUNO);
        $dados['plano_ativo'] = $request->filled('plano');

        if ($filialId !== null) {
            $dados['filial_id'] = $filialId;
        } else {
            $escolhida = $request->input('filial_id');
            $dados['filial_id'] = ($escolhida && Filial::where('id', $escolhida)->where('academia_id', $academia->id)->exists())
                ? (int) $escolhida
                : null;
        }

        $cliente = Cliente::create($dados);

        return response()->json([
            'success' => true,
            'message' => 'Aluno cadastrado! Senha padrão: ' . self::SENHA_PADRAO_ALUNO . ' — o aluno troca no primeiro acesso.',
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome],
        ], 201);
    }

    // GET /api/v1/academia/alunos/{clienteId}/anamnese
    public function anamnese(Request $request, $clienteId)
    {
        $academia = $this->academiaAutenticada($request);
        $cliente = $this->alunoAcessivel($request, $academia->id, $clienteId);
        if (! $cliente) {
            return response()->json(['error' => 'Aluno não encontrado.'], 404);
        }

        return response()->json([
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome],
            'anamnese' => Anamnese::where('cliente_id', $cliente->id)->first(),
        ]);
    }

    // POST /api/v1/academia/alunos/{clienteId}/anamnese
    public function salvarAnamnese(Request $request, $clienteId)
    {
        $academia = $this->academiaAutenticada($request);
        $cliente = $this->alunoAcessivel($request, $academia->id, $clienteId);
        if (! $cliente) {
            return response()->json(['error' => 'Aluno não encontrado.'], 404);
        }

        $request->validate([
            'objetivo_principal' => 'nullable|string|max:255',
            'nivel_atividade' => 'nullable|in:sedentario,leve,moderado,intenso',
            'historico_lesoes' => 'nullable|string',
            'restricoes_medicas' => 'nullable|string',
            'doencas_preexistentes' => 'nullable|string',
            'medicamentos' => 'nullable|string',
            'cirurgias' => 'nullable|string',
            'parq_observacoes' => 'nullable|string',
            'observacoes' => 'nullable|string',
        ]);

        $anamnese = Anamnese::updateOrCreate(
            ['cliente_id' => $cliente->id],
            [
                'objetivo_principal' => $request->objetivo_principal,
                'nivel_atividade' => $request->nivel_atividade,
                'historico_lesoes' => $request->historico_lesoes,
                'restricoes_medicas' => $request->restricoes_medicas,
                'doencas_preexistentes' => $request->doencas_preexistentes,
                'medicamentos' => $request->medicamentos,
                'cirurgias' => $request->cirurgias,
                'parq_1' => $request->boolean('parq_1'),
                'parq_2' => $request->boolean('parq_2'),
                'parq_3' => $request->boolean('parq_3'),
                'parq_4' => $request->boolean('parq_4'),
                'parq_5' => $request->boolean('parq_5'),
                'parq_6' => $request->boolean('parq_6'),
                'parq_7' => $request->boolean('parq_7'),
                'parq_observacoes' => $request->parq_observacoes,
                'observacoes' => $request->observacoes,
                'preenchida_em' => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => "Anamnese de {$cliente->nome} salva com sucesso! 💪", 'anamnese' => $anamnese]);
    }

    // ===================== PLANOS =====================

    // GET /api/v1/academia/planos
    public function planos(Request $request)
    {
        $academia = $this->academiaAutenticada($request);

        return response()->json([
            'planos' => Plano::where('academia_id', $academia->id)->orderBy('valor')->get()
                ->map(fn ($p) => $this->planoJson($p)),
        ]);
    }

    // POST /api/v1/academia/planos
    public function criarPlano(Request $request)
    {
        $academia = $this->academiaAutenticada($request);

        $request->validate([
            'nome' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'duracao_meses' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:500',
        ]);

        if (Plano::where('academia_id', $academia->id)->count() >= 5) {
            return response()->json(['error' => 'Limite de 5 planos atingido. Exclua um plano antes de adicionar outro.'], 422);
        }

        $plano = Plano::create([
            'academia_id' => $academia->id,
            'nome' => $request->nome,
            'valor' => $request->valor,
            'duracao_meses' => $request->duracao_meses,
            'descricao' => $request->descricao,
            'ativo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Plano criado com sucesso!', 'plano' => $this->planoJson($plano)], 201);
    }

    // PUT /api/v1/academia/planos/{id}
    public function atualizarPlano(Request $request, $id)
    {
        $academia = $this->academiaAutenticada($request);

        $plano = Plano::where('id', $id)->where('academia_id', $academia->id)->firstOrFail();

        $request->validate([
            'nome' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'duracao_meses' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:500',
            'ativo' => 'nullable|boolean',
        ]);

        $plano->update([
            'nome' => $request->nome,
            'valor' => $request->valor,
            'duracao_meses' => $request->duracao_meses,
            'descricao' => $request->descricao,
            'ativo' => $request->boolean('ativo', true),
        ]);

        return response()->json(['success' => true, 'message' => 'Plano atualizado com sucesso!', 'plano' => $this->planoJson($plano->fresh())]);
    }

    // DELETE /api/v1/academia/planos/{id}
    public function excluirPlano(Request $request, $id)
    {
        $academia = $this->academiaAutenticada($request);

        Plano::where('id', $id)->where('academia_id', $academia->id)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Plano removido com sucesso!']);
    }

    // ===================== FILIAIS =====================

    // GET /api/v1/academia/filiais
    public function filiais(Request $request)
    {
        $academia = $this->academiaAutenticada($request);
        if ($this->filialDoToken($request) !== null) {
            return response()->json(['error' => 'Apenas a conta principal gerencia as filiais.'], 403);
        }

        $filiais = Filial::where('academia_id', $academia->id)
            ->withCount('clientes')
            ->orderBy('nome')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'nome' => $f->nome,
                'cidade' => $f->cidade,
                'estado' => $f->estado,
                'telefone' => $f->telefone,
                'alunos' => $f->clientes_count,
            ]);

        return response()->json(['filiais' => $filiais]);
    }

    // POST /api/v1/academia/filiais
    public function criarFilial(Request $request)
    {
        $academia = $this->academiaAutenticada($request);
        if ($this->filialDoToken($request) !== null) {
            return response()->json(['error' => 'Apenas a conta principal pode criar filiais.'], 403);
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'senha' => 'required|string|min:6|max:255',
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:300',
            'bairro' => 'required|string|max:200',
            'cidade' => 'required|string|max:200',
            'estado' => 'required|string|max:100',
            'complemento' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:20',
        ], [
            'senha.required' => 'Defina uma senha para a subconta desta filial.',
            'senha.min' => 'A senha da subconta deve ter ao menos 6 caracteres.',
        ]);

        Filial::create(array_merge(
            ['academia_id' => $academia->id, 'senha' => Hash::make($request->senha)],
            $request->only(['nome', 'cep', 'rua', 'bairro', 'cidade', 'estado', 'complemento', 'telefone'])
        ));

        return response()->json([
            'success' => true,
            'message' => 'Filial criada! A subconta loga com o mesmo e-mail/CNPJ da academia e a senha que você definiu.',
        ], 201);
    }

    // DELETE /api/v1/academia/filiais/{id}
    public function excluirFilial(Request $request, $id)
    {
        $academia = $this->academiaAutenticada($request);
        if ($this->filialDoToken($request) !== null) {
            return response()->json(['error' => 'Apenas a conta principal pode remover filiais.'], 403);
        }

        Filial::where('id', $id)->where('academia_id', $academia->id)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Filial removida com sucesso!']);
    }

    // ===================== GESTÃO: PROFESSORES E AULAS =====================

    // GET /api/v1/academia/gestao
    public function gestao(Request $request)
    {
        $academia = $this->academiaAutenticada($request);

        return response()->json([
            'infraestrutura' => $academia->infraestrutura,
            'professores' => AcademiaProfessor::where('academia_id', $academia->id)->orderBy('nome')->get()
                ->map(fn ($p) => ['id' => $p->id, 'nome' => $p->nome, 'resumo' => $p->resumo, 'ativo' => (bool) $p->ativo]),
            'aulas' => AcademiaAula::with('professor:id,nome')->where('academia_id', $academia->id)->orderBy('nome')->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'nome' => $a->nome,
                    'resumo' => $a->resumo,
                    'professor' => $a->professor?->nome,
                    'professor_id' => $a->professor_id,
                    'dia_semana' => $a->dia_semana,
                    'hora_inicio' => $a->hora_inicio,
                    'duracao_min' => $a->duracao_min,
                    'ativo' => (bool) $a->ativo,
                ]),
        ]);
    }

    // POST /api/v1/academia/professores
    public function criarProfessor(Request $request)
    {
        $academia = $this->academiaAutenticada($request);

        $request->validate([
            'nome' => 'required|string|max:120',
            'resumo' => 'nullable|string|max:2000',
        ]);

        AcademiaProfessor::create([
            'academia_id' => $academia->id,
            'nome' => $request->nome,
            'resumo' => $request->resumo,
            'ativo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Profissional cadastrado!'], 201);
    }

    // DELETE /api/v1/academia/professores/{id}
    public function excluirProfessor(Request $request, $id)
    {
        $academia = $this->academiaAutenticada($request);

        AcademiaProfessor::where('academia_id', $academia->id)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Profissional removido!']);
    }

    // POST /api/v1/academia/aulas
    public function criarAula(Request $request)
    {
        $academia = $this->academiaAutenticada($request);

        $request->validate([
            'nome' => 'required|string|max:120',
            'resumo' => 'nullable|string|max:2000',
            'professor_id' => 'nullable|integer',
            'dia_semana' => 'nullable|integer|min:0|max:6',
            'hora_inicio' => 'nullable|date_format:H:i',
            'duracao_min' => 'nullable|integer|min:5|max:600',
        ]);

        $professorId = null;
        if ($request->filled('professor_id')) {
            $professorId = AcademiaProfessor::where('academia_id', $academia->id)->find($request->professor_id)?->id;
        }

        AcademiaAula::create([
            'academia_id' => $academia->id,
            'nome' => $request->nome,
            'resumo' => $request->resumo,
            'professor_id' => $professorId,
            'dia_semana' => $request->dia_semana,
            'hora_inicio' => $request->hora_inicio,
            'duracao_min' => $request->duracao_min,
            'ativo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Aula adicionada!'], 201);
    }

    // DELETE /api/v1/academia/aulas/{id}
    public function excluirAula(Request $request, $id)
    {
        $academia = $this->academiaAutenticada($request);

        AcademiaAula::where('academia_id', $academia->id)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Aula removida!']);
    }

    // ===================== SOLICITAÇÕES DE PERSONAIS =====================

    // GET /api/v1/academia/solicitacoes
    public function solicitacoes(Request $request)
    {
        $academia = $this->academiaAutenticada($request);

        $pendentes = $academia->solicitacoesPendentes()->orderByPivot('solicitado_em', 'desc')->get();
        $aprovados = $academia->personaisAprovados()->orderBy('nome')->get();

        $json = fn ($p) => [
            'id' => $p->id,
            'nome' => $p->nome,
            'cref' => $p->cref,
            'cidade' => $p->cidade,
            'solicitado_em' => $p->pivot->solicitado_em,
        ];

        return response()->json([
            'pendentes' => $pendentes->map($json),
            'aprovados' => $aprovados->map($json),
        ]);
    }

    // POST /api/v1/academia/solicitacoes/{personalId}/aprovar | rejeitar
    public function responderSolicitacao(Request $request, $personalId, $acao)
    {
        $academia = $this->academiaAutenticada($request);

        if (! in_array($acao, ['aprovar', 'rejeitar'], true)) {
            return response()->json(['error' => 'Ação inválida.'], 422);
        }

        $vinculo = $academia->personais()->where('personals.id', $personalId)->first();
        if (! $vinculo || $vinculo->pivot->status !== 'pendente') {
            return response()->json(['error' => 'Solicitação não encontrada ou já respondida.'], 404);
        }

        $academia->personais()->updateExistingPivot($personalId, [
            'status' => $acao === 'aprovar' ? 'aprovado' : 'rejeitado',
            'respondido_em' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $acao === 'aprovar'
                ? 'Personal aprovado! Agora ele aparece na sua página pública.'
                : 'Solicitação rejeitada.',
        ]);
    }

    // ===================== HELPERS =====================

    private function clientesVisiveis(int $academiaId, ?int $filialId)
    {
        $query = Cliente::where('academia_id', $academiaId);
        if ($filialId !== null) {
            $query->where('filial_id', $filialId);
        }
        return $query;
    }

    private function alunoAcessivel(Request $request, int $academiaId, $clienteId): ?Cliente
    {
        return $this->clientesVisiveis($academiaId, $this->filialDoToken($request))
            ->where('id', $clienteId)
            ->first();
    }

    private function planoJson(Plano $p): array
    {
        return [
            'id' => $p->id,
            'nome' => $p->nome,
            'valor' => (float) $p->valor,
            'duracao_meses' => $p->duracao_meses,
            'descricao' => $p->descricao,
            'ativo' => (bool) $p->ativo,
        ];
    }
}
