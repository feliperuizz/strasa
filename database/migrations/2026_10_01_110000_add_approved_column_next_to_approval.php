<?php

use App\Models\Client;
use App\Models\Column;
use Illuminate\Database\Migrations\Migration;

/**
 * "Aprovado / Agendado" à direita de toda coluna de aprovação.
 *
 * - Quadros que já têm coluna de aprovação ganham a coluna nova logo à
 *   direita (ou, se a vizinha já faz esse papel pelo nome — "Fila de
 *   Publicação", "Agendado" —, ela só é marcada). Quadro que já tem uma
 *   coluna marcada como fila de postagem fica como está.
 * - Clientes com o modelo de colunas antigo (cópia do padrão de antes)
 *   passam a usar o padrão novo nos próximos projetos.
 *
 * Só acrescenta: nenhum card muda de coluna e nenhuma coluna é apagada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Column::withoutGlobalScopes()
            ->where('is_approval_column', true)
            ->orderBy('project_id')
            ->orderBy('position')
            ->get()
            ->each(fn (Column $aprovacao) => Column::garantirAprovadosAoLado($aprovacao));

        Client::withoutGlobalScopes()
            ->whereNotNull('default_columns')
            ->get()
            ->filter(fn (Client $c) => array_column((array) $c->default_columns, 'name') === Client::OLD_DEFAULT_COLUMN_NAMES)
            ->each(fn (Client $c) => $c->forceFill(['default_columns' => null])->saveQuietly());
    }

    public function down(): void
    {
        // Nada a desfazer com segurança: as colunas criadas podem já ter cards.
    }
};
