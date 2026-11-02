<?php

namespace Tests\Feature;

use App\Models\AvaliacaoFisica;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Support\ResumoAvaliacao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * O "Resumo" da avaliação física — a leitura dos números, não os números.
 *
 * O site tem essa aba desde sempre: um card por categoria com o valor mais
 * recente e, onde existe referência, uma classificação (Ótimo…Péssimo). O app
 * não tinha nada — listava registros crus. A regra vivia em cinco métodos
 * PRIVADOS do controller web e o mapa de cores estava copiado em TRÊS blades.
 *
 * Estes testes prendem as três coisas que a extração poderia ter quebrado:
 *
 *  1. o conteúdo dos cards continua o mesmo (a extração foi um movimento, não
 *     uma reescrita);
 *  2. a API entrega o MESMO que a fonte compartilhada calcula — é o que impede
 *     o app e o site de voltarem a divergir;
 *  3. as faixas de classificação, que são o único ponto onde o sistema diz algo
 *     sobre a saúde de alguém, continuam nos limites escritos.
 */
class ResumoAvaliacaoTest extends TestCase
{
    use DatabaseTransactions;

    private function aluno(): Cliente
    {
        return Cliente::create([
            'nome' => 'Aluno Resumo',
            'email' => 'resumo'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'),
        ]);
    }

    private function personal(): Personal
    {
        $cpf = str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);

        return Personal::create([
            'nome' => 'PT Resumo', 'email' => 'ptres'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'), 'cpf' => $cpf,
            'cref' => '777888-G/PR', 'idade' => '1990-01-01',
            'valor_secao' => 100, 'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'complemento' => '1', 'foto' => '',
        ]);
    }

    private function registro(Cliente $c, string $tipo, array $dados = [], ?string $data = null): AvaliacaoFisica
    {
        return AvaliacaoFisica::create(array_merge([
            'cliente_id' => $c->id,
            'tipo' => $tipo,
            'data_avaliacao' => $data ?: now()->toDateString(),
        ], $dados));
    }

    private function token(Cliente $c): array
    {
        return [
            'Authorization' => 'Bearer '.$c->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    // ── 1. O conteúdo dos cards ─────────────────────────────────────────

    public function test_oximetro_traz_valor_e_classificacao(): void
    {
        $c = $this->aluno();
        $this->registro($c, 'oximetro', ['spo2' => 98, 'bpm' => 62]);

        $res = $this->getJson('/api/v1/avaliacoes/resumo', $this->token($c));

        $res->assertOk();
        $card = collect($res->json('resumo'))->firstWhere('tipo', 'oximetro');

        $this->assertNotNull($card);
        $this->assertSame('98% SpO2 · 62 bpm', $card['valor']);
        $this->assertSame('otimo', $card['classificacao']);
        $this->assertSame('Oxímetro', $card['tipo_label'] ?? 'Oxímetro');
    }

    /**
     * A variação de peso usa o PERÍODO INTEIRO — primeiro contra último.
     *
     * É a razão de o resumo ter endpoint próprio em vez de ser um campo do
     * index, que pagina em 20: sobre uma página a variação daria um número
     * menor, plausível, e ninguém desconfiaria.
     */
    public function test_variacao_de_peso_usa_o_periodo_inteiro(): void
    {
        $c = $this->aluno();
        $this->registro($c, 'antes_depois', ['peso' => 80], now()->subMonths(3)->toDateString());
        $this->registro($c, 'antes_depois', ['peso' => 76], now()->subMonth()->toDateString());
        $this->registro($c, 'antes_depois', ['peso' => 74], now()->toDateString());

        $res = $this->getJson('/api/v1/avaliacoes/resumo', $this->token($c));
        $card = collect($res->json('resumo'))->firstWhere('tipo', 'antes_depois');

        // Valor = peso ATUAL; detalhe = variação desde o primeiro registro.
        $this->assertSame('74,0 kg', $card['valor']);
        $this->assertStringContainsString('-6,0 kg', $card['detalhe']);
    }

    /** Categoria sem o dado que a define é OMITIDA, não vira card vazio. */
    public function test_categoria_sem_dado_nao_vira_card(): void
    {
        $c = $this->aluno();
        $this->registro($c, 'oximetro', ['spo2' => null]);

        $res = $this->getJson('/api/v1/avaliacoes/resumo', $this->token($c));

        $this->assertSame([], $res->json('resumo'));
    }

    // ── 2. API e fonte compartilhada contam a mesma história ────────────

    /**
     * O payload da API bate com `ResumoAvaliacao::montar()` na MESMA linha.
     *
     * Comparar com valores escritos à mão no teste deixaria passar o caso em
     * que a API serializa diferente do que o site renderiza — que é exatamente
     * a divergência que a extração veio impedir.
     */
    public function test_api_entrega_o_mesmo_que_a_fonte_compartilhada(): void
    {
        $c = $this->aluno();
        $this->registro($c, 'dinamometro', ['forca' => 38.5]);
        $this->registro($c, 'dobras', ['percentual_gordura' => 17.4, 'massa_magra' => 60.2]);

        $registros = AvaliacaoFisica::where('cliente_id', $c->id)
            ->orderByDesc('data_avaliacao')->orderByDesc('id')->get();
        $daFonte = ResumoAvaliacao::montar($registros);

        $daApi = collect($this->getJson('/api/v1/avaliacoes/resumo', $this->token($c))->json('resumo'))
            ->keyBy('tipo');

        $this->assertCount(count($daFonte), $daApi);

        foreach ($daFonte as $tipo => $item) {
            $this->assertSame($item['valor'], $daApi[$tipo]['valor'], "valor de {$tipo}");
            $this->assertSame($item['detalhe'], $daApi[$tipo]['detalhe'], "detalhe de {$tipo}");
            $this->assertSame($item['classificacao'], $daApi[$tipo]['classificacao'], "classificação de {$tipo}");
        }
    }

    /** O mapa de rótulo/cor vai no payload, para o app não embutir cópia. */
    public function test_classificacoes_vao_no_payload(): void
    {
        $c = $this->aluno();
        $this->registro($c, 'oximetro', ['spo2' => 91]);

        $res = $this->getJson('/api/v1/avaliacoes/resumo', $this->token($c));

        $this->assertSame(ResumoAvaliacao::CLASSIFICACOES, $res->json('classificacoes'));
        // E a classificação devolvida tem de existir no mapa, senão o app
        // desenharia um badge sem rótulo nem cor.
        $card = collect($res->json('resumo'))->firstWhere('tipo', 'oximetro');
        $this->assertArrayHasKey($card['classificacao'], $res->json('classificacoes'));
    }

    // ── 3. As faixas, nos limites ───────────────────────────────────────

    /**
     * @dataProvider faixasSpo2
     */
    public function test_faixas_de_spo2(int $spo2, string $esperado): void
    {
        $this->assertSame($esperado, ResumoAvaliacao::classificarSpo2($spo2));
    }

    public static function faixasSpo2(): array
    {
        return [
            'limite ótimo' => [97, 'otimo'],
            'um abaixo do ótimo' => [96, 'bom'],
            'limite bom' => [95, 'bom'],
            'limite normal' => [93, 'normal'],
            'limite ruim' => [90, 'ruim'],
            'abaixo de tudo' => [89, 'pessimo'],
        ];
    }

    /**
     * @dataProvider faixasPressao
     */
    public function test_faixas_de_pressao(int $sis, int $dia, string $esperado): void
    {
        $this->assertSame($esperado, ResumoAvaliacao::classificarPressao($sis, $dia));
    }

    public static function faixasPressao(): array
    {
        return [
            'ótima' => [110, 70, 'otimo'],
            'limiar do bom pela sistólica' => [121, 70, 'bom'],
            'limiar do bom pela diastólica' => [110, 81, 'bom'],
            'normal' => [130, 70, 'normal'],
            'ruim' => [140, 70, 'ruim'],
            // Qualquer uma das duas acima do limite já puxa a pior faixa: a
            // classificação é pelo PIOR dos dois valores, não pela média.
            'péssima só pela diastólica' => [120, 100, 'pessimo'],
        ];
    }

    /**
     * @dataProvider faixasForca
     */
    public function test_faixas_de_forca(float $forca, string $esperado): void
    {
        $this->assertSame($esperado, ResumoAvaliacao::classificarForca($forca));
    }

    public static function faixasForca(): array
    {
        return [
            [45.0, 'otimo'],
            [44.9, 'bom'],
            [35.0, 'bom'],
            [25.0, 'normal'],
            [15.0, 'ruim'],
            [14.9, 'pessimo'],
        ];
    }

    // ── Posse ───────────────────────────────────────────────────────────

    /** Personal sem `cliente_id` é recusado: misturaria alunos num só quadro. */
    public function test_personal_precisa_informar_o_aluno(): void
    {
        $p = $this->personal();

        $this->getJson('/api/v1/avaliacoes/resumo', [
            'Authorization' => 'Bearer '.$p->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ])->assertStatus(422);
    }

    /** O aluno só vê o próprio resumo, nunca o de outro. */
    public function test_aluno_nao_ve_resumo_alheio(): void
    {
        $meu = $this->aluno();
        $outro = $this->aluno();
        $this->registro($outro, 'oximetro', ['spo2' => 98]);

        $res = $this->getJson('/api/v1/avaliacoes/resumo', $this->token($meu));

        $res->assertOk();
        $this->assertSame([], $res->json('resumo'));
    }

    /** Academia não tem avaliação física — fail-closed, como no index. */
    public function test_academia_recebe_403(): void
    {
        $a = Academia::create([
            'nome' => 'Acad Resumo',
            'email' => 'acadres'.uniqid().'@teste.local',
            'senha' => bcrypt('senha12345'),
            'cnpj' => str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT),
            'status' => 'aprovado',
            'cep' => '87000-000', 'rua' => 'R', 'bairro' => 'B',
            'cidade' => 'Maringá', 'estado' => 'PR', 'endereco' => 'R, 100',
        ]);

        $this->getJson('/api/v1/avaliacoes/resumo', [
            'Authorization' => 'Bearer '.$a->createToken('app')->plainTextToken,
            'Accept' => 'application/json',
        ])->assertStatus(403);
    }
}
