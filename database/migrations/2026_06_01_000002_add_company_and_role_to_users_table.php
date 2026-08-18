<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acrescenta multi-tenant (company_id), papel (role) e avatar ao usuário.
 * Roles: admin (administrador), manager (gestor), member (colaborador).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
            $table->string('role')->default('member')->after('email');
            $table->string('avatar_color', 7)->default('#6366f1')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['role', 'avatar_color']);
        });
    }
};
