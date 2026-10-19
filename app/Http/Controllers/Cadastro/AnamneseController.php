<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Concerns\VinculoComAluno;
use App\Http\Controllers\Controller;
use App\Models\Anamnese;
use App\Models\Cadastro\Cliente;
use App\Models\Consentimento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * FEATURE 5 — Anamnese digital.
 * O aluno preenche/edita; o personal visualiza (somente leitura).
 *
 * A anamnese é dado pessoal SENSÍVEL (art. 11 da LGPD), então salvá-la exige
 * consentimento destacado registrado — ver o bloco de validação em `salvar()`.
 * A revogação (apagar a anamnese sem encerrar a conta) vive em
 * `Cadastro\LgpdController::revogarSaude`, junto com os outros direitos do
 * titular, porque é lá que o usuário vai procurar.
 */
class AnamneseController extends Controller
{
    use VinculoComAluno;

    // ALUNO: formulário (cria ou edita a própria anamnese)
    public function form()
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $anamnese = Anamnese::firstOrNew(['cliente_id' => $clienteId]);

        return view('cliente.Anamnese', compact('cliente', 'anamnese'));
    }

    // ALUNO: salvar
    public function salvar(Request $request)
    {
        $clienteId = session('cliente_id');
        if (! $clienteId) {
            return redirect()->route('login.index');
        }

        $request->validate(
            [
                /*
                 * Consentimento DESTACADO para dado sensível (art. 11, I, da
                 * LGPD) — `accepted`, não `boolean`, que aceitaria o falso.
                 *
                 * A Política já afirmava que a anamnese é coletada "mediante
                 * consentimento específico e destacado". O que existia de fato
                 * era um parágrafo acima do formulário dizendo que salvar
                 * equivale a consentir, e NENHUM registro — nem coluna, nem
                 * data, nem IP. Para dado sensível é o pior arranjo possível: o
                 * documento assume o ônus da prova (art. 8º, §1º) e o sistema
                 * não tem o que apresentar. E consentimento tácito por clicar
                 * em "Salvar" não é "destacado" em nenhuma leitura do art. 11.
                 */
                'consentimento' => 'accepted',
                'objetivo_principal' => 'nullable|string|max:255',
                'nivel_atividade' => 'nullable|in:sedentario,leve,moderado,intenso',
                'historico_lesoes' => 'nullable|string',
                'restricoes_medicas' => 'nullable|string',
                'doencas_preexistentes' => 'nullable|string',
                'medicamentos' => 'nullable|string',
                'cirurgias' => 'nullable|string',
                'parq_observacoes' => 'nullable|string',
                'observacoes' => 'nullable|string',
            ],
            ['consentimento.accepted' => 'Marque o consentimento de uso dos dados de saúde para salvar a anamnese.']
        );

        $cliente = Cliente::findOrFail($clienteId);

        /*
         * Consentimento e dado na MESMA transação, e o consentimento PRIMEIRO.
         *
         * Não é zelo: o registro é a base legal do tratamento. Gravar a
         * anamnese e perder o consentimento numa falha deixaria a Plataforma
         * com dado de saúde sem fundamento — exatamente a situação que esta
         * mudança existe para encerrar. Nesta ordem, a falha impede a gravação,
         * que é o resultado seguro.
         */
        DB::transaction(function () use ($cliente, $clienteId, $request) {
            Consentimento::registrar(
                $cliente,
                Consentimento::TIPO_SAUDE,
                true,
                $request,
                Consentimento::ORIGEM_SITE
            );

            Anamnese::updateOrCreate(
                ['cliente_id' => $clienteId],
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
        });

        return redirect()->route('cliente.index')->with('success', 'Anamnese salva com sucesso! 💪');
    }

    // PERSONAL: visualizar a anamnese de um aluno (somente leitura)
    public function verPersonal($clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        if (! $this->podeVer($personalId, $clienteId)) {
            return redirect()->route('fichas-treino.alunos')->with('error', 'Acesso negado!');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $anamnese = Anamnese::where('cliente_id', $clienteId)->first();

        return view('personal.Anamnese', compact('cliente', 'anamnese'));
    }
}
