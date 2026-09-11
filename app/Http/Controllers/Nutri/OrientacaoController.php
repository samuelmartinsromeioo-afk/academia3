<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Orientacao;
use Illuminate\Http\Request;

/**
 * Biblioteca de orientações nutricionais do profissional. O texto é escrito uma
 * vez e anexado a quantos pacientes quiser — quem lê é o paciente, no portal.
 */
class OrientacaoController extends Controller
{
    use ResolveNutri;

    public function index(Request $request)
    {
        $nutri = $this->nutri();

        $categoria = $request->query('categoria');
        $orientacoes = Orientacao::where('personal_id', $nutri->id)
            ->when($categoria, fn ($q) => $q->where('categoria', $categoria))
            ->orderBy('categoria')->orderBy('titulo')
            ->get();

        // Semeia exemplos na primeira visita, como os modelos de anamnese fazem.
        if ($orientacoes->isEmpty() && ! $categoria) {
            foreach ($this->semente() as $s) {
                Orientacao::create(array_merge($s, ['personal_id' => $nutri->id]));
            }
            $orientacoes = Orientacao::where('personal_id', $nutri->id)->orderBy('categoria')->orderBy('titulo')->get();
        }

        $categorias = Orientacao::where('personal_id', $nutri->id)
            ->whereNotNull('categoria')->distinct()->pluck('categoria')->sort()->values();

        return view('nutri.orientacoes.index', compact('nutri', 'orientacoes', 'categorias', 'categoria'));
    }

    public function store(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'id' => 'nullable|integer',
            'titulo' => 'required|string|max:255',
            'conteudo' => 'required|string|max:20000',
            'categoria' => 'nullable|string|max:60',
        ]);

        $orientacao = ! empty($dados['id'])
            ? Orientacao::where('id', $dados['id'])->where('personal_id', $nutri->id)->firstOrFail()
            : new Orientacao(['personal_id' => $nutri->id]);

        $orientacao->fill([
            'titulo' => $dados['titulo'],
            'conteudo' => $dados['conteudo'],
            'categoria' => $dados['categoria'] ?: null,
        ])->save();

        return back()->with('success', 'Orientação salva na biblioteca.');
    }

    public function destroy(int $id)
    {
        $orientacao = Orientacao::where('id', $id)->where('personal_id', $this->nutri()->id)->firstOrFail();
        $orientacao->pacientes()->detach();
        $orientacao->delete();

        return back()->with('success', 'Orientação removida da biblioteca e dos pacientes.');
    }

    /** Define quais orientações este paciente enxerga no portal. */
    public function sincronizarPaciente(int $pid, Request $request)
    {
        $nutri = $this->nutri();
        $paciente = $this->pacienteDoNutri($pid);

        $dados = $request->validate([
            'orientacoes' => 'nullable|array',
            'orientacoes.*' => 'integer',
        ]);

        // Só aceita ids da própria biblioteca — sem isso dava para anexar a
        // orientação de outro profissional mandando o id na mão.
        $ids = Orientacao::where('personal_id', $nutri->id)
            ->whereIn('id', $dados['orientacoes'] ?? [])
            ->pluck('id');

        $paciente->orientacoes()->sync($ids);

        return back()->with('success', $ids->count().' orientação(ões) visível(is) para este paciente.');
    }

    /** Textos de partida, editáveis e apagáveis. */
    private function semente(): array
    {
        return [
            [
                'titulo' => 'Como montar o prato fora de casa',
                'categoria' => 'Rotina',
                'conteudo' => "Metade do prato de salada e legumes, um quarto de proteína (carne, frango, peixe ou ovo) e um quarto de carboidrato (arroz, batata, macarrão).\n\nEvite o segundo prato: espere 15 minutos antes de repetir — a saciedade demora a chegar.\n\nEm rodízio ou self-service, dê a primeira volta só olhando, sem servir. Você escolhe melhor.",
            ],
            [
                'titulo' => 'O que comer antes e depois do treino',
                'categoria' => 'Treino',
                'conteudo' => "Antes (30–60 min): carboidrato de fácil digestão — banana, tapioca, pão com geleia. Evite gordura e excesso de fibra, que atrasam o esvaziamento do estômago.\n\nDepois (até 2 h): proteína + carboidrato. Não precisa ser shake: arroz com frango resolve.\n\nTreino em jejum não queima mais gordura no fim do dia. O que conta é o total de calorias.",
            ],
            [
                'titulo' => 'Hidratação no dia a dia',
                'categoria' => 'Rotina',
                'conteudo' => "Meta geral: 35 ml por quilo de peso. Para 70 kg, cerca de 2,5 L por dia.\n\nDeixe uma garrafa à vista — o principal motivo de não beber água é esquecer, não falta de vontade.\n\nCafé e chá contam parcialmente. Refrigerante e álcool, não.",
            ],
            [
                'titulo' => 'Leitura de rótulo em 30 segundos',
                'categoria' => 'Compras',
                'conteudo' => "Olhe primeiro a LISTA DE INGREDIENTES, não a tabela. Ela vem em ordem de quantidade: se açúcar está nos três primeiros, o produto é doce.\n\nQuanto menor a lista, melhor.\n\nNa tabela, compare sempre por 100 g — a \"porção\" do fabricante muda de produto para produto justamente para confundir.",
            ],
            [
                'titulo' => 'Quando bater o plano não for possível',
                'categoria' => 'Comportamento',
                'conteudo' => "Um dia fora do plano não apaga a semana. O problema nunca é a refeição isolada, é o abandono que vem depois dela.\n\nNão compense pulando a próxima refeição: isso aumenta a fome e a chance de repetir o episódio.\n\nVolte na refeição seguinte, normalmente. Sem punição e sem treino extra para \"pagar\".",
            ],
        ];
    }
}
