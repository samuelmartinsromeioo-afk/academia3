<?php

namespace Tests\Unit;

use App\Support\Cref;
use PHPUnit\Framework\TestCase;

/**
 * O parser de CREF, fixado caso por caso.
 *
 * Antes desta classe o campo era `required|string|max:30`: "aaa" e
 * "não tenho" passavam, e o número aparecia no perfil público ao lado da foto
 * como se tivesse sido conferido. O risco de um parser assim não é recusar
 * lixo — é **recusar profissional de verdade** por causa de um espaço, de uma
 * barra ou da categoria que ele não decorou. Por isso a maior parte dos casos
 * aqui é de ACEITE, e os de recusa são os inequívocos.
 */
class CrefTest extends TestCase
{
    /** @dataProvider valoresAceitos */
    public function test_aceita_e_normaliza(string $digitado, string $esperado): void
    {
        $this->assertSame(
            $esperado,
            Cref::normalizar($digitado),
            "O CREF [{$digitado}] devia ser aceito e normalizado para [{$esperado}]."
        );
    }

    public static function valoresAceitos(): array
    {
        return [
            'canônico' => ['123456-G/PR', '123456-G/PR'],
            'minúsculas' => ['123456-g/pr', '123456-G/PR'],
            'com a palavra CREF' => ['CREF 123456-G/PR', '123456-G/PR'],
            'com a região junto' => ['CREF9/PR 123456-G', '123456-G/PR'],
            'só espaços' => ['123456 G PR', '123456-G/PR'],
            'barra antes da categoria' => ['123456/G-PR', '123456-G/PR'],
            'espaços sobrando' => ['  123456 - G / PR  ', '123456-G/PR'],

            // Categoria ausente: aceito de propósito — ver `partes()`.
            'sem categoria' => ['123456/PR', '123456/PR'],
            'sem categoria com espaço' => ['123456 SP', '123456/SP'],

            // As quatro categorias.
            'provisionado' => ['123456-P/SP', '123456-P/SP'],
            'estagiário' => ['123456-E/SP', '123456-E/SP'],
            'licenciado' => ['123456-L/SP', '123456-L/SP'],

            // Matrícula curta (registros antigos) e longa.
            'matrícula de 3 dígitos' => ['001-G/RJ', '001-G/RJ'],
            'matrícula de 8 dígitos' => ['12345678-G/RJ', '12345678-G/RJ'],

            /*
             * As armadilhas de verdade: UF cuja sigla COMEÇA com uma letra de
             * categoria. Uma busca da categoria na string inteira pegaria o "G"
             * de GO, o "P" de PA/PB/PE/PI/PR, o "E" de ES e o "L" de... nenhuma,
             * mas as outras três bastam para o defeito aparecer.
             */
            'GO sem categoria' => ['123456/GO', '123456/GO'],
            'GO com categoria' => ['123456-G/GO', '123456-G/GO'],
            'PA sem categoria' => ['123456/PA', '123456/PA'],
            'PE com categoria P' => ['123456-P/PE', '123456-P/PE'],
            'ES com categoria E' => ['123456-E/ES', '123456-E/ES'],
            'PR com categoria P' => ['123456-P/PR', '123456-P/PR'],
        ];
    }

    /** @dataProvider valoresRecusados */
    public function test_recusa(string $digitado): void
    {
        $this->assertFalse(
            Cref::valido($digitado),
            "O valor [{$digitado}] NÃO devia ser aceito como CREF."
        );
    }

    public static function valoresRecusados(): array
    {
        return [
            'vazio' => [''],
            'só espaços' => ['   '],
            'texto solto' => ['aaa'],
            'recusa explícita' => ['não tenho'],
            'só número, sem UF' => ['123456'],
            'só número e categoria' => ['123456-G'],
            'UF inexistente' => ['123456-G/ZZ'],
            'UF inexistente sem categoria' => ['123456/XX'],
            'sem matrícula' => ['G/PR'],
            'matrícula curta demais' => ['12-G/PR'],
            'matrícula só de zeros' => ['000000-G/SP'],
            'zeros sem categoria' => ['0000/SP'],
        ];
    }

    public function test_extrai_as_partes(): void
    {
        $p = Cref::partes('CREF 123456-G/SP');

        $this->assertSame('123456', $p['matricula']);
        $this->assertSame('G', $p['categoria']);
        $this->assertSame('SP', $p['uf']);
        $this->assertSame('123456-G/SP', $p['formatado']);
    }

    public function test_categoria_fica_nula_quando_nao_informada(): void
    {
        $p = Cref::partes('123456/SP');

        $this->assertNull($p['categoria']);
        $this->assertSame('SP', $p['uf']);
        $this->assertNull(Cref::categoriaLabel('123456/SP'));
    }

    public function test_rotulo_da_categoria(): void
    {
        $this->assertSame('Graduado', Cref::categoriaLabel('123456-G/SP'));
        $this->assertSame('Provisionado', Cref::categoriaLabel('123456-P/SP'));
        $this->assertSame('Estagiário', Cref::categoriaLabel('123456-E/SP'));
        $this->assertSame('Licenciado', Cref::categoriaLabel('123456-L/SP'));
    }

    /** Normalizar o que já está normalizado não muda nada (idempotência). */
    public function test_normalizacao_e_idempotente(): void
    {
        foreach (['123456-G/PR', '123456/PR', '001-E/RJ'] as $canonico) {
            $this->assertSame($canonico, Cref::normalizar(Cref::normalizar($canonico)));
        }
    }

    /** As 27 UFs são aceitas — nenhuma sigla real pode ficar de fora. */
    public function test_todas_as_ufs_sao_aceitas(): void
    {
        foreach (Cref::UFS as $uf) {
            $this->assertTrue(
                Cref::valido("123456-G/{$uf}"),
                "A UF [{$uf}] tem de ser aceita."
            );
        }

        $this->assertCount(27, Cref::UFS);
    }
}
