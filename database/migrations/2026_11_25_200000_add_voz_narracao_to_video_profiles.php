<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Voz padrão da narração gerada para os vídeos do cliente (chave de config video.narracao.vozes).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_profiles', function (Blueprint $table) {
            $table->string('voz_narracao', 30)->nullable()->after('cor_grade');
        });
    }

    public function down(): void
    {
        Schema::table('video_profiles', function (Blueprint $table) {
            $table->dropColumn('voz_narracao');
        });
    }
};
