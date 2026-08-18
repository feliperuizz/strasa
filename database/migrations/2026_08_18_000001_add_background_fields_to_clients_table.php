<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('bg_type', 20)->default('default')->after('color');
            $table->string('bg_color', 20)->nullable()->after('bg_type');
            $table->string('bg_gradient', 500)->nullable()->after('bg_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['bg_type', 'bg_color', 'bg_gradient']);
        });
    }
};
