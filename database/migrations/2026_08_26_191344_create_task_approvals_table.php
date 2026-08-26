<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Submissões de um card para o painel de aprovação do cliente.
 *
 * É esta tabela — e não uma flag na task — que decide o que o cliente vê.
 * Cada reenvio depois de um ajuste cria uma nova `round`, preservando o
 * histórico de idas e vindas de cada peça.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('round')->default(1);
            $table->string('status', 20)->default('pending'); // pending | approved | rejected

            // Envio (lado da agência).
            $table->timestamp('submitted_at');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('origin_column_id')->nullable()->constrained('columns')->nullOnDelete();

            // Resposta (lado do cliente).
            $table->timestamp('responded_at')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->text('feedback')->nullable();

            $table->timestamps();

            $table->unique(['task_id', 'round']);
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'status', 'responded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_approvals');
    }
};
