<?php

namespace Tests\Feature;

use App\Services\AsaasService;
use App\Support\CadastroHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Os documentos legais têm de descrever o sistema que existe.
 *
 * Este arquivo nasceu de um defeito real e silencioso: a Política de Privacidade
 * afirmou por meses que a idade mínima do aluno era **14 anos**, enquanto
 * `CadastroHelper::IDADE_MINIMA_ALUNO` recusava qualquer pessoa com menos de 18.
 * Nada quebrava, nenhum teste falhava e ninguém via — porque nada comparava o
 * texto com a regra.
 *
 * O prejuízo desse tipo de divergência não é estético. Um documento que descreve
 * errado o próprio sistema deixa de proteger quem o escreveu e passa a servir de
 * prova contra ele: se o texto público prometia aceitar 14 anos, é o texto que o
 * consumidor invoca, não a constante no código. O mesmo vale para a comissão e
 * para o valor mínimo de cobrança — números que a pessoa lê para decidir se
 * contrata.
 *
 * Por isso os testes abaixo não conferem redação: conferem que **o número
 * exibido é o número aplicado**. Toda asserção é escrita em função da fonte da
 * verdade (constante ou config), nunca do literal, de modo que mudar a regra
 * continue passando e mudar só o texto falhe.
 */
class DocumentosLegaisTest extends TestCase
{
    use DatabaseTransactions;

    /** Todas as páginas legais públicas. */
    private const ROTAS = [
        'termos',
        'termos.aluno',
        'termos.personal',
        'termos.academia',
        'termos.studio',
        'termos.loja',
        'lgpd.politica',
    ];

    private function html(string $rota): string
    {
        return $this->get(route($rota))->assertOk()->getContent();
    }

    /** Formata como as views formatam, para a comparação ser do mesmo texto. */
    private function reais(float $v): string
    {
        return 'R$ '.number_format($v, 2, ',', '.');
    }

    private function pct(float $f): string
    {
        return rtrim(rtrim(number_format($f * 100, 2, ',', '.'), '0'), ',').'%';
    }

    // ── As páginas existem e abrem sem autenticação ──────────────────────

    public function test_todos_os_documentos_legais_sao_publicos(): void
    {
        foreach (self::ROTAS as $rota) {
            $this->get(route($rota))->assertOk();
        }
    }

    // ── Idade mínima: o defeito que originou este arquivo ────────────────

    /**
     * A idade exibida é a que o cadastro aplica.
     *
     * Escrito a partir das constantes: se a idade mínima mudar, este teste
     * continua válido e passa a exigir o número novo nos documentos.
     */
    public function test_idade_minima_dos_documentos_bate_com_o_cadastro(): void
    {
        $aluno = CadastroHelper::IDADE_MINIMA_ALUNO;
        $prof = CadastroHelper::IDADE_MINIMA_PROFISSIONAL;

        $termos = $this->html('termos');
        $this->assertStringContainsString("{$aluno} anos", $termos);
        $this->assertStringContainsString("{$prof} anos", $termos);

        $this->assertStringContainsString("{$aluno} anos", $this->html('termos.aluno'));
        $this->assertStringContainsString("{$prof} anos", $this->html('termos.personal'));

        $politica = $this->html('lgpd.politica');
        $this->assertStringContainsString("{$aluno} anos", $politica);
        $this->assertStringContainsString("{$prof} anos", $politica);
    }

    /**
     * E o número ERRADO não pode estar em nenhum documento.
     *
     * Esta é a metade que faltava: afirmar o número certo não impede que o
     * antigo tenha sobrado numa frase esquecida — foi exatamente o que
     * aconteceu, com "14 anos" convivendo com a regra dos 18.
     */
    public function test_nenhum_documento_cita_idade_minima_diferente_da_regra(): void
    {
        $permitidas = [
            CadastroHelper::IDADE_MINIMA_ALUNO,
            CadastroHelper::IDADE_MINIMA_PROFISSIONAL,
        ];

        foreach (self::ROTAS as $rota) {
            $html = $this->html($rota);

            // Procura qualquer "N anos" / "N (dezoito) anos" usado como idade.
            preg_match_all('/(\d{1,2})\s*(?:\([^)]*\)\s*)?anos/u', $html, $m);

            foreach ($m[1] as $numero) {
                $n = (int) $numero;

                // 12 e 110 aparecem em prazos ("12 meses", "110 anos" na faixa
                // de nascimento) e não são idade mínima — a heurística se limita
                // à faixa plausível de maioridade para não gerar falso positivo.
                if ($n < 13 || $n > 25) {
                    continue;
                }

                $this->assertContains(
                    $n,
                    $permitidas,
                    "A rota [{$rota}] cita \"{$n} anos\" como idade, mas o cadastro aplica "
                        .implode(' e ', $permitidas).'. Documento e código têm de dizer o mesmo.'
                );
            }
        }
    }

    // ── Comissão e valor mínimo ──────────────────────────────────────────

    /**
     * A taxa exibida é a que o sistema cobra.
     *
     * `company_fee` é a base do bônus de indicação e o acréscimo que o aluno
     * paga: o documento que anuncia um percentual diferente do cobrado é
     * publicidade enganosa (art. 37 do CDC), não erro de redação.
     */
    public function test_taxa_de_intermediacao_exibida_bate_com_a_cobrada(): void
    {
        $taxa = $this->pct(AsaasService::feeRate());

        foreach (['termos', 'termos.aluno', 'termos.personal', 'termos.academia'] as $rota) {
            $this->assertStringContainsString(
                $taxa,
                $this->html($rota),
                "A rota [{$rota}] tem de exibir a taxa vigente ({$taxa}), lida de AsaasService::feeRate()."
            );
        }
    }

    /** O valor mínimo de cobrança é derivado — e o documento mostra o derivado. */
    public function test_valor_minimo_exibido_bate_com_o_aplicado(): void
    {
        $minimo = $this->reais(AsaasService::valorMinimo());

        $this->assertStringContainsString($minimo, $this->html('termos'));
        $this->assertStringContainsString($minimo, $this->html('termos.personal'));
    }

    /**
     * O exemplo de preço dos documentos é CALCULADO, não escrito à mão.
     *
     * Um "R$ 110,00" cravado começa a mentir no dia em que a comissão muda, e
     * ninguém abre as seis páginas legais ao editar um config.
     */
    public function test_exemplo_de_preco_acompanha_a_conta_real(): void
    {
        $conta = AsaasService::detalhar(100.00, 'PIX');

        foreach (['termos', 'termos.aluno', 'termos.personal'] as $rota) {
            $this->assertStringContainsString(
                $this->reais($conta['total']),
                $this->html($rota),
                "A rota [{$rota}] tem de exibir o valor final do exemplo calculado por AsaasService::detalhar()."
            );
        }
    }

    // ── Programa de indicação ────────────────────────────────────────────

    /** Percentual, janela e meta vêm do config que a apuração usa. */
    public function test_regras_da_indicacao_batem_com_o_config(): void
    {
        $html = $this->html('termos');

        $this->assertStringContainsString($this->pct((float) config('indicacao.percentual')), $html);
        $this->assertStringContainsString((string) config('indicacao.janela_dias').' dias', $html);
        $this->assertStringContainsString((string) config('indicacao.meta_alunos').' alunos', $html);
        $this->assertStringContainsString($this->reais((float) config('indicacao.saque_minimo')), $html);
    }

    // ── Versão e vigência ────────────────────────────────────────────────

    /**
     * A data exibida é a de VIGÊNCIA, não a de hoje.
     *
     * As views imprimiam `date('d/m/Y')`, de modo que o documento afirmava ter
     * sido atualizado no dia em que a pessoa o abria. Num contrato, é uma
     * afirmação falsa sobre a própria vigência — e é justamente a vigência que
     * importa quando se discute qual versão alguém aceitou.
     */
    public function test_documentos_exibem_a_vigencia_e_nao_a_data_de_hoje(): void
    {
        $vigencia = \Carbon\Carbon::parse(config('termos.vigente_desde'))->format('d/m/Y');
        $hoje = now()->format('d/m/Y');

        foreach (self::ROTAS as $rota) {
            $html = $this->html($rota);

            $this->assertStringContainsString(
                $vigencia,
                $html,
                "A rota [{$rota}] tem de exibir a data de vigência dos termos."
            );
            $this->assertStringContainsString(
                (string) config('termos.versao'),
                $html,
                "A rota [{$rota}] tem de exibir a versão vigente."
            );

            // Se hoje != vigência, a data de hoje não pode aparecer como
            // "atualização" — era o sintoma exato do defeito.
            if ($hoje !== $vigencia) {
                $this->assertStringNotContainsString(
                    "atualização: {$hoje}",
                    $html,
                    "A rota [{$rota}] voltou a imprimir a data de hoje como data de atualização."
                );
            }
        }
    }

    // ── Identificação do titular ─────────────────────────────────────────

    /**
     * Os Termos e a Política exibem a qualificação do titular.
     *
     * O art. 2º do Decreto nº 7.962/2013 exige nome empresarial e CNPJ em
     * destaque, e o art. 41 da LGPD exige a identidade do Encarregado. Os três
     * saem de `config/empresa.php` e o teste cobra o **valor**, não a palavra
     * "CNPJ": a asserção antiga passava com o campo vazio ao lado do rótulo.
     *
     * Endereço físico e telefone saíram dos documentos por decisão do titular —
     * ver o bloco que registra isso em `config/empresa.php`. Por isso não são
     * cobrados aqui.
     */
    public function test_documentos_identificam_o_titular(): void
    {
        foreach (['termos', 'lgpd.politica'] as $rota) {
            $html = $this->html($rota);

            foreach (['razao_social', 'cnpj', 'dpo_nome', 'email_suporte'] as $campo) {
                $this->assertStringContainsString(
                    \App\Support\Empresa::get($campo),
                    $html,
                    "A rota [{$rota}] tem de exibir `empresa.{$campo}`."
                );
            }
        }
    }

    /**
     * Nenhum documento público pode exibir um placeholder `[ASSIM]`.
     *
     * É o que substitui a trava da minuta. A convenção do colchete continua
     * valendo em `config/empresa.php` para quem for acrescentar um campo novo, e
     * este teste é o que impede a lacuna de chegar ao documento em vigor — antes
     * o aviso de minuta a tornava tolerável, e agora ele não existe mais.
     */
    public function test_nenhum_documento_publica_lacuna_entre_colchetes(): void
    {
        $this->assertSame(
            [],
            \App\Support\Empresa::lacunas(),
            'A identificação do titular está incompleta em config/empresa.php.'
        );

        foreach (self::ROTAS as $rota) {
            // Só o miolo do documento: o HTML de volta carrega CSS e JS com
            // colchetes legítimos (seletores de atributo, arrays).
            $texto = strip_tags($this->html($rota));

            $this->assertDoesNotMatchRegularExpression(
                '/\[[A-ZÀ-Ú][A-ZÀ-Ú0-9 ,\/º°\-]*\]/u',
                $texto,
                "A rota [{$rota}] está publicando uma lacuna entre colchetes."
            );
        }
    }

    // ── Coerência entre documentos ───────────────────────────────────────

    /**
     * Nenhum documento pode descrever o modelo ANTIGO de cobrança.
     *
     * A comissão deixou de ser desconto e passou a ser acréscimo. Uma frase
     * sobrevivente do modelo antigo ("retém sua taxa e repassa o restante")
     * contradiz o que o sistema faz e o que os outros documentos afirmam — e a
     * contradição interna de um contrato é interpretada em favor do
     * consumidor/aderente (art. 47 do CDC e art. 423 do Código Civil).
     */
    public function test_nenhum_documento_descreve_o_modelo_antigo_de_comissao(): void
    {
        $proibidos = [
            'retém sua taxa e o restante é repassado',
            'retém sua taxa e o restante',
            '90/10',
            'repasse automático de 90%',
            'split 90/10',
        ];

        foreach (self::ROTAS as $rota) {
            $html = $this->html($rota);

            foreach ($proibidos as $frase) {
                $this->assertStringNotContainsString(
                    $frase,
                    $html,
                    "A rota [{$rota}] ainda descreve o modelo antigo (\"{$frase}\"), em que a comissão "
                        .'era descontada do profissional. Hoje ela é acrescida ao preço e cobrada do aluno.'
                );
            }
        }
    }

    /** Toda página legal aponta para a Política e para os Termos gerais. */
    public function test_documentos_se_referenciam_entre_si(): void
    {
        foreach (['termos.aluno', 'termos.personal', 'termos.academia', 'termos.studio', 'termos.loja'] as $rota) {
            $html = $this->html($rota);

            $this->assertStringContainsString(route('termos'), $html);
            $this->assertStringContainsString(route('lgpd.politica'), $html);
            $this->assertStringContainsString(
                route('termos').'#s9',
                $html,
                "A rota [{$rota}] tem de apontar para a cláusula 9 (Programa de indicação) dos Termos gerais."
            );
        }
    }
}
