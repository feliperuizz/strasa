<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes da agência. Cada cliente pertence a uma empresa e guarda
 * sua identidade (logo, segmento, redes sociais) e um template de colunas
 * padrão que é copiado para cada novo projeto/quadro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('segment')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_disk')->nullable();
            // Redes sociais ativas do cliente: ["instagram","facebook",...]
            $table->json('social_networks')->nullable();
            // Template de colunas padrão do quadro deste cliente.
            $table->json('default_columns')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
