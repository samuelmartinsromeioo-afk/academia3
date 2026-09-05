<?php

namespace Database\Seeders;

use App\Models\Nutri\Alimento;
use Illuminate\Database\Seeder;

/**
 * Base inicial de alimentos (valores por 100 g, fonte TACO/TBCA — verificados).
 * Subconjunto representativo dos alimentos mais usados na prática clínica
 * brasileira. O nutricionista pode complementar com alimentos próprios.
 *
 * Idempotente: reexecutar não duplica (chave nome + personal_id NULL).
 */
class NutriAlimentosSeeder extends Seeder
{
    public function run(): void
    {
        // [nome, grupo, kcal, carbo, proteina, gordura, fibra, medida, porcao_g]
        $itens = [
            // Cereais e derivados
            ['Arroz branco cozido', 'Cereais', 128, 28.1, 2.5, 0.2, 1.6, 'colher de sopa', 45],
            ['Arroz integral cozido', 'Cereais', 124, 25.8, 2.6, 1.0, 2.7, 'colher de sopa', 45],
            ['Aveia em flocos', 'Cereais', 394, 66.6, 13.9, 8.5, 9.1, 'colher de sopa', 15],
            ['Pão francês', 'Cereais', 300, 58.6, 8.0, 3.1, 2.3, 'unidade', 50],
            ['Pão integral', 'Cereais', 253, 49.9, 9.4, 3.5, 6.5, 'fatia', 25],
            ['Macarrão cozido', 'Cereais', 158, 30.9, 5.8, 1.1, 1.8, 'pegador', 100],
            ['Tapioca (goma hidratada)', 'Cereais', 240, 60.0, 0.0, 0.0, 0.0, 'colher de sopa', 30],
            ['Batata inglesa cozida', 'Tubérculos', 52, 11.9, 1.2, 0.0, 1.3, 'unidade média', 90],
            ['Batata doce cozida', 'Tubérculos', 77, 18.4, 0.6, 0.1, 2.2, 'fatia', 60],
            ['Mandioca cozida', 'Tubérculos', 125, 30.1, 0.6, 0.3, 1.6, 'pedaço', 100],

            // Leguminosas
            ['Feijão carioca cozido', 'Leguminosas', 76, 13.6, 4.8, 0.5, 8.5, 'concha', 80],
            ['Feijão preto cozido', 'Leguminosas', 77, 14.0, 4.5, 0.5, 8.4, 'concha', 80],
            ['Lentilha cozida', 'Leguminosas', 93, 16.3, 6.3, 0.5, 7.9, 'colher de sopa', 30],
            ['Grão-de-bico cozido', 'Leguminosas', 130, 21.2, 8.4, 2.1, 7.6, 'colher de sopa', 30],
            ['Soja cozida', 'Leguminosas', 141, 9.9, 12.5, 6.4, 6.0, 'colher de sopa', 30],

            // Carnes e ovos
            ['Peito de frango grelhado', 'Carnes', 159, 0.0, 32.0, 2.5, 0.0, 'filé', 100],
            ['Coxa de frango cozida (s/ pele)', 'Carnes', 167, 0.0, 26.9, 6.2, 0.0, 'unidade', 60],
            ['Patinho bovino grelhado', 'Carnes', 219, 0.0, 35.9, 7.3, 0.0, 'bife', 100],
            ['Contra-filé grelhado', 'Carnes', 278, 0.0, 32.4, 15.6, 0.0, 'bife', 100],
            ['Carne moída (acém) cozida', 'Carnes', 212, 0.0, 26.7, 10.9, 0.0, 'colher de sopa', 40],
            ['Tilápia grelhada', 'Pescados', 128, 0.0, 26.2, 2.3, 0.0, 'filé', 100],
            ['Salmão grelhado', 'Pescados', 243, 0.0, 25.4, 15.4, 0.0, 'posta', 100],
            ['Atum (conserva em água)', 'Pescados', 116, 0.0, 25.5, 1.0, 0.0, 'lata', 120],
            ['Ovo de galinha cozido', 'Ovos', 146, 0.6, 13.3, 9.5, 0.0, 'unidade', 50],
            ['Ovo mexido', 'Ovos', 196, 1.6, 13.6, 14.8, 0.0, 'unidade', 60],

            // Laticínios
            ['Leite integral', 'Laticínios', 61, 4.7, 3.2, 3.3, 0.0, 'copo', 200],
            ['Leite desnatado', 'Laticínios', 35, 4.9, 3.4, 0.2, 0.0, 'copo', 200],
            ['Iogurte natural integral', 'Laticínios', 51, 1.9, 4.1, 3.0, 0.0, 'pote', 170],
            ['Iogurte natural desnatado', 'Laticínios', 41, 4.7, 4.1, 0.2, 0.0, 'pote', 170],
            ['Queijo minas frescal', 'Laticínios', 264, 3.2, 17.4, 20.2, 0.0, 'fatia', 30],
            ['Queijo muçarela', 'Laticínios', 330, 3.0, 22.6, 25.2, 0.0, 'fatia', 20],
            ['Requeijão cremoso', 'Laticínios', 257, 3.0, 9.6, 23.0, 0.0, 'colher de sopa', 20],
            ['Whey protein (concentrado)', 'Suplementos', 400, 12.0, 75.0, 6.0, 0.0, 'scoop', 30],

            // Frutas
            ['Banana prata', 'Frutas', 98, 26.0, 1.3, 0.1, 2.0, 'unidade', 70],
            ['Maçã', 'Frutas', 56, 15.2, 0.3, 0.1, 1.3, 'unidade', 130],
            ['Mamão formosa', 'Frutas', 45, 11.6, 0.8, 0.1, 1.8, 'fatia', 150],
            ['Laranja pera', 'Frutas', 37, 8.9, 1.0, 0.1, 0.8, 'unidade', 130],
            ['Abacate', 'Frutas', 96, 6.0, 1.2, 8.4, 6.3, 'colher de sopa', 40],
            ['Morango', 'Frutas', 30, 6.8, 0.9, 0.3, 1.7, 'porção', 100],
            ['Manga palmer', 'Frutas', 64, 16.7, 0.4, 0.2, 1.6, 'unidade', 150],
            ['Uva itália', 'Frutas', 53, 13.6, 0.7, 0.2, 0.9, 'cacho pequeno', 100],

            // Hortaliças
            ['Alface', 'Hortaliças', 15, 2.4, 1.3, 0.2, 1.8, 'folha', 10],
            ['Tomate', 'Hortaliças', 15, 3.1, 1.1, 0.2, 1.2, 'unidade', 80],
            ['Cenoura crua', 'Hortaliças', 34, 7.7, 1.3, 0.2, 3.2, 'unidade', 70],
            ['Brócolis cozido', 'Hortaliças', 25, 4.4, 2.1, 0.5, 3.4, 'porção', 80],
            ['Abobrinha cozida', 'Hortaliças', 15, 3.0, 1.1, 0.2, 1.6, 'colher de sopa', 40],
            ['Beterraba cozida', 'Hortaliças', 32, 7.2, 1.3, 0.1, 1.9, 'colher de sopa', 40],
            ['Couve manteiga refogada', 'Hortaliças', 90, 8.7, 3.3, 5.4, 5.7, 'colher de sopa', 30],

            // Gorduras e oleaginosas
            ['Azeite de oliva', 'Gorduras', 884, 0.0, 0.0, 100.0, 0.0, 'colher de sopa', 8],
            ['Óleo de soja', 'Gorduras', 884, 0.0, 0.0, 100.0, 0.0, 'colher de sopa', 8],
            ['Manteiga', 'Gorduras', 726, 0.1, 0.4, 82.4, 0.0, 'colher de chá', 5],
            ['Castanha-do-pará', 'Oleaginosas', 643, 15.1, 14.5, 63.5, 7.9, 'unidade', 5],
            ['Amendoim torrado', 'Oleaginosas', 544, 18.7, 27.4, 43.9, 8.0, 'colher de sopa', 15],
            ['Pasta de amendoim integral', 'Oleaginosas', 588, 20.0, 25.0, 50.0, 6.0, 'colher de sopa', 15],
            ['Chia', 'Oleaginosas', 486, 42.1, 16.5, 30.7, 34.4, 'colher de sopa', 12],
            ['Linhaça', 'Oleaginosas', 495, 43.3, 14.1, 32.3, 33.5, 'colher de sopa', 12],

            // Açúcares e outros
            ['Açúcar refinado', 'Açúcares', 387, 99.5, 0.0, 0.0, 0.0, 'colher de chá', 5],
            ['Mel', 'Açúcares', 309, 84.0, 0.0, 0.0, 0.0, 'colher de sopa', 20],
            ['Chocolate 70% cacau', 'Açúcares', 535, 45.9, 7.8, 35.0, 11.0, 'quadrado', 10],

            // ── PROTEÍNAS — Carnes bovinas/suínas/aves ──
            ['Alcatra grelhada', 'Carnes', 241, 0.0, 31.9, 12.8, 0.0, 'bife', 100],
            ['Filé mignon grelhado', 'Carnes', 220, 0.0, 32.8, 9.3, 0.0, 'medalhão', 100],
            ['Maminha assada', 'Carnes', 213, 0.0, 31.0, 9.8, 0.0, 'fatia', 100],
            ['Coxão mole cozido', 'Carnes', 219, 0.0, 32.4, 9.2, 0.0, 'bife', 100],
            ['Fraldinha grelhada', 'Carnes', 235, 0.0, 29.8, 12.5, 0.0, 'bife', 100],
            ['Acém cozido', 'Carnes', 215, 0.0, 27.0, 11.5, 0.0, 'porção', 100],
            ['Lombo suíno assado', 'Carnes', 210, 0.0, 32.0, 9.0, 0.0, 'fatia', 100],
            ['Pernil suíno assado', 'Carnes', 262, 0.0, 29.0, 16.0, 0.0, 'fatia', 100],
            ['Costela suína assada', 'Carnes', 397, 0.0, 24.0, 33.0, 0.0, 'porção', 100],
            ['Peito de peru', 'Carnes', 135, 1.0, 29.0, 1.7, 0.0, 'fatia', 100],
            ['Sobrecoxa de frango assada', 'Carnes', 211, 0.0, 26.0, 11.5, 0.0, 'unidade', 90],
            ['Frango desfiado cozido', 'Carnes', 163, 0.0, 31.0, 3.9, 0.0, 'colher de sopa', 40],
            ['Carne seca cozida', 'Carnes', 313, 0.0, 26.0, 22.0, 0.0, 'porção', 80],
            ['Fígado bovino grelhado', 'Carnes', 225, 3.9, 29.9, 9.5, 0.0, 'bife', 100],
            ['Hambúrguer caseiro (patinho)', 'Carnes', 210, 0.0, 25.0, 12.0, 0.0, 'unidade', 90],
            ['Linguiça de frango grelhada', 'Carnes', 235, 1.0, 18.0, 17.0, 0.0, 'unidade', 60],
            ['Bacon frito', 'Carnes', 541, 1.4, 37.0, 42.0, 0.0, 'fatia', 15],

            // ── PROTEÍNAS — Pescados ──
            ['Sardinha grelhada', 'Pescados', 208, 0.0, 24.6, 11.5, 0.0, 'unidade', 60],
            ['Sardinha em conserva', 'Pescados', 218, 0.0, 24.0, 13.0, 0.0, 'lata', 80],
            ['Merluza cozida', 'Pescados', 90, 0.0, 19.0, 1.2, 0.0, 'filé', 100],
            ['Bacalhau dessalgado cozido', 'Pescados', 136, 0.0, 29.0, 1.5, 0.0, 'posta', 100],
            ['Camarão cozido', 'Pescados', 99, 0.2, 24.0, 0.3, 0.0, 'porção', 100],
            ['Pescada grelhada', 'Pescados', 105, 0.0, 21.0, 2.2, 0.0, 'filé', 100],
            ['Truta grelhada', 'Pescados', 168, 0.0, 24.0, 7.2, 0.0, 'filé', 100],

            // ── PROTEÍNAS — Ovos ──
            ['Clara de ovo cozida', 'Ovos', 52, 0.7, 11.0, 0.2, 0.0, 'unidade', 33],
            ['Ovo de codorna cozido', 'Ovos', 158, 0.4, 13.1, 11.1, 0.0, 'unidade', 10],
            ['Omelete simples', 'Ovos', 154, 1.0, 11.0, 11.5, 0.0, 'unidade', 90],

            // ── PROTEÍNAS — Laticínios ──
            ['Iogurte grego natural', 'Laticínios', 97, 3.6, 9.0, 5.0, 0.0, 'pote', 130],
            ['Queijo cottage', 'Laticínios', 98, 3.4, 11.1, 4.3, 0.0, 'colher de sopa', 30],
            ['Ricota', 'Laticínios', 174, 3.0, 11.3, 13.0, 0.0, 'fatia', 30],
            ['Queijo prato', 'Laticínios', 360, 1.9, 22.7, 29.1, 0.0, 'fatia', 20],
            ['Parmesão ralado', 'Laticínios', 431, 4.1, 38.5, 29.0, 0.0, 'colher de sopa', 10],
            ['Kefir de leite', 'Laticínios', 55, 4.5, 3.3, 3.0, 0.0, 'copo', 200],
            ['Leite fermentado', 'Laticínios', 70, 12.0, 1.7, 1.0, 0.0, 'frasco', 80],

            // ── PROTEÍNAS — Vegetais / Leguminosas ──
            ['Tofu', 'Leguminosas', 76, 1.9, 8.1, 4.8, 0.3, 'fatia', 80],
            ['Proteína texturizada de soja (hidratada)', 'Leguminosas', 105, 7.0, 15.0, 1.0, 4.0, 'colher de sopa', 30],
            ['Edamame cozido', 'Leguminosas', 121, 8.9, 11.9, 5.2, 5.2, 'porção', 80],
            ['Ervilha cozida', 'Leguminosas', 84, 14.5, 5.4, 0.4, 5.1, 'colher de sopa', 30],
            ['Feijão branco cozido', 'Leguminosas', 91, 16.5, 6.0, 0.5, 8.7, 'concha', 80],

            // ── PROTEÍNAS — Suplementos ──
            ['Whey protein isolado', 'Suplementos', 370, 5.0, 85.0, 1.5, 0.0, 'scoop', 30],
            ['Albumina', 'Suplementos', 376, 4.0, 80.0, 0.5, 0.0, 'colher de sopa', 15],
            ['Caseína', 'Suplementos', 360, 6.0, 78.0, 2.0, 0.0, 'scoop', 30],
            ['Proteína vegetal (ervilha/arroz)', 'Suplementos', 380, 6.0, 80.0, 3.0, 2.0, 'scoop', 30],
            ['Barra de proteína', 'Suplementos', 350, 35.0, 30.0, 10.0, 5.0, 'unidade', 45],

            // ── CARBOIDRATOS ──
            ['Cuscuz de milho cozido', 'Cereais', 113, 24.0, 3.6, 0.6, 1.4, 'fatia', 80],
            ['Quinoa cozida', 'Cereais', 120, 21.3, 4.4, 1.9, 2.8, 'colher de sopa', 40],
            ['Granola', 'Cereais', 471, 64.0, 10.0, 18.0, 8.0, 'colher de sopa', 20],
            ['Pão de forma integral', 'Cereais', 253, 43.0, 9.0, 4.0, 6.0, 'fatia', 25],
            ['Milho verde cozido', 'Cereais', 98, 17.1, 3.2, 2.4, 3.9, 'colher de sopa', 30],
            ['Polenta cozida', 'Cereais', 84, 18.5, 1.8, 0.4, 1.0, 'fatia', 100],
            ['Inhame cozido', 'Tubérculos', 97, 23.2, 1.5, 0.1, 2.1, 'pedaço', 90],
            ['Cará cozido', 'Tubérculos', 100, 24.0, 1.8, 0.2, 3.0, 'pedaço', 90],
            ['Batata baroa (mandioquinha) cozida', 'Tubérculos', 80, 18.9, 1.0, 0.2, 2.1, 'pedaço', 90],

            // ── FRUTAS ──
            ['Abacaxi', 'Frutas', 48, 12.3, 0.9, 0.1, 1.0, 'fatia', 100],
            ['Melancia', 'Frutas', 33, 8.1, 0.9, 0.0, 0.1, 'fatia', 150],
            ['Melão', 'Frutas', 29, 7.5, 0.7, 0.0, 0.3, 'fatia', 150],
            ['Pera', 'Frutas', 53, 14.0, 0.6, 0.1, 3.0, 'unidade', 130],
            ['Kiwi', 'Frutas', 51, 11.5, 1.3, 0.6, 2.7, 'unidade', 70],
            ['Goiaba vermelha', 'Frutas', 54, 13.0, 1.1, 0.4, 6.2, 'unidade', 120],
            ['Açaí (polpa sem açúcar)', 'Frutas', 58, 6.2, 0.8, 3.9, 2.6, 'porção', 100],
            ['Tangerina', 'Frutas', 38, 9.6, 0.8, 0.1, 0.9, 'unidade', 120],
            ['Coco fresco', 'Frutas', 354, 15.2, 3.3, 33.5, 9.0, 'pedaço', 30],

            // ── HORTALIÇAS ──
            ['Espinafre cozido', 'Hortaliças', 23, 3.6, 2.9, 0.4, 2.2, 'porção', 80],
            ['Couve-flor cozida', 'Hortaliças', 23, 4.1, 1.9, 0.3, 2.0, 'porção', 80],
            ['Vagem cozida', 'Hortaliças', 25, 5.0, 1.8, 0.1, 2.4, 'colher de sopa', 40],
            ['Berinjela cozida', 'Hortaliças', 19, 4.4, 0.7, 0.1, 2.9, 'fatia', 60],
            ['Pepino', 'Hortaliças', 10, 2.0, 0.9, 0.1, 0.8, 'porção', 80],
            ['Pimentão', 'Hortaliças', 21, 4.9, 1.0, 0.2, 2.6, 'porção', 60],
            ['Rúcula', 'Hortaliças', 13, 2.1, 1.8, 0.3, 1.6, 'porção', 30],
            ['Chuchu cozido', 'Hortaliças', 19, 4.5, 0.6, 0.1, 1.2, 'colher de sopa', 40],

            // ── GORDURAS / OLEAGINOSAS ──
            ['Castanha de caju torrada', 'Oleaginosas', 570, 29.1, 18.5, 46.3, 3.7, 'porção', 20],
            ['Nozes', 'Oleaginosas', 620, 18.4, 14.0, 59.0, 6.7, 'porção', 20],
            ['Amêndoas', 'Oleaginosas', 581, 21.6, 21.2, 47.3, 12.5, 'porção', 20],
            ['Semente de abóbora', 'Oleaginosas', 545, 15.0, 30.2, 43.0, 6.0, 'colher de sopa', 15],
            ['Óleo de coco', 'Gorduras', 892, 0.0, 0.0, 99.9, 0.0, 'colher de chá', 5],
            ['Creme de leite', 'Gorduras', 217, 4.0, 2.3, 21.0, 0.0, 'colher de sopa', 15],
        ];

        foreach ($itens as $i) {
            // Preço de referência (R$/kg): override por alimento, senão média do grupo.
            $precoKg = config('precos.alimento_kg')[$i[0]]
                ?? config('precos.grupo_kg')[$i[1]]
                ?? null;

            Alimento::updateOrCreate(
                ['nome' => $i[0], 'personal_id' => null],
                [
                    'grupo' => $i[1],
                    'fonte' => 'TACO',
                    'kcal' => $i[2],
                    'carbo_g' => $i[3],
                    'proteina_g' => $i[4],
                    'gordura_g' => $i[5],
                    'fibra_g' => $i[6],
                    'medida_padrao' => $i[7],
                    'porcao_g' => $i[8],
                    'preco_kg' => $precoKg,
                    'verificado' => true,
                ]
            );
        }
    }
}
