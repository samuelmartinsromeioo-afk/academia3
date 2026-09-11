<?php

namespace App\Support;

/**
 * Converte um valor em reais para a forma escrita ("mil duzentos e trinta reais
 * e cinquenta centavos"). Recibo sem valor por extenso não é recibo — é a parte
 * que impede adulteração do número depois de assinado.
 */
class ValorPorExtenso
{
    private const UNIDADES = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];

    private const DEZ_A_DEZENOVE = ['dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];

    private const DEZENAS = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];

    private const CENTENAS = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

    public static function reais(float $valor): string
    {
        $valor = round($valor, 2);
        $inteiro = (int) floor($valor);
        $centavos = (int) round(($valor - $inteiro) * 100);

        $partes = [];

        if ($inteiro > 0) {
            // Milhão/bilhão pedem a preposição: "um milhão DE reais", mas
            // "mil reais" e "duzentos reais" não levam nada.
            $escrito = self::numero($inteiro);
            $liga = preg_match('/(milhão|milhões|bilhão|bilhões)$/u', $escrito) ? ' de ' : ' ';
            $partes[] = $escrito.$liga.($inteiro === 1 ? 'real' : 'reais');
        }
        if ($centavos > 0) {
            $partes[] = self::numero($centavos).' '.($centavos === 1 ? 'centavo' : 'centavos');
        }
        if (! $partes) {
            return 'zero real';
        }

        return implode(' e ', $partes);
    }

    /** Inteiro por extenso, até bilhões. */
    public static function numero(int $n): string
    {
        if ($n === 0) {
            return 'zero';
        }
        if ($n === 100) {
            return 'cem';
        }

        foreach ([1_000_000_000 => ['bilhão', 'bilhões'], 1_000_000 => ['milhão', 'milhões'], 1000 => ['mil', 'mil']] as $base => [$sing, $plur]) {
            if ($n >= $base) {
                $quantos = intdiv($n, $base);
                $resto = $n % $base;
                // "mil" não leva "um" na frente: 1500 é "mil e quinhentos".
                $prefixo = ($base === 1000 && $quantos === 1) ? 'mil' : self::numero($quantos).' '.($quantos === 1 ? $sing : $plur);

                return $resto === 0 ? $prefixo : $prefixo.self::ligacao($resto).self::numero($resto);
            }
        }

        if ($n >= 100) {
            $resto = $n % 100;

            return self::CENTENAS[intdiv($n, 100)].($resto ? ' e '.self::numero($resto) : '');
        }
        if ($n >= 20) {
            $resto = $n % 10;

            return self::DEZENAS[intdiv($n, 10)].($resto ? ' e '.self::UNIDADES[$resto] : '');
        }
        if ($n >= 10) {
            return self::DEZ_A_DEZENOVE[$n - 10];
        }

        return self::UNIDADES[$n];
    }

    /**
     * Liga a casa maior ao resto. Usa "e" quando o resto é menor que cem ou é
     * centena redonda (mil e duzentos), e vírgula nos demais (mil, duzentos e um).
     */
    private static function ligacao(int $resto): string
    {
        return ($resto < 100 || $resto % 100 === 0) ? ' e ' : ', ';
    }
}
