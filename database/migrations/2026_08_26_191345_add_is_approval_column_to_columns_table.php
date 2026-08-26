<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca a coluna que envia o card para o painel do cliente.
 *
 * Segue o mesmo padrão das flags que a tabela já tem (marks_published,
 * requires_rejection_reason, is_publish_column): arrastar um card para uma
 * coluna com esta flag submete a peça para aprovação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('columns', function (Blueprint $table) {
            $table->boolean('is_approval_column')->default(false)->after('is_publish_column');
        });
    }

    public function down(): void
    {
        Schema::table('columns', function (Blueprint $table) {
            $table->dropColumn('is_approval_column');
        });
    }
};
