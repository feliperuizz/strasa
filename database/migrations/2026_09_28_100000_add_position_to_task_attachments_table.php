<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ordem manual dos anexos — é ela que define a sequência do carrossel no
     * card e no painel do cliente. Antes a ordem era a de chegada no servidor,
     * que num envio múltiplo não segue a ordem dos arquivos.
     *
     * Backfill: dentro de cada card e pasta, a ordem de envio (id). Nada muda
     * de lugar até alguém arrastar.
     */
    public function up(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('is_image');
        });

        $grupos = DB::table('task_attachments')
            ->select('id', 'task_id', 'folder_id')
            ->orderBy('task_id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($a) => $a->task_id.'-'.($a->folder_id ?? 'raiz'));

        foreach ($grupos as $anexos) {
            foreach ($anexos->values() as $posicao => $anexo) {
                DB::table('task_attachments')->where('id', $anexo->id)->update(['position' => $posicao]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
