<?php

namespace App\Http\Resources\Api;

use App\Models\ApiToken;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Um post (card do STRASA) como o sistema de postagem enxerga.
 *
 * Só sai o necessário para publicar. Ficam de fora de propósito: a anotação
 * interna (description), comentários, checklist e dados da equipe.
 *
 * @mixin Task
 */
class PostResource extends JsonResource
{
    public const FUSO = 'America/Sao_Paulo';

    public function toArray(Request $request): array
    {
        /** @var ApiToken $chave */
        $chave = $request->attributes->get('api_token');
        $motivo = self::motivoNaoPronto($this->resource);
        $aprovacao = $this->relationLoaded('approvals') ? $this->approvals->first() : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'caption' => $this->caption,
            'content_type' => $this->content_type,
            'content_type_label' => $this->content_type ? $this->contentTypeLabel() : null,
            'scheduled_at' => self::agendadoPara($this->resource),
            'publish_date' => $this->publish_date?->format('Y-m-d'),
            'publish_time' => $this->publish_time ? substr((string) $this->publish_time, 0, 5) : null,
            'timezone' => self::FUSO,
            'ready_to_publish' => $motivo === null,
            'not_ready_reason' => $motivo,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'client' => ClientResource::make($this->client),
            'project' => $this->project ? ['id' => $this->project->id, 'name' => $this->project->name] : null,
            'column' => $this->column ? [
                'id' => $this->column->id,
                'name' => $this->column->name,
                'is_publish_queue' => (bool) $this->column->is_publish_column,
            ] : null,
            'tags' => $this->tags->pluck('name')->values()->all(),
            'approval' => $aprovacao ? [
                'status' => $aprovacao->status,
                'round' => $aprovacao->round,
                'reviewer_name' => $aprovacao->reviewer_name,
                'responded_at' => $aprovacao->responded_at?->toIso8601String(),
            ] : null,
            // Na ordem do carrossel (a mesma do card e do painel do cliente).
            'media' => $this->approvalMedia()->values()->map(
                fn ($anexo, $i) => (new MediaResource($anexo))->naPosicao($i + 1)->daChave($chave)->resolve($request)
            )->all(),
            'publications' => PublicationResource::collection($this->publications)->resolve($request),
            'strasa_url' => route('tasks.show', $this->id),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Por que o post ainda não pode ir ao ar (null = pode).
     *
     * Pronto = está na coluna marcada como fila de postagem, tem data E hora
     * e ainda não foi publicado. É a agência que decide, arrastando o card
     * para essa coluna — um rascunho nunca é publicado por engano, e um post
     * sem horário não vai ao ar num horário inventado.
     */
    public static function motivoNaoPronto(Task $task): ?string
    {
        return match (true) {
            (bool) $task->is_published => 'already_published',
            ! $task->column?->is_publish_column => 'not_in_publish_queue',
            $task->publish_date === null => 'missing_publish_date',
            blank($task->publish_time) => 'missing_publish_time',
            default => null,
        };
    }

    /** Data e hora de publicação no fuso de Brasília (ISO 8601 com -03:00), ou null sem horário. */
    public static function agendadoPara(Task $task): ?string
    {
        if (! $task->publish_date || ! $task->publish_time) {
            return null;
        }

        return Carbon::parse($task->publish_date->format('Y-m-d').' '.$task->publish_time, self::FUSO)->toIso8601String();
    }
}
