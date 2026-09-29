<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prioridade pessoal em Minhas Tarefas: cada responsável arrasta as
     * tarefas do dia na ordem em que vai fazer. Fica no vínculo tarefa ×
     * pessoa (e não na tarefa) porque duas pessoas no mesmo card podem ter
     * prioridades diferentes. Nulo = ainda não ordenada, vai para o fim.
     */
    public function up(): void
    {
        Schema::table('task_user', function (Blueprint $table) {
            $table->unsignedInteger('personal_position')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('task_user', function (Blueprint $table) {
            $table->dropColumn('personal_position');
        });
    }
};
