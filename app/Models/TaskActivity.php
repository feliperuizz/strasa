<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha do histórico do card: quem fez, o quê e quando.
 *
 * O registro é sempre escrito pelo ponto que fez a mudança (controller ou
 * service) através de {@see self::registrar()} — assim o log não depende de
 * eventos do Eloquent e continua contando quem foi o autor.
 */
class TaskActivity extends Model
{
    use BelongsToCompany;

    public const TYPE_CREATED = 'created';
    public const TYPE_COLUMN_CHANGED = 'column_changed';
    public const TYPE_ASSIGNEE_CHANGED = 'assignee_changed';
    public const TYPE_PUBLISH_DATE_CHANGED = 'publish_date_changed';
    public const TYPE_PUBLISHED = 'published';
    public const TYPE_REJECTED = 'rejected';
    public const TYPE_TITLE_CHANGED = 'title_changed';
    public const TYPE_DESCRIPTION_CHANGED = 'description_changed';
    public const TYPE_TAGS_CHANGED = 'tags_changed';
    public const TYPE_ATTACHMENT_ADDED = 'attachment_added';
    public const TYPE_ATTACHMENT_REMOVED = 'attachment_removed';
    public const TYPE_FOLDER_CREATED = 'folder_created';
    public const TYPE_CHECKLIST_ADDED = 'checklist_added';
    public const TYPE_CHECKLIST_DONE = 'checklist_done';
    public const TYPE_CHECKLIST_REOPENED = 'checklist_reopened';
    public const TYPE_CHECKLIST_REMOVED = 'checklist_removed';
    public const TYPE_COMMENTED = 'commented';

    protected $fillable = [
        'company_id', 'task_id', 'user_id', 'type', 'description', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    /**
     * Grava uma linha no histórico da tarefa.
     *
     * $userId só é passado quando o autor não é quem está logado — o caso do
     * cliente respondendo pelo painel, que não tem sessão no sistema.
     */
    public static function registrar(Task $task, string $type, string $description, array $meta = [], ?int $userId = null): self
    {
        return $task->activities()->create([
            'company_id' => $task->company_id,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Igual ao registrar(), mas funde com a linha anterior quando é o mesmo
     * autor mexendo na mesma coisa dentro da janela.
     *
     * O card salva sozinho a cada pausa de digitação: sem isso, escrever uma
     * descrição encheria o histórico de "editou a descrição" e esconderia o
     * que interessa. A data também é reposicionada porque o que vale para
     * quem lê é a última vez que a pessoa mexeu.
     */
    public static function registrarAgrupado(Task $task, string $type, string $description, array $meta = [], int $janelaEmMinutos = 10): self
    {
        $ultima = $task->activities()
            ->where('type', $type)
            ->where('user_id', auth()->id())
            ->reorder('id', 'desc')
            ->first();

        if ($ultima && $ultima->created_at?->gt(now()->subMinutes($janelaEmMinutos))) {
            $ultima->forceFill([
                'description' => $description,
                'meta' => $meta ?: null,
                'created_at' => now(),
            ])->save();

            return $ultima;
        }

        return self::registrar($task, $type, $description, $meta);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Nome de quem fez a ação. Sem usuário, foi o cliente pelo painel. */
    public function authorName(): string
    {
        return $this->user?->name
            ?? ($this->meta['author'] ?? 'Cliente');
    }

    /** Nome curto do tipo, para o resumo do log ("3 cards movidos"). */
    public const LABELS = [
        self::TYPE_CREATED => 'criou card',
        self::TYPE_COLUMN_CHANGED => 'moveu card',
        self::TYPE_ASSIGNEE_CHANGED => 'mudou responsável',
        self::TYPE_PUBLISH_DATE_CHANGED => 'mudou data',
        self::TYPE_PUBLISHED => 'concluiu',
        self::TYPE_REJECTED => 'rejeitou',
        self::TYPE_TITLE_CHANGED => 'editou título',
        self::TYPE_DESCRIPTION_CHANGED => 'editou texto',
        self::TYPE_TAGS_CHANGED => 'mexeu em flags',
        self::TYPE_ATTACHMENT_ADDED => 'anexou arquivo',
        self::TYPE_ATTACHMENT_REMOVED => 'removeu arquivo',
        self::TYPE_FOLDER_CREATED => 'criou pasta',
        self::TYPE_CHECKLIST_ADDED => 'item de checklist',
        self::TYPE_CHECKLIST_DONE => 'concluiu item',
        self::TYPE_CHECKLIST_REOPENED => 'reabriu item',
        self::TYPE_CHECKLIST_REMOVED => 'removeu item',
        self::TYPE_COMMENTED => 'comentou',
    ];

    public function typeLabel(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }

    /** Cor da bolinha na timeline, por família de evento. */
    public function color(): string
    {
        return match ($this->type) {
            self::TYPE_PUBLISHED, self::TYPE_CHECKLIST_DONE => '#34d399',
            self::TYPE_REJECTED, self::TYPE_ATTACHMENT_REMOVED, self::TYPE_CHECKLIST_REMOVED => '#fb7185',
            self::TYPE_ASSIGNEE_CHANGED => '#fbbf24',
            self::TYPE_ATTACHMENT_ADDED, self::TYPE_FOLDER_CREATED => '#38bdf8',
            self::TYPE_COMMENTED => '#c084fc',
            default => '#94a3b8',
        };
    }
}
