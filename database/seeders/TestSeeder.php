<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\cadastro\Cliente;
use App\Models\cadastro\Personal;
use App\Models\cadastro\academia as Academia;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        Cliente::firstOrCreate(
            ['email' => 'cliente@teste.com'],
            [
                'nome'               => 'Cliente Teste',
                'senha'              => Hash::make('12345678'),
                'sexo'               => 'masculino',
                'cep'                => '30710-580',
                'rua'                => 'Rua Teste',
                'bairro'             => 'Bairro Teste',
                'cidade'             => 'Belo Horizonte',
                'estado'             => 'MG',
                'complemento'        => 'Casa',
                'altura'             => 175.00,
                'peso'               => 73.00,
                'idade'              => '2000-01-01',
                'frequencia_semanal' => 3,
                'resumo_objetivo'    => 'Ganhar massa muscular',
                'condicao_clinica'   => 'Nenhuma',
            ]
        );

        Personal::firstOrCreate(
            ['email' => 'personal@teste.com'],
            [
                'nome'        => 'Personal Teste',
                'senha'       => Hash::make('12345678'),
                'cpf'         => '123.456.789-00',
                'cep'         => '30710-580',
                'rua'         => 'Rua Teste',
                'bairro'      => 'Bairro Teste',
                'cidade'      => 'Belo Horizonte',
                'estado'      => 'MG',
                'complemento' => 'Apto 101',
                'valor_secao' => 100.00,
                'idade'       => '1990-05-15',
                'avaliacao'   => 'Aguardando avaliação inicial',
                'resultados'  => 'Nenhum resultado registrado',
                'foto'        => 'personals/default.jpg',
                'certificado' => 'certificados/default.pdf',
                'latitude'    => -19.9200,
                'longitude'   => -43.9400,
            ]
        );

        Academia::firstOrCreate(
            ['email' => 'academia@teste.com'],
            [
                'nome'              => 'Academia Teste',
                'senha'             => Hash::make('12345678'),
                'cnpj'              => '12.345.678/0001-00',
                'cep'               => '30710-580',
                'rua'               => 'Rua Teste',
                'bairro'            => 'Centro',
                'cidade'            => 'Belo Horizonte',
                'estado'            => 'MG',
                'complemento'       => 'Loja 1',
                'endereco'          => 'Rua Teste, 100 - Centro',
                'valor_mensalidade' => 150.00,
                'descricao'         => 'Academia de teste',
                'tipos_aulas'       => 'Musculação, Yoga',
                'latitude'          => -19.9167,
                'longitude'         => -43.9345,
            ]
        );
    }
}