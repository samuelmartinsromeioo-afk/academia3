<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Concerns\EscopoAcademia;
use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Services\Celebracoes;
use App\Services\EstatisticasTreino;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FichaTreinoController extends Controller
{
    use EscopoAcademia;

    // Regra de validação do vídeo demonstrativo (limite de 15s é validado no navegador).
    private array $videoRules = [
        'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm,video/3gpp,video/x-msvideo|max:51200',
        'video_catalogo' => 'nullable|string|max:255',
    ];

    // Salva o vídeo enviado (se houver) e retorna o caminho; senão, null.
    private function uploadVideoExercicio(Request $request): ?string
    {
        if ($request->hasFile('video')) {
            return $request->file('video')->store('fichas/videos', 'public');
        }

        return null;
    }

    // Resolve o vídeo do exercício: prioriza o upload manual; caso não haja,
    // usa o vídeo do catálogo (biblioteca SNR) informado em "video_catalogo".
    // Só aceita caminhos dentro de "exercicios/" que existam de fato no disco público,
    // evitando que o cliente injete um caminho arbitrário.
    private function resolverVideoExercicio(Request $request): ?string
    {
        $upload = $this->uploadVideoExercicio($request);
        if ($upload) {
            return $upload;
        }

        $catalogo = $request->input('video_catalogo');
        if (is_string($catalogo)
            && str_starts_with($catalogo, 'exercicios/')
            && ! str_contains($catalogo, '..')
            && Storage::disk('public')->exists($catalogo)) {
            return $catalogo;
        }

        return null;
    }

    // Remove do disco apenas vídeos ENVIADOS pelo usuário (pasta fichas/videos).
    // Vídeos do catálogo (exercicios/) são compartilhados entre fichas e nunca são apagados.
    private function apagarVideoDoExercicio(?string $caminho): void
    {
        if ($caminho && str_starts_with($caminho, 'fichas/videos/')) {
            Storage::disk('public')->delete($caminho);
        }
    }

    // ✅ PERSONAL: Ver seus alunos com pacote
    public function meusAlunos()
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        // Alunos com pacote ativo (frequencia_pacote preenchido)
        $alunos = Agenda::with('cliente')
            ->where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('frequencia_pacote', '!=', null)
            ->get()
            ->unique('cliente_id')
            ->values();

        return view('personal.PersonalFichasTreinoAlunos', compact('alunos'));
    }

    // ✅ PERSONAL: Ver fichas de um aluno específico
    public function fichasDoAluno($clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $fichas = FichaTreino::with('exercicios')
            ->where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        $exerciciosData = $this->getExerciciosData();

        return view('personal.PersonalFichasTreinoLista', compact('cliente', 'fichas', 'exerciciosData'));
    }

    // ✅ PERSONAL: Criar nova ficha
    public function criarFicha(Request $request)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dia_semana' => 'required|integer|min:0|max:6',
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
            'nivel' => 'required|in:iniciante,avancado',
            'divisao' => 'nullable|string|max:100',
        ]);

        // ✅ Verificar se já existe ficha para este dia
        $jáExiste = FichaTreino::where('personal_id', $personalId)
            ->where('cliente_id', $request->cliente_id)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->exists();

        if ($jáExiste) {
            return redirect()->route('fichas-treino.aluno', $request->cliente_id)
                ->with('error', 'Já existe uma ficha para este dia da semana!');
        }

        FichaTreino::create([
            'personal_id' => $personalId,
            'cliente_id' => $request->cliente_id,
            'dia_semana' => $request->dia_semana,
            'nome_treino' => $request->nome_treino,
            'observacoes' => $request->observacoes,
            'ativo' => true,
            'nivel' => $request->nivel,
            'divisao' => $request->nivel === 'avancado' ? $request->divisao : null,
        ]);

        return redirect()->route('fichas-treino.aluno', $request->cliente_id)
            ->with('success', 'Ficha criada com sucesso!');
    }

    // ✅ PERSONAL: Adicionar exercício à ficha
    public function adicionarExercicio(Request $request, $fichaId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::findOrFail($fichaId);

        // Verificar se personal é o dono
        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate(array_merge([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ], $this->videoRules));

        // Encontrar maior ordem para adicionar ao final
        $ultimaOrdem = ExercicioFicha::where('ficha_id', $fichaId)->max('ordem') ?? 0;

        ExercicioFicha::create([
            'ficha_id' => $fichaId,
            'nome_exercicio' => $request->nome_exercicio,
            'series' => $request->series,
            'repeticoes' => $request->repeticoes,
            'peso' => $request->peso,
            'observacoes' => $request->observacoes,
            'video' => $this->resolverVideoExercicio($request),
            'ordem' => $ultimaOrdem + 1,
        ]);

        return redirect()->route('fichas-treino.aluno', $ficha->cliente_id)
            ->with('success', 'Exercício adicionado!');
    }

    // ✅ PERSONAL: Editar exercício
    public function editarExercicio(Request $request, $exercicioId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $exercicio = ExercicioFicha::findOrFail($exercicioId);
        $ficha = $exercicio->ficha;

        if (! $ficha || $ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate(array_merge([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ], $this->videoRules));

        $dados = [
            'nome_exercicio' => $request->nome_exercicio,
            'series' => $request->series,
            'repeticoes' => $request->repeticoes,
            'peso' => $request->peso,
            'observacoes' => $request->observacoes,
        ];

        if ($request->hasFile('video')) {
            $this->apagarVideoDoExercicio($exercicio->video);
            $dados['video'] = $this->uploadVideoExercicio($request);
        } elseif ($request->boolean('remover_video') && $exercicio->video) {
            $this->apagarVideoDoExercicio($exercicio->video);
            $dados['video'] = null;
        }

        $exercicio->update($dados);

        return redirect()->route('fichas-treino.aluno', $ficha->cliente_id)
            ->with('success', 'Exercício atualizado!');
    }

    // ✅ PERSONAL: Editar ficha
    public function editarFicha(Request $request, $fichaId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::findOrFail($fichaId);

        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
        ]);

        $ficha->update([
            'nome_treino' => $request->nome_treino,
            'observacoes' => $request->observacoes,
        ]);

        return redirect()->route('fichas-treino.aluno', $ficha->cliente_id)
            ->with('success', 'Ficha atualizada!');
    }

    // ✅ PERSONAL: Deletar exercício
    public function deletarExercicio($exercicioId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $exercicio = ExercicioFicha::findOrFail($exercicioId);
        $ficha = $exercicio->ficha;

        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $clienteId = $ficha->cliente_id;
        $exercicio->delete();

        return redirect()->route('fichas-treino.aluno', $clienteId)
            ->with('success', 'Exercício removido!');
    }

    // ✅ PERSONAL: Deletar ficha inteira
    public function deletarFicha($fichaId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::findOrFail($fichaId);

        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $clienteId = $ficha->cliente_id;
        $ficha->delete();

        return redirect()->route('fichas-treino.aluno', $clienteId)
            ->with('success', 'Ficha deletada!');
    }

    // ==========================================
    // ACADEMIA: fichas de treino para seus alunos
    // ==========================================

    // ✅ ACADEMIA: Ver/gerenciar fichas de um aluno
    public function fichasDoAlunoAcademia($clienteId)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return redirect()->route('login.index');
        }

        $cliente = $this->clienteAcessivel($clienteId);
        if (! $cliente) {
            return redirect()->route('academia.alunos')->with('error', 'Aluno não encontrado.');
        }

        $fichas = FichaTreino::with('exercicios')
            ->where('academia_id', $academiaId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        $exerciciosData = $this->getExerciciosData();

        return view('academia.aluno-fichas', compact('cliente', 'fichas', 'exerciciosData'));
    }

    // ✅ ACADEMIA: Criar nova ficha
    public function criarFichaAcademia(Request $request)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return redirect()->route('login.index');
        }

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dia_semana' => 'required|integer|min:0|max:6',
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
            'nivel' => 'required|in:iniciante,avancado',
            'divisao' => 'nullable|string|max:100',
        ]);

        // O aluno precisa pertencer a esta academia (e à filial, se for subconta).
        if (! $this->clienteAcessivel($request->cliente_id)) {
            return redirect()->route('academia.alunos')->with('error', 'Aluno não encontrado.');
        }

        $jaExiste = FichaTreino::where('academia_id', $academiaId)
            ->where('cliente_id', $request->cliente_id)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->exists();

        if ($jaExiste) {
            return redirect()->route('academia.aluno-fichas', $request->cliente_id)
                ->with('error', 'Já existe uma ficha para este dia da semana!');
        }

        FichaTreino::create([
            'academia_id' => $academiaId,
            'cliente_id' => $request->cliente_id,
            'dia_semana' => $request->dia_semana,
            'nome_treino' => $request->nome_treino,
            'observacoes' => $request->observacoes,
            'ativo' => true,
            'nivel' => $request->nivel,
            'divisao' => $request->nivel === 'avancado' ? $request->divisao : null,
        ]);

        return redirect()->route('academia.aluno-fichas', $request->cliente_id)
            ->with('success', 'Ficha criada com sucesso!');
    }

    // ✅ ACADEMIA: Adicionar exercício à ficha
    public function adicionarExercicioAcademia(Request $request, $fichaId)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::findOrFail($fichaId);
        if ($ficha->academia_id != $academiaId || ! $this->clienteAcessivel($ficha->cliente_id)) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate(array_merge([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ], $this->videoRules));

        $ultimaOrdem = ExercicioFicha::where('ficha_id', $fichaId)->max('ordem') ?? 0;

        ExercicioFicha::create([
            'ficha_id' => $fichaId,
            'nome_exercicio' => $request->nome_exercicio,
            'series' => $request->series,
            'repeticoes' => $request->repeticoes,
            'peso' => $request->peso,
            'observacoes' => $request->observacoes,
            'video' => $this->resolverVideoExercicio($request),
            'ordem' => $ultimaOrdem + 1,
        ]);

        return redirect()->route('academia.aluno-fichas', $ficha->cliente_id)
            ->with('success', 'Exercício adicionado!');
    }

    // ✅ ACADEMIA: Editar exercício
    public function editarExercicioAcademia(Request $request, $exercicioId)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return redirect()->route('login.index');
        }

        $exercicio = ExercicioFicha::findOrFail($exercicioId);
        $ficha = $exercicio->ficha;

        if (! $ficha || $ficha->academia_id != $academiaId || ! $this->clienteAcessivel($ficha->cliente_id)) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate(array_merge([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ], $this->videoRules));

        $dados = [
            'nome_exercicio' => $request->nome_exercicio,
            'series' => $request->series,
            'repeticoes' => $request->repeticoes,
            'peso' => $request->peso,
            'observacoes' => $request->observacoes,
        ];

        if ($request->hasFile('video')) {
            $this->apagarVideoDoExercicio($exercicio->video);
            $dados['video'] = $this->uploadVideoExercicio($request);
        } elseif ($request->boolean('remover_video') && $exercicio->video) {
            $this->apagarVideoDoExercicio($exercicio->video);
            $dados['video'] = null;
        }

        $exercicio->update($dados);

        return redirect()->route('academia.aluno-fichas', $ficha->cliente_id)
            ->with('success', 'Exercício atualizado!');
    }

    // ✅ ACADEMIA: Deletar exercício
    public function deletarExercicioAcademia($exercicioId)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return redirect()->route('login.index');
        }

        $exercicio = ExercicioFicha::findOrFail($exercicioId);
        $ficha = $exercicio->ficha;

        if (! $ficha || $ficha->academia_id != $academiaId || ! $this->clienteAcessivel($ficha->cliente_id)) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $clienteId = $ficha->cliente_id;
        $exercicio->delete();

        return redirect()->route('academia.aluno-fichas', $clienteId)
            ->with('success', 'Exercício removido!');
    }

    // ✅ ACADEMIA: Deletar ficha inteira
    public function deletarFichaAcademia($fichaId)
    {
        $academiaId = session('academia_id');
        if (! $academiaId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::findOrFail($fichaId);
        if ($ficha->academia_id != $academiaId || ! $this->clienteAcessivel($ficha->cliente_id)) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $clienteId = $ficha->cliente_id;
        $ficha->delete();

        return redirect()->route('academia.aluno-fichas', $clienteId)
            ->with('success', 'Ficha deletada!');
    }

    // ✅ CLIENTE: Ver suas fichas de treino
    public function minhasFichas()
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);

        // Buscar todos os personals que têm pacote com este cliente
        $personalsComPacote = Agenda::where('cliente_id', $clienteId)
            ->where('cancelado', false)
            ->where('frequencia_pacote', '!=', null)
            ->pluck('personal_id')
            ->unique();

        // Buscar fichas de treino para estes personals
        $fichas = FichaTreino::with('exercicios', 'personal')
            ->where('cliente_id', $clienteId)
            ->whereIn('personal_id', $personalsComPacote)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        // Agrupar por personal
        $fichasPorPersonal = $fichas->groupBy('personal_id');

        // Fichas criadas pela academia (não dependem de pacote com personal)
        $fichasAcademia = FichaTreino::with('exercicios', 'academia')
            ->where('cliente_id', $clienteId)
            ->whereNotNull('academia_id')
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get()
            ->groupBy('academia_id');

        return view('cliente.MinhasFichasTreino', compact('cliente', 'fichasPorPersonal', 'fichasAcademia'));
    }

    // ✅ CLIENTE: Modo executar treino (guiado, com timer de descanso)
    public function executar($fichaId)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::with('exercicios')->findOrFail($fichaId);
        if ($ficha->cliente_id != $clienteId) {
            return redirect()->route('fichas-treino.minhas')->with('error', 'Acesso negado!');
        }

        return view('cliente.ExecutarTreino', compact('ficha'));
    }

    // ✅ CLIENTE: Marcar treino como concluído
    public function marcarConcluido(Request $request, $fichaId)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::with('exercicios')->findOrFail($fichaId);

        if ($ficha->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'data_treino' => 'required|date',
            'observacoes' => 'nullable|string',
            'rpe' => 'nullable|integer|min:1|max:10',
            'sensacao' => 'nullable|in:otimo,bem,cansado,exausto,dor',
            'registros' => 'nullable|array',
            'registros.*.peso' => 'nullable|numeric|min:0|max:9999',
            'registros.*.repeticoes' => 'nullable|integer|min:0|max:9999',
            'registros.*.series' => 'nullable|integer|min:0|max:999',
        ]);

        $data = $request->data_treino;

        // Buscar ou criar registro do treino
        $treino = TreinoConcluido::firstOrCreate(
            [
                'ficha_id' => $fichaId,
                'cliente_id' => $clienteId,
                'data_treino' => $data,
            ],
            [
                'concluido' => true,
                'observacoes' => $request->observacoes,
                'rpe' => $request->rpe,
                'sensacao' => $request->sensacao,
            ]
        );

        // Se já existia, apenas atualiza
        if (! $treino->wasRecentlyCreated) {
            $treino->update([
                'concluido' => true,
                'observacoes' => $request->observacoes,
                'rpe' => $request->rpe,
                'sensacao' => $request->sensacao,
            ]);
        }

        // ✅ FEATURE 1: histórico de carga executada por exercício (+ detecção de recordes)
        $registros = $request->input('registros', []);
        $recordes = [];
        $topCarga = null; // maior aumento de carga deste treino (p/ celebração)
        foreach ($ficha->exercicios as $exercicio) {
            $dados = $registros[$exercicio->id] ?? [];

            // Sem informe do aluno → assume a prescrição da ficha.
            $peso       = array_key_exists('peso', $dados) ? $dados['peso'] : $exercicio->peso;
            $repeticoes = array_key_exists('repeticoes', $dados) ? $dados['repeticoes'] : $exercicio->repeticoes;
            $series     = array_key_exists('series', $dados) ? $dados['series'] : $exercicio->series;

            $pesoFinal = ($peso === '' || $peso === null) ? null : (float) $peso;

            // Recorde: superou a maior carga já registrada deste exercício?
            if ($pesoFinal !== null) {
                $maxAnterior = RegistroExercicio::where('cliente_id', $clienteId)
                    ->where('nome_exercicio', $exercicio->nome_exercicio)
                    ->where('treino_concluido_id', '!=', $treino->id)
                    ->max('peso');

                if ($maxAnterior !== null && $pesoFinal > (float) $maxAnterior) {
                    $recordes[] = $exercicio->nome_exercicio . ' — '
                        . rtrim(rtrim(number_format($pesoFinal, 2, ',', '.'), '0'), ',') . ' kg';

                    $aumento = round($pesoFinal - (float) $maxAnterior, 1);
                    if ($aumento > 0 && ($topCarga === null || $aumento > $topCarga['aumento'])) {
                        $topCarga = ['exercicio' => $exercicio->nome_exercicio, 'aumento' => $aumento, 'nova' => $pesoFinal];
                    }
                }
            }

            RegistroExercicio::updateOrCreate(
                [
                    'treino_concluido_id' => $treino->id,
                    'nome_exercicio'      => $exercicio->nome_exercicio,
                ],
                [
                    'cliente_id'         => $clienteId,
                    'exercicio_ficha_id' => $exercicio->id,
                    'data_treino'        => $data,
                    'peso'               => $pesoFinal,
                    'repeticoes'         => ($repeticoes === '' || $repeticoes === null) ? null : $repeticoes,
                    'series'             => ($series === '' || $series === null) ? null : $series,
                ]
            );
        }

        // 🎉 Celebração: recorde de carga (bateu a maior carga de todos os tempos).
        if ($topCarga !== null) {
            Celebracoes::push('cliente', (int) $clienteId,
                Celebracoes::recordeCarga($topCarga['exercicio'], $topCarga['nova'], $topCarga['nova'] - $topCarga['aumento']));
        }

        // 🎉 Celebração: bateu um marco de sequência (3/7/14/30/60/100 dias)?
        $datasStreak = TreinoConcluido::where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->pluck('data_treino')
            ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->toArray();
        $streak = EstatisticasTreino::streak($datasStreak);
        Celebracoes::push('cliente', (int) $clienteId, Celebracoes::sequenciaDias($streak['atual']));

        return redirect()->route('fichas-treino.minhas')
            ->with('success', 'Treino marcado como concluído!')
            ->with('recordes', $recordes);
    }

    // ✅ CLIENTE: Desmarcar treino como concluído
    public function desmarcarConcluido($fichaId)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $ficha = FichaTreino::findOrFail($fichaId);

        if ($ficha->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $hoje = now()->format('Y-m-d');

        TreinoConcluido::where('ficha_id', $fichaId)
            ->where('cliente_id', $clienteId)
            ->where('data_treino', $hoje)
            ->update(['concluido' => false]);

        return redirect()->route('fichas-treino.minhas')
            ->with('success', 'Treino desmarcado!');
    }

    // ✅ API: Buscar ficha de um dia específico (para modal)
    public function buscarFichaDia($fichaId, $data)
    {
        $ficha = FichaTreino::with('exercicios')->findOrFail($fichaId);

        $clienteId = session('cliente_id');
        $personalId = session('personal_id');

        if ($ficha->cliente_id !== $clienteId && $ficha->personal_id !== $personalId) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        $treino = TreinoConcluido::where('ficha_id', $fichaId)
            ->where('data_treino', $data)
            ->first();

        return response()->json([
            'ficha' => $ficha,
            'treino' => $treino,
        ]);
    }

    // ==========================================================
    // FEATURE 1 — Evolução de carga por exercício
    // ==========================================================

    // ✅ CLIENTE: tela de evolução de carga (próprios dados)
    public function evolucaoCarga()
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);

        return view('cliente.EvolucaoCarga', [
            'cliente'    => $cliente,
            'exercicios' => $this->exerciciosComRegistro($clienteId),
            'modo'       => 'cliente',
            'dadosUrl'   => route('evolucao-carga.dados', ['cliente_id' => $clienteId]),
            'voltarUrl'  => route('fichas-treino.minhas'),
        ]);
    }

    // ✅ PERSONAL: evolução de carga de um aluno
    public function evolucaoCargaAluno($clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        if (! $this->personalPodeVerCliente($personalId, $clienteId)) {
            return redirect()->route('fichas-treino.alunos')->with('error', 'Acesso negado!');
        }

        $cliente = Cliente::findOrFail($clienteId);

        return view('cliente.EvolucaoCarga', [
            'cliente'    => $cliente,
            'exercicios' => $this->exerciciosComRegistro($clienteId),
            'modo'       => 'personal',
            'dadosUrl'   => route('evolucao-carga.dados', ['cliente_id' => $clienteId]),
            'voltarUrl'  => route('fichas-treino.aluno', $clienteId),
        ]);
    }

    // ✅ API: série temporal de carga de um exercício
    public function evolucaoCargaDados(Request $request)
    {
        $clienteId = (int) $request->query('cliente_id');

        // Autorização: aluno só vê os próprios dados; personal só de seus alunos.
        $sessCliente  = session('cliente_id');
        $sessPersonal = session('personal_id');

        if ($sessCliente) {
            if ($clienteId !== (int) $sessCliente) {
                return response()->json(['error' => 'Não autorizado'], 403);
            }
        } elseif ($sessPersonal) {
            if (! $this->personalPodeVerCliente($sessPersonal, $clienteId)) {
                return response()->json(['error' => 'Não autorizado'], 403);
            }
        } else {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $exercicio = $request->query('exercicio');

        $dias = (int) $request->query('dias', 90);
        if (! in_array($dias, [30, 90, 180], true)) {
            $dias = 90;
        }

        $query = RegistroExercicio::where('cliente_id', $clienteId)
            ->whereNotNull('peso')
            ->where('data_treino', '>=', now()->subDays($dias)->format('Y-m-d'));

        if ($exercicio) {
            $query->where('nome_exercicio', $exercicio);
        }

        $registros = $query->orderBy('data_treino')
            ->get(['data_treino', 'peso', 'repeticoes', 'series']);

        // Um ponto por data: a maior carga do dia.
        $porData = [];
        foreach ($registros as $r) {
            $d = $r->data_treino->format('Y-m-d');
            if (! isset($porData[$d]) || (float) $r->peso > $porData[$d]['peso']) {
                $porData[$d] = [
                    'peso'   => (float) $r->peso,
                    'reps'   => $r->repeticoes,
                    'series' => $r->series,
                ];
            }
        }
        ksort($porData);

        return response()->json([
            'labels' => array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m/Y'), array_keys($porData)),
            'pesos'  => array_values(array_map(fn ($v) => $v['peso'], $porData)),
            'reps'   => array_values(array_map(fn ($v) => $v['reps'], $porData)),
            'series' => array_values(array_map(fn ($v) => $v['series'], $porData)),
        ]);
    }

    // Nomes de exercícios que já têm carga registrada para o aluno.
    private function exerciciosComRegistro($clienteId): array
    {
        return RegistroExercicio::where('cliente_id', $clienteId)
            ->distinct()
            ->orderBy('nome_exercicio')
            ->pluck('nome_exercicio')
            ->toArray();
    }

    // O personal pode ver o aluno se já montou ficha ou se há agendamento entre eles.
    private function personalPodeVerCliente($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists();
    }

    // ==========================================================
    // FEATURE 2 — Dashboard de frequência e aderência
    // ==========================================================

    // Dias sem treino para considerar um aluno "sumido".
    private const DIAS_SUMIDO = 7;

    // ✅ PERSONAL: aderência de todos os seus alunos no mês
    public function dashboardAderencia()
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $personal = Personal::findOrFail($personalId);

        // Alunos com ficha ativa montada por este personal.
        $fichas = FichaTreino::where('personal_id', $personalId)->where('ativo', true)->get();
        $fichasPorCliente = $fichas->groupBy('cliente_id');

        $clientes = Cliente::whereIn('id', $fichasPorCliente->keys())->orderBy('nome')->get();

        $alunos = [];
        $somaAderencia = 0;
        $comPlano = 0;
        $sumidos = 0;

        foreach ($clientes as $cliente) {
            $resumo = $this->aderenciaResumo($cliente->id, $fichasPorCliente->get($cliente->id, collect()));
            $resumo['cliente'] = $cliente;
            $alunos[] = $resumo;

            if ($resumo['aderencia'] !== null) {
                $somaAderencia += $resumo['aderencia'];
                $comPlano++;
            }
            if ($resumo['sumido']) {
                $sumidos++;
            }
        }

        // Sumidos primeiro; depois menor aderência no topo (precisa de atenção).
        usort($alunos, function ($a, $b) {
            if ($a['sumido'] !== $b['sumido']) {
                return $b['sumido'] <=> $a['sumido'];
            }
            return ($a['aderencia'] ?? 101) <=> ($b['aderencia'] ?? 101);
        });

        $resumoGeral = [
            'totalAlunos'    => count($alunos),
            'mediaAderencia' => $comPlano > 0 ? (int) round($somaAderencia / $comPlano) : null,
            'sumidos'        => $sumidos,
        ];

        return view('personal.Aderencia', compact('personal', 'alunos', 'resumoGeral'));
    }

    // ✅ PERSONAL: enviar incentivo a um aluno (e-mail/WhatsApp)
    public function cutucarAluno($clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }
        if (! $this->personalPodeVerCliente($personalId, $clienteId)) {
            return redirect()->route('aderencia.dashboard')->with('error', 'Acesso negado!');
        }

        $cliente = Cliente::find($clienteId);
        if ($cliente) {
            $nome = explode(' ', trim($cliente->nome))[0];
            $nomePersonal = Personal::find($personalId)?->nome ?? 'Seu personal';

            \App\Services\NotificacaoService::cliente(
                $cliente,
                'Senti sua falta nos treinos! 💪',
                "Olá, {$nome}! 👋\n\n" .
                "Notei que você está um tempinho sem treinar. Bora retomar o ritmo? Cada treino conta — e seu shape agradece! 🔥\n\n" .
                "Conte comigo. — {$nomePersonal}",
                'incentivo_aluno',
                [$nome]
            );
        }

        return redirect()->route('aderencia.dashboard')
            ->with('success', 'Incentivo enviado para ' . ($cliente->nome ?? 'o aluno') . '! 💪');
    }

    // ✅ ALUNO: meu desempenho do mês + sequência (streak)
    public function meuDesempenho()
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);

        $fichas = FichaTreino::where('cliente_id', $clienteId)->where('ativo', true)->get();
        $resumo = $this->aderenciaResumo($clienteId, $fichas);

        $datas = TreinoConcluido::where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->orderBy('data_treino')
            ->pluck('data_treino')
            ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->toArray();

        $streak = \App\Services\EstatisticasTreino::streak($datas);
        $game = \App\Services\EstatisticasTreino::gamificacao($streak['atual'], $streak['recorde']);

        // Heatmap de consistência: últimas 12 semanas (domingo a sábado).
        $treinados = array_flip($datas);
        $hojeDia = \Carbon\Carbon::today();
        $cursor = $hojeDia->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)->subWeeks(11);
        $heatmap = [];
        for ($w = 0; $w < 12; $w++) {
            $semana = [];
            for ($d = 0; $d < 7; $d++) {
                $ds = $cursor->toDateString();
                $semana[] = [
                    'treinou' => isset($treinados[$ds]),
                    'futuro'  => $cursor->gt($hojeDia),
                    'rotulo'  => $cursor->format('d/m/Y'),
                ];
                $cursor->addDay();
            }
            $heatmap[] = $semana;
        }

        // Recordes pessoais: maior carga registrada por exercício.
        $recordes = RegistroExercicio::where('cliente_id', $clienteId)
            ->whereNotNull('peso')
            ->where('peso', '>', 0)
            ->selectRaw('nome_exercicio, MAX(peso) as recorde')
            ->groupBy('nome_exercicio')
            ->orderByDesc('recorde')
            ->get();

        // Esforço médio (RPE) no mês.
        $rpeMedio = TreinoConcluido::where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->whereNotNull('rpe')
            ->whereBetween('data_treino', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->avg('rpe');

        return view('cliente.MeuDesempenho', compact('cliente', 'resumo', 'streak', 'game', 'heatmap', 'recordes', 'rpeMedio'));
    }

    // Resumo de aderência do mês para um cliente, dado seu conjunto de fichas.
    private function aderenciaResumo($clienteId, $fichas): array
    {
        $inicio = now()->startOfMonth();
        $hoje   = now();

        $planejados = 0;
        foreach ($fichas as $f) {
            $planejados += $this->ocorrenciasDiaSemana((int) $f->dia_semana, $inicio, $hoje);
        }

        $fichaIds = $fichas->pluck('id');

        $realizados = $fichaIds->isEmpty() ? 0 : TreinoConcluido::whereIn('ficha_id', $fichaIds)
            ->where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->whereBetween('data_treino', [$inicio->toDateString(), $hoje->toDateString()])
            ->count();

        $aderencia = $planejados > 0 ? min(100, (int) round($realizados / $planejados * 100)) : null;

        $ultimoTreino = $fichaIds->isEmpty() ? null : TreinoConcluido::whereIn('ficha_id', $fichaIds)
            ->where('cliente_id', $clienteId)
            ->where('concluido', true)
            ->orderByDesc('data_treino')
            ->orderByDesc('id')
            ->first();

        $ultimo = $ultimoTreino?->data_treino;

        $diasSemTreino = $ultimo
            ? (int) \Carbon\Carbon::parse($ultimo)->startOfDay()->diffInDays(now()->startOfDay())
            : null;

        $sumido = $diasSemTreino === null || $diasSemTreino >= self::DIAS_SUMIDO;

        return [
            'planejados'      => $planejados,
            'realizados'      => $realizados,
            'aderencia'       => $aderencia,
            'ultimo'          => $ultimo ? \Carbon\Carbon::parse($ultimo) : null,
            'diasSemTreino'   => $diasSemTreino,
            'sumido'          => $sumido,
            'fichasAtivas'    => $fichas->count(),
            'ultimoRpe'       => $ultimoTreino?->rpe,
            'ultimaSensacao'  => $ultimoTreino?->sensacao,
        ];
    }

    // Quantas vezes um dia da semana (0=Dom..6=Sáb) ocorre no intervalo.
    private function ocorrenciasDiaSemana(int $diaSemana, $inicio, $fim): int
    {
        $count = 0;
        $d = $inicio->copy()->startOfDay();
        $limite = $fim->copy()->endOfDay();
        while ($d->lte($limite)) {
            if ($d->dayOfWeek === $diaSemana) {
                $count++;
            }
            $d->addDay();
        }
        return $count;
    }

    private function getExerciciosData(): array
    {
        return \App\Support\CatalogoExercicios::todos();
    }
}
