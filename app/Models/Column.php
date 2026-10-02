<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Column extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'project_id', 'name', 'key', 'color',
        'position', 'marks_published', 'requires_rejection_reason', 'is_publish_column',
        'is_approval_column',
    ];

    protected function casts(): array
    {
        return [
            'marks_published' => 'boolean',
            'requires_rejection_reason' => 'boolean',
            'is_publish_column' => 'boolean',
            'is_approval_column' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }

    /* "Aprovado / Agendado" ------------------------------------------------
     * É a coluna marcada com is_publish_column (no menu: "Aprovado /
     * agendado"). Fica à direita da coluna de aprovação: o card vem para cá
     * quando o cliente aprova (ou quando a equipe arrasta), a API de postagem
     * automática puxa os posts daqui e o responsável é avisado no horário.
     */

    public const NOME_APROVADOS = 'Aprovado / Agendado';

    /**
     * Para onde vai o card aprovado: a coluna "Aprovado / Agendado" mais
     * próxima à direita da coluna de aprovação; sem ela à direita, a
     * primeira do quadro. Sem filtro de empresa automático (o painel do
     * cliente não tem usuário logado), então filtra pelo projeto.
     */
    public static function destinoDosAprovados(int $projectId, ?self $aprovacao = null): ?self
    {
        $colunas = self::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('is_publish_column', true)
            ->orderBy('position')
            ->get();

        if ($aprovacao) {
            $aDireita = $colunas->first(fn (self $c) => $c->position > $aprovacao->position);

            if ($aDireita) {
                return $aDireita;
            }
        }

        return $colunas->first();
    }

    /**
     * Garante a coluna "Aprovado / Agendado" logo à direita da coluna de
     * aprovação. Se o quadro já tem uma, não mexe. Se a vizinha da direita
     * já faz esse papel pelo nome ("Fila de Publicação", "Agendado"...),
     * só a marca. Senão, cria uma nova e empurra as outras uma casa.
     */
    public static function garantirAprovadosAoLado(self $aprovacao): self
    {
        $existente = self::destinoDosAprovados($aprovacao->project_id, $aprovacao);

        if ($existente) {
            return $existente;
        }

        $vizinha = self::withoutGlobalScopes()
            ->where('project_id', $aprovacao->project_id)
            ->where('position', '>', $aprovacao->position)
            ->orderBy('position')
            ->first();

        if ($vizinha && ! $vizinha->marks_published && ! $vizinha->is_approval_column && ! $vizinha->requires_rejection_reason
            && ($vizinha->key === 'queue' || preg_match('/fila|agendad|aprovad|programad/iu', $vizinha->name))) {
            $vizinha->forceFill(['is_publish_column' => true])->save();
            cache()->forget("project:{$aprovacao->project_id}:columns");

            return $vizinha;
        }

        self::withoutGlobalScopes()
            ->where('project_id', $aprovacao->project_id)
            ->where('position', '>', $aprovacao->position)
            ->increment('position');

        $nova = self::withoutGlobalScopes()->create([
            'company_id' => $aprovacao->company_id,
            'project_id' => $aprovacao->project_id,
            'name' => self::NOME_APROVADOS,
            'key' => 'approved',
            'color' => '#14b8a6',
            'position' => $aprovacao->position + 1,
            'is_publish_column' => true,
        ]);

        cache()->forget("project:{$aprovacao->project_id}:columns");

        return $nova;
    }
}
