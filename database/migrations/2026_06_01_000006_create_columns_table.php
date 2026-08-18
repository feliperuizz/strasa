<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colunas do quadro Kanban (etapas do fluxo) por projeto.
 * `key` identifica colunas especiais do workflow:
 *   posted    -> ao mover para cá marca o post como publicado
 *   rejected  -> ao mover para cá exige motivo de rejeição
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key')->nullable();
            $table->string('color', 7)->default('#64748b');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('marks_published')->default(false);
            $table->boolean('requires_rejection_reason')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('columns');
    }
};
