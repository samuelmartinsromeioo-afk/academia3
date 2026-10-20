<?php

namespace Tests\Feature;

use App\Support\TextoAviso;
use Tests\TestCase;

/**
 * Renderização da marcação `*negrito*` dos textos de aviso.
 *
 * Os ~38 textos usam a marcação do WhatsApp, canal removido em 19/10/2026. A
 * marcação ficou, e as superfícies que não a interpretavam mostravam o
 * asterisco cru — a lista de avisos do site e a do app exibiam literalmente
 * `*Aula cancelada*`.
 *
 * O teste mais importante daqui é o do ESCAPE: estes textos interpolam nome de
 * usuário (`"*{$cliente->nome}* contratou um plano"`), então a ordem
 * escapa-depois-converte não é estilo, é o que separa negrito de XSS no e-mail
 * e na lista do site.
 */
class TextoAvisoTest extends TestCase
{
    public function test_negrito_vira_strong(): void
    {
        $this->assertSame(
            'Aula de <strong>13/10</strong> cancelada',
            TextoAviso::html('Aula de *13/10* cancelada')
        );
    }

    public function test_html_do_usuario_e_escapado_antes_de_converter(): void
    {
        // Nome de cadastro usado como vetor: se a conversão rodasse antes do
        // escape, isto sairia como <script> executável no e-mail.
        $html = TextoAviso::html('*<script>alert(1)</script>* contratou um plano');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        // E o negrito continua funcionando em volta do conteúdo escapado.
        $this->assertStringContainsString('<strong>', $html);
    }

    public function test_nenhum_asterisco_sobrevive_na_saida(): void
    {
        // Pares, ímpar solto e asterisco no fim — nenhum caso pode deixar o
        // sinal visível, que é o defeito que originou este arquivo.
        foreach (['*a* e *b*', 'solto * aqui', 'no fim *', '**', '*não fecha'] as $entrada) {
            $this->assertStringNotContainsString('*', TextoAviso::html($entrada), "entrada: {$entrada}");
            $this->assertStringNotContainsString('*', TextoAviso::simples($entrada), "entrada: {$entrada}");
        }
    }

    public function test_asterisco_solto_nao_abre_negrito_ate_a_proxima_linha(): void
    {
        // O regex antigo (/\*(.+?)\*/s) atravessava \n: um asterisco solto numa
        // linha casava com o da linha seguinte e engolia a quebra no meio.
        $html = TextoAviso::html("linha com * solto\noutra linha *negrito*");

        $this->assertStringContainsString('<strong>negrito</strong>', $html);
        $this->assertStringContainsString('<br', $html);
    }

    public function test_quebra_de_linha_vira_br(): void
    {
        $this->assertStringContainsString('<br', TextoAviso::html("uma\nduas"));
    }

    public function test_truncamento_nao_parte_a_tag_strong(): void
    {
        // Corta ANTES de converter. Se cortasse depois, o limite poderia cair no
        // meio de "<strong>" e a lista do site renderizaria a tag quebrada.
        $html = TextoAviso::html('*um nome bem comprido aqui* e mais texto depois', 20);

        $this->assertStringNotContainsString('<stro…', $html);
        $this->assertStringNotContainsString('*', $html);
        // Fecha toda tag que abriu.
        $this->assertSame(
            substr_count($html, '<strong>'),
            substr_count($html, '</strong>')
        );
    }

    public function test_simples_nao_escapa_porque_o_destino_nao_e_html(): void
    {
        // É o corpo do push: escapar faria o banner do aparelho mostrar "&amp;".
        $this->assertSame(
            'Marina & você',
            TextoAviso::simples('*Marina* & você')
        );
    }

    public function test_texto_sem_marcacao_atravessa_intacto(): void
    {
        $this->assertSame('Aula concluída', TextoAviso::simples('Aula concluída'));
        $this->assertSame('Aula concluída', TextoAviso::html('Aula concluída'));
    }

    public function test_null_nao_quebra(): void
    {
        // Os avisos vêm de colunas nullable; isto roda em toda renderização de
        // lista e não pode lançar por um corpo vazio.
        $this->assertSame('', TextoAviso::html(null));
        $this->assertSame('', TextoAviso::simples(null));
    }
}
