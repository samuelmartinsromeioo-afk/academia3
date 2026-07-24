<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Models\Anamnese;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use App\Models\Meta;
use Illuminate\Http\Request;
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

        return response()->json(['anamnese' => $anamnese]);
    }

    // POST /api/v1/anamnese
    public function salvarAnamnese(Request $request)
    {
        $cliente = $this->clienteAutenticado($request);

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

        $anamnese = Anamnese::updateOrCreate(
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

        return response()->json(['success' => true, 'message' => 'Anamnese salva com sucesso! 💪', 'anamnese' => $anamnese]);
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
