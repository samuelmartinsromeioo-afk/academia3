<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Renderização do texto dos avisos.
 *
 * Os ~38 textos de aviso (ver `AvisoService` e os controllers) usam a marcação
 * simples do WhatsApp — `*negrito*` e quebras de linha — porque o WhatsApp foi
 * um dos canais até 19/10/2026. O canal saiu; a marcação ficou dentro dos
 * textos, e **as superfícies que não a interpretam mostram o asterisco cru**:
 * a lista de avisos do site e a do app exibiam literalmente `*Aula cancelada*`.
 *
 * A correção mora AQUI e não nos 38 textos, por dois motivos: reescrever os
 * textos seria 38 edições para um problema de apresentação, e o e-mail (que já
 * convertia certo, em `emails/notificacao.blade.php`) passaria a mostrar texto
 * sem destaque nenhum. Um lugar que converte serve as três superfícies.
 *
 * Duas saídas, porque os destinos são diferentes:
 *
 * - `html()` para onde existe formatação (e-mail e lista do site).
 * - `simples()` para onde NÃO existe: o banner do push. O Expo não interpreta
 *   marcação, então ali o asterisco só pode ser removido.
 *
 * O app tem a sua própria renderização (`componentes/TextoMarcado` no
 * snrfit-app) porque React Native não desenha HTML — é inevitável que sejam
 * duas implementações, e o que importa é que cada runtime tenha UMA.
 */
class TextoAviso
{
    /** `*negrito*` — não captura quebra de linha, para um asterisco solto numa linha não "abrir" negrito até a seguinte. */
    private const NEGRITO = '/\*([^*\n]+)\*/';

    /**
     * Converte o texto do aviso em HTML seguro, com o negrito aplicado.
     *
     * A ORDEM é a parte que importa e não pode ser trocada: escapa primeiro,
     * converte depois. Estes textos interpolam nome de usuário
     * (`"*{$cliente->nome}* contratou um plano"`), então converter antes de
     * escapar entregaria XSS no e-mail e na lista do site a quem escolhesse o
     * nome certo no cadastro.
     *
     * @param  int|null  $limite  trunca ANTES de converter, para o corte nunca
     *                            cair no meio de uma tag `<strong>`
     */
    public static function html(?string $texto, ?int $limite = null): string
    {
        $texto = (string) $texto;

        if ($limite !== null) {
            $texto = Str::limit($texto, $limite);
        }

        $html = e($texto);
        $html = preg_replace(self::NEGRITO, '<strong>$1</strong>', $html);

        // Um truncamento pode cortar entre os dois asteriscos do par e deixar o
        // primeiro órfão — que não casa no regex e reapareceria na tela, que é
        // exatamente o defeito que este arquivo existe para apagar.
        $html = str_replace('*', '', $html);

        return nl2br($html);
    }

    /**
     * Texto limpo, sem marcação — para o push, que não formata nada.
     *
     * Não escapa: o destino não é HTML. Escapar aqui faria o banner do aparelho
     * exibir `&amp;` no lugar do `&`.
     */
    public static function simples(?string $texto, ?int $limite = null): string
    {
        $texto = preg_replace(self::NEGRITO, '$1', (string) $texto);
        $texto = str_replace('*', '', $texto);

        return $limite !== null ? Str::limit($texto, $limite) : $texto;
    }
}
