<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chaves da API de postagem automática.
     *
     * A chave em si nunca é guardada: só o hash SHA-256 (como senha) e o
     * prefixo, para reconhecer a chave no painel. Quem perde a chave gera
     * outra — não há como recuperar.
     */
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token_prefix', 16);
            $table->string('token_hash', 64)->unique();
            // true = todos os clientes da empresa; false = só os de api_token_client
            $table->boolean('all_clients')->default(true);
            // Ao receber "publicado", mover o card para a coluna de concluído.
            $table->boolean('auto_complete')->default(true);
            // IPs/faixas (CIDR) autorizados, separados por vírgula. Vazio = qualquer IP.
            $table->text('allowed_ips')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('api_token_client', function (Blueprint $table) {
            $table->foreignId('api_token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->primary(['api_token_id', 'client_id']);
        });

        // Situação da postagem em cada rede, informada pelo sistema parceiro.
        Schema::create('task_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_token_id')->nullable()->constrained()->nullOnDelete();
            $table->string('network', 30);
            $table->string('status', 20);
            $table->string('external_id')->nullable();
            $table->string('permalink', 2048)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();

            // Um registro por post × chave × rede: reenviar o mesmo aviso atualiza, não duplica.
            $table->unique(['task_id', 'api_token_id', 'network']);
            $table->index(['company_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_publications');
        Schema::dropIfExists('api_token_client');
        Schema::dropIfExists('api_tokens');
    }
};
