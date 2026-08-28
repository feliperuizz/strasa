<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas de redes sociais lançadas à mão pela equipe.
 *
 * Cada linha é uma FOTOGRAFIA de uma rede numa data: guardamos os números
 * absolutos daquele momento (seguidores, alcance, etc.) e o sistema calcula
 * o ganho comparando com o lançamento anterior. Guardar o total em vez do
 * ganho evita que um lançamento esquecido quebre a série histórica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_metrics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('network', 30);
            $table->date('reference_date');

            // Todos opcionais: a equipe preenche o que tiver em mãos.
            $table->unsignedBigInteger('followers')->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedBigInteger('engagement')->nullable();
            $table->unsignedBigInteger('profile_visits')->nullable();
            $table->unsignedBigInteger('link_clicks')->nullable();
            $table->unsignedInteger('posts_count')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Uma leitura por rede por data — relançar a mesma data atualiza.
            $table->unique(['client_id', 'network', 'reference_date'], 'client_metrics_unico');

            // Índice que serve as duas consultas da tela: série de uma rede e
            // listagem geral do cliente, ambas ordenadas por data.
            $table->index(['client_id', 'network', 'reference_date'], 'client_metrics_serie');
            $table->index(['company_id', 'reference_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_metrics');
    }
};
