<?php

namespace App\Support;

/**
 * Registro profissional de educação física (CREF) — formato e normalização.
 *
 * ─── O QUE ESTA CLASSE FAZ, E O QUE ELA NÃO FAZ ──────────────────────────────
 *
 * Ela valida a **forma** do registro e normaliza o que o usuário digitou. Ela
 * **não** confirma que o registro existe nem que está ativo — isso só o
 * conselho pode dizer, e quem faz é o **admin**, na aprovação do cadastro, com
 * o link direto para a consulta pública do conselho certo (ver
 * `config/cref.php` e `AdminController@aprovar`, que exige e grava o carimbo da
 * conferência).
 *
 * A distinção é importante porque é fácil confundir as duas e prometer a
 * segunda entregando a primeira. Um documento que diga "verificamos o CREF"
 * quando só se conferiu o formato é publicidade enganosa, e pior: cria para a
 * Plataforma um dever que ela não cumpre. A cláusula **2.1** dos Termos do
 * Personal diz exatamente o alcance de cada camada.
 *
 * ─── POR QUE NÃO HÁ DÍGITO VERIFICADOR ──────────────────────────────────────
 *
 * Diferente de CPF e CNPJ, o número do CREF **não tem** dígito verificador
 * publicado: é matrícula sequencial do conselho regional. Então não existe
 * conta aritmética capaz de dizer "este número é impossível" — só é possível
 * recusar o que não tem a forma de um CREF. Quem procurar aqui o equivalente a
 * `validarCPF` não vai achar, e não é omissão.
 *
 * ─── O QUE HAVIA ANTES ───────────────────────────────────────────────────────
 *
 * `cref => 'required|string|max:30'`, nas duas portas de cadastro. Isto é,
 * **qualquer** string passava: "aaa", "000000-X/ZZ", "não tenho". O campo
 * existia, era obrigatório, aparecia no perfil público do profissional ao lado
 * da foto — e não queria dizer nada. Era pior que um campo ausente, porque
 * parecia uma credencial conferida.
 *
 * ─── FORMATO ─────────────────────────────────────────────────────────────────
 *
 *   NNNNNN-C/UF        ex.: 123456-G/PR
 *
 *   NNNNNN  matrícula no conselho (3 a 8 dígitos; as antigas são mais curtas)
 *   C       categoria: G, P, E ou L (ver CATEGORIAS)
 *   UF      sigla do estado do conselho que emitiu
 */
class Cref
{
    /**
     * Categorias de registro, conforme a Resolução CONFEF sobre registro
     * profissional. O rótulo é o que aparece ao admin na conferência.
     */
    public const CATEGORIAS = [
        'G' => 'Graduado',
        'P' => 'Provisionado',
        'E' => 'Estagiário',
        'L' => 'Licenciado',
    ];

    /** As 27 unidades federativas. */
    public const UFS = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS',
        'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC',
        'SE', 'SP', 'TO',
    ];

    /**
     * Quebra o CREF digitado nas suas partes, ou devolve null se não tiver a
     * forma de um CREF.
     *
     * Aceita o que a pessoa realmente digita — "cref 123456-g/pr",
     * "123456 G PR", "CREF-123456/G-PR", "123456/PR" — porque recusar por
     * causa de um espaço ou de uma barra a mais é atrito sem ganho nenhum. O
     * que sai daqui é sempre canônico.
     *
     * **Matrícula e UF são obrigatórias; categoria não.** A escolha é
     * deliberada e vale a pena explicar, porque é onde a validação encosta no
     * cadastro de gente real:
     *
     *  - matrícula e UF são o que o conselho precisa para localizar a pessoa.
     *    Sem as duas, nem nós nem o admin conseguem conferir nada — exigir é o
     *    que torna a verificação possível;
     *  - a categoria está impressa na carteira, mas é a parte que o
     *    profissional menos decora. Barrar o cadastro de quem digitou
     *    "123456/SP" seria trocar um cadastro legítimo por um campo mais
     *    bonito. Quando vem, é validada; quando não vem, fica null e o admin
     *    lê a categoria direto no conselho.
     *
     * @return array{matricula:string, categoria:?string, uf:string, formatado:string}|null
     */
    public static function partes(?string $valor): ?array
    {
        $s = strtoupper(trim((string) $valor));
        if ($s === '') {
            return null;
        }

        /*
         * Remove a palavra CREF e, quando GRUDADO nela, o número da região
         * ("CREF9/PR") — nenhum dos dois faz parte do registro em si.
         *
         * O `\d*` só vale colado, sem `\s*` no meio, e isso é load-bearing: com
         * `/\bCREF\s*\d*\b/` a expressão casava "CREF 123456" por inteiro em
         * "CREF 123456-G/SP" e **engolia a matrícula**, fazendo o parser
         * recusar o formato mais comum que existe. Foi o teste
         * `extrai as partes` que pegou.
         */
        $s = preg_replace('/\bCREF\d*\b/u', ' ', $s);

        // Matrícula = primeiro bloco de 3 a 8 dígitos.
        if (! preg_match('/(\d{3,8})/u', $s, $mMat)) {
            return null;
        }
        $matricula = $mMat[1];

        // Matrícula só de zeros não é matrícula — é o placeholder de quem não
        // tem registro e quer passar pela validação.
        if ((int) $matricula === 0) {
            return null;
        }

        // UF: sigla de duas letras válida, procurada do FIM para o começo. No
        // formato canônico a UF é o último elemento, e varrer de trás evita
        // que um pedaço do nome ("SE" em "JOSE") ganhe da sigla verdadeira.
        $uf = null;
        if (preg_match_all('/(?<![A-Z])([A-Z]{2})(?![A-Z])/u', $s, $mUf)) {
            foreach (array_reverse($mUf[1]) as $candidata) {
                if (in_array($candidata, self::UFS, true)) {
                    $uf = $candidata;
                    break;
                }
            }
        }
        if ($uf === null) {
            return null;
        }

        /*
         * Categoria: letra isolada de CATEGORIAS, procurada no que sobra DEPOIS
         * de tirar a UF da string.
         *
         * Tirar a UF primeiro é o que resolve as duas armadilhas de uma vez:
         *
         *  - siglas que COMEÇAM com letra de categoria (GO, PA, PB, PE, PI, PR,
         *    ES): buscar na string inteira pegaria o "G" de GO como categoria;
         *  - ordem invertida ("CREF9/PR 123456-G"), em que a categoria vem
         *    DEPOIS da UF. Uma versão anterior cortava em `strrpos($s, $uf)` e
         *    só olhava o que vinha antes — perdia a categoria nesses casos.
         */
        $semUf = preg_replace('/(?<![A-Z])'.$uf.'(?![A-Z])/u', ' ', $s);
        $categoria = null;
        $letras = implode('', array_keys(self::CATEGORIAS));
        if (preg_match('/(?<![A-Z])(['.$letras.'])(?![A-Z])/u', $semUf, $mCat)) {
            $categoria = $mCat[1];
        }

        return [
            'matricula' => $matricula,
            'categoria' => $categoria,
            'uf' => $uf,
            'formatado' => $categoria
                ? "{$matricula}-{$categoria}/{$uf}"
                : "{$matricula}/{$uf}",
        ];
    }

    /** O CREF digitado tem a forma de um registro válido? */
    public static function valido(?string $valor): bool
    {
        return self::partes($valor) !== null;
    }

    /**
     * Forma canônica (`123456-G/PR`) para gravar no banco.
     *
     * Gravar normalizado é o que permite comparar, procurar duplicata e montar
     * a consulta ao conselho sem adivinhar. Devolve null quando não é válido —
     * quem chama já validou.
     */
    public static function normalizar(?string $valor): ?string
    {
        return self::partes($valor)['formatado'] ?? null;
    }

    /** Rótulo da categoria ("Graduado"), para exibir ao admin. */
    public static function categoriaLabel(?string $valor): ?string
    {
        $cat = self::partes($valor)['categoria'] ?? null;

        return $cat ? (self::CATEGORIAS[$cat] ?? null) : null;
    }

    /**
     * Regra de validação pronta para entrar em `$request->validate()`.
     *
     * Devolve um closure em vez de uma string de regra porque a mensagem tem de
     * explicar o FORMATO esperado: "cref inválido" manda a pessoa adivinhar, e
     * o formato do CREF não é óbvio para quem não o escreve todo dia.
     */
    public static function regra(): array
    {
        return [
            'required',
            'string',
            'max:30',
            function ($attribute, $value, $fail) {
                if (! self::valido($value)) {
                    $fail(
                        'Informe o seu CREF como está na carteira: número de registro e a sigla '
                        .'do estado do seu conselho — por exemplo 123456-G/SP ou 123456/SP.'
                    );
                }
            },
        ];
    }
}
