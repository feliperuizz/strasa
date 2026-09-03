<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cria as flags padrão do fluxo de conteúdo em cada empresa existente.
 *
 * São só um ponto de partida: a equipe adiciona, renomeia a cor e exclui à
 * vontade pelo painel de flags do card.
 */
return new class extends Migration
{
    public function up(): void
    {
        $empresas = DB::table('companies')->pluck('id');

        foreach ($empresas as $companyId) {
            foreach (Tag::PREDEFINIDAS as $nome => $cor) {
                // firstOrCreate respeita a unique (company_id, name) e não
                // sobrescreve a cor de uma flag que a equipe já tenha ajustado.
                DB::table('tags')->updateOrInsert(
                    ['company_id' => $companyId, 'name' => $nome],
                    ['color' => $cor, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // Só remove as que continuam sem uso — flag já aplicada em card fica.
        $semUso = DB::table('tags')
            ->whereIn('name', array_keys(Tag::PREDEFINIDAS))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('task_tag')
                    ->whereColumn('task_tag.tag_id', 'tags.id');
            })
            ->pluck('id');

        DB::table('tags')->whereIn('id', $semUso)->delete();
    }
};
