<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada item do checklist pode ter um responsável e um prazo próprios —
     * o card tem os dele, mas a peça costuma ser dividida em etapas que
     * pessoas diferentes entregam em dias diferentes.
     */
    public function up(): void
    {
        Schema::table('task_items', function (Blueprint $table) {
            $table->foreignId('assignee_id')->nullable()->after('is_completed')
                ->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->after('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::table('task_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignee_id');
            $table->dropColumn('due_date');
        });
    }
};
