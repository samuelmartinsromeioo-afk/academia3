<?php

namespace Tests\Feature;

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
}
