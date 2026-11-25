<?php

namespace Tests\Feature;

use App\Support\Empresa;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Conformidade com o programa BaaS do Asaas.
 *
 * O "Playbook de BaaS para clientes Asaas" (recebido em 25/11/2026) traduz a
 * Resolução Conjunta nº 16/2025 do Banco Central em obrigações de INTERFACE, e
 * é por isso que elas cabem num teste de feature: o cliente final tem de saber
 * que os serviços financeiros são prestados pelo Asaas sempre que houver
 * movimentação ou gestão de valores, e o selo tem de estar visível nesses
 * fluxos.
 *
 * POR QUE ESTE ARQUIVO EXISTE: nada aqui quebra funcionalidade quando
 * desaparece. Um selo removido, uma cláusula "melhorada", um rótulo trocado de
 * volta para "Minha Carteira" — tudo isso passa em qualquer outro teste do
 * projeto, continua vendendo, continua cobrando, e ao mesmo tempo é
 * descumprimento de contrato com o prestador que detém o dinheiro dos usuários.
 * É a mesma classe de defeito do `SegurancaOwaspTest`: invisível ao uso,
 * grave na consequência.
 *
 * Os textos são afirmados por FRASE, não por arquivo: o que o programa exige é
 * o que o usuário lê na tela.
 */
class ConformidadeBaasAsaasTest extends TestCase
{
    /** Páginas legais, todas públicas (GET), que têm de trazer a cláusula. */
    private const ROTAS_LEGAIS = [
        'termos', 'termos.aluno', 'termos.personal',
        'termos.academia', 'termos.studio', 'termos.loja',
    ];

    /** Cadastros com subconta — "Criação de subconta" no playbook. */
    private const ROTAS_CADASTRO_SUBCONTA = [
        'form.personal', 'form.academia', 'form.studio', 'form.loja',
    ];

    /**
     * HTML da rota com os espaços em branco colapsados.
     *
     * Obrigatório para afirmar FRASE: o Blade quebra as linhas longas do
     * documento, então "integradora tecnológica e distribuidora da experiência
     * do produto" chega ao HTML com um `\n` e indentação no meio. Sem
     * normalizar, o teste passaria a depender de onde a linha foi quebrada no
     * arquivo — e quebraria na primeira reindentação, dizendo que a cláusula
     * desapareceu quando ela está lá.
     */
    private function textoDe(string $rota): string
    {
        $html = $this->get(route($rota))->assertOk()->getContent();

        return preg_replace('/\s+/u', ' ', $html);
    }

    public function test_os_seis_documentos_legais_trazem_a_clausula_modelo_do_asaas(): void
    {
        // As três afirmações do modelo de cláusula (playbook, slide 11). Não é
        // paráfrase: o playbook veda ao tomador alterar ou criar documento
        // legal sobre o processamento financeiro de pagamentos.
        $frases = [
            'incluindo abertura e manutenção de conta de pagamento',
            'instituição de pagamento autorizada a funcionar pelo',
            'integradora tecnológica e distribuidora da experiência do produto',
            'nem realizando intermediação financeira em nome próprio',
            'responsabilidade regulatória pelos serviços acima descritos',
        ];

        foreach (self::ROTAS_LEGAIS as $rota) {
            $html = $this->textoDe($rota);

            foreach ($frases as $frase) {
                $this->assertStringContainsString(
                    $frase,
                    $html,
                    "A cláusula de Prestação de Serviços Financeiros não saiu em {$rota}: falta \"{$frase}\"."
                );
            }

            $this->assertStringContainsString(
                Empresa::get('psp_razao_social'),
                $html,
                "O documento {$rota} não nomeia a instituição que presta o serviço financeiro."
            );
        }
    }

    /**
     * O rótulo que o playbook veda não pode voltar.
     *
     * "Subadquirente" e "facilitadora" nomeiam atividade de instituição de
     * pagamento. A SNR FIT não a exerce, não é autorizada a exercê-la, e
     * afirmá-la num documento é exatamente a vedação de "apresentar-se como
     * instituição financeira ou de pagamento em qualquer canal".
     */
    public function test_nenhum_documento_diz_que_a_plataforma_e_subadquirente(): void
    {
        foreach (self::ROTAS_LEGAIS as $rota) {
            $html = $this->textoDe($rota);

            foreach (['subadquirente', 'subadquirência', 'facilitadora de pagamento'] as $vedado) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $vedado,
                    $html,
                    "O documento {$rota} voltou a atribuir à Plataforma o papel de \"{$vedado}\"."
                );
            }
        }
    }

    public function test_as_telas_de_criacao_de_subconta_mostram_o_selo_e_dizem_de_quem_e_a_conta(): void
    {
        foreach (self::ROTAS_CADASTRO_SUBCONTA as $rota) {
            $html = $this->textoDe($rota);

            $this->assertStringContainsString('Serviços financeiros Asaas', $html,
                "A tela {$rota} abre conta de pagamento e não mostra o selo do Asaas.");
            $this->assertStringContainsString('conta de pagamento aberta e', $html,
                "A tela {$rota} não diz que a conta de recebimentos é aberta e mantida pelo Asaas.");
        }
    }

    /**
     * O cadastro de ALUNO não tem subconta — e não deve ganhar o bloco por
     * arrastão. Não é purismo: afirmar ali que "a sua conta de recebimentos é
     * aberta pelo Asaas" é informação falsa para quem só paga.
     */
    public function test_o_cadastro_de_aluno_nao_fala_de_conta_de_recebimentos(): void
    {
        $html = $this->textoDe('form.cliente');

        $this->assertStringNotContainsString('conta de pagamento aberta e', $html);
    }

    public function test_o_selo_abre_o_site_do_asaas_com_as_protecoes_que_o_playbook_pede(): void
    {
        $html = $this->textoDe('termos');

        // `noopener noreferrer` é a recomendação literal do playbook para o
        // link do selo; vale para a NAVEGAÇÃO, nunca para o carregamento da
        // imagem (ver o teste do referrer, abaixo).
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="'.preg_quote(Empresa::get('psp_site'), '/').'"[^>]*rel="noopener noreferrer"/',
            $html,
            'O link do selo perdeu o target/rel que o playbook recomenda.'
        );
    }

    /**
     * O Referer da IMAGEM do selo tem de continuar saindo.
     *
     * É assim que o Asaas verifica que a aplicação carregou o selo — o playbook
     * pede explicitamente que não se use política de referrer restritiva na
     * `<img>`. `no-referrer` no header quebraria essa verificação em todo o
     * site sem quebrar nada visível, que é o pior tipo de regressão.
     */
    public function test_o_header_de_referrer_nao_bloqueia_a_verificacao_do_selo(): void
    {
        $politica = $this->get(route('termos'))->assertOk()->headers->get('Referrer-Policy');

        $this->assertNotNull($politica, 'Sem Referrer-Policy declarada, o navegador decide por conta.');
        $this->assertNotSame('no-referrer', $politica,
            'Com `no-referrer` o Asaas não consegue verificar o carregamento do selo.');
        $this->assertStringNotContainsString('same-origin', $politica,
            '`same-origin` suprime o Referer em requisição cross-origin, que é o caso do selo.');
    }

    /**
     * Sem o host no `img-src`, o selo é BLOQUEADO pelo navegador assim que a
     * URL homologada for configurada — descumprimento silencioso, porque o
     * HTML continua "certo".
     */
    public function test_a_csp_permite_o_cdn_do_selo(): void
    {
        $this->assertContains('https://baas.asaas.com', config('csp.directives.img-src'),
            'O CDN do selo saiu do img-src: o selo passaria a ser bloqueado pelo navegador.');
    }

    /**
     * Configurada a URL homologada, é ELA que vai ao HTML — a cópia local é
     * fallback, não destino (só a URL do Asaas, com o `?id=`, permite a
     * verificação do lado dele).
     */
    public function test_a_url_homologada_do_asaas_substitui_a_copia_local_do_selo(): void
    {
        $url = 'https://baas.asaas.com/selos/Servicos_financeiros_Asaas-Reduzida-Positivo.svg?id=teste';
        config(['empresa.psp_selo_url' => $url]);

        $html = $this->textoDe('termos');

        $this->assertStringContainsString($url, $html);
    }

    public function test_a_central_de_suporte_publica_os_canais_do_asaas(): void
    {
        $html = $this->textoDe('suporte');

        $this->assertStringContainsString(Empresa::get('psp_suporte_email'), $html);
        $this->assertStringContainsString(Empresa::get('psp_suporte_telefone'), $html);

        // O 0800 é PJ. Divulgar o número sem a ressalva manda o aluno (pessoa
        // física) para uma ligação que não o atende.
        $this->assertStringContainsString('pessoa jurídica', $html,
            'O 0800 do Asaas é para pessoa jurídica e a página não diz isso.');
    }

    public function test_os_documentos_legais_dizem_que_o_asaas_pode_ser_acionado_direto(): void
    {
        $html = $this->textoDe('termos');

        $this->assertStringContainsString(Empresa::get('psp_suporte_email'), $html,
            'O documento não informa como acionar o prestador dos serviços financeiros.');
    }

    /**
     * Comunicações também contam ("todos os fluxos, páginas e comunicações").
     *
     * O rodapé é afirmado pelo que ele DIZ, e a ligação dele aos cinco e-mails
     * de dinheiro é afirmada pelo include — renderizar os cinco exigiria montar
     * Payment, Cliente e Personal só para ler um rodapé.
     */
    public function test_os_emails_de_dinheiro_atribuem_a_operacao_financeira_ao_asaas(): void
    {
        $rodape = preg_replace('/\s+/u', ' ', View::make('emails.partials.asaas-rodape')->render());

        $this->assertStringContainsString(Empresa::get('psp_razao_social'), $rodape);
        $this->assertStringContainsString('não é instituição de pagamento', $rodape);
        $this->assertStringContainsString(Empresa::get('psp_suporte_email'), $rodape);

        foreach (['payment_successful', 'payment_failed', 'trainer_payout',
            'pacote-contratado', 'aula-contratada'] as $email) {
            $this->assertStringContainsString(
                'emails.partials.asaas-rodape',
                file_get_contents(resource_path("views/emails/{$email}.blade.php")),
                "O e-mail {$email} move dinheiro e não traz a atribuição ao Asaas."
            );
        }
    }

    /**
     * "Wallet" está na lista de nomenclaturas vedadas ao tomador, e "Carteira"
     * é a tradução que o produto usava para a MESMA coisa: uma conta de
     * recebimentos que não é nossa. Os ids de JS (`carteira*`) seguem livres —
     * o que o programa restringe é o que o usuário lê.
     */
    public function test_nenhuma_tela_de_recebimentos_se_chama_carteira(): void
    {
        foreach (['personal', 'academia', 'studio', 'loja'] as $papel) {
            $fonte = file_get_contents(resource_path("views/{$papel}/dashboard.blade.php"));

            // Só o texto visível: fora os blocos <script>, os comentários
            // Blade (que explicam a própria troca de nome) e os atributos
            // HTML, onde vivem os ids e handlers `carteira*`.
            $visivel = preg_replace('/<script\b.*?<\/script>/is', '', $fonte);
            $visivel = preg_replace('/\{\{--.*?--\}\}/s', '', $visivel);
            $visivel = preg_replace('/\w+="[^"]*"/', '', $visivel);
            $visivel = preg_replace("/\w+='[^']*'/", '', $visivel);

            $this->assertStringNotContainsStringIgnoringCase('carteira', $visivel,
                "O painel de {$papel} voltou a chamar os recebimentos de \"carteira\".");
        }
    }
}
