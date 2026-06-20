<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'acesso_ate')) {
                // Data até quando o acesso continua válido (último pagamento + 30 dias).
                $table->date('acesso_ate')->nullable()->after('next_due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'acesso_ate')) {
                $table->dropColumn('acesso_ate');
            }
        });
    }
};
