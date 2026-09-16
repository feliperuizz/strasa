<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recorrência de verdade. Até aqui "monthly" era só uma etiqueta: nada
     * criava a cobrança do mês seguinte, e cada boleto/Pix tinha que ser
     * cadastrado na mão.
     *
     * - recurrence_series_id: liga todas as cobranças da mesma mensalidade
     *   (aponta para a primeira). É o que o gerador percorre.
     * - recurrence_day: dia do mês em que repete. Guardado à parte para o
     *   dia 31 não virar 30 e depois 28 conforme os meses passam.
     * - recurrence_ended_at: quando a série foi encerrada; nulo = ativa.
     *
     * Sem backfill de propósito: o formulário sempre veio com "Mensalidade"
     * marcado por padrão, então cobranças avulsas antigas também estão como
     * monthly. Quem quiser repetir uma delas ativa pelo botão na linha.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('recurrence_series_id')->nullable()->after('recurrence')->index();
            $table->unsignedTinyInteger('recurrence_day')->nullable()->after('recurrence_series_id');
            $table->date('recurrence_ended_at')->nullable()->after('recurrence_day');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['recurrence_series_id', 'recurrence_day', 'recurrence_ended_at']);
        });
    }
};
