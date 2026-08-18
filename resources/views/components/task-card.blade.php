@props(['task'])

@php $cover = $task->coverImage(); @endphp

<div data-id="{{ $task->id }}"
     @click.stop="window.__kanbanDragging || $dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')"
     @contextmenu.prevent.stop="$dispatch('open-context-menu', { taskId: {{ $task->id }}, currentColumn: {{ $task->column_id }}, event: $event, url: '{{ route('tasks.destroy', $task) }}' })"
     class="task-card group relative cursor-grab rounded-xl border border-ink-600 bg-[#2a2b2d] p-0 shadow-sm transition-colors hover:border-slate-500 active:cursor-grabbing select-none">

    @if($cover)
        <img src="{{ $cover->url }}" alt="" loading="lazy" draggable="false"
             class="h-28 w-full rounded-t-xl object-cover pointer-events-none">
    @endif

    <div class="p-3">
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
                <x-avatar :user="$task->assignee" :size="6" />
            </div>
        </div>

        @if($task->is_published)
            <span class="mt-2 inline-block rounded bg-emerald-900/40 px-1.5 py-0.5 text-[10px] text-emerald-400">● Publicado</span>
        @endif
    </div>
</div>
