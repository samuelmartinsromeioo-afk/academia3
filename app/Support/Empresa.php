<?php

namespace App\Support;

/**
 * Acesso à qualificação do titular da plataforma (`config/empresa.php`).
 *
 * Existe para as views legais não repetirem `config('empresa.x')` nem a lógica
 * de "este dado ainda é um placeholder", e para dar um lugar único onde checar
 * se a identificação exigida pelo Decreto 7.962/2013 está completa.
 */
class Empresa
{
    /**
     * Campos sem os quais a identificação legal está incompleta.
     *
     * O endereço voltou em 2026-10-19 (ver o bloco em `config/empresa.php`).
     *
     * Três ausências desta lista, por três motivos diferentes:
     *
     *  - `telefone` continua não divulgado, por decisão do titular.
     *  - `comarca` é DERIVADA de `cidade`/`uf`. Campo derivado na lista de
     *    obrigatórios seria uma segunda cópia do dado esperando para divergir.
     *  - `cep` **não é requisito legal**. O art. 2º, I e II, do Decreto nº
     *    7.962/2013 exige "endereço físico", e rua + número + bairro + cidade
     *    + UF já é um endereço físico — localiza a empresa e define a comarca.
     *    O CEP é completude, não identificação, e por isso a sua falta não
     *    pode tornar `incompleto()` verdadeiro: isso deixaria um teste
     *    vermelho em permanência, que é como um sinal de alarme deixa de ser
     *    lido. Ele segue como placeholder visível no config para quem for
     *    preencher, e `enderecoCompleto()` o omite enquanto não vier.
     */
    private const OBRIGATORIOS = [
        'razao_social', 'cnpj', 'dpo_nome',
        'endereco', 'numero', 'bairro', 'cidade', 'uf',
    ];

    public static function get(string $chave): string
    {
        return (string) config("empresa.{$chave}", '');
    }

    /**
     * Endereço da sede em uma linha, para a seção de identificação.
     *
     * Parte pendente é OMITIDA, não impressa como `[CEP]`. A convenção do
     * colchete existe para o dado que, faltando, tornaria a frase falsa — uma
     * razão social ou um CNPJ inventado é declaração falsa, e ali o
     * placeholder visível é o certo. Um endereço sem CEP não afirma nada de
     * errado: é só menos completo. Então o documento público mostra o
     * endereço verdadeiro que se tem, e quem mantém o sistema continua vendo
     * a falta por `incompleto()`/`lacunas()`.
     */
    public static function enderecoCompleto(): string
    {
        $rua = self::pendente('endereco') ? '' : self::get('endereco');
        $numero = self::pendente('numero') ? '' : self::get('numero');
        $bairro = self::pendente('bairro') ? '' : self::get('bairro');
        $cidade = self::pendente('cidade') ? '' : self::get('cidade');
        $uf = self::pendente('uf') ? '' : self::get('uf');
        $cep = self::pendente('cep') ? '' : self::get('cep');

        $logradouro = trim($rua.($numero !== '' ? ', '.$numero : ''));
        $municipio = trim($cidade.($uf !== '' ? '/'.$uf : ''), '/');

        return implode(' — ', array_filter([
            $logradouro,
            $bairro,
            $municipio,
            $cep !== '' ? 'CEP '.$cep : '',
        ], fn ($p) => trim((string) $p) !== ''));
    }

    /**
     * Comarca do foro da sede, para a cláusula 21 dos Termos.
     *
     * Derivada de `cidade`/`uf` de propósito: a comarca É a da sede, e um
     * campo próprio no config permitiria que os dois discordassem — um foro
     * eleito que não corresponde à sede registrada não é eleição válida.
     * Devolve null quando a cidade não é conhecida, e aí a cláusula volta a
     * dizer "comarca da sede, conforme o registro no CNPJ", que é
     * determinável e continua válido.
     */
    public static function comarca(): ?string
    {
        if (self::pendente('cidade') || self::get('cidade') === '') {
            return null;
        }

        $uf = self::pendente('uf') ? '' : self::get('uf');

        return self::get('cidade').($uf !== '' ? '/'.$uf : '');
    }

    /**
     * True quando o valor ainda é a lacuna do config (`[ALGO]`).
     *
     * A convenção do colchete é o contrato: é assim que o documento público
     * mostra o que falta, em vez de esconder o vazio com texto plausível.
     */
    public static function pendente(string $chave): bool
    {
        return (bool) preg_match('/^\s*\[.*\]\s*$/u', self::get($chave));
    }

    /** Os campos obrigatórios que ainda estão por preencher. */
    public static function lacunas(): array
    {
        return array_values(array_filter(self::OBRIGATORIOS, fn ($c) => self::pendente($c)));
    }

    /**
     * A identificação legal está incompleta?
     *
     * Útil para um aviso no painel do admin ou uma trava de deploy: publicar os
     * Termos sem razão social, CNPJ ou nome do Encarregado deixa o documento sem
     * a identificação que o Decreto 7.962/2013 (art. 2º) e a LGPD (art. 41)
     * exigem, e fragiliza toda cláusula de limitação de responsabilidade.
     */
    public static function incompleto(): bool
    {
        return self::lacunas() !== [];
    }
}
