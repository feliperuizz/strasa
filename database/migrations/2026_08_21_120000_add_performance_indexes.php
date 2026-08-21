<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para as consultas mais frequentes do app.
 *
 * - task_user.user_id: a chave primária é (task_id, user_id), então buscar
 *   "as tarefas do usuário X" (dashboard, minhas tarefas, filtro do quadro)
 *   não conseguia usar índice nenhum e varria a tabela inteira.
 * - tasks (is_published, publish_date): usado nos contadores de pendentes/
 *   atrasadas e nas listas de próximas entregas.
 * - tasks.published_at: gráfico de produtividade dos últimos 14 dias.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->safely(fn () => Schema::table('task_user', function (Blueprint $table) {
            $table->index('user_id', 'task_user_user_id_index');
        }));

        $this->safely(fn () => Schema::table('tasks', function (Blueprint $table) {
            $table->index(['is_published', 'publish_date'], 'tasks_published_date_index');
        }));

        $this->safely(fn () => Schema::table('tasks', function (Blueprint $table) {
            $table->index('published_at', 'tasks_published_at_index');
        }));
    }

    public function down(): void
    {
        $this->safely(fn () => Schema::table('task_user', function (Blueprint $table) {
            $table->dropIndex('task_user_user_id_index');
        }));

        $this->safely(fn () => Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_published_date_index');
        }));

        $this->safely(fn () => Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_published_at_index');
        }));
    }

    /** Índice já existente não deve derrubar o deploy. */
    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            // ignora
        }
    }
};
