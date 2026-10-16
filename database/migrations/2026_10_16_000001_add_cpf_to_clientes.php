<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CPF do aluno — exigência da API do Asaas, não nossa.
 *
 * `POST /v3/customers` declara `"required": ["name", "cpfCnpj"]`, e a tabela
 * `clientes` nunca teve o campo: nenhum dos dois cadastros coleta. Resultado,
 * descoberto ao auditar a integração contra a referência oficial: todo aluno
 * cujo cliente ainda não existisse no Asaas tinha a cobrança recusada — e a
 * recusa chegava ao app como "Erro interno. Tente novamente.", porque a
 * criação do cliente não checava a resposta.
 *
 * NULLABLE de propósito, e isso é a decisão principal desta migration. O CPF
 * é pedido no CHECKOUT, na primeira vez que o aluno vai pagar, e não no
 * cadastro:
 *
 *  - quem só usa a parte gratuita nunca precisa informar documento, o que é
 *    minimização de dado na acepção da LGPD (art. 6º, III) e não só conforto;
 *  - tornar obrigatório no cadastro quebraria o app já publicado na loja, que
 *    não envia o campo — 422 em cima de quem tentasse criar conta;
 *  - é o que o fluxo de cartão já fazia: ele pede o CPF do portador na hora
 *    de pagar. Aqui o valor passa a ser guardado, para não ser pedido de novo.
 *
 * Sem unique: CPF repetido entre contas é possível de verdade (conta criada
 * duas vezes pela mesma pessoa) e um índice único faria a SEGUNDA cobrança da
 * vida dessa pessoa falhar, em vez de impedir o cadastro duplicado lá atrás —
 * que é onde o problema estaria.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A tabela `migrations` de produção está dessincronizada do diretório,
        // então a coluna pode já existir. Guardar é mais barato que descobrir
        // no meio de um deploy.
        if (Schema::hasColumn('clientes', 'cpf')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            // 14 = CNPJ sem máscara. Guardamos só dígitos, mas a folga evita
            // truncamento silencioso se algum dia um aluno for PJ.
            $table->string('cpf', 14)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('clientes', 'cpf')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('cpf');
        });
    }
};
