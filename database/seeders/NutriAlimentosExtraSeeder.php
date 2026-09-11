<?php

namespace Database\Seeders;

use App\Models\Nutri\Alimento;
use Illuminate\Database\Seeder;

/**
 * Complementa a base do NutriAlimentosSeeder com:
 *
 *  1. Mais fontes de proteína (carnes, pescados, ovos, leguminosas, laticínios
 *     e suplementos), para o gerador ter variedade real ao rotacionar o pool.
 *  2. Vitaminas e sucos — preparações PRONTAS, com os macros já somados da
 *     bebida inteira e a receita no campo `preparo`. Ficam em grupos próprios
 *     ('Vitaminas' e 'Sucos') para o gerador poder usá-las como refeição ou
 *     como opção de substituição.
 *
 * Valores por 100 g (ou 100 ml nas bebidas). Idempotente: reexecutar atualiza
 * em vez de duplicar (chave nome + personal_id NULL).
 */
class NutriAlimentosExtraSeeder extends Seeder
{
    public function run(): void
    {
        // A base original (TACO) não tem modo de preparo, então a receita da
        // refeição sairia quase vazia. Aqui os itens mais recorrentes ganham o
        // preparo sem mexer nos macros já validados.
        foreach ($this->preparoDosBasicos() as $nome => $preparo) {
            Alimento::where('nome', $nome)->whereNull('personal_id')->update(['preparo' => $preparo]);
        }

        foreach ($this->itens() as $i) {
            [$nome, $grupo, $kcal, $carbo, $prot, $gord, $fibra, $medida, $porcao, $preparo, $contem] = $i;

            $precoKg = config('precos.alimento_kg')[$nome]
                ?? config('precos.grupo_kg')[$grupo]
                ?? null;

            Alimento::updateOrCreate(
                ['nome' => $nome, 'personal_id' => null],
                [
                    'grupo' => $grupo,
                    'fonte' => in_array($grupo, Alimento::GRUPOS_PREPARO, true) ? 'Receita' : 'TACO',
                    'kcal' => $kcal,
                    'carbo_g' => $carbo,
                    'proteina_g' => $prot,
                    'gordura_g' => $gord,
                    'fibra_g' => $fibra,
                    'medida_padrao' => $medida,
                    'porcao_g' => $porcao,
                    'preparo' => $preparo,
                    'contem' => $contem,
                    'preco_kg' => $precoKg,
                    'verificado' => true,
                ]
            );
        }
    }

    /** Modo de preparo dos alimentos que já existiam na base TACO. */
    private function preparoDosBasicos(): array
    {
        return [
            'Arroz branco cozido' => 'Refogue o arroz no alho e azeite, junte o dobro de água fervente e cozinhe tampado por 15 min.',
            'Arroz integral cozido' => 'Deixe de molho 30 min, refogue no alho e cozinhe com 2,5x de água por 35 min.',
            'Aveia em flocos' => 'Misture no iogurte ou cozinhe 3 min no leite com canela para virar mingau.',
            'Pão francês' => 'Aqueça no forno ou na airfryer por 3 min para voltar a crocância.',
            'Pão integral' => 'Toste na frigideira sem óleo até dourar dos dois lados.',
            'Macarrão cozido' => 'Cozinhe em água fervente salgada até al dente e reserve uma concha da água para o molho.',
            'Tapioca (goma hidratada)' => 'Peneire a goma na frigideira quente, espalhe e vire quando soltar sozinha.',
            'Batata inglesa cozida' => 'Cozinhe com casca por 20 min; asse depois com azeite e alecrim se quiser dourada.',
            'Batata doce cozida' => 'Cozinhe em rodelas por 15 min ou asse a 200 °C por 30 min com casca.',
            'Mandioca cozida' => 'Cozinhe na pressão por 20 min até amolecer e descarte o fio central.',
            'Inhame cozido' => 'Cozinhe com casca por 20 min, descasque ainda morno e amasse com azeite.',
            'Feijão carioca cozido' => 'Deixe de molho na véspera, cozinhe na pressão 25 min e refogue com alho e cebola.',
            'Feijão preto cozido' => 'Molho de 12 h, pressão por 30 min e tempero final com alho, louro e cheiro-verde.',
            'Lentilha cozida' => 'Não precisa de molho: cozinhe 20 min com louro e finalize com azeite e limão.',
            'Grão-de-bico cozido' => 'Molho de 12 h e pressão por 25 min. Sobrou? Asse com páprica para petisco.',
            'Soja cozida' => 'Molho de 12 h trocando a água e cozimento na pressão por 30 min.',
            'Peito de frango grelhado' => 'Tempere com limão, alho e sal 20 min antes. Grelhe 5 min de cada lado sem furar.',
            'Coxa de frango cozida (s/ pele)' => 'Refogue com cebola e tomate e cozinhe 25 min no próprio caldo.',
            'Patinho bovino grelhado' => 'Corte em bifes finos, tempere só com sal e grelhe 2 min de cada lado.',
            'Carne moída (acém) cozida' => 'Doure sem mexer para selar, junte alho, cebola e tomate e apure 15 min.',
            'Tilápia grelhada' => 'Tempere com limão e sal, grelhe 4 min de cada lado em fogo médio.',
            'Salmão grelhado' => 'Grelhe com a pele para baixo 5 min, vire por 2 min e finalize com limão.',
            'Atum (conserva em água)' => 'Escorra e misture com cebola roxa, azeite e limão.',
            'Ovo de galinha cozido' => 'Água fervente por 8 min para gema cremosa, 10 min para firme; choque térmico em seguida.',
            'Ovo mexido' => 'Fogo baixo, mexendo sempre, e tire do fogo ainda úmido — ele termina de cozinhar no prato.',
            'Brócolis cozido' => 'Cozinhe no vapor por 5 min até ficar verde vivo e salteie no alho e azeite.',
            'Couve manteiga refogada' => 'Corte bem fino, refogue no alho por 2 min em fogo alto e sirva na hora.',
            'Abobrinha cozida' => 'Corte em meia-lua e refogue 6 min com azeite e alho, sem tampar, para não soltar água.',
            'Cenoura cozida' => 'Cozinhe em rodelas por 10 min ou asse com mel e tomilho.',
            'Beterraba cozida' => 'Cozinhe inteira com casca por 30 min e descasque depois, para não perder a cor.',
            'Abacate' => 'Amasse com limão e sal, ou bata com cacau e adoçante para virar mousse.',
            'Banana prata' => 'Boa in natura; grelhada com canela vira sobremesa sem açúcar.',
            'Pasta de amendoim integral' => 'Use como recheio de pão, na aveia ou batida na vitamina.',
            'Linhaça' => 'Triture antes de usar — inteira passa reta pelo intestino e não aproveita o ômega-3.',
            'Chia' => 'Hidrate em água ou iogurte por 15 min até virar gel.',
            'Azeite de oliva extravirgem' => 'Use cru, sobre o prato pronto. No fogo alto ele perde a qualidade.',
        ];
    }

    /** [nome, grupo, kcal, carbo, prot, gord, fibra, medida, porção_g, preparo, contem] */
    private function itens(): array
    {
        return [
            // ── Carnes ──────────────────────────────────────────────────────
            ['Alcatra grelhada', 'Carnes', 241, 0.0, 31.9, 12.8, 0.0, 'bife', 100, 'Tempere com sal e alho, deixe a frigideira bem quente e grelhe 3–4 min de cada lado. Descanse 2 min antes de cortar.', 'animal'],
            ['Filé mignon grelhado', 'Carnes', 220, 0.0, 32.8, 9.4, 0.0, 'medalhão', 100, 'Sele em fogo alto 2 min de cada lado e finalize em fogo baixo até o ponto desejado.', 'animal'],
            ['Maminha assada', 'Carnes', 213, 0.0, 31.0, 9.6, 0.0, 'fatia', 100, 'Tempere com sal grosso e asse a 200 °C por 40 min, com a gordura voltada para cima.', 'animal'],
            ['Músculo bovino cozido', 'Carnes', 194, 0.0, 29.6, 7.7, 0.0, 'pedaço', 100, 'Cozinhe na pressão por 30 min com cebola, alho e louro. Rende um caldo ótimo para a própria refeição.', 'animal'],
            ['Frango desfiado cozido', 'Carnes', 163, 0.0, 30.0, 4.0, 0.0, 'porção', 100, 'Cozinhe o peito com louro e sal, desfie ainda morno e refogue com tomate e cebola.', 'animal'],
            ['Sobrecoxa de frango assada (s/ pele)', 'Carnes', 187, 0.0, 28.5, 7.6, 0.0, 'unidade', 90, 'Marine com limão, alho e páprica por 30 min e asse a 200 °C por 35 min.', 'animal'],
            ['Lombo suíno assado', 'Carnes', 210, 0.0, 35.7, 6.8, 0.0, 'fatia', 100, 'Marine com alho, laranja e ervas na véspera e asse coberto a 180 °C por 50 min.', 'animal'],
            ['Peito de peru defumado', 'Carnes', 96, 1.6, 17.6, 2.0, 0.0, 'fatia', 20, 'Pronto para consumo. Bom no pão integral com queijo branco e tomate.', 'animal'],
            ['Carne seca dessalgada cozida', 'Carnes', 197, 0.0, 32.0, 7.0, 0.0, 'porção', 100, 'Deixe de molho trocando a água 3 vezes, cozinhe na pressão 20 min e desfie.', 'animal'],
            ['Fígado bovino grelhado', 'Carnes', 225, 4.3, 29.9, 9.1, 0.0, 'bife', 100, 'Deixe no leite por 20 min para suavizar o sabor, escorra e grelhe com cebola.', 'animal,lactose'],

            // ── Pescados ────────────────────────────────────────────────────
            ['Sardinha assada', 'Pescados', 164, 0.0, 32.2, 3.0, 0.0, 'unidade', 80, 'Tempere com limão e sal e asse a 200 °C por 20 min. Fonte barata de ômega-3.', 'animal'],
            ['Sardinha em conserva (escorrida)', 'Pescados', 208, 0.0, 24.6, 11.5, 0.0, 'lata', 84, 'Escorra bem o óleo. Amasse com limão e cebola roxa para rechear pão ou salada.', 'animal'],
            ['Merluza cozida', 'Pescados', 122, 0.0, 26.6, 1.0, 0.0, 'filé', 100, 'Cozinhe no vapor ou refogue com tomate, cebola e coentro por 10 min.', 'animal'],
            ['Bacalhau dessalgado cozido', 'Pescados', 136, 0.0, 29.0, 1.5, 0.0, 'posta', 100, 'Dessalgue por 24 h trocando a água, cozinhe 15 min e desfie em lascas.', 'animal'],
            ['Camarão cozido', 'Pescados', 90, 0.0, 19.4, 1.0, 0.0, 'porção', 100, 'Salteie 3 min com alho e azeite — passou disso, borracha.', 'animal'],
            ['Polvo cozido', 'Pescados', 96, 2.2, 17.9, 1.2, 0.0, 'porção', 100, 'Cozinhe na pressão 20 min sem água (ele solta a própria) e finalize na brasa.', 'animal'],
            ['Atum fresco grelhado', 'Pescados', 184, 0.0, 29.9, 6.3, 0.0, 'posta', 100, 'Sele 1 min de cada lado deixando o miolo rosado, com gergelim e shoyu.', 'animal'],
            ['Truta grelhada', 'Pescados', 168, 0.0, 24.3, 7.2, 0.0, 'filé', 100, 'Grelhe com a pele para baixo até ficar crocante e finalize com limão siciliano.', 'animal'],

            // ── Ovos ────────────────────────────────────────────────────────
            ['Clara de ovo cozida', 'Ovos', 52, 0.7, 10.9, 0.2, 0.0, 'unidade', 33, 'Cozinhe o ovo por 10 min e descarte a gema. Proteína quase pura.', 'animal'],
            ['Omelete simples', 'Ovos', 180, 1.2, 12.5, 13.6, 0.0, 'unidade', 120, 'Bata 2 ovos com sal, despeje na frigideira antiaderente em fogo baixo e recheie a gosto.', 'animal'],
            ['Ovo de codorna cozido', 'Ovos', 158, 0.4, 13.7, 11.1, 0.0, 'unidade', 10, 'Cozinhe por 5 min a partir da fervura e passe na água fria para descascar fácil.', 'animal'],

            // ── Leguminosas (proteína vegetal) ──────────────────────────────
            ['Tofu firme', 'Leguminosas', 76, 1.9, 8.1, 4.8, 0.3, 'fatia', 80, 'Prense 20 min para tirar a água, corte em cubos e doure com shoyu e gengibre.', ''],
            ['Tempeh', 'Leguminosas', 193, 9.4, 18.5, 10.8, 4.8, 'fatia', 80, 'Corte em fatias finas, marine com shoyu e alho e grelhe até dourar dos dois lados.', ''],
            ['Proteína texturizada de soja (hidratada)', 'Leguminosas', 108, 7.0, 15.0, 1.5, 3.5, 'porção', 80, 'Hidrate em água quente por 10 min, esprema bem e refogue como carne moída.', ''],
            ['Edamame cozido', 'Leguminosas', 122, 8.9, 11.9, 5.2, 5.2, 'porção', 80, 'Cozinhe 5 min em água salgada e sirva na vagem com flor de sal.', ''],
            ['Feijão branco cozido', 'Leguminosas', 92, 16.0, 6.4, 0.5, 6.3, 'concha', 80, 'Deixe de molho na véspera e cozinhe na pressão 20 min com louro.', ''],
            ['Ervilha seca cozida', 'Leguminosas', 89, 15.0, 6.0, 0.4, 5.5, 'concha', 80, 'Cozinhe até desmanchar e bata parte do caldo para engrossar a sopa.', ''],

            // ── Laticínios ──────────────────────────────────────────────────
            ['Iogurte grego natural', 'Laticínios', 97, 3.6, 9.0, 5.0, 0.0, 'pote', 130, 'Sirva gelado com frutas e canela. Sem açúcar, serve de base para molhos.', 'animal,lactose'],
            ['Skyr (iogurte proteico)', 'Laticínios', 63, 4.0, 11.0, 0.2, 0.0, 'pote', 150, 'Bata com frutas congeladas para virar um sorvete proteico.', 'animal,lactose'],
            ['Queijo cottage', 'Laticínios', 98, 3.4, 11.1, 4.3, 0.0, 'colher de sopa', 30, 'Tempere com azeite, orégano e tomate picado para rechear torrada ou tapioca.', 'animal,lactose'],
            ['Ricota fresca', 'Laticínios', 140, 3.8, 11.4, 8.8, 0.0, 'fatia', 40, 'Amasse com iogurte natural e ervas para virar patê de sanduíche.', 'animal,lactose'],
            ['Queijo minas frescal', 'Laticínios', 264, 3.2, 17.4, 20.2, 0.0, 'fatia', 30, 'Sirva em cubos com tomate e azeite, ou grelhe rapidamente na antiaderente.', 'animal,lactose'],
            ['Kefir de leite', 'Laticínios', 55, 4.5, 3.3, 2.6, 0.0, 'copo', 200, 'Deixe fermentar 24 h, coe os grãos e beba puro ou batido com fruta.', 'animal,lactose'],

            // ── Suplementos ─────────────────────────────────────────────────
            ['Whey protein concentrado', 'Suplementos', 400, 8.0, 76.0, 6.0, 0.0, 'scoop', 30, 'Dissolva em 200 ml de água ou leite gelado. Bata com gelo para dar corpo.', 'animal,lactose'],
            ['Proteína de ervilha', 'Suplementos', 380, 5.0, 80.0, 6.0, 0.0, 'scoop', 30, 'Bata com bebida vegetal e banana — sozinha na água a textura fica arenosa.', ''],
            ['Proteína isolada de soja', 'Suplementos', 370, 5.0, 85.0, 1.5, 0.0, 'scoop', 30, 'Dissolva em bebida vegetal gelada com cacau para mascarar o sabor.', ''],

            // ── Vitaminas (preparação pronta, valores por 100 ml) ───────────
            ['Vitamina de banana com aveia e whey', 'Vitaminas', 98, 12.4, 7.6, 1.8, 1.1, 'copo', 300,
                'Bata 1 banana média, 200 ml de leite desnatado, 1 colher de sopa de aveia e 1 scoop de whey com gelo. Rende 1 copo de 300 ml — refeição completa de café da manhã ou lanche.', 'animal,lactose,gluten'],
            ['Vitamina de morango com iogurte grego', 'Vitaminas', 84, 10.2, 6.4, 1.9, 1.0, 'copo', 300,
                'Bata 1 pote de iogurte grego natural, 8 morangos, 150 ml de leite e adoçante a gosto. Bem gelado.', 'animal,lactose'],
            ['Vitamina de abacate com cacau', 'Vitaminas', 132, 8.6, 5.2, 8.4, 2.2, 'copo', 300,
                'Bata 1/2 abacate pequeno, 200 ml de leite, 1 colher de sopa de cacau em pó e adoçante. Densa — rende lanche da tarde inteiro.', 'animal,lactose'],
            ['Vitamina vegana de banana e pasta de amendoim', 'Vitaminas', 118, 13.0, 5.8, 4.9, 1.8, 'copo', 300,
                'Bata 1 banana, 250 ml de bebida de amêndoas, 1 colher de sopa de pasta de amendoim e 1 scoop de proteína de ervilha.', 'oleaginosa'],
            ['Vitamina de mamão com linhaça', 'Vitaminas', 76, 12.8, 3.1, 1.9, 1.9, 'copo', 300,
                'Bata 1 fatia grossa de mamão, 200 ml de leite e 1 colher de sopa de linhaça dourada. Bom para o intestino.', 'animal,lactose'],
            ['Vitamina de açaí com banana', 'Vitaminas', 128, 15.4, 4.2, 5.6, 2.4, 'copo', 300,
                'Bata 100 g de polpa de açaí sem açúcar, 1 banana e 150 ml de leite. Sirva na tigela com granola se preferir.', 'animal,lactose'],

            // ── Sucos (preparação pronta, valores por 100 ml) ───────────────
            ['Suco verde (couve, limão e maçã)', 'Sucos', 38, 8.4, 0.9, 0.2, 1.4, 'copo', 300,
                'Bata 2 folhas de couve, 1 maçã, suco de 1 limão e 250 ml de água. Não coe para manter a fibra.', ''],
            ['Suco de laranja com cenoura', 'Sucos', 48, 11.0, 0.8, 0.2, 1.0, 'copo', 300,
                'Bata o suco de 3 laranjas com 1 cenoura média e sirva na hora, antes de perder a vitamina C.', ''],
            ['Suco de melancia com hortelã', 'Sucos', 32, 7.6, 0.6, 0.1, 0.4, 'copo', 300,
                'Bata 2 fatias de melancia gelada com folhas de hortelã. Sem adicionar água nem açúcar.', ''],
            ['Suco de abacaxi com gengibre', 'Sucos', 44, 10.4, 0.5, 0.1, 0.8, 'copo', 300,
                'Bata 2 rodelas de abacaxi, 1 cm de gengibre e 250 ml de água gelada.', ''],
            ['Suco de uva integral', 'Sucos', 58, 14.2, 0.4, 0.1, 0.2, 'copo', 200,
                'Use suco de uva integral sem açúcar adicionado, gelado. Combina com refeição pré-treino.', ''],
        ];
    }
}
