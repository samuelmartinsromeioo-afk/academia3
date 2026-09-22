<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `personals.certificado` continuou NOT NULL sem default depois que o upload
 * de certificado saiu do formulário de cadastro. Como o store não manda mais
 * a coluna, todo cadastro novo de personal/nutricionista morria com
 * "SQLSTATE[HY000] 1364 Field 'certificado' doesn't have a default value".
 *
 * A coluna NÃO é removida: os cadastros antigos ainda têm certificado gravado
 * e a tela do admin (admin/personals/detalhes) continua exibindo quem tem.
 *
 * Sem doctrine/dbal no projeto, o ->change() do Blueprint não está disponível
 * — daí o ALTER cru.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `personals` MODIFY `certificado` VARCHAR(255) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // Não dá para voltar ao NOT NULL deixando linha nula para trás.
        DB::statement("UPDATE `personals` SET `certificado` = '' WHERE `certificado` IS NULL");
        DB::statement('ALTER TABLE `personals` MODIFY `certificado` VARCHAR(255) NOT NULL');
    }
};
