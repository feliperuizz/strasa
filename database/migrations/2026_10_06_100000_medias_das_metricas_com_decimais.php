<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Médias por publicação aceitam casas decimais (ex.: 2,2 comentários por
 * post). Eram inteiras e obrigavam a arredondar. Os valores já lançados
 * continuam iguais (2 vira 2,00).
 *
 * Seguidores, visualizações, visitas, cliques e publicações seguem inteiros:
 * são contagens.
 */
return new class extends Migration
{
    private const MEDIAS = ['avg_likes', 'avg_comments', 'avg_shares'];

    public function up(): void
    {
        Schema::table('client_metrics', function (Blueprint $table) {
            foreach (self::MEDIAS as $coluna) {
                $table->decimal($coluna, 12, 2)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_metrics', function (Blueprint $table) {
            foreach (self::MEDIAS as $coluna) {
                $table->unsignedBigInteger($coluna)->nullable()->change();
            }
        });
    }
};
