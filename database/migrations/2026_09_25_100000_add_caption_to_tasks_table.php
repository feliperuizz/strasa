<?php

use App\Support\HtmlParaTexto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa anotação interna (description) de legenda do post (caption).
     *
     * Até aqui o card tinha um campo só, em rich text, e era ele que ia para o
     * painel do cliente — onde a marcação aparecia crua. A legenda agora é
     * texto puro, com as quebras de linha que o cliente vê.
     *
     * O backfill COPIA o texto que já existia (convertido de HTML para texto)
     * em vez de mover: nada se perde se a conversão errar em algum card, e o
     * painel do cliente continua mostrando o que mostrava.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('caption')->nullable()->after('description');
        });

        DB::table('tasks')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($tarefas) {
                foreach ($tarefas as $tarefa) {
                    $texto = HtmlParaTexto::converter($tarefa->description);

                    if ($texto !== '') {
                        DB::table('tasks')->where('id', $tarefa->id)->update(['caption' => $texto]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('caption');
        });
    }
};
