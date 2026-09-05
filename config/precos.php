<?php

/*
|--------------------------------------------------------------------------
| Custo estimado da dieta (editável)
|--------------------------------------------------------------------------
| Preços de REFERÊNCIA em R$/kg (base nacional ~ SP) e um ÍNDICE regional por
| UF que multiplica o preço, porque o custo dos alimentos varia bastante entre
| estados (frete, oferta local). São estimativas — ajuste conforme sua região.
|
| Custo de um item = (gramas/1000) * preço_kg * indice_uf.
| Rode `php artisan config:clear` após editar.
*/

return [

    // Preço médio por grupo (R$/kg) — usado quando o alimento não tem preço próprio.
    'grupo_kg' => [
        'Carnes' => 35.0,
        'Pescados' => 45.0,
        'Ovos' => 14.0,
        'Laticínios' => 30.0,
        'Leguminosas' => 12.0,
        'Cereais' => 10.0,
        'Tubérculos' => 6.0,
        'Hortaliças' => 8.0,
        'Frutas' => 8.0,
        'Gorduras' => 25.0,
        'Oleaginosas' => 60.0,
        'Suplementos' => 150.0,
        'Açúcares' => 12.0,
    ],

    // Ajustes por alimento (R$/kg) para itens cujo preço foge da média do grupo.
    'alimento_kg' => [
        'Filé mignon grelhado' => 75.0,
        'Contra-filé grelhado' => 55.0,
        'Alcatra grelhada' => 48.0,
        'Maminha assada' => 50.0,
        'Fraldinha grelhada' => 45.0,
        'Patinho bovino grelhado' => 42.0,
        'Peito de peru' => 45.0,
        'Bacon frito' => 40.0,
        'Salmão grelhado' => 90.0,
        'Camarão cozido' => 75.0,
        'Bacalhau dessalgado cozido' => 85.0,
        'Truta grelhada' => 55.0,
        'Atum (conserva em água)' => 55.0,
        'Sardinha em conserva' => 30.0,
        'Merluza cozida' => 30.0,
        'Whey protein isolado' => 220.0,
        'Whey protein (concentrado)' => 150.0,
        'Caseína' => 180.0,
        'Proteína vegetal (ervilha/arroz)' => 170.0,
        'Barra de proteína' => 120.0,
        'Castanha-do-pará' => 90.0,
        'Castanha de caju torrada' => 80.0,
        'Nozes' => 80.0,
        'Amêndoas' => 70.0,
        'Azeite de oliva' => 45.0,
        'Óleo de coco' => 60.0,
        'Pasta de amendoim integral' => 35.0,
        'Queijo parmesão ralado' => 90.0,
        'Iogurte grego natural' => 40.0,
        'Queijo minas frescal' => 45.0,
        'Tofu' => 25.0,
        'Quinoa cozida' => 40.0,
        'Açaí (polpa sem açúcar)' => 25.0,
    ],

    // Índice regional (multiplicador do preço). Base SP = 1.00. Estimativas.
    'uf_indice' => [
        'SP' => 1.00, 'RJ' => 1.06, 'MG' => 0.98, 'ES' => 1.00,
        'PR' => 0.97, 'SC' => 0.99, 'RS' => 0.98,
        'DF' => 1.05, 'GO' => 0.96, 'MT' => 1.00, 'MS' => 0.98,
        'BA' => 1.00, 'SE' => 1.02, 'AL' => 1.02, 'PE' => 1.01,
        'PB' => 1.02, 'RN' => 1.02, 'CE' => 1.00, 'PI' => 1.03, 'MA' => 1.04,
        'PA' => 1.10, 'AP' => 1.18, 'AM' => 1.15, 'RR' => 1.20,
        'RO' => 1.12, 'AC' => 1.22, 'TO' => 1.05,
    ],

    // Dias considerados no custo mensal.
    'dias_mes' => 30,
];
