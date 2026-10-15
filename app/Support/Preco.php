<?php

namespace App\Support;

use App\Services\AsaasService;

/**
 * O preço como o CLIENTE vê.
 *
 * Existe para as vitrines e páginas de detalhe não repetirem a conta nem
 * importarem o AsaasService (que fala HTTP) só para formatar um número. Toda a
 * aritmética continua em um lugar só — aqui só se delega e se formata.
 *
 * A regra que isto torna difícil de errar: **nenhuma tela de cliente mostra o
 * preço cadastrado pelo profissional**. O profissional cadastra R$ 100 e recebe
 * R$ 100; o aluno vê R$ 110. Mostrar o valor cru numa vitrine e cobrar outro no
 * checkout é a pior versão possível dessa mudança — o aluno descobre o
 * acréscimo quando já decidiu, e a culpa parece ser do profissional.
 *
 * Nas telas do PROFISSIONAL vale o contrário: ali o número que importa é o que
 * ele recebe, e `cliente()` serve para mostrar ao lado ("o aluno paga X").
 *
 * **Para o JS, use `dados()` e nunca refaça a conta no front.** O gross-up do
 * cartão (ver AsaasService::detalhar) é uma divisão, não uma soma; uma segunda
 * implementação em JavaScript divergiria da do servidor em centavos e a tela
 * passaria a prometer um total diferente do que é cobrado. Mandar o resultado
 * pronto custa um objeto por item e elimina a classe de bug inteira.
 */
class Preco
{
    public const PIX = 'PIX';

    public const CARTAO = 'CREDIT_CARD';

    /** Quanto o cliente paga por um serviço cadastrado a R$ `$base`. */
    public static function cliente($base, string $metodo = self::PIX): float
    {
        return AsaasService::valorCobrado((float) ($base ?? 0), $metodo);
    }

    /** `cliente()` já formatado em pt-BR, sem o "R$" (ex.: `110,00`). */
    public static function fmt($base, string $metodo = self::PIX): string
    {
        return number_format(self::cliente($base, $metodo), 2, ',', '.');
    }

    /** A comissão da plataforma embutida no preço exibido. */
    public static function taxa($base): float
    {
        return AsaasService::comissao((float) ($base ?? 0));
    }

    /**
     * A quebra dos dois meios de pagamento, pronta para virar JSON e alimentar
     * um modal de checkout: `{pix: {base, comissao, gateway, total}, cartao: {…}}`.
     */
    public static function dados($base): array
    {
        $base = (float) ($base ?? 0);

        return [
            'pix' => AsaasService::detalhar($base, self::PIX),
            'cartao' => AsaasService::detalhar($base, self::CARTAO),
        ];
    }

    /**
     * A regra que as telas do cliente precisam conhecer (taxa vigente e valor
     * mínimo), para rótulos como "inclui 10% de taxa de serviço".
     *
     * O front NÃO guarda esses números: recebe daqui. É o mesmo arranjo de
     * `idade_minima` em `/cadastro/opcoes` — a regra mora no servidor e a tela
     * só desenha. Um `0.10` dentro de um `<script>` (ou de um binário já
     * publicado na loja) fica velho no dia em que a comissão mudar.
     */
    public static function configJs(): array
    {
        return [
            'taxa' => AsaasService::feeRate(),
            'taxa_pct' => round(AsaasService::feeRate() * 100, 2),
            'minimo' => AsaasService::valorMinimo(),
        ];
    }
}
