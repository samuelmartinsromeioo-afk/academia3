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

    /**
     * Payload mínimo VÁLIDO de cadastro de personal, aceito pelas duas portas
     * (POST /api/v1/register/personal e POST route('personal.store')).
     *
     * Mesma razão de existir do método acima: o cadastro de personal é o mais
     * longo do projeto e cada teste que mexe nele carregava a sua própria
     * cópia de 15 chaves. O CPF é parâmetro porque é `unique` — dois cadastros
     * no mesmo teste precisam de dois CPFs válidos distintos.
     *
     * Quem chama precisa de `Storage::fake('public')` (a foto é obrigatória) e
     * `Http::fake()` (a criação da subconta Asaas não deve sair da máquina).
     */
    protected function dadosRegistroPersonal(array $extra = []): array
    {
        return array_merge([
            'nome' => 'PT Teste',
            'email' => 'pt@teste.com',
            'senha' => 'senhaforte123',
            'senha_confirmation' => 'senhaforte123',
            'cpf' => '529.982.247-25', // válido (dígitos verificadores ok)
            'cref' => '123456-G/PR',
            'idade' => '1990-01-01',
            'valor_secao' => 90,
            'cep' => '87000-000',
            'rua' => 'Av. Brasil',
            'bairro' => 'Centro',
            'cidade' => 'Maringá',
            'estado' => 'PR',
            'complemento' => '100',
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('eu.jpg'),
            'aceita_termos' => 1,
        ], $extra);
    }
}
