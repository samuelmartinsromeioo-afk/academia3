<?php

namespace Database\Seeders;

use App\Models\Cadastro\Academia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 10 academias reais de Belo Horizonte, em bairros diferentes, já APROVADAS.
 * Idempotente: usa updateOrCreate por e-mail, então rodar de novo não duplica.
 * Senha padrão de todas: "academia123".
 */
class AcademiasBhSeeder extends Seeder
{
    public function run(): void
    {
        $senha = Hash::make('academia123');

        $academias = [
            [
                'nome'         => 'Iron House Savassi',
                'email'        => 'contato@ironhousesavassi.com.br',
                'cnpj'         => '21.456.789/0001-10',
                'cep'          => '30130-151',
                'rua'          => 'Rua Pernambuco, 1000',
                'bairro'       => 'Savassi',
                'endereco'     => 'Rua Pernambuco, 1000 - Savassi, Belo Horizonte - MG',
                'latitude'     => -19.9391,
                'longitude'    => -43.9339,
                'valor_mensalidade' => 169.90,
                'quantidade_alunos' => 420,
                'descricao'    => 'Academia premium na Savassi com estrutura completa de musculação e treinamento funcional.',
                'tipos_aulas'  => 'Musculação, Treinamento Funcional, Cross Training, Spinning',
                'infraestrutura' => 'Área de musculação climatizada, sala de funcional, estúdio de bike, vestiários com armários e ducha.',
            ],
            [
                'nome'         => 'Bodyforce Funcionários',
                'email'        => 'contato@bodyforcefuncionarios.com.br',
                'cnpj'         => '22.567.890/0001-21',
                'cep'          => '30140-071',
                'rua'          => 'Avenida do Contorno, 6000',
                'bairro'       => 'Funcionários',
                'endereco'     => 'Avenida do Contorno, 6000 - Funcionários, Belo Horizonte - MG',
                'latitude'     => -19.9330,
                'longitude'    => -43.9281,
                'valor_mensalidade' => 149.90,
                'quantidade_alunos' => 350,
                'descricao'    => 'Musculação e aulas coletivas no coração dos Funcionários, aberta das 6h à meia-noite.',
                'tipos_aulas'  => 'Musculação, Pilates, Yoga, Zumba',
                'infraestrutura' => 'Dois andares de musculação, sala de pilates, espaço de alongamento e avaliação física.',
            ],
            [
                'nome'         => 'Studio Vita Lourdes',
                'email'        => 'contato@studiovitalourdes.com.br',
                'cnpj'         => '23.678.901/0001-32',
                'cep'          => '30170-091',
                'rua'          => 'Rua Gonçalves Dias, 1500',
                'bairro'       => 'Lourdes',
                'endereco'     => 'Rua Gonçalves Dias, 1500 - Lourdes, Belo Horizonte - MG',
                'latitude'     => -19.9333,
                'longitude'    => -43.9436,
                'valor_mensalidade' => 199.90,
                'quantidade_alunos' => 260,
                'descricao'    => 'Studio boutique no Lourdes focado em treino personalizado e pilates.',
                'tipos_aulas'  => 'Personal Training, Pilates, Fisioterapia, Funcional',
                'infraestrutura' => 'Salas individuais para personal, estúdio de pilates com aparelhos, sala de recuperação.',
            ],
            [
                'nome'         => 'Pampulha Fitness Club',
                'email'        => 'contato@pampulhafitnessclub.com.br',
                'cnpj'         => '24.789.012/0001-43',
                'cep'          => '31275-060',
                'rua'          => 'Avenida Otacílio Negrão de Lima, 3000',
                'bairro'       => 'Pampulha',
                'endereco'     => 'Avenida Otacílio Negrão de Lima, 3000 - Pampulha, Belo Horizonte - MG',
                'latitude'     => -19.8519,
                'longitude'    => -43.9770,
                'valor_mensalidade' => 129.90,
                'quantidade_alunos' => 510,
                'descricao'    => 'Academia às margens da Lagoa da Pampulha com pista de corrida e área externa.',
                'tipos_aulas'  => 'Musculação, Corrida, Natação, Cross Training',
                'infraestrutura' => 'Piscina semiolímpica, área externa de treino, musculação ampla e bicicletário.',
            ],
            [
                'nome'         => 'Centro Power Academia',
                'email'        => 'contato@centropoweracademia.com.br',
                'cnpj'         => '25.890.123/0001-54',
                'cep'          => '30130-005',
                'rua'          => 'Avenida Afonso Pena, 1500',
                'bairro'       => 'Centro',
                'endereco'     => 'Avenida Afonso Pena, 1500 - Centro, Belo Horizonte - MG',
                'latitude'     => -19.9208,
                'longitude'    => -43.9376,
                'valor_mensalidade' => 99.90,
                'quantidade_alunos' => 600,
                'descricao'    => 'Academia tradicional no Centro, ideal para quem treina no horário de almoço.',
                'tipos_aulas'  => 'Musculação, Spinning, Ginástica, Boxe',
                'infraestrutura' => 'Musculação em dois pisos, ringue de boxe, sala de spinning e vestiários amplos.',
            ],
            [
                'nome'         => 'Buritis Cross Training',
                'email'        => 'contato@buritiscrosstraining.com.br',
                'cnpj'         => '26.901.234/0001-65',
                'cep'          => '30575-815',
                'rua'          => 'Avenida Professor Mário Werneck, 2000',
                'bairro'       => 'Buritis',
                'endereco'     => 'Avenida Professor Mário Werneck, 2000 - Buritis, Belo Horizonte - MG',
                'latitude'     => -19.9760,
                'longitude'    => -43.9660,
                'valor_mensalidade' => 159.90,
                'quantidade_alunos' => 300,
                'descricao'    => 'Box de cross training no Buritis com turmas em todos os níveis.',
                'tipos_aulas'  => 'Cross Training, LPO, Ginástica, Mobilidade',
                'infraestrutura' => 'Galpão com rig completo, área de levantamento de peso olímpico e espaço kids.',
            ],
            [
                'nome'         => 'Barreiro Strong Gym',
                'email'        => 'contato@barreirostronggym.com.br',
                'cnpj'         => '27.012.345/0001-76',
                'cep'          => '30640-070',
                'rua'          => 'Avenida Afonso Vaz de Melo, 640',
                'bairro'       => 'Barreiro',
                'endereco'     => 'Avenida Afonso Vaz de Melo, 640 - Barreiro, Belo Horizonte - MG',
                'latitude'     => -19.9740,
                'longitude'    => -44.0290,
                'valor_mensalidade' => 89.90,
                'quantidade_alunos' => 480,
                'descricao'    => 'Academia de bairro no Barreiro com foco em musculação e preço acessível.',
                'tipos_aulas'  => 'Musculação, Jump, Zumba, Funcional',
                'infraestrutura' => 'Musculação completa, sala de aulas coletivas e estacionamento próprio.',
            ],
            [
                'nome'         => 'Cidade Nova Health Center',
                'email'        => 'contato@cidadenovahealth.com.br',
                'cnpj'         => '28.123.456/0001-87',
                'cep'          => '31170-800',
                'rua'          => 'Avenida Cristiano Machado, 4000',
                'bairro'       => 'Cidade Nova',
                'endereco'     => 'Avenida Cristiano Machado, 4000 - Cidade Nova, Belo Horizonte - MG',
                'latitude'     => -19.8930,
                'longitude'    => -43.9210,
                'valor_mensalidade' => 139.90,
                'quantidade_alunos' => 390,
                'descricao'    => 'Centro de saúde e treino na Cidade Nova, com nutrição e avaliação física.',
                'tipos_aulas'  => 'Musculação, Pilates, Nutrição Esportiva, Funcional',
                'infraestrutura' => 'Musculação, consultório de nutrição, sala de avaliação e estúdio de pilates.',
            ],
            [
                'nome'         => 'Prime Fit Santa Efigênia',
                'email'        => 'contato@primefitsantaefigenia.com.br',
                'cnpj'         => '29.234.567/0001-98',
                'cep'          => '30260-070',
                'rua'          => 'Avenida dos Andradas, 3500',
                'bairro'       => 'Santa Efigênia',
                'endereco'     => 'Avenida dos Andradas, 3500 - Santa Efigênia, Belo Horizonte - MG',
                'latitude'     => -19.9200,
                'longitude'    => -43.9260,
                'valor_mensalidade' => 119.90,
                'quantidade_alunos' => 340,
                'descricao'    => 'Academia moderna em Santa Efigênia, próxima aos hospitais e à UFMG.',
                'tipos_aulas'  => 'Musculação, Spinning, HIIT, Alongamento',
                'infraestrutura' => 'Equipamentos novos de musculação, sala de spinning e área de HIIT.',
            ],
            [
                'nome'         => 'Belvedere Wellness Club',
                'email'        => 'contato@belvederewellness.com.br',
                'cnpj'         => '30.345.678/0001-09',
                'cep'          => '30320-570',
                'rua'          => 'Avenida Luiz Paulo Franco, 500',
                'bairro'       => 'Belvedere',
                'endereco'     => 'Avenida Luiz Paulo Franco, 500 - Belvedere, Belo Horizonte - MG',
                'latitude'     => -19.9720,
                'longitude'    => -43.9430,
                'valor_mensalidade' => 189.90,
                'quantidade_alunos' => 280,
                'descricao'    => 'Wellness club de alto padrão no Belvedere, com spa e treino de elite.',
                'tipos_aulas'  => 'Musculação, Personal Training, Pilates, Yoga',
                'infraestrutura' => 'Musculação premium, spa, sauna, estúdios de yoga e pilates e piscina aquecida.',
            ],
        ];

        foreach ($academias as $dados) {
            Academia::updateOrCreate(
                ['email' => $dados['email']],
                array_merge($dados, [
                    'senha'          => $senha,
                    'cidade'         => 'Belo Horizonte',
                    'estado'         => 'MG',
                    'complemento'    => '-',
                    'status'         => 'aprovado',
                    'data_aprovacao' => now(),
                ])
            );
        }

        $this->command->info('10 academias de BH criadas/atualizadas (status aprovado, senha: academia123).');
    }
}
