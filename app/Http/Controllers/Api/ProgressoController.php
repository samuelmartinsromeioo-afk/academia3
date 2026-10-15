<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Anamnese;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Consentimento;
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use App\Models\Meta;
use App\Services\AvisoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Progresso (medidas + fotos), metas e anamnese do ALUNO pela API — espelha
 * Cadastro\ProgressoController, MetaController e AnamneseController.
 */
class ProgressoController extends Controller
{
    use ResolvesApiUser;

    private array $regrasMeta = [
        'tipo' => 'required|in:treinos_mes,carga,livre',
        'titulo' => 'required|string|max:255',
        'alvo' => 'nullable|numeric|min:0|max:99999',
        'exercicio' => 'nullable|string|max:255',
        'prazo' => 'nullable|date',
    ];

    // ===================== PROGRESSO =====================

    // GET /api/v1/progresso
    public function index(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $medidas = MedidaCorporal::where('cliente_id', $cliente->id)->orderBy('data')->get();
        $fotos = FotoProgresso::where('cliente_id', $cliente->id)->orderByDesc('data')->get();

        return response()->json([
            'medidas' => $medidas->map(fn ($m) => $this->medidaJson($m)),
            'fotos' => $fotos->map(fn ($f) => $this->fotoJson($f)),
            'campos' => MedidaCorporal::CAMPOS,
        ]);
    }

    // POST /api/v1/progresso/medidas
    public function salvarMedida(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $request->validate([
            'data' => 'required|date',
            'peso' => 'nullable|numeric|min:0|max:500',
            'percentual_gordura' => 'nullable|numeric|min:0|max:100',
            'cintura' => 'nullable|numeric|min:0|max:300',
            'quadril' => 'nullable|numeric|min:0|max:300',
            'braco' => 'nullable|numeric|min:0|max:200',
            'peito' => 'nullable|numeric|min:0|max:300',
            'coxa' => 'nullable|numeric|min:0|max:200',
            'observacoes' => 'nullable|string',
        ]);

        $medida = MedidaCorporal::create(array_merge(
            $request->only(array_keys(MedidaCorporal::CAMPOS)),
            ['cliente_id' => $cliente->id, 'data' => $request->data, 'observacoes' => $request->observacoes]
        ));

        return response()->json(['success' => true, 'message' => 'Medidas registradas! 📏', 'medida' => $this->medidaJson($medida)], 201);
    }

    // DELETE /api/v1/progresso/medidas/{id}
    public function excluirMedida(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $medida = MedidaCorporal::findOrFail($id);
        if ($medida->cliente_id != $cliente->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        $medida->delete();

        return response()->json(['success' => true, 'message' => 'Registro removido.']);
    }

    // POST /api/v1/progresso/fotos (multipart: foto)
    public function uploadFoto(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $request->validate([
            'data' => 'required|date',
            // mimes explícito (não a regra "image", que aceita SVG — vetor de
            // XSS armazenado quando o arquivo é aberto direto do /storage).
            'foto' => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:8192',
            'peso' => 'nullable|numeric|min:0|max:500',
            'observacao' => 'nullable|string|max:255',
        ]);

        $caminho = $request->file('foto')->store('progresso', 'public');

        $foto = FotoProgresso::create([
            'cliente_id' => $cliente->id,
            'data' => $request->data,
            'caminho' => $caminho,
            'peso' => $request->peso,
            'observacao' => $request->observacao,
        ]);

        return response()->json(['success' => true, 'message' => 'Foto adicionada! 📸', 'foto' => $this->fotoJson($foto)], 201);
    }

    // DELETE /api/v1/progresso/fotos/{id}
    public function excluirFoto(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $foto = FotoProgresso::findOrFail($id);
        if ($foto->cliente_id != $cliente->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        if ($foto->caminho) {
            Storage::disk('public')->delete($foto->caminho);
        }
        $foto->delete();

        return response()->json(['success' => true, 'message' => 'Foto removida.']);
    }

    // ===================== METAS =====================

    // GET /api/v1/metas
    public function metas(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $metas = Meta::where('cliente_id', $cliente->id)
            ->orderBy('concluida')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Meta $m) => $this->metaJson($m));

        $exercicios = RegistroExercicio::where('cliente_id', $cliente->id)
            ->distinct()->orderBy('nome_exercicio')->pluck('nome_exercicio');

        return response()->json(['metas' => $metas, 'exercicios' => $exercicios, 'tipos' => Meta::TIPOS]);
    }

    // POST /api/v1/metas
    public function salvarMeta(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $request->validate($this->regrasMeta);

        $meta = Meta::create([
            'cliente_id' => $cliente->id,
            'tipo' => $request->tipo,
            'titulo' => $request->titulo,
            'alvo' => $request->tipo === 'livre' ? null : $request->alvo,
            'exercicio' => $request->tipo === 'carga' ? $request->exercicio : null,
            'prazo' => $request->prazo,
        ]);

        return response()->json(['success' => true, 'message' => 'Meta criada! 🎯', 'meta' => $this->metaJson($meta)], 201);
    }

    // POST /api/v1/metas/{id}/alternar
    public function alternarMeta(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $meta = Meta::findOrFail($id);
        if ($meta->cliente_id != $cliente->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        $meta->update(['concluida' => ! $meta->concluida]);

        return response()->json(['success' => true, 'meta' => $this->metaJson($meta->fresh())]);
    }

    // DELETE /api/v1/metas/{id}
    public function excluirMeta(Request $request, $id)
    {
        $cliente = $this->clienteAutenticado($request);

        $meta = Meta::findOrFail($id);
        if ($meta->cliente_id != $cliente->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        $meta->delete();

        return response()->json(['success' => true, 'message' => 'Meta removida.']);
    }

    // ===================== ANAMNESE =====================

    // GET /api/v1/anamnese
    public function anamnese(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $anamnese = Anamnese::where('cliente_id', $cliente->id)->first();

        return response()->json([
            'anamnese' => $anamnese,
            // O app precisa saber o estado do consentimento para desenhar a
            // caixa já marcada em quem só está editando — e para mostrar o
            // botão de revogar somente a quem tem o que revogar.
            'consentimento' => Consentimento::concedido($cliente, Consentimento::TIPO_SAUDE),
        ]);
    }

    // POST /api/v1/anamnese
    public function salvarAnamnese(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        $request->validate(
            [
                /*
                 * Consentimento destacado para dado sensível (art. 11, I) —
                 * mesma regra do site (`Cadastro\AnamneseController@salvar`).
                 *
                 * `accepted` e SEM `sometimes`, o que quebra o binário já
                 * publicado na loja (ele não manda o campo). É a mesma decisão
                 * tomada na idade mínima e pela mesma razão: `sometimes`
                 * tornaria a trava opcional — bastaria omitir o campo —, e aqui
                 * o campo não é completude de dado, é a BASE LEGAL do
                 * tratamento. Anamnese sem consentimento não é "anamnese pela
                 * metade" que o aluno termina depois; é tratamento de dado
                 * sensível sem fundamento, e não se grava. Daí a mensagem
                 * mandar ATUALIZAR O APP, em vez de pedir para marcar uma caixa
                 * que naquela tela não existe.
                 */
                'consentimento'         => 'accepted',
                'objetivo_principal'    => 'nullable|string|max:255',
                'nivel_atividade'       => 'nullable|in:sedentario,leve,moderado,intenso',
                'historico_lesoes'      => 'nullable|string',
                'restricoes_medicas'    => 'nullable|string',
                'doencas_preexistentes' => 'nullable|string',
                'medicamentos'          => 'nullable|string',
                'cirurgias'             => 'nullable|string',
                'parq_observacoes'      => 'nullable|string',
                'observacoes'           => 'nullable|string',
            ],
            ['consentimento.accepted' => 'Atualize o app para continuar: a anamnese passou a exigir o consentimento de uso dos dados de saúde.']
        );

        // Consentimento e dado na MESMA transação, consentimento primeiro — ver
        // o comentário em Cadastro\AnamneseController@salvar.
        $anamnese = DB::transaction(function () use ($cliente, $request) {
            Consentimento::registrar(
                $cliente,
                Consentimento::TIPO_SAUDE,
                true,
                $request,
                Consentimento::ORIGEM_APP
            );

            return Anamnese::updateOrCreate(
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
        });

        return response()->json(['success' => true, 'message' => 'Anamnese salva com sucesso! 💪', 'anamnese' => $anamnese]);
    }

    /**
     * DELETE /api/v1/anamnese — revoga o consentimento e apaga a anamnese.
     *
     * Espelha `Cadastro\LgpdController::revogarSaude`. Não encerra a conta: o
     * aluno continua treinando e contratando, só sem dado de saúde aqui. Antes
     * desta rota, a Política prometia revogação "a qualquer momento" e o único
     * caminho real era excluir a conta inteira — para tirar um dado, perder
     * todos.
     */
    public function revogarAnamnese(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

        // Revogação registrada ANTES da exclusão e na mesma transação: o livro
        // de consentimentos é o que explica por que o dado existiu e por que
        // deixou de existir. Apagar o dado e perder o registro do ato seria
        // pior do que não apagar.
        DB::transaction(function () use ($cliente, $request) {
            Consentimento::registrar(
                $cliente,
                Consentimento::TIPO_SAUDE,
                false,
                $request,
                Consentimento::ORIGEM_APP
            );

            Anamnese::where('cliente_id', $cliente->id)->delete();
        });

        // Fora da transação, de propósito: o aviso é efeito colateral de um ato
        // já consumado e não pode desfazê-lo se o e-mail falhar. O texto vive em
        // AvisoService para o site e o app dizerem a mesma coisa.
        AvisoService::consentimentoSaudeRevogado($cliente);

        return response()->json([
            'success' => true,
            'message' => 'Consentimento revogado e anamnese apagada. Sua conta continua ativa, '
                . 'e enviamos a confirmação para o seu e-mail.',
        ]);
    }

    // ===================== HELPERS =====================

    private function medidaJson(MedidaCorporal $m): array
    {
        $json = ['id' => $m->id, 'data' => $m->data?->toDateString(), 'observacoes' => $m->observacoes];
        foreach (array_keys(MedidaCorporal::CAMPOS) as $campo) {
            $json[$campo] = $m->$campo !== null ? (float) $m->$campo : null;
        }
        return $json;
    }

    private function fotoJson(FotoProgresso $f): array
    {
        return [
            'id' => $f->id,
            'data' => $f->data instanceof \Carbon\Carbon ? $f->data->toDateString() : $f->data,
            'url' => $f->caminho ? Storage::disk('public')->url($f->caminho) : null,
            'peso' => $f->peso !== null ? (float) $f->peso : null,
            'observacao' => $f->observacao,
        ];
    }

    private function metaJson(Meta $m): array
    {
        return [
            'id' => $m->id,
            'tipo' => $m->tipo,
            'tipo_nome' => Meta::TIPOS[$m->tipo] ?? $m->tipo,
            'titulo' => $m->titulo,
            'alvo' => $m->alvo !== null ? (float) $m->alvo : null,
            'exercicio' => $m->exercicio,
            'prazo' => $m->prazo?->toDateString(),
            'concluida' => (bool) $m->concluida,
            'criada_pelo_personal' => $m->criada_por_personal_id !== null,
            'progresso' => $m->calcularProgresso(),
        ];
    }
}
