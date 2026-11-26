<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Vídeo final arquivado no Google Drive (e apagado do servidor).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_jobs', function (Blueprint $table) {
            $table->string('nuvem_disco', 30)->nullable()->after('capa_path');
            $table->string('nuvem_final')->nullable()->after('nuvem_disco');
            $table->string('nuvem_capa')->nullable()->after('nuvem_final');
            $table->unsignedBigInteger('nuvem_tamanho')->nullable()->after('nuvem_capa');
            $table->string('nuvem_status', 20)->nullable()->after('nuvem_tamanho'); // enviando | salvo | erro
            $table->string('nuvem_erro', 500)->nullable()->after('nuvem_status');
            $table->timestamp('nuvem_em')->nullable()->after('nuvem_erro');
        });
    }

    public function down(): void
    {
        Schema::table('video_jobs', function (Blueprint $table) {
            $table->dropColumn(['nuvem_disco', 'nuvem_final', 'nuvem_capa', 'nuvem_tamanho', 'nuvem_status', 'nuvem_erro', 'nuvem_em']);
        });
    }
};
