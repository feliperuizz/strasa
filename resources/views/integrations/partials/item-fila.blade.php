{{-- Um post da coluna "Aprovado / Agendado" com um selo à direita ($selo, $tom). --}}
<li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2.5 text-sm">
    <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $t->client?->color ?? '#64748b' }}"></span>
    <a href="{{ route('tasks.show', $t) }}" @click.prevent="$dispatch('open-task-modal', '{{ route('tasks.show', $t) }}')"
       class="min-w-0 flex-1 font-medium text-slate-200 hover:text-brand-300">{{ $t->title ?: 'Sem título' }}</a>
    <span class="text-xs text-slate-500">{{ $t->client?->name }}</span>
    <span class="rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $tom }}">{{ $selo }}</span>
</li>
