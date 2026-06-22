<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\cadastro\Academia;
use App\Models\cadastro\Cliente;
use App\Models\cadastro\Personal;
use App\Models\cadastro\Studio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cria contas de teste para TODOS os tipos de login do sistema:
 * Admin, Personal, Cliente, Academia e Studio.
 *
 * Para cada tipo gera 10 contas numeradas (ex.: personal1@teste.com ... personal10@teste.com)
 * todas com a senha 12345678, além das contas "clássicas" (admin@teste.com etc.).
 *
 * Uso: php artisan db:seed --class=TestSeeder
 *
 * Observações importantes:
 *  - O model Admin tem mutator (setSenhaAttribute) que já faz o Hash. Por isso a senha
 *    do admin é passada em TEXTO PURO; os demais models não têm mutator e recebem Hash::make().
 *  - Personais e Studios precisam de status = 'aprovado' para conseguir logar.
 *  - Usa updateOrCreate para ser idempotente e reparar contas antigas (ex.: admin com hash duplo).
 */
class TestSeeder extends Seeder
{
    private const SENHA = '12345678';

    public function run(): void
    {
        $this->seedAdmins();
        $academias = $this->seedAcademias();
        $this->seedPersonais($academias);
        $this->seedClientes($academias);
        $this->seedStudios();

        $this->command?->info('Contas de teste criadas. Senha de todas: '.self::SENHA);
    }

    private function seedAdmins(): void
    {
        // Conta clássica (repara hash duplo de execuções antigas).
        Admin::updateOrCreate(
            ['email' => 'admin@teste.com'],
            ['nome' => 'Administrador', 'senha' => self::SENHA] // mutator faz o Hash
        );

        for ($i = 1; $i <= 10; $i++) {
            Admin::updateOrCreate(
                ['email' => "admin{$i}@teste.com"],
                ['nome' => "Admin Teste {$i}", 'senha' => self::SENHA]
            );
        }
    }

    /** @return array<int, \App\Models\cadastro\Academia> */
    private function seedAcademias(): array
    {
        $base = [
            'senha' => Hash::make(self::SENHA),
            'cep' => '30710-580',
            'rua' => 'Rua das Academias',
            'bairro' => 'Centro',
            'cidade' => 'Belo Horizonte',
            'estado' => 'MG',
            'complemento' => 'Loja',
            'endereco' => 'Rua das Academias, 100 - Centro',
            'valor_mensalidade' => 150.00,
            'descricao' => 'Academia de teste',
            'tipos_aulas' => 'Musculação, Yoga, Spinning',
            'latitude' => -19.9167,
            'longitude' => -43.9345,
        ];

        $academias = [];

        $academias[] = Academia::updateOrCreate(
            ['email' => 'academia@teste.com'],
            array_merge($base, ['nome' => 'Academia Teste', 'cnpj' => '12.345.678/0001-00'])
        );

        for ($i = 1; $i <= 10; $i++) {
            $academias[] = Academia::updateOrCreate(
                ['email' => "academia{$i}@teste.com"],
                array_merge($base, [
                    'nome' => "Academia Teste {$i}",
                    'cnpj' => sprintf('11.111.%03d/0001-%02d', $i, $i),
                ])
            );
        }

        return $academias;
    }

    /** @param array<int, \App\Models\cadastro\Academia> $academias */
    private function seedPersonais(array $academias): void
    {
        $base = [
            'senha' => Hash::make(self::SENHA),
            'status' => 'aprovado',
            'cep' => '30710-580',
            'rua' => 'Rua dos Personais',
            'bairro' => 'Savassi',
            'cidade' => 'Belo Horizonte',
            'estado' => 'MG',
            'complemento' => 'Sala',
            'valor_secao' => 100.00,
            'idade' => '1990-05-15',
            'avaliacao' => 'Aguardando avaliação inicial',
            'resultados' => 'Nenhum resultado registrado',
            'foto' => 'personals/default.jpg',
            'certificado' => 'certificados/default.pdf',
            'latitude' => -19.9200,
            'longitude' => -43.9400,
        ];

        Personal::updateOrCreate(
            ['email' => 'personal@teste.com'],
            array_merge($base, ['nome' => 'Personal Teste', 'cpf' => '123.456.789-00'])
        );

        for ($i = 1; $i <= 10; $i++) {
            $academia = $academias[($i - 1) % count($academias)] ?? null;
            Personal::updateOrCreate(
                ['email' => "personal{$i}@teste.com"],
                array_merge($base, [
                    'nome' => "Personal Teste {$i}",
                    'cpf' => sprintf('100.200.%03d-%02d', $i, $i),
                    'academia_id' => $academia?->id,
                ])
            );
        }
    }

    /** @param array<int, \App\Models\cadastro\Academia> $academias */
    private function seedClientes(array $academias): void
    {
        $base = [
            'senha' => Hash::make(self::SENHA),
            'sexo' => 'masculino',
            'cep' => '30710-580',
            'rua' => 'Rua dos Clientes',
            'bairro' => 'Funcionários',
            'cidade' => 'Belo Horizonte',
            'estado' => 'MG',
            'complemento' => 'Apto',
            'altura' => 175.00,
            'peso' => 73.00,
            'idade' => '2000-01-01',
            'frequencia_semanal' => 3,
            'resumo_objetivo' => 'Ganhar massa muscular',
            'condicao_clinica' => 'Nenhuma',
        ];

        Cliente::updateOrCreate(
            ['email' => 'cliente@teste.com'],
            array_merge($base, [
                'nome' => 'Cliente Teste',
                'academia_id' => $academias[0]->id ?? null,
            ])
        );

        for ($i = 1; $i <= 10; $i++) {
            $academia = $academias[($i - 1) % count($academias)] ?? null;
            Cliente::updateOrCreate(
                ['email' => "cliente{$i}@teste.com"],
                array_merge($base, [
                    'nome' => "Cliente Teste {$i}",
                    'academia_id' => $academia?->id,
                ])
            );
        }
    }

    private function seedStudios(): void
    {
        $base = [
            'senha' => Hash::make(self::SENHA),
            'status' => 'aprovado',
            'whatsapp' => '31999990000',
            'cep' => '30710-580',
            'rua' => 'Rua dos Studios',
            'bairro' => 'Lourdes',
            'cidade' => 'Belo Horizonte',
            'estado' => 'MG',
            'complemento' => 'Studio',
            'endereco' => 'Rua dos Studios, 200 - Lourdes',
            'descricao' => 'Studio de teste',
            'modalidades' => 'Pilates, Funcional',
            'tipo' => 'yoga_pilates',
            'valor_aula' => 80.00,
            'capacidade_padrao' => 10,
            'latitude' => -19.9300,
            'longitude' => -43.9350,
        ];

        Studio::updateOrCreate(
            ['email' => 'studio@teste.com'],
            array_merge($base, ['nome' => 'Studio Teste', 'cnpj' => '22.222.000/0001-00'])
        );

        for ($i = 1; $i <= 10; $i++) {
            Studio::updateOrCreate(
                ['email' => "studio{$i}@teste.com"],
                array_merge($base, [
                    'nome' => "Studio Teste {$i}",
                    'cnpj' => sprintf('22.222.%03d/0001-%02d', $i, $i),
                ])
            );
        }
    }
}
