<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Payload mínimo VÁLIDO de cadastro de aluno (POST /api/v1/register).
     *
     * Vive aqui porque três arquivos de teste repetiam a sua própria versão
     * disso, e quando o cadastro do app passou a exigir nascimento, sexo e CEP
     * — para ficar igual ao do site — os três quebraram de uma vez por estarem
     * presos a um contrato que não existe mais. Com um ponto só, o próximo
     * campo obrigatório é uma edição, não cinco.
     *
     * `$extra` sobrescreve qualquer chave (e-mail, cupom, modalidade...).
     */
    protected function dadosRegistroAluno(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Aluno Teste',
            'email' => 'aluno@teste.com',
            'senha' => 'senha12345',
            'idade' => '1995-01-01',
            'sexo' => 'Masculino',
            'cep' => '87000-000',
            'aceita_termos' => true,
        ], $extra);
    }
}
