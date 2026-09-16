<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Column;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function create(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $data = $this->formData($project);
        $data['task'] = new Task(['project_id' => $project->id]); // Mock task for form

        if ($request->ajax() || $request->wantsJson()) {
            return view('tasks.partials.slideover', $data);
        }

        return view('tasks.create', $data);
    }

    public function store(TaskRequest $request, Project $project)
    {
        $this->authorize('view', $project);

        $column = $this->resolveColumn($request->integer('column_id'), $project);

        $task = DB::transaction(function () use ($request, $project, $column) {
            $task = $project->tasks()->create([
                'company_id' => $project->company_id,
                'client_id' => $project->client_id,
                'column_id' => $column->id,
                'created_by' => $request->user()->id,
                'title' => $request->validated('title') ?? 'Nova Tarefa', // Permite salvar sem título temporariamente
                'description' => $request->validated('description'),
                'content_type' => $request->validated('content_type'),
                'publish_date' => $request->validated('publish_date'),
                'publish_time' => $request->validated('publish_time'),
                'position' => (int) $project->tasks()->where('column_id', $column->id)->max('position') + 1,
                'is_published' => $column->marks_published,
                'published_at' => $column->marks_published ? now() : null,
            ]);

            $this->syncTags($task, $request->input('tags', []));
            if ($request->has('has_assignees') || $request->has('assignees')) {
                $task->assignees()->sync($request->input('assignees', []));
            }
            
            $this->log($task, TaskActivity::TYPE_CREATED, 'criou a tarefa');

            return $task;
        });

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('components.task-card', ['task' => $task])->render();
            return response()->json(['task' => $task, 'message' => 'Criado com sucesso', 'html' => $html]);
        }

        return redirect()->route('tasks.show', $task)->with('status', 'Tarefa criada.');
    }

    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        $task->load([
            'client', 'project.columns', 'column', 'assignees', 'creator', 'tags',
            'attachments.uploader',
            'comments.user',
            'activities.user',
        ]);

        $members = User::where('company_id', $task->company_id)->orderBy('name')->get();
        $data = array_merge($this->formData($task->project), ['task' => $task, 'members' => $members]);

        if ($request->ajax() || $request->wantsJson()) {
            return view('tasks.partials.slideover', $data);
        }

        return view('tasks.show', $data);
    }

    public function edit(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        // O slideover percorre todas essas relações. Sem o eager loading era
        // uma query por pasta/comentário/anexo toda vez que a tarefa abria —
        // e ela reabre a cada upload, comentário ou item de checklist.
        $task->load([
            'assignees', 'tags', 'items',
            'folders.attachments',
            'attachments',
            'comments.user',
            'activities.user',
        ]);

        $data = array_merge($this->formData($task->project), ['task' => $task]);

        if ($request->ajax() || $request->wantsJson()) {
            return view('tasks.partials.slideover', $data);
        }

        return view('tasks.edit', $data);
    }

    public function update(TaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        // O slideover não troca de coluna (não há seletor): se column_id não vier, mantém a
        // coluna atual. A troca de coluna acontece só via drag/menu de contexto (rota move).
        $column = $request->filled('column_id')
            ? $this->resolveColumn($request->integer('column_id'), $task->project)
            : ($task->column ?? $task->project->columns()->orderBy('position')->first());

        DB::transaction(function () use ($request, $task, $column) {
            $this->trackChanges($request, $task, $column);

            $task->update([
                'column_id' => $column->id,
                'title' => $request->validated('title') ?? 'Nova Tarefa',
                'description' => $request->validated('description'),
                'content_type' => $request->validated('content_type'),
                'publish_date' => $request->validated('publish_date'),
                'publish_time' => $request->validated('publish_time'),
                'is_published' => $column->marks_published,
                'published_at' => $column->marks_published ? ($task->published_at ?? now()) : null,
            ]);

            $this->syncTags($task, $request->input('tags', []));
            if ($request->has('has_assignees') || $request->has('assignees')) {
                $task->assignees()->sync($request->input('assignees', []));
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['task' => $task, 'message' => 'Atualizado com sucesso']);
        }

        return redirect()->route('tasks.show', $task)->with('status', 'Tarefa atualizada.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $project = $task->project;

        // Apaga os arquivos no bucket antes de remover os registros.
        foreach ($task->attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $task->delete();

        return redirect()->route('projects.board', $project)->with('status', 'Tarefa excluída.');
    }

    /**
     * Move o card entre colunas (drag & drop do Kanban).
     * Aplica as regras de workflow: "Postado" publica; "Rejeitado" exige motivo.
     */
    public function move(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'column_id' => ['required', 'integer'],
            'ordered_ids' => ['nullable', 'array'],
            'ordered_ids.*' => ['integer'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = $this->resolveColumn((int) $data['column_id'], $task->project);
        $origin = $task->column;
        $changedColumn = $origin->id !== $target->id;

        if ($changedColumn && $target->requires_rejection_reason && blank($data['rejection_reason'] ?? null)) {
            return response()->json([
                'ok' => false,
                'requires_reason' => true,
                'message' => 'Informe o motivo da rejeição.',
            ], 422);
        }

        DB::transaction(function () use ($task, $target, $origin, $changedColumn, $data) {
            $task->column_id = $target->id;

            if ($target->marks_published) {
                $task->is_published = true;
                $task->published_at ??= now();
            } elseif ($origin->marks_published) {
                $task->is_published = false;
                $task->published_at = null;
            }

            if ($target->requires_rejection_reason && ! blank($data['rejection_reason'] ?? null)) {
                $task->rejection_reason = $data['rejection_reason'];
            }

            $task->save();

            // Persiste a nova ordem dos cards na coluna destino.
            foreach ($data['ordered_ids'] ?? [] as $position => $id) {
                Task::where('id', $id)
                    ->where('project_id', $task->project_id)
                    ->update(['column_id' => $target->id, 'position' => $position]);
            }

            if ($changedColumn) {
                $this->log($task, TaskActivity::TYPE_COLUMN_CHANGED,
                    "moveu de \"{$origin->name}\" para \"{$target->name}\"");

                if ($target->marks_published) {
                    $this->log($task, TaskActivity::TYPE_PUBLISHED, 'marcou como publicado');
                }
                if ($target->requires_rejection_reason) {
                    $this->log($task, TaskActivity::TYPE_REJECTED, 'rejeitou a tarefa',
                        ['reason' => $task->rejection_reason]);
                }

                // Coluna de aprovação: arrastar para dentro submete a peça ao
                // painel do cliente; arrastar para fora cancela um envio que
                // ainda não foi respondido.
                if ($target->is_approval_column && $task->client_id) {
                    app(ApprovalService::class)->submit($task, auth()->user(), $origin);
                } elseif ($origin->is_approval_column) {
                    $pendente = $task->approvals()->first();

                    if ($pendente && $pendente->isPending()) {
                        $pendente->delete();
                    }
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    /* --------------------------------------------------------------------- */
    /* Helpers                                                               */
    /* --------------------------------------------------------------------- */

    private function formData(Project $project): array
    {
        return [
            'project' => $project->load('client', 'columns'),
            'members' => User::where('company_id', $project->company_id)->orderBy('name')->get(),
            'contentTypes' => Task::CONTENT_TYPES,
            // Inclui as predefinidas que ainda nao viraram registro.
            'allTags' => Tag::comPredefinidas(),
        ];
    }

    public function complete(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        // A coluna de destino é a que estiver marcada como "concluído" no menu
        // da coluna. Antes daqui pegávamos a ÚLTIMA coluna por posição, o que
        // mandava a tarefa para "Rejeitado" no template padrão — e para lugares
        // diferentes sempre que alguém reordenava o quadro.
        //
        // Sem nenhuma coluna marcada, a automação está desligada: a tarefa é
        // concluída onde está, sem mudar de coluna. Antes isso devolvia 422 e
        // um alerta pedindo para marcar uma coluna — parecia erro do sistema.
        $destino = $task->project->columns()
            ->where('marks_published', true)
            ->orderBy('position')
            ->first();

        $origem = $task->column;
        $mudouDeColuna = $destino && $origem && $origem->id !== $destino->id;

        $task->update([
            'column_id' => $destino?->id ?? $task->column_id,
            'is_published' => true,
            'published_at' => $task->published_at ?? now(),
        ]);

        if ($mudouDeColuna) {
            $this->log($task, TaskActivity::TYPE_COLUMN_CHANGED,
                "concluiu e moveu para \"{$destino->name}\"");
        }

        $this->log($task, TaskActivity::TYPE_PUBLISHED, 'marcou como concluído');

        return response()->json([
            'ok' => true,
            'moved' => $mudouDeColuna,
            'column_id' => $destino?->id,
            'column_name' => $destino?->name,
        ]);
    }

    /** Garante que a coluna pertence ao projeto (e à empresa via scope). */
    private function resolveColumn(int $columnId, Project $project): Column
    {
        if (!$columnId) {
            $column = $project->columns()->orderBy('position')->first();
            abort_if(!$column, 422, 'O projeto precisa ter pelo menos uma coluna.');
            return $column;
        }
        return $project->columns()->findOrFail($columnId);
    }

    private function syncTags(Task $task, array $tagsInput): void
    {
        $ids = collect($tagsInput)
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->map(function ($input) use ($task) {
                $parts = explode('|', $input);
                $name = trim($parts[0]);
                $color = count($parts) > 1 ? trim($parts[1]) : '#94a3b8';
                
                // Try to find the tag by name, or create it with the specified color
                $tag = Tag::where('company_id', $task->company_id)->where('name', $name)->first();
                if ($tag) {
                    if (count($parts) > 1 && $tag->color !== $color) {
                        $tag->update(['color' => $color]);
                    }
                } else {
                    $tag = Tag::create([
                        'company_id' => $task->company_id,
                        'name' => $name,
                        'color' => $color
                    ]);
                }
                return $tag->id;
            });

        $task->tags()->sync($ids);
    }

    /**
     * Compara o que veio do formulário com o que está no banco e escreve no
     * histórico o que de fato mudou — com nome de quem entrou/saiu, flag
     * adicionada, título antigo etc. Sem isso o log dizia só "alterou os
     * responsáveis", que não conta para quem a tarefa foi passada.
     *
     * Roda antes do update(), então $task ainda tem os valores antigos.
     */
    private function trackChanges(TaskRequest $request, Task $task, Column $column): void
    {
        $this->trackAssignees($request, $task);
        $this->trackTitle($request, $task);
        $this->trackDescription($request, $task);
        $this->trackTags($request, $task);

        if ((string) $request->validated('publish_date') !== (string) optional($task->publish_date)->toDateString()) {
            $nova = $request->validated('publish_date');

            $this->logAgrupado($task, TaskActivity::TYPE_PUBLISH_DATE_CHANGED,
                $nova
                    ? 'marcou a publicação para '.\Carbon\Carbon::parse($nova)->format('d/m/Y')
                    : 'tirou a data de publicação');
        }

        if ($column->id !== $task->column_id) {
            $this->log($task, TaskActivity::TYPE_COLUMN_CHANGED, "moveu para \"{$column->name}\"");
        }
    }

    /**
     * "Passou para a Letícia" é justamente o evento que ninguém enxergava:
     * o log registra quem entrou e quem saiu, pelo nome.
     */
    private function trackAssignees(TaskRequest $request, Task $task): void
    {
        $atuais = $task->assignees()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $novos = collect($request->input('assignees', []))->map(fn ($id) => (int) $id)->unique()->values()->all();

        sort($atuais);
        sort($novos);

        if ($atuais === $novos) {
            return;
        }

        $entraram = array_values(array_diff($novos, $atuais));
        $sairam = array_values(array_diff($atuais, $novos));

        $nomes = User::whereIn('id', array_merge($entraram, $sairam))->pluck('name', 'id');
        $listar = fn (array $ids) => $this->listaLegivel(
            collect($ids)->map(fn ($id) => $nomes[$id] ?? 'alguém')->all()
        );

        // Quem sai é quase sempre a própria pessoa repassando o card — dizer
        // "Leticia passou de Leticia para Bruno" só polui. O nome de quem saiu
        // continua no meta para quem precisar do detalhe.
        $eu = auth()->id();

        $descricao = match (true) {
            $entraram && $sairam === [$eu] => 'passou a tarefa para '.$listar($entraram),
            $entraram && $sairam => 'passou de '.$listar($sairam).' para '.$listar($entraram),
            $entraram === [$eu] => 'assumiu a tarefa',
            (bool) $entraram => $atuais
                ? 'adicionou '.$listar($entraram).' como responsável'
                : 'passou a tarefa para '.$listar($entraram),
            $sairam === [$eu] => 'saiu da tarefa',
            default => 'tirou '.$listar($sairam).' dos responsáveis',
        };

        $this->logAgrupado($task, TaskActivity::TYPE_ASSIGNEE_CHANGED, $descricao, [
            'entraram' => $listar($entraram) ?: null,
            'sairam' => $listar($sairam) ?: null,
        ]);
    }

    private function trackTitle(TaskRequest $request, Task $task): void
    {
        $antes = trim((string) $task->title);
        $depois = trim((string) ($request->validated('title') ?? ''));

        if ($depois === '' || $antes === $depois) {
            return;
        }

        $novo = Str::limit($depois, 60);

        $this->logAgrupado($task, TaskActivity::TYPE_TITLE_CHANGED,
            ($antes === '' || $antes === 'Nova Tarefa')
                ? "nomeou o card de \"{$novo}\""
                : 'renomeou "'.Str::limit($antes, 60)."\" para \"{$novo}\"");
    }

    private function trackDescription(TaskRequest $request, Task $task): void
    {
        $antes = (string) $task->description;
        $depois = (string) ($request->validated('description') ?? '');

        $textoAntes = $this->textoDoHtml($antes);
        $textoDepois = $this->textoDoHtml($depois);

        // O Quill manda "<p><br></p>" no lugar de vazio, então comparar o HTML
        // cru marcaria mudança em todo card que nunca teve descrição. Só o que
        // muda o texto conta — ou uma formatação nova sobre texto existente.
        $mudou = $textoAntes !== $textoDepois
            || ($textoDepois !== '' && $antes !== $depois);

        if (! $mudou) {
            return;
        }

        $this->logAgrupado($task, TaskActivity::TYPE_DESCRIPTION_CHANGED,
            $textoDepois === ''
                ? 'apagou a descrição'
                : ($textoAntes === '' ? 'escreveu a descrição' : 'editou a descrição'),
            ['trecho' => Str::limit($textoDepois, 140) ?: null]);
    }

    private function trackTags(TaskRequest $request, Task $task): void
    {
        $antes = $task->tags->pluck('name')->map(fn ($n) => trim((string) $n))->unique();

        $depois = collect($request->input('tags', []))
            ->map(fn ($t) => trim(explode('|', (string) $t)[0]))
            ->filter()
            ->unique();

        $entraram = $depois->diff($antes)->values()->all();
        $sairam = $antes->diff($depois)->values()->all();

        if (! $entraram && ! $sairam) {
            return;
        }

        $partes = [];
        if ($entraram) {
            $partes[] = 'marcou '.$this->listaLegivel($entraram);
        }
        if ($sairam) {
            $partes[] = 'tirou '.$this->listaLegivel($sairam);
        }

        $this->logAgrupado($task, TaskActivity::TYPE_TAGS_CHANGED,
            implode(' e ', $partes).' nas flags');
    }

    /** Texto puro de um HTML do Quill, para comparar conteúdo sem a marcação. */
    private function textoDoHtml(string $html): string
    {
        $texto = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $html);

        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($texto)));
    }

    /** ["Ana"] => "Ana"; ["Ana","Bia"] => "Ana e Bia"; 3+ => "Ana, Bia e Caio". */
    private function listaLegivel(array $nomes): string
    {
        if (count($nomes) <= 1) {
            return (string) ($nomes[0] ?? '');
        }

        $ultimo = array_pop($nomes);

        return implode(', ', $nomes).' e '.$ultimo;
    }

    private function log(Task $task, string $type, string $description, array $meta = []): void
    {
        TaskActivity::registrar($task, $type, $description, $meta);
    }

    /**
     * O slideover salva sozinho a cada tecla parada (500ms). Sem agrupar, uma
     * frase digitada na descrição viraria dez linhas de histórico — por isso
     * edições seguidas do mesmo autor, do mesmo tipo, atualizam a última linha
     * em vez de criar outra.
     */
    private function logAgrupado(Task $task, string $type, string $description, array $meta = []): void
    {
        TaskActivity::registrarAgrupado($task, $type, $description, $meta);
    }
}
