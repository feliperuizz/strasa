<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tarefa/Post. É o "card" do Kanban e o item do calendário.
 * client_id é denormalizado (vem do projeto) para acelerar filtros e o
 * calendário do cliente sem precisar de join com projects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('column_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->longText('description')->nullable();
            // carousel, feed, story, reel, blog...
            $table->string('content_type')->nullable();
            // Redes alvo: ["instagram","linkedin",...]
            $table->json('social_networks')->nullable();
            // Data de publicação planejada (usada no calendário).
            $table->date('publish_date')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'column_id', 'position']);
            $table->index(['client_id', 'publish_date']);
            $table->index(['company_id', 'assignee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
