<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Models\Nutri\AnamneseModelo;
use App\Models\Nutri\AnamneseResposta;
use App\Models\Nutri\Checkin;
use App\Models\Nutri\DiarioRefeicao;
use App\Models\Nutri\MensagemNutri;
use App\Models\Nutri\MetaRegistro;
use App\Models\Nutri\Paciente;
use Illuminate\Http\Request;

/**
 * Portal do paciente — acesso por link seguro com token (sem senha), no espírito
 * "app do paciente". NÃO usa o login por sessão da plataforma.
 */
class PortalController extends Controller
{
    private function paciente(string $token): Paciente
    {
        return Paciente::where('portal_token', $token)->where('ativo', true)->firstOrFail();
    }

    public function home(string $token)
    {
        $paciente = $this->paciente($token);
        $plano = $paciente->planoAtivo();
        $ultimoCheckin = $paciente->checkins()->first();
        $metasHoje = $paciente->metasAtivas()->with('registros')->get();
        $temOrientacoes = $paciente->orientacoes()->exists();

        return view('nutri.portal.home', compact('paciente', 'plano', 'token', 'ultimoCheckin', 'metasHoje', 'temOrientacoes'));
    }

    public function plano(string $token, Request $request)
    {
        $paciente = $this->paciente($token);

        // Fichas ativas (pode haver mais de uma — ex.: uma por dia da semana).
        $ativos = $paciente->planosAtivos()
            ->with('refeicoes.itens.opcoes')
            ->get();

        // Dia selecionado (padrão: hoje). Mostra a ficha correspondente ao dia.
        $dia = $request->filled('dia') ? (int) $request->query('dia') : now()->dayOfWeek;
        $dia = max(0, min(6, $dia));
        $plano = Paciente::escolherPlanoDoDia($ativos, $dia) ?? $ativos->first();

        return view('nutri.portal.plano', compact('paciente', 'plano', 'ativos', 'dia', 'token'));
    }

    /** Lista de compras agregada a partir do plano ativo. */
    public function listaCompras(string $token)
    {
        $paciente = $this->paciente($token);
        $plano = $paciente->planoAtivo();

        // Agrega todas as fichas ativas do paciente (cobre a semana inteira,
        // quando há uma ficha por dia).
        $ativos = $paciente->planosAtivos()->with('refeicoes.itens')->get();

        $itens = [];
        foreach ($ativos as $pl) {
            foreach ($pl->refeicoes as $ref) {
                foreach ($ref->itens as $item) {
                    $chave = mb_strtolower($item->descricao);
                    $itens[$chave]['descricao'] = $item->descricao;
                    $itens[$chave]['total_g'] = ($itens[$chave]['total_g'] ?? 0) + $item->quantidade_g;
                }
            }
        }
        ksort($itens);
        $plano = $ativos->first();

        return view('nutri.portal.lista-compras', compact('paciente', 'itens', 'token', 'plano', 'ativos'));
    }

    public function diario(string $token)
    {
        $paciente = $this->paciente($token);
        $registros = DiarioRefeicao::where('paciente_id', $paciente->id)
            ->orderByDesc('data')->orderByDesc('id')->limit(60)->get();

        return view('nutri.portal.diario', compact('paciente', 'registros', 'token'));
    }

    public function salvarDiario(string $token, Request $request)
    {
        $paciente = $this->paciente($token);
        $dados = $request->validate([
            'data' => 'required|date',
            'refeicao' => 'nullable|string|max:120',
            'descricao' => 'nullable|string|max:1000',
            'foto' => 'nullable|file|mimes:jpeg,jpg,png,webp,heic|max:10240',
        ]);

        if ($request->hasFile('foto')) {
            $dados['foto'] = $request->file('foto')->store('nutri/diario', 'public');
        }
        $dados['paciente_id'] = $paciente->id;

        DiarioRefeicao::create($dados);

        return back()->with('success', 'Refeição registrada!');
    }

    /** Metas do dia + orientações que o nutricionista liberou para este paciente. */
    public function metas(string $token, Request $request)
    {
        $paciente = $this->paciente($token);

        // Permite marcar um dia anterior (esqueceu ontem), mas nunca o futuro.
        $data = $request->filled('data') ? $request->date('data') : now();
        $data = $data->gt(now()) ? now() : $data;
        $dia = $data->toDateString();

        $metas = $paciente->metasAtivas()->with('registros')->get();

        return view('nutri.portal.metas', compact('paciente', 'metas', 'token', 'dia'));
    }

    public function salvarMetas(string $token, Request $request)
    {
        $paciente = $this->paciente($token);

        $dados = $request->validate([
            'data' => 'required|date|before_or_equal:today',
            'marcadas' => 'nullable|array',
            'marcadas.*' => 'integer',
            'valores' => 'nullable|array',
            'valores.*' => 'nullable|numeric|min:0|max:99999',
        ]);

        $marcadas = array_map('intval', $dados['marcadas'] ?? []);

        // Percorre as metas DO PACIENTE, não o que veio no POST — assim um id de
        // outro paciente enviado na mão não cria registro nenhum.
        foreach ($paciente->metasAtivas()->get() as $meta) {
            $valor = $dados['valores'][$meta->id] ?? null;
            $concluida = in_array($meta->id, $marcadas, true);

            // No tipo quantidade, informar um valor já conta como cumprido
            // quando ele alcança o alvo — o paciente não precisa marcar duas coisas.
            if ($meta->tipo === 'quantidade' && $valor !== null && $meta->alvo) {
                $concluida = (float) $valor >= $meta->alvo;
            }

            MetaRegistro::updateOrCreate(
                ['meta_id' => $meta->id, 'data' => $dados['data']],
                ['concluida' => $concluida, 'valor' => $valor]
            );
        }

        return back()->with('success', 'Metas do dia registradas!');
    }

    /** Biblioteca de orientações liberada para este paciente. */
    public function orientacoes(string $token)
    {
        $paciente = $this->paciente($token);
        $orientacoes = $paciente->orientacoes()->get();

        return view('nutri.portal.orientacoes', compact('paciente', 'orientacoes', 'token'));
    }

    public function salvarCheckin(string $token, Request $request)
    {
        $paciente = $this->paciente($token);
        $dados = $request->validate([
            'data' => 'required|date',
            'peso' => 'nullable|numeric|min:0|max:500',
            'adesao' => 'nullable|integer|min:0|max:100',
            'humor' => 'nullable|string|max:30',
            'comentario' => 'nullable|string|max:1000',
        ]);
        $dados['paciente_id'] = $paciente->id;

        Checkin::updateOrCreate(
            ['paciente_id' => $paciente->id, 'data' => $dados['data']],
            $dados
        );

        return back()->with('success', 'Check-in enviado ao seu nutricionista!');
    }

    public function chat(string $token)
    {
        $paciente = $this->paciente($token);
        MensagemNutri::where('paciente_id', $paciente->id)
            ->where('remetente', 'nutri')->whereNull('lida_em')->update(['lida_em' => now()]);

        $mensagens = MensagemNutri::where('paciente_id', $paciente->id)->orderBy('created_at')->get();

        return view('nutri.portal.chat', compact('paciente', 'mensagens', 'token'));
    }

    public function enviarChat(string $token, Request $request)
    {
        $paciente = $this->paciente($token);
        $dados = $request->validate(['texto' => 'required|string|max:2000']);

        MensagemNutri::create([
            'paciente_id' => $paciente->id,
            'remetente' => 'paciente',
            'texto' => $dados['texto'],
        ]);

        return back();
    }

    /** Questionário pré-consulta (paciente responde antes do atendimento). */
    public function anamnese(string $token, Request $request)
    {
        $paciente = $this->paciente($token);
        $modelos = AnamneseModelo::where('personal_id', $paciente->personal_id)->get();

        // O questionário do paciente é escolhido explicitamente pelo profissional
        // (uso_portal). O `is_padrao` vale para a consulta e costuma ser o modelo
        // completo, longo demais para o paciente responder sozinho no celular.
        $modelo = $modelos->firstWhere('uso_portal', true)
            ?? $modelos->firstWhere('is_padrao', true)
            ?? $modelos->first();

        return view('nutri.portal.anamnese', compact('paciente', 'modelo', 'token'));
    }

    public function salvarAnamnese(string $token, Request $request)
    {
        $paciente = $this->paciente($token);
        $dados = $request->validate([
            'modelo_id' => 'nullable|integer',
            'respostas' => 'required|array',
        ]);

        AnamneseResposta::create([
            'paciente_id' => $paciente->id,
            'modelo_id' => $dados['modelo_id'] ?? null,
            'respostas' => AnamneseResposta::limpar($dados['respostas']),
            'origem' => 'pre_consulta',
            'preenchida_em' => now(),
        ]);

        return redirect()->route('portal.home', $token)
            ->with('success', 'Obrigado! Suas respostas foram enviadas ao nutricionista.');
    }
}
