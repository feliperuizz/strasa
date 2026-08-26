<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que o comentário do cliente caia no mesmo feed que a equipe já
 * usa, em vez de criar uma segunda caixa de mensagens.
 *
 * - is_from_client:    veio do portal (user_id fica nulo nesse caso).
 * - client_author_name: quem digitou, do lado do cliente.
 * - visible_to_client: comentário nosso marcado para aparecer no portal.
 *                      Padrão false — nada interno vaza sem ação explícita.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->boolean('is_from_client')->default(false)->after('user_id');
            $table->string('client_author_name')->nullable()->after('is_from_client');
            $table->boolean('visible_to_client')->default(false)->after('client_author_name');
        });

        Schema::table('task_comments', function (Blueprint $table) {
            $table->index(['task_id', 'is_from_client']);
        });
    }

    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropIndex(['task_id', 'is_from_client']);
            $table->dropColumn(['is_from_client', 'client_author_name', 'visible_to_client']);
        });
    }
};
