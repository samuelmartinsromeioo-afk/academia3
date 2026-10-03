<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Passo intermediário do link de convite: /cadastro/selecionar leva a
 * /cadastro/ir-cadastro/{tipo}, que redireciona ao formulário do perfil
 * escolhido carregando o cupom de indicação.
 *
 * Existe porque esta rota ficou quebrada sem ninguém notar: o método
 * type-hintava `Request` sem importar `Illuminate\Http\Request`, então o PHP
 * resolvia `App\Http\Controllers\Cadastro\Request` e estourava
 * "Class ... does not exist". Erro de import não aparece em lint nem em
 * compilação — só quando a rota é chamada. Daí o teste.
 */
class SelecaoCadastroTest extends TestCase
{
    /** @dataProvider perfis */
    public function test_redireciona_para_o_formulario_do_perfil(string $tipo, string $rotaDestino): void
    {
        $this->get("/cadastro/ir-cadastro/{$tipo}")
            ->assertRedirect(route($rotaDestino));
    }

    public static function perfis(): array
    {
        return [
            'personal' => ['personal', 'form.personal'],
            'cliente'  => ['cliente',  'form.cliente'],
            'academia' => ['academia', 'form.academia'],
            'studio'   => ['studio',   'form.studio'],
            'loja'     => ['loja',     'form.loja'],
        ];
    }

    /** O cupom do link de convite tem de sobreviver ao redirecionamento. */
    public function test_carrega_o_cupom_para_o_formulario(): void
    {
        $this->get('/cadastro/ir-cadastro/personal?cupom=MARIA7F3K')
            ->assertRedirect(route('form.personal', ['cupom' => 'MARIA7F3K']));
    }

    /** O que o usuário digita passa por Cupom::normalizar antes de seguir. */
    public function test_normaliza_o_cupom_recebido(): void
    {
        $this->get('/cadastro/ir-cadastro/personal?cupom=' . urlencode(' maria-7f3k '))
            ->assertRedirect(route('form.personal', ['cupom' => 'MARIA7F3K']));
    }

    public function test_sem_cupom_nao_inventa_parametro(): void
    {
        $this->get('/cadastro/ir-cadastro/personal')
            ->assertRedirect(route('form.personal'));
    }

    public function test_tipo_desconhecido_da_404(): void
    {
        $this->get('/cadastro/ir-cadastro/hacker')->assertNotFound();
    }

    /** A tela de seleção em si abre, com e sem cupom no link. */
    public function test_tela_de_selecao_abre(): void
    {
        $this->get(route('cadastro.SelecaoCadastro'))->assertOk();
        $this->get(route('cadastro.SelecaoCadastro', ['cupom' => 'MARIA7F3K']))->assertOk();
    }

    /**
     * Toda view referenciada por `view('...')` no app tem de existir.
     *
     * Nome de view errado não aparece em lint nem na compilação de Blade: a
     * página simplesmente responde 500 quando alguém a abre. Foi assim que
     * /admin/relatorio-financeiro (hífen em vez de underscore) e quatro rotas do
     * personal ficaram quebradas sem ninguém notar. Esta varredura é barata e
     * pega a classe inteira de uma vez.
     */
    public function test_toda_view_referenciada_existe(): void
    {
        $faltando = [];
        $total = 0;

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));

        foreach ($it as $arquivo) {
            if ($arquivo->getExtension() !== 'php') {
                continue;
            }

            preg_match_all(
                "/(?:\bview|View::make)\(\s*'([a-zA-Z0-9_.\-\/]+)'/",
                file_get_contents($arquivo->getPathname()),
                $m
            );

            foreach ($m[1] as $nome) {
                $total++;

                if (! View::exists($nome)) {
                    $faltando[] = $nome . ' (em ' . $arquivo->getFilename() . ')';
                }
            }
        }

        $this->assertGreaterThan(50, $total, 'a varredura deveria encontrar as referências a view');
        $this->assertSame([], array_values(array_unique($faltando)), 'view referenciada que não existe');
    }
}
