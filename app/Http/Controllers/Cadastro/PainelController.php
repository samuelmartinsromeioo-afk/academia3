<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\MedidaCorporal;
use App\Services\EstatisticasTreino;
use App\Services\NotificacaoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Painéis analíticos do personal: feed de atividade dos alunos e relatório
 * mensal por aluno. Tudo derivado dos dados já existentes.
 */
class PainelController extends Controller
{
    private const DIAS_SUMIDO = 7;

    // ===================== FEED =====================
    public function feed()
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }

        $alunoIds = $this->alunoIds($personalId);
        $fichaAlunoIds = FichaTreino::where('personal_id', $personalId)->where('ativo', true)->pluck('cliente_id')->unique();

        $desde = now()->subDays(14)->toDateString();

        $atividades = TreinoConcluido::with(['ficha', 'cliente'])
            ->whereIn('cliente_id', $alunoIds)
            ->where('concluido', true)
            ->where('data_treino', '>=', $desde)
            ->orderByDesc('data_treino')->orderByDesc('id')
            ->limit(40)->get();

        $recordes = $this->recordesRecentes($alunoIds, 14);

        $hoje = now()->toDateString();
        $treinosHoje = $atividades->where('data_treino', '>=', $hoje)->count();

        $sumidos = 0;
        foreach ($fichaAlunoIds as $cid) {
            $ult = TreinoConcluido::where('cliente_id', $cid)->where('concluido', true)->max('data_treino');
            if (! $ult || Carbon::parse($ult)->diffInDays(now()) >= self::DIAS_SUMIDO) {
                $sumidos++;
            }
        }

        return view('personal.Feed', compact('atividades', 'recordes', 'treinosHoje', 'sumidos'));
    }

    // ===================== RELATÓRIO =====================
    public function relatorio($clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }
        if (! $this->podeVer($personalId, $clienteId)) {
            return redirect()->route('fichas-treino.alunos')->with('error', 'Acesso negado!');
        }

        $cliente = Cliente::findOrFail($clienteId);
        $dados = $this->dadosRelatorio($personalId, $clienteId);

        return view('personal.Relatorio', array_merge(['cliente' => $cliente], $dados));
    }

    public function enviarRelatorio($clienteId)
    {
        $personalId = session('personal_id');
        if (! $personalId) {
            return redirect()->route('login.index');
        }
        if (! $this->podeVer($personalId, $clienteId)) {
            return redirect()->route('fichas-treino.alunos')->with('error', 'Acesso negado!');
        }

        $cliente = Cliente::find($clienteId);
        $d = $this->dadosRelatorio($personalId, $clienteId);
        $nome = explode(' ', trim($cliente->nome))[0];

        $linhasRec = collect($d['recordes'])->take(5)
            ->map(fn ($r) => '• ' . $r['exercicio'] . ': ' . $this->kg($r['peso']))->implode("\n");

        $texto = "📊 *Seu resumo do mês, {$nome}!*\n\n"
            . "✅ Treinos: *{$d['realizados']}* de {$d['planejados']} planejados ({$d['aderencia']}%)\n"
            . "🔥 Sequência atual: *{$d['streak']['atual']}* dias (recorde {$d['streak']['recorde']})\n"
            . ($d['rpeMedio'] ? "💪 Esforço médio: {$d['rpeMedio']}/10\n" : '')
            . ($linhasRec ? "\n🏆 *Recordes do mês:*\n{$linhasRec}\n" : '')
            . "\nBora manter o ritmo! 🚀";

        NotificacaoService::cliente($cliente, 'Seu resumo do mês — SnrFit 📊', $texto, 'relatorio_mensal', [$nome]);

        return back()->with('success', 'Relatório enviado para ' . ($cliente->nome ?? 'o aluno') . '! 📨');
    }

    // ===================== CÁLCULOS =====================
    private function dadosRelatorio($personalId, $clienteId): array
    {
        $inicio = now()->startOfMonth();
        $hoje = now();

        $fichas = FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('ativo', true)->get();

        $planejados = 0;
        foreach ($fichas as $f) {
            $planejados += $this->ocorrenciasDiaSemana((int) $f->dia_semana, $inicio, $hoje);
        }
        $fichaIds = $fichas->pluck('id');

        $realizados = $fichaIds->isEmpty() ? 0 : TreinoConcluido::whereIn('ficha_id', $fichaIds)
            ->where('cliente_id', $clienteId)->where('concluido', true)
            ->whereBetween('data_treino', [$inicio->toDateString(), $hoje->toDateString()])->count();

        $aderencia = $planejados > 0 ? min(100, (int) round($realizados / $planejados * 100)) : 0;

        $datas = TreinoConcluido::where('cliente_id', $clienteId)->where('concluido', true)
            ->orderBy('data_treino')->pluck('data_treino')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())->toArray();
        $streak = EstatisticasTreino::streak($datas);

        $rpeMedio = TreinoConcluido::where('cliente_id', $clienteId)->where('concluido', true)
            ->whereNotNull('rpe')
            ->whereBetween('data_treino', [$inicio->toDateString(), $hoje->toDateString()])->avg('rpe');
        $rpeMedio = $rpeMedio ? round($rpeMedio, 1) : null;

        // recordes do mês
        $recordes = collect($this->recordesRecentes([$clienteId], $hoje->day))
            ->filter(fn ($r) => $r['data']->gte($inicio))->values()->all();

        // medidas: primeira e última do mês (ou as duas últimas)
        $medidasMes = MedidaCorporal::where('cliente_id', $clienteId)
            ->whereBetween('data', [$inicio->toDateString(), $hoje->toDateString()])
            ->orderBy('data')->get();
        if ($medidasMes->count() < 2) {
            $medidasMes = MedidaCorporal::where('cliente_id', $clienteId)->orderBy('data')->get();
        }
        $pesoIni = $medidasMes->first()?->peso;
        $pesoFim = $medidasMes->last()?->peso;

        return compact('planejados', 'realizados', 'aderencia', 'streak', 'rpeMedio', 'recordes', 'pesoIni', 'pesoFim');
    }

    /** Recordes (PRs) batidos nos últimos N dias entre os alunos dados. */
    private function recordesRecentes($alunoIds, $dias): array
    {
        $desde = now()->subDays($dias)->toDateString();
        $regs = RegistroExercicio::whereIn('cliente_id', $alunoIds)->whereNotNull('peso')->where('peso', '>', 0)
            ->orderBy('cliente_id')->orderBy('nome_exercicio')->orderBy('data_treino')->orderBy('id')
            ->get(['cliente_id', 'nome_exercicio', 'peso', 'data_treino']);

        $maxAtual = [];
        $recordes = [];
        foreach ($regs as $r) {
            $key = $r->cliente_id . '|' . $r->nome_exercicio;
            $prev = $maxAtual[$key] ?? null;
            $peso = (float) $r->peso;
            if ($prev !== null && $peso > $prev && $r->data_treino->toDateString() >= $desde) {
                $recordes[] = ['cliente_id' => $r->cliente_id, 'exercicio' => $r->nome_exercicio, 'peso' => $peso, 'data' => $r->data_treino];
            }
            if ($prev === null || $peso > $prev) {
                $maxAtual[$key] = $peso;
            }
        }

        $nomes = Cliente::whereIn('id', collect($recordes)->pluck('cliente_id')->unique())->pluck('nome', 'id');
        foreach ($recordes as &$r) {
            $r['cliente'] = $nomes[$r['cliente_id']] ?? 'Aluno';
        }
        usort($recordes, fn ($a, $b) => $b['data'] <=> $a['data']);

        return array_slice($recordes, 0, 15);
    }

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

    private function kg($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',') . ' kg';
    }

    private function alunoIds($personalId)
    {
        return FichaTreino::where('personal_id', $personalId)->pluck('cliente_id')
            ->merge(Agenda::where('personal_id', $personalId)->where('cancelado', false)->whereNotNull('cliente_id')->pluck('cliente_id'))
            ->unique()->values();
    }

    private function podeVer($personalId, $clienteId): bool
    {
        return FichaTreino::where('personal_id', $personalId)->where('cliente_id', $clienteId)->exists()
            || Agenda::where('personal_id', $personalId)->where('cliente_id', $clienteId)->where('cancelado', false)->exists()
            || Cliente::where('id', $clienteId)->where('personal_id', $personalId)->exists();
    }
}
