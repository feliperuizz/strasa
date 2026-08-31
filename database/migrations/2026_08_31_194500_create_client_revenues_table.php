<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faturamento do PRÓPRIO CLIENTE, informado por ele e lançado pela equipe.
 *
 * Nada a ver com a tabela `payments`, que é o controle interno da agência
 * (se o cliente pagou o boleto NOSSO). Aqui é o quanto o negócio DELE
 * faturou no mês — o número que mostra se o trabalho está dando resultado.
 *
 * Com investimento em mídia e número de vendas no mesmo lançamento, dá para
 * derivar ROAS e ticket médio sem pedir mais nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_revenues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Primeiro dia do mês de referência, para ordenar e agrupar direito.
            $table->date('reference_month');

            $table->decimal('revenue', 14, 2);
            $table->decimal('ad_spend', 14, 2)->nullable();
            $table->unsignedInteger('orders')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Um lançamento por cliente por mês; relançar corrige o valor.
            $table->unique(['client_id', 'reference_month'], 'client_revenues_unico');
            $table->index(['company_id', 'reference_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_revenues');
    }
};
