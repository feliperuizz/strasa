<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_user', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->primary(['task_id', 'user_id']);
        });

        // Copiar os responsáveis atuais (assignee_id) para a nova tabela
        DB::table('tasks')->whereNotNull('assignee_id')->orderBy('id')->chunkById(100, function ($tasks) {
            $inserts = [];
            foreach ($tasks as $task) {
                // Prevenir duplicatas se a mesma task foi lida ou migrada de alguma forma
                $inserts[] = [
                    'task_id' => $task->id,
                    'user_id' => $task->assignee_id,
                ];
            }
            if (!empty($inserts)) {
                DB::table('task_user')->insertOrIgnore($inserts);
            }
        });

        // Remover a coluna antiga
        try {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['assignee_id']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex(['company_id', 'assignee_id']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_assignee_id_foreign');
            });
        } catch (\Exception $e) {}

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Tentar restaurar o primeiro assignee da pivot para a task (para rollback)
        DB::table('task_user')->orderBy('task_id')->chunkById(100, function ($pivotRecords) {
            foreach ($pivotRecords as $record) {
                DB::table('tasks')
                    ->where('id', $record->task_id)
                    ->whereNull('assignee_id') // apenas pegar o primeiro
                    ->update(['assignee_id' => $record->user_id]);
            }
        }, 'task_id');

        Schema::dropIfExists('task_user');
    }
};
