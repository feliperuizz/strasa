@props(['task'])

@php
    $cover = $task->coverImage();

    // Estado da peça no painel do cliente. Vem da relação já carregada pelo
    // BoardController, então não custa uma query por card.
    $aprovacao = $task->currentApproval();

    $selo = match ($aprovacao?->status) {
        'approved' => ['Aprovado',      'bg-emerald-500/15 text-emerald-400 border-emerald-500/30', 'border-l-emerald-500'],
        'rejected' => ['Ajuste pedido', 'bg-rose-500/15 text-rose-400 border-rose-500/30',          'border-l-rose-500'],
        'pending'  => ['Aguardando',    'bg-amber-500/15 text-amber-400 border-amber-500/30',       'border-l-amber-500'],
        default    => null,
    };
@endphp

<div data-id="{{ $task->id }}"
     @click.stop="window.__kanbanDragging || $dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')"
     @contextmenu.prevent.stop="$dispatch('open-context-menu', { taskId: {{ $task->id }}, currentColumn: {{ $task->column_id }}, event: $event, url: '{{ route('tasks.destroy', $task) }}' })"
     class="task-card group relative cursor-grab rounded-xl border border-ink-600 bg-ink-800 p-0 shadow-sm transition-colors hover:border-slate-500 active:cursor-grabbing select-none @if($selo) border-l-4 {{ $selo[2] }} @endif">

    @if($cover)
        <img src="{{ $cover->url }}" alt="" loading="lazy" draggable="false"
             class="h-28 w-full rounded-t-xl object-cover pointer-events-none">
    @endif

    <div class="p-3">
        @if($selo)
            <div class="mb-2 flex items-center gap-1.5">
                <span class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $selo[1] }}">
                    @if($aprovacao->isApproved())
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @elseif($aprovacao->isRejected())
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4l16 16M20 4L4 20"/></svg>
                    @else
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                    @endif
                    {{ $selo[0] }}
                </span>

                @if($aprovacao->round > 1)
                    <span class="text-[9.5px] font-semibold text-slate-500" title="Rodada de aprovação">v{{ $aprovacao->round }}</span>
                @endif
            </div>
        @endif

        <div class="flex items-start gap-2">
            <button type="button" 
                    @click.stop="$event.preventDefault(); $event.stopPropagation(); window.completeTask($el, {{ $task->id }}, $event)"
                    class="mt-0.5 shrink-0 text-slate-400 hover:text-emerald-400 focus:outline-none group/check transition-colors relative z-10"
                    title="Concluir tarefa">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="1.5"></circle>
                    <path d="M8 12l3 3 5-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-0 group-hover/check:opacity-100 transition-opacity"></path>
                </svg>
            </button>
            <span class="text-[13px] font-medium leading-snug text-slate-200 mt-0.5 relative z-10"
               style="{{ $task->is_published ? 'text-decoration: line-through; opacity: 0.6;' : '' }}">
                {{ $task->title }}
            </span>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <div class="flex flex-wrap items-center gap-1.5">
                @if($task->items->count() > 0)
                    <span class="text-[10px] text-slate-400 font-medium bg-ink-800 px-1.5 py-0.5 rounded-md border border-ink-600 flex items-center gap-1" title="Checklist">
                        <svg class="w-3 h-3 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ $task->items->where('is_completed', true)->count() }}/{{ $task->items->count() }}
                    </span>
                @endif
                @if($task->tags->isNotEmpty())
                    @foreach($task->tags as $tag)
                        <span class="rounded px-1.5 py-0.5 text-[10px] font-medium"
                              style="background: {{ $tag->color }}22; color: {{ $tag->color }}">#{{ $tag->name }}</span>
                    @endforeach
                @endif
            </div>
            <div class="relative z-10 flex items-center gap-2">
                @if($task->publish_date)
                    <span class="text-[11px] text-slate-400" title="Publicação: {{ $task->publish_date->format('d/m/Y') }} {{ $task->publish_time ? \Carbon\Carbon::parse($task->publish_time)->format('H:i') : '' }}">
                        {{ $task->publish_date->format('d/m') }}
                        @if($task->publish_time)
                            <span class="opacity-75 ml-0.5">{{ \Carbon\Carbon::parse($task->publish_time)->format('H:i') }}</span>
                        @endif
                    </span>
                @endif
                <div class="flex -space-x-1.5">
                    @foreach($task->assignees->take(3) as $assignee)
                        <div class="ring-2 ring-ink-800 rounded-full" title="{{ $assignee->name }}">
                            <x-avatar :user="$assignee" :size="6" />
                        </div>
                    @endforeach
                    @if($task->assignees->count() > 3)
                        <div class="h-6 w-6 rounded-full bg-ink-700 ring-2 ring-ink-800 flex items-center justify-center text-[9px] font-bold text-slate-300">
                            +{{ $task->assignees->count() - 3 }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($task->is_published)
            <span class="mt-2 inline-block rounded bg-emerald-900/40 px-1.5 py-0.5 text-[10px] text-emerald-400">● Publicado</span>
        @endif
    </div>
</div>
