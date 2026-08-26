<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal de aprovação do cliente.
 *
 * Um registro por cliente. O `token` vai na URL (público, mas longo o
 * bastante para não ser adivinhado) e o `access_code` é a chave curta que o
 * cliente digita — guardada criptografada, porque precisamos exibi-la para
 * copiar na mensagem de envio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();

            // Identificação e acesso.
            $table->string('token', 64)->unique();
            $table->text('access_code');                   // encrypted cast no model
            $table->string('code_hint', 8)->nullable();     // últimos dígitos, para conferência rápida
            $table->boolean('is_active')->default(true);
            $table->timestamp('code_updated_at')->nullable();

            // Telemetria de uso — ajuda a saber se o cliente chegou a entrar.
            $table->timestamp('last_accessed_at')->nullable();
            $table->unsignedInteger('access_count')->default(0);

            // Notificações push para os responsáveis quando o cliente responde.
            $table->boolean('notify_enabled')->default(true);
            $table->json('notify_user_ids')->nullable();

            // Texto de boas-vindas exibido no topo do portal (opcional).
            $table->string('welcome_message', 500)->nullable();

            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portals');
    }
};
