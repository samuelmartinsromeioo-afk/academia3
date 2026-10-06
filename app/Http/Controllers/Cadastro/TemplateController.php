<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Concerns\VinculoComAluno;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTemplate;
use App\Models\Cadastro\FichaTreino;
use App\Support\CatalogoExercicios;
use App\Support\VideosExercicios;
use Illuminate\Http\Request;

/**
 * Templates de ficha do personal: criar/clonar modelos e aplicá-los a alunos.
 */
class TemplateController extends Controller
{
    use VinculoComAluno;

    // Lista de templates + alunos (para aplicar)
    public function index()
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $templates = FichaTemplate::where('personal_id', $personalId)->orderByDesc('created_at')->get();
        $alunos = $this->alunosDoPersonal($personalId);

        // Só os exercícios que TÊM vídeo: o seletor existe para escolher o vídeo,
        // e listar os sem vídeo seria oferecer opção que não faz nada.
        $videosCatalogo = collect(CatalogoExercicios::todos())
            ->filter(fn ($e) => ! empty($e['video']))
            ->sortBy([['grupo', 'asc'], ['nome', 'asc']])
            ->groupBy('grupo');

        return view('personal.Templates', compact('templates', 'alunos', 'videosCatalogo'));
    }

    public function criar(Request $request)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'nome' => 'required|string|max:255',
            'nivel' => FichaTreino::regraNivel(false),
        ]);

        FichaTemplate::create([
            'personal_id' => $personalId,
            'nome' => $request->nome,
            'nivel' => $request->nivel ?? 'iniciante',
            'exercicios' => [],
        ]);

        return redirect()->route('templates.index')->with('success', 'Template criado! Adicione os exercícios.');
    }

    public function adicionarExercicio(Request $request, $id)
    {
        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
            'video_catalogo' => 'nullable|string|max:255',
        ]);

        $exs = $t->exercicios ?? [];
        $exs[] = [
            'nome' => $request->nome_exercicio,
            'series' => (int) $request->series,
            'repeticoes' => (int) $request->repeticoes,
            'peso' => ($request->peso === null || $request->peso === '') ? null : (float) $request->peso,
            'observacoes' => $request->observacoes,
            'video' => $this->videoDoCatalogo($request->input('video_catalogo')),
        ];
        $t->update(['exercicios' => $exs]);

        return back()->with('success', 'Exercício adicionado ao template!');
    }

    /**
     * Troca (ou limpa) o vídeo de um exercício que já está no template.
     *
     * Sem isto só havia o vídeo escolhido na hora de adicionar: corrigir exigia
     * apagar o exercício e recriar, perdendo séries/reps/peso/observação.
     *
     * Valor vazio volta ao automático — o casamento por nome de
     * `videoResolvido()` — em vez de deixar o exercício sem vídeo.
     */
    public function trocarVideoExercicio(Request $request, $id, $index)
    {
        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'video_catalogo' => 'nullable|string|max:255',
        ]);

        $exs = $t->exercicios ?? [];
        $index = (int) $index;

        if (! isset($exs[$index])) {
            return back()->with('error', 'Exercício não encontrado neste template.');
        }

        $escolhido = $this->videoDoCatalogo($request->input('video_catalogo'));

        // Recusa em silêncio seria pior: o personal escolheu algo da lista e
        // veria o vídeo sumir sem explicação.
        if ($request->filled('video_catalogo') && $escolhido === null) {
            return back()->with('error', 'Esse vídeo não está no catálogo SNR.');
        }

        $exs[$index]['video'] = $escolhido;
        $t->update(['exercicios' => array_values($exs)]);

        return back()->with('success', $escolhido
            ? 'Vídeo atualizado: '.($exs[$index]['nome'] ?? 'exercício').'.'
            : 'Vídeo voltou ao automático (casa pelo nome do exercício).');
    }

    public function deletarExercicio($id, $index)
    {
        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }
        $exs = $t->exercicios ?? [];
        if (isset($exs[$index])) {
            array_splice($exs, (int) $index, 1);
            $t->update(['exercicios' => array_values($exs)]);
        }

        return back()->with('success', 'Exercício removido.');
    }

    public function deletar($id)
    {
        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }
        $t->delete();

        return redirect()->route('templates.index')->with('success', 'Template excluído.');
    }

    // Cria um template a partir de uma ficha existente (snapshot)
    public function salvarDeFicha($fichaId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::with('exercicios')->findOrFail($fichaId);
        if ($ficha->personal_id != $personalId) {
            return back()->with('error', 'Acesso negado!');
        }

        // O vídeo vai junto, mas só se for do catálogo — ver
        // VideosExercicios::ehDoCatalogo(). Vídeo do catálogo sobrevive à
        // exclusão da ficha de origem; upload do personal, não.
        //
        // Exercício sem vídeo de catálogo fica com null e continua exibindo pelo
        // casamento por nome em `videoResolvido()`, como já era.
        $exs = $ficha->exercicios->map(fn ($e) => [
            'nome' => $e->nome_exercicio,
            'series' => $e->series,
            'repeticoes' => $e->repeticoes,
            'peso' => $e->peso !== null ? (float) $e->peso : null,
            'observacoes' => $e->observacoes,
            'video' => $this->videoDoCatalogo($e->video),
        ])->values()->all();

        FichaTemplate::create([
            'personal_id' => $personalId,
            'nome' => $ficha->nome_treino,
            'nivel' => $ficha->nivel ?? 'iniciante',
            'exercicios' => $exs,
        ]);

        return back()->with('success', 'Ficha salva como template! 📋');
    }

    // Aplica um template a um aluno (cria a ficha + exercícios)
    public function aplicar(Request $request, $id)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $t = $this->meuTemplate($id);
        if (! $t) {
            return back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dia_semana' => 'required|integer|min:0|max:6',
            'nome_treino' => 'nullable|string|max:255',
        ]);

        $clienteId = $request->cliente_id;
        if (! $this->podeVer($personalId, $clienteId)) {
            return back()->with('error', 'Acesso negado a este aluno!');
        }

        $existe = FichaTreino::where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->exists();
        if ($existe) {
            return back()->with('error', 'Já existe uma ficha ativa para esse dia da semana.');
        }

        $ficha = FichaTreino::create([
            'personal_id' => $personalId,
            'cliente_id' => $clienteId,
            'dia_semana' => $request->dia_semana,
            'nome_treino' => $request->nome_treino ?: $t->nome,
            'ativo' => true,
            'nivel' => $t->nivel ?? 'iniciante',
        ]);

        foreach (($t->exercicios ?? []) as $ordem => $ex) {
            ExercicioFicha::create([
                'ficha_id' => $ficha->id,
                'nome_exercicio' => $ex['nome'] ?? 'Exercício',
                'series' => $ex['series'] ?? 3,
                'repeticoes' => $ex['repeticoes'] ?? 10,
                'peso' => $ex['peso'] ?? null,
                'observacoes' => $ex['observacoes'] ?? null,
                // Revalidado em vez de copiado cru: o JSON do template pode ser
                // antigo e apontar para vídeo que saiu do catálogo.
                'video' => $this->videoDoCatalogo($ex['video'] ?? null),
                'ordem' => $ordem,
            ]);
        }

        return redirect()->route('fichas-treino.aluno', $clienteId)
            ->with('success', 'Template aplicado! Ficha criada para o aluno. 📋');
    }

    // ───── helpers ─────

    private function meuTemplate($id): ?FichaTemplate
    {
        $t = FichaTemplate::find($id);
        return ($t && $t->personal_id == session('personal_id')) ? $t : null;
    }

    /** Devolve o caminho só se for vídeo do catálogo SNR; senão, null. */
    private function videoDoCatalogo(?string $caminho): ?string
    {
        return VideosExercicios::ehDoCatalogo($caminho) ? $caminho : null;
    }

    private function alunosDoPersonal($personalId)
    {
        $ids = FichaTreino::where('personal_id', $personalId)->pluck('cliente_id')
            ->merge(Agenda::where('personal_id', $personalId)->where('cancelado', false)->whereNotNull('cliente_id')->pluck('cliente_id'))
            ->unique();

        return Cliente::whereIn('id', $ids)->orderBy('nome')->get();
    }

}
