<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesApiUser;
use App\Http\Controllers\Controller;
use App\Http\Resources\AvaliacaoFisicaResource;
use App\Models\Agenda;
use App\Models\AvaliacaoFisica;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\SolicitacaoAvaliacao;
use App\Services\Celebracoes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Avaliações físicas via API (dinamômetro, oxímetro, pressão arterial etc.).
 *
 * Personal: lista/cria avaliações dos seus clientes (mesma regra de permissão
 * do fluxo web — cliente de pacote ativo ou com avaliação avulsa paga).
 * Cliente: lista apenas as próprias avaliações.
 */
class AvaliacaoFisicaController extends Controller
{
    use ResolvesApiUser;

    /**
     * Mesma regra de AvaliacaoFisicaController web (clienteIdsPermitidos):
     * clientes de pacote ativo na agenda + avaliações avulsas pagas.
     */
    private function clienteIdsPermitidos(int $personalId): array
    {
        $pacote = Agenda::where('personal_id', $personalId)
            ->where('tipo_aula', 'pacote')
            ->where('cancelado', false)
            ->whereNotNull('cliente_id')
            ->pluck('cliente_id');

        $avulsos = SolicitacaoAvaliacao::where('personal_id', $personalId)
            ->where('payment_status', 'pago')
            ->pluck('cliente_id');

        return $pacote->merge($avulsos)->unique()->values()->all();
    }

    // GET /api/v1/avaliacoes?cliente_id=&tipo=
    public function index(Request $request)
    {
        $user = $request->user();

        $query = AvaliacaoFisica::with('cliente')
            ->orderByDesc('data_avaliacao')
            ->orderByDesc('id');

        if ($user instanceof Personal) {
            $query->where('personal_id', $user->id);
            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', (int) $request->query('cliente_id'));
            }
        } elseif ($user instanceof Cliente) {
            // Cliente: sempre e somente as próprias avaliações.
            $query->where('cliente_id', $user->id);
        } else {
            // Academia/Studio/Loja não têm avaliação física — nega (fail-closed)
            // para nunca cair numa query sem filtro de posse.
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->query('tipo'));
        }

        return AvaliacaoFisicaResource::collection($query->paginate(20));
    }

    /**
     * POST /api/v1/avaliacoes — somente personal, para cliente permitido.
     *
     * Espelha o fluxo web (AvaliacaoFisicaController::salvarRegistro): aceita
     * todos os campos de todos os módulos, uploads (foto do antes/depois, PDF
     * de bioimpedância e fotos posturais via multipart) e faz os mesmos
     * cálculos automáticos (IMC e % de gordura por Pollock).
     */
    public function store(Request $request)
    {
        $personal = $this->personalAutenticado($request);

        $dados = $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'tipo' => ['required', Rule::in(AvaliacaoFisica::TIPOS)],
            'data_avaliacao' => 'required|date',
            'foto' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'arquivo' => 'nullable|file|mimes:pdf|max:10240',
            'peso' => 'nullable|numeric|min:0|max:500',
            'medidas' => 'nullable|string|max:2000',
            'forca' => 'nullable|numeric|min:0|max:500',
            'spo2' => 'nullable|integer|min:0|max:100',
            'bpm' => 'nullable|integer|min:0|max:300',
            'pressao_sistolica' => 'nullable|integer|min:0|max:400',
            'pressao_diastolica' => 'nullable|integer|min:0|max:300',
            'observacoes' => 'nullable|string|max:2000',
            // Anamnese
            'objetivo_principal' => 'nullable|string|max:5000',
            'historico_atividade' => 'nullable|string|max:5000',
            'lesoes' => 'nullable|string|max:2000',
            'cirurgias' => 'nullable|string|max:2000',
            'medicamentos' => 'nullable|string|max:2000',
            'restricoes_medicas' => 'nullable|string|max:2000',
            'habitos_sono' => 'nullable|string|max:100',
            'nivel_estresse' => 'nullable|integer|min:0|max:10',
            'alimentacao' => 'nullable|string|max:5000',
            // Antropométrica
            'altura' => 'nullable|numeric|min:0|max:300',
            'circ_cintura' => 'nullable|numeric|min:0|max:300',
            'circ_abdomen' => 'nullable|numeric|min:0|max:300',
            'circ_quadril' => 'nullable|numeric|min:0|max:300',
            'circ_torax' => 'nullable|numeric|min:0|max:300',
            'circ_braco' => 'nullable|numeric|min:0|max:200',
            'circ_coxa' => 'nullable|numeric|min:0|max:200',
            'circ_panturrilha' => 'nullable|numeric|min:0|max:200',
            // Dobras
            'protocolo_dobras' => 'nullable|string|max:50',
            'dobra_triceps' => 'nullable|numeric|min:0|max:200',
            'dobra_biceps' => 'nullable|numeric|min:0|max:200',
            'dobra_subescapular' => 'nullable|numeric|min:0|max:200',
            'dobra_suprailiaca' => 'nullable|numeric|min:0|max:200',
            'dobra_abdominal' => 'nullable|numeric|min:0|max:200',
            'dobra_coxa_dc' => 'nullable|numeric|min:0|max:200',
            'dobra_peitoral' => 'nullable|numeric|min:0|max:200',
            'dobra_axilar_media' => 'nullable|numeric|min:0|max:200',
            'percentual_gordura' => 'nullable|numeric|min:0|max:100',
            'massa_gorda' => 'nullable|numeric|min:0|max:500',
            'massa_magra' => 'nullable|numeric|min:0|max:500',
            // Postural
            'foto_anterior' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'foto_posterior' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'foto_lateral_direita' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'foto_lateral_esquerda' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'postural_checklist' => 'nullable|array',
            'postural_checklist.*' => 'nullable|string|max:100',
            // Neuromotora
            'equil_unipodal' => 'nullable|string|max:100',
            'coordenacao_motora' => 'nullable|string|max:100',
            'mob_ombro' => 'nullable|string|max:50',
            'mob_quadril' => 'nullable|string|max:50',
            'mob_tornozelo' => 'nullable|string|max:50',
            'agach_profundidade' => 'nullable|string|max:50',
            'agach_estabilidade' => 'nullable|string|max:50',
            'agach_simetria' => 'nullable|string|max:50',
            // Flexibilidade
            'flex_sentar_alcancar' => 'nullable|numeric|min:-50|max:100',
            'flex_ombros' => 'nullable|string|max:50',
            'flex_quadril' => 'nullable|numeric|min:0|max:200',
            // Cardiorrespiratória
            'teste_caminhada_dist' => 'nullable|numeric|min:0|max:9999',
            'teste_cooper_dist' => 'nullable|numeric|min:0|max:9999',
            'teste_rockport_tempo' => 'nullable|numeric|min:0|max:999',
            'vo2max_estimado' => 'nullable|numeric|min:0|max:200',
            // Força
            'flexao_braco_reps' => 'nullable|integer|min:0|max:9999',
            'prancha_tempo' => 'nullable|integer|min:0|max:9999',
            'testes_submax' => 'nullable|string|max:2000',
            // Funcional
            'func_agachamento' => 'nullable|string|max:2000',
            'func_avanco' => 'nullable|string|max:2000',
            'func_stepup' => 'nullable|string|max:2000',
            'func_prancha' => 'nullable|string|max:2000',
            'func_mob_toracica' => 'nullable|string|max:2000',
            // Dor
            'dor_lombar' => 'nullable|integer|min:0|max:10',
            'dor_ombro' => 'nullable|integer|min:0|max:10',
            'dor_joelho' => 'nullable|integer|min:0|max:10',
            'dor_quadril' => 'nullable|integer|min:0|max:10',
            'dor_cervical' => 'nullable|integer|min:0|max:10',
        ]);

        $clienteId = (int) $dados['cliente_id'];

        if (! in_array($clienteId, $this->clienteIdsPermitidos($personal->id), true)) {
            return response()->json([
                'error' => 'Este cliente não está vinculado a você (pacote ativo ou avaliação avulsa paga).',
            ], 403);
        }

        // Obrigatoriedades por tipo — mesmas regras do fluxo web.
        if ($dados['tipo'] === 'dinamometro' && ! isset($dados['forca'])) {
            return response()->json(['error' => 'Informe a força medida no dinamômetro.'], 422);
        }
        if ($dados['tipo'] === 'oximetro' && ! isset($dados['spo2'])) {
            return response()->json(['error' => 'Informe a saturação (SpO2) medida no oxímetro.'], 422);
        }
        if ($dados['tipo'] === 'pressao_arterial' && (! isset($dados['pressao_sistolica']) || ! isset($dados['pressao_diastolica']))) {
            return response()->json(['error' => 'Informe a pressão sistólica e diastólica.'], 422);
        }
        if ($dados['tipo'] === 'bioimpedancia' && ! $request->hasFile('arquivo')) {
            return response()->json(['error' => 'Anexe o PDF com os dados da bioimpedância.'], 422);
        }

        // Uploads
        $arquivos = ['foto' => null, 'arquivo' => null];
        foreach (['foto', 'arquivo'] as $campo) {
            if ($request->hasFile($campo)) {
                $arquivos[$campo] = $request->file($campo)->store('avaliacoes_fisicas', 'public');
            }
        }
        $fotosPosturais = [];
        foreach (['foto_anterior', 'foto_posterior', 'foto_lateral_direita', 'foto_lateral_esquerda'] as $campo) {
            $fotosPosturais[$campo] = $request->hasFile($campo)
                ? $request->file($campo)->store('avaliacoes_fisicas/postural', 'public')
                : null;
        }

        // IMC automático
        $imcCalculado = null;
        $peso = $dados['peso'] ?? null;
        $altura = $dados['altura'] ?? null;
        if ($peso && $altura && $altura > 0) {
            $alturaM = $altura / 100;
            $imcCalculado = round($peso / ($alturaM * $alturaM), 2);
        }

        // % de gordura via Pollock (3 ou 7 dobras) quando aplicável.
        $percentualGordura = $dados['percentual_gordura'] ?? null;
        $massaGorda = $dados['massa_gorda'] ?? null;
        $massaMagra = $dados['massa_magra'] ?? null;

        $protocolo = $dados['protocolo_dobras'] ?? null;
        if ($protocolo && $peso) {
            $cliente = Cliente::findOrFail($clienteId);
            $sexo = $cliente->sexo ?? 'masculino';
            $idade = $cliente->data_nascimento
                ? Carbon::parse($cliente->data_nascimento)->age
                : 30;

            $D = null;

            if ($protocolo === 'pollock3') {
                $peitoral = $dados['dobra_peitoral'] ?? null;
                $abdominal = $dados['dobra_abdominal'] ?? null;
                $coxaDc = $dados['dobra_coxa_dc'] ?? null;
                $triceps = $dados['dobra_triceps'] ?? null;
                $supra = $dados['dobra_suprailiaca'] ?? null;

                if ($sexo === 'masculino' && $peitoral !== null && $abdominal !== null && $coxaDc !== null) {
                    $soma = $peitoral + $abdominal + $coxaDc;
                    $D = 1.10938 - (0.0008267 * $soma) + (0.0000016 * $soma * $soma) - (0.0002574 * $idade);
                } elseif ($sexo !== 'masculino' && $triceps !== null && $supra !== null && $coxaDc !== null) {
                    $soma = $triceps + $supra + $coxaDc;
                    $D = 1.0994921 - (0.0009929 * $soma) + (0.0000023 * $soma * $soma) - (0.0001392 * $idade);
                }
            } elseif ($protocolo === 'pollock7') {
                $peitoral = $dados['dobra_peitoral'] ?? null;
                $abdominal = $dados['dobra_abdominal'] ?? null;
                $coxaDc = $dados['dobra_coxa_dc'] ?? null;
                $triceps = $dados['dobra_triceps'] ?? null;
                $supra = $dados['dobra_suprailiaca'] ?? null;
                $sub = $dados['dobra_subescapular'] ?? null;
                $axilar = $dados['dobra_axilar_media'] ?? null;

                if ($peitoral !== null && $abdominal !== null && $coxaDc !== null
                    && $triceps !== null && $supra !== null && $sub !== null && $axilar !== null) {
                    $soma = $peitoral + $abdominal + $coxaDc + $triceps + $supra + $sub + $axilar;
                    $D = $sexo === 'masculino'
                        ? 1.112 - (0.00043499 * $soma) + (0.00000055 * $soma * $soma) - (0.00028826 * $idade)
                        : 1.097 - (0.00046971 * $soma) + (0.00000056 * $soma * $soma) - (0.00012828 * $idade);
                }
            }

            if ($D !== null && $D > 0) {
                $pg = ((4.95 / $D) - 4.50) * 100;
                $pg = max(0, min(100, round($pg, 2)));
                $percentualGordura = $pg;
                $massaGorda = round($peso * $pg / 100, 2);
                $massaMagra = round($peso - $massaGorda, 2);
            }
        }

        // Peso anterior (para celebrar perda de peso com o aluno, como no site).
        $pesoNovo = isset($dados['peso']) ? (float) $dados['peso'] : null;
        $pesoAnterior = $pesoNovo === null ? null : AvaliacaoFisica::where('cliente_id', $clienteId)
            ->whereNotNull('peso')
            ->orderByDesc('data_avaliacao')
            ->orderByDesc('id')
            ->value('peso');

        $avaliacao = AvaliacaoFisica::create(array_merge(
            collect($dados)->except(['cliente_id', 'foto', 'arquivo', 'foto_anterior', 'foto_posterior', 'foto_lateral_direita', 'foto_lateral_esquerda'])->all(),
            $fotosPosturais,
            [
                'personal_id' => $personal->id,
                'cliente_id' => $clienteId,
                'foto' => $arquivos['foto'],
                'arquivo' => $arquivos['arquivo'],
                'imc' => $imcCalculado,
                'percentual_gordura' => $percentualGordura,
                'massa_gorda' => $massaGorda,
                'massa_magra' => $massaMagra,
            ]
        ));

        if ($pesoNovo !== null && $pesoAnterior !== null && $pesoNovo < (float) $pesoAnterior) {
            Celebracoes::push('cliente', $clienteId, Celebracoes::perdaPeso((float) $pesoAnterior - $pesoNovo));
        }

        return (new AvaliacaoFisicaResource($avaliacao->load('cliente')))
            ->response()
            ->setStatusCode(201);
    }

    // GET /api/v1/avaliacoes/{id}
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $query = AvaliacaoFisica::with('cliente');

        if ($user instanceof Personal) {
            $query->where('personal_id', $user->id);
        } elseif ($user instanceof Cliente) {
            $query->where('cliente_id', $user->id);
        } else {
            // Academia/Studio/Loja: sem vínculo com avaliação física — nega
            // (fail-closed) para não devolver um registro de outro dono por id.
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $avaliacao = $query->find($id);
        if (! $avaliacao) {
            return response()->json(['error' => 'Avaliação não encontrada.'], 404);
        }

        return new AvaliacaoFisicaResource($avaliacao);
    }
}
