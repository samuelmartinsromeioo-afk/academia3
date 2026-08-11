<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Agenda;
use App\Models\Avaliacao;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Pedido;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;

/**
 * Avaliações de serviço (nota + comentário) que os alunos deixam para
 * personais, academias, studios e lojas. Centraliza:
 *  - a regra de quem PODE avaliar (só quem realmente contratou/usou);
 *  - a coluna certa da tabela `avaliacoes` para cada tipo;
 *  - o bloco de dados (média, total, avaliações recentes, pode_avaliar,
 *    minha_avaliacao) que os endpoints de detalhe devolvem para o app.
 *
 * Espelha as validações do AvaliacaoController web, estendendo para academia
 * e loja, e mantém uma avaliação por cliente por serviço (updateOrCreate).
 */
trait AvaliaServicos
{
    /** Tipos aceitos → coluna FK correspondente em `avaliacoes`. */
    private array $colunaAvaliacao = [
        'personal' => 'personal_id',
        'academia' => 'academia_id',
        'studio'   => 'studio_id',
        'loja'     => 'loja_id',
    ];

    /**
     * O cliente já contratou/usou este serviço e portanto pode avaliá-lo?
     *  - personal: já realizou ao menos uma aula (agenda no passado);
     *  - academia: é aluno matriculado (academia_id do cliente);
     *  - studio: já realizou ao menos uma aula (agenda no passado);
     *  - loja: já fez ao menos um pedido pago/concluído.
     */
    protected function podeAvaliarServico(string $tipo, int $id, Cliente $cliente): bool
    {
        $hoje = now()->format('Y-m-d');

        return match ($tipo) {
            'personal' => Agenda::where('cliente_id', $cliente->id)
                ->where('personal_id', $id)
                ->where('cancelado', false)
                ->where('descricao', 'like', '%Aula agendada%')
                ->where('data', '<', $hoje)
                ->exists(),

            'academia' => (int) $cliente->academia_id === $id,

            'studio' => Agenda::where('cliente_id', $cliente->id)
                ->where('studio_id', $id)
                ->where('cancelado', false)
                ->where('tipo_aula', '!=', 'bloqueio')
                ->where('data', '<', $hoje)
                ->exists(),

            'loja' => Pedido::where('cliente_id', $cliente->id)
                ->where('loja_id', $id)
                ->whereIn('status', ['pago', 'concluido'])
                ->exists(),

            default => false,
        };
    }

    /** Registra/atualiza a avaliação do cliente para um serviço. */
    protected function salvarAvaliacaoServico(string $tipo, int $id, Cliente $cliente, int $nota, ?string $comentario): Avaliacao
    {
        $coluna = $this->colunaAvaliacao[$tipo];

        return Avaliacao::updateOrCreate(
            ['cliente_id' => $cliente->id, $coluna => $id],
            ['nota' => $nota, 'comentario' => $comentario]
        );
    }

    /**
     * Bloco de avaliações para as telas de detalhe. `$model` é a instância
     * (Personal/Academia/Studio/Loja) já carregada — usa a relação avaliacoes().
     * `$cliente` pode ser null (visitante sem sessão de aluno).
     */
    protected function blocoAvaliacoes(string $tipo, $model, ?Cliente $cliente): array
    {
        $coluna = $this->colunaAvaliacao[$tipo];

        $todas = Avaliacao::with('cliente:id,nome')
            ->where($coluna, $model->id)
            ->latest()
            ->get();

        $media = $todas->avg('nota');
        $minha = $cliente ? $todas->firstWhere('cliente_id', $cliente->id) : null;

        return [
            'media_avaliacao'  => $media ? round($media, 1) : null,
            'total_avaliacoes' => $todas->count(),
            'avaliacoes'       => $todas->take(20)->map(fn ($a) => [
                'nota'       => (int) $a->nota,
                'comentario' => $a->comentario,
                'cliente'    => $a->cliente?->nome,
                'data'       => $a->created_at?->toDateString(),
            ])->values(),
            'pode_avaliar'   => $cliente ? $this->podeAvaliarServico($tipo, (int) $model->id, $cliente) : false,
            'minha_avaliacao' => $minha ? ['nota' => (int) $minha->nota, 'comentario' => $minha->comentario] : null,
        ];
    }

    /** Model do serviço a partir do tipo (para validar existência/aprovação). */
    protected function modeloServico(string $tipo, int $id)
    {
        return match ($tipo) {
            'personal' => Personal::where('status', 'aprovado')->find($id),
            'academia' => Academia::where('status', 'aprovado')->find($id),
            'studio'   => Studio::where('status', 'aprovado')->find($id),
            'loja'     => Loja::where('status', 'aprovado')->find($id),
            default    => null,
        };
    }
}
