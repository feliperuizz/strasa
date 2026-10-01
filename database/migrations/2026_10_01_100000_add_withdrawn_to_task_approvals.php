<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Excluir do painel do cliente": a agência desiste de uma peça (ex.: o
 * cliente pediu ajuste e não vamos seguir). As rodadas não são apagadas —
 * o histórico continua na aba Aprovações —, só deixam de aparecer no painel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_approvals', function (Blueprint $table) {
            $table->timestamp('withdrawn_at')->nullable()->after('feedback');
            $table->foreignId('withdrawn_by')->nullable()->after('withdrawn_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawn_by');
            $table->dropColumn('withdrawn_at');
        });
    }
};
