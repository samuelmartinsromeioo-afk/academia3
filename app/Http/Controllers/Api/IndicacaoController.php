<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CupomService;
use Illuminate\Http\Request;

/**
 * Programa "Indique e ganhe" no APP — espelha IndicacaoController (web), com
 * UMA ausência deliberada: **não há solicitação de saque aqui**.
 *
 * O saque é o caminho mais perigoso do sistema (transferência Pix saindo do
 * saldo da própria plataforma, protegida por sete camadas e por um webhook de
 * autorização fail-closed). Abrir essa porta numa segunda superfície exige
 * replicar todas elas, e isso é um trabalho isolado — não um efeito colateral
 * da paridade de telas. Até lá o app mostra o saldo e manda sacar no site.
 *
 * Nada aqui escreve valor: `painel` só lê, e `validar` é consulta pública.
 */
class IndicacaoController extends Controller
{
    public function __construct(private CupomService $cupons)
    {
    }

    /**
     * GET /api/v1/cupom/validar?codigo=XXXX — checagem ao vivo no cadastro.
     *
     * Pública como no web (quem está se cadastrando não tem token), e por isso
     * com throttle na rota: sem ele a rota viraria um oráculo para enumerar
     * códigos válidos de terceiros.
     */
    public function validar(Request $request)
    {
        $request->validate(['codigo' => 'nullable|string|max:40']);

        $cupom = $this->cupons->buscar($request->query('codigo'));

        if (! $cupom || ! $cupom->estaValido()) {
            return response()->json([
                'valido' => false,
                'mensagem' => config('indicacao.invalido'),
            ]);
        }

        $nome = $cupom->nomeDono();

        return response()->json([
            'valido' => true,
            'codigo' => $cupom->codigo,
            // Só o primeiro nome: confirma para quem digitou que é a pessoa
            // certa sem expor o nome completo de outra conta a um desconhecido.
            'mensagem' => $nome
                ? 'Código válido — indicado por ' . strtok(trim($nome), ' ') . '.'
                : 'Código válido!',
        ]);
    }

    /**
     * GET /api/v1/indicacoes — painel do indicador.
     *
     * Reavalia antes de responder (abre janelas de quem foi aprovado, apura o
     * acumulado e libera o que já passou pelos dois portões), pelo mesmo motivo
     * do web: a apuração é uma VARREDURA, não um gancho no pagamento, então sem
     * reavaliar aqui o painel mostraria um valor velho. É idempotente.
     */
    public function painel(Request $request)
    {
        $usuario = $request->user();
        $cupom = $this->cupons->cupomDe($usuario);

        $this->cupons->reavaliarDoIndicador($usuario);

        // `creditos` alimenta o extrato de cada indicado; sem o eager load é
        // uma query por linha da lista.
        $indicacoes = $usuario->indicacoesFeitas()
            ->with(['usuario', 'creditos' => fn ($q) => $q->orderBy('ocorreu_em')])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'codigo' => $cupom->codigo,
            'link_convite' => route('cadastro.SelecaoCadastro', ['cupom' => $cupom->codigo]),

            'saldo' => [
                // Já liberado e sem saque vinculado — é o que dá para sacar.
                'disponivel' => (float) $usuario->saldoDisponivel(),
                // Ainda acumulando ou esperando a meta do indicado.
                'pendente' => (float) $usuario->bonusPendente(),
                'em_saque' => (float) $usuario->bonusEmSaque(),
                'sacado' => (float) $usuario->bonusSacado(),
            ],

            /*
             * As regras vão para o app em vez de serem escritas na tela: o
             * percentual, a janela e a meta são configuráveis
             * (config/indicacao.php) e um texto fixo no app começaria a mentir
             * no dia em que qualquer um deles mudasse — e app leva dias para
             * atualizar na loja.
             */
            'regras' => [
                'percentual' => (float) config('indicacao.percentual', 0.10),
                'janela_dias' => (int) config('indicacao.janela_dias', 35),
                'meta_alunos' => (int) config('indicacao.meta_alunos', 6),
                'saque_minimo' => (float) config('indicacao.saque_minimo', 20.00),
                // A comissão da plataforma: a BASE sobre a qual o percentual
                // incide. O bônus é 10% da comissão, não do faturamento bruto.
                'taxa_plataforma' => \App\Services\AsaasService::feeRate(),
            ],

            // O app não solicita saque (ver o cabeçalho da classe); manda para
            // o site, e precisa saber se há pedido em aberto para não oferecer
            // um segundo.
            'saque' => [
                'disponivel_no_app' => false,
                'tem_em_aberto' => $usuario->temSaqueEmAberto(),
                'url_site' => route('indicacoes.painel'),
            ],

            'total' => $usuario->totalIndicacoes(),

            'indicados' => $indicacoes->map(fn ($uso) => [
                'id' => $uso->id,
                // Primeiro nome: a lista é do indicador, mas ainda é dado de
                // outra conta.
                'nome' => $uso->usuario?->nome ? strtok(trim($uso->usuario->nome), ' ') : 'Conta removida',
                'tipo' => $uso->tipoLabel(),
                'situacao' => $uso->situacao(),
                'status' => $uso->status,
                'gera_bonus' => $uso->geraBonus(),
                'bonus_acumulado' => (float) $uso->bonus_valor,
                'entrou_em' => $uso->created_at?->toIso8601String(),
                'janela_fim' => $uso->janela_fim?->toIso8601String(),
                'dias_restantes' => $uso->diasRestantes(),
                'janela_aberta' => $uso->janelaAberta(),
                'alunos' => $uso->alunosDoIndicado(),
                // Extrato: de quais receitas o valor saiu, para o total ser
                // auditável antes de sacar.
                'extrato' => $uso->creditos->map(fn ($c) => [
                    'em' => $c->ocorreu_em?->toIso8601String(),
                    'origem' => $c->origemLabel(),
                    'bruto' => $c->bruto_valor !== null ? (float) $c->bruto_valor : null,
                    'comissao' => (float) $c->base_valor,
                    'seu_valor' => (float) $c->valor,
                ]),
            ]),
        ]);
    }
}
