<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajusta os campos das métricas para o que a equipe realmente acompanha.
 *
 * Entram médias por publicação (curtidas, comentários, compartilhamentos) e
 * visualizações; saem alcance, impressões e o engajamento agregado — este
 * último passa a ser DERIVADO da soma das três médias, então não precisa
 * mais ser digitado.
 *
 * O conteúdo de `impressions` é copiado para `views` antes do descarte, para
 * não perder o que já tiver sido lançado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_metrics', function (Blueprint $table) {
            $table->unsignedBigInteger('avg_likes')->nullable()->after('followers');
            $table->unsignedBigInteger('avg_comments')->nullable()->after('avg_likes');
            $table->unsignedBigInteger('avg_shares')->nullable()->after('avg_comments');
            $table->unsignedBigInteger('views')->nullable()->after('avg_shares');
        });

        // Impressões viraram "visualizações": aproveita o que já existe.
        DB::table('client_metrics')
            ->whereNull('views')
            ->whereNotNull('impressions')
            ->update(['views' => DB::raw('impressions')]);

        Schema::table('client_metrics', function (Blueprint $table) {
            $table->dropColumn(['reach', 'impressions', 'engagement']);
        });
    }

    public function down(): void
    {
        Schema::table('client_metrics', function (Blueprint $table) {
            $table->unsignedBigInteger('reach')->nullable()->after('followers');
            $table->unsignedBigInteger('impressions')->nullable()->after('reach');
            $table->unsignedBigInteger('engagement')->nullable()->after('impressions');
        });

        DB::table('client_metrics')
            ->whereNull('impressions')
            ->whereNotNull('views')
            ->update(['impressions' => DB::raw('views')]);

        Schema::table('client_metrics', function (Blueprint $table) {
            $table->dropColumn(['avg_likes', 'avg_comments', 'avg_shares', 'views']);
        });
    }
};
