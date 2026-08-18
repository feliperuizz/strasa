<x-app-layout title="{{ $task->title }}">
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('projects.board', $task->project) }}" class="text-sm text-slate-400 hover:text-white">{{ $task->project->name }}</a>
            <span class="text-slate-600">/</span>
            <span class="text-sm text-slate-300">{{ $task->title }}</span>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 max-w-5xl mx-auto flex flex-col lg:flex-row gap-6">
        
        {{-- Coluna principal --}}
        <div class="flex-1 space-y-6">
            {{-- Detalhes principais --}}
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-white">{{ $task->title }}</h1>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('tasks.edit', $task) }}" class="rounded bg-ink-700 px-3 py-1.5 text-sm font-medium text-slate-200 hover:bg-ink-600">Editar</a>
                    </div>
                </div>

                <div class="mt-6 prose prose-invert max-w-none text-slate-300 prose-a:text-brand-400">
                    {!! nl2br(e($task->description ?: 'Sem descrição.')) !!}
                </div>
            </div>

            {{-- Anexos (R2/S3) --}}
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-white">Anexos</h2>
                    <form action="{{ route('attachments.store', $task) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input type="file" name="file" required class="text-sm text-slate-400 file:mr-2 file:rounded file:border-0 file:bg-ink-700 file:px-2 file:py-1 file:text-xs file:text-white hover:file:bg-ink-600">
                        <button class="rounded bg-ink-700 px-2 py-1 text-xs font-medium text-white hover:bg-ink-600">Enviar</button>
                    </form>
                </div>

                @if($task->attachments->isEmpty())
                    <p class="text-sm text-slate-500">Nenhum anexo.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($task->attachments as $att)
                            <div class="group relative rounded-lg border border-ink-600 bg-ink-900 p-2">
                                @if(str_starts_with($att->mime_type, 'image/'))
                                    <a href="{{ $att->url }}" target="_blank">
                                        <img src="{{ $att->url }}" class="h-24 w-full rounded object-cover mb-2" alt="">
                                    </a>
                                @else
                                    <div class="h-24 w-full rounded bg-ink-800 flex items-center justify-center mb-2">
                                        <span class="text-xs text-slate-500">{{ strtoupper(pathinfo($att->name, PATHINFO_EXTENSION)) }}</span>
                                    </div>
                                @endif
                                <div class="truncate text-xs text-slate-300" title="{{ $att->name }}">{{ $att->name }}</div>
                                
                                <form action="{{ route('attachments.destroy', $att) }}" method="POST" class="absolute top-1 right-1 hidden group-hover:block">
                                    @csrf @method('DELETE')
                                    <button class="rounded bg-rose-600/90 p-1 text-white hover:bg-rose-500" title="Excluir" onsubmit="return confirm('Excluir anexo?')">✕</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Comentários --}}
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                <h2 class="mb-4 font-semibold text-white">Comentários</h2>
                
                <div class="space-y-4 mb-6">
                    @forelse($task->comments as $comment)
                        <div class="flex gap-3">
                            <x-avatar :user="$comment->user" :size="8" />
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-slate-200">{{ $comment->user->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="mt-1 text-sm text-slate-300">
                                    {!! nl2br(e($comment->content)) !!}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Sem comentários ainda.</p>
                    @endforelse
                </div>

                <form action="{{ route('comments.store', $task) }}" method="POST" class="flex gap-3">
                    @csrf
                    <x-avatar :user="auth()->user()" :size="8" />
                    <div class="flex-1">
                        <textarea name="content" required rows="2" placeholder="Escreva um comentário..."
                                  class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none"></textarea>
                        <div class="mt-2 text-right">
                            <button class="rounded-lg bg-brand-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-500">Comentar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sidebar Direita (Metadados) --}}
        <div class="w-full lg:w-80 space-y-5 shrink-0">
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-4 space-y-4">
                
                <div>
                    <span class="block text-xs font-semibold uppercase text-slate-500">Coluna</span>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full" style="background: {{ $task->column->color }}"></span>
                        <span class="text-sm font-medium text-white">{{ $task->column->name }}</span>
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-semibold uppercase text-slate-500">Responsável</span>
                    <div class="mt-1 flex items-center gap-2">
                        @if($task->assignee)
                            <x-avatar :user="$task->assignee" :size="6" />
                            <span class="text-sm text-slate-200">{{ $task->assignee->name }}</span>
                        @else
                            <span class="text-sm text-slate-500">Não atribuído</span>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-semibold uppercase text-slate-500">Data de Publicação</span>
                    <div class="mt-1 text-sm text-slate-200">
                        {{ $task->publish_date ? $task->publish_date->format('d/m/Y') : 'Não definida' }}
                    </div>
                </div>

                
                <div>
                    <span class="block text-xs font-semibold uppercase text-slate-500">Status Publicação</span>
                    <div class="mt-1">
                        @if($task->is_published)
                            <span class="rounded bg-emerald-900/40 px-2 py-1 text-xs text-emerald-400">Publicado</span>
                        @else
                            <span class="rounded bg-ink-700 px-2 py-1 text-xs text-slate-400">Pendente</span>
                        @endif
                    </div>
                </div>

                <div class="pt-4 border-t border-ink-600">
                    <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Excluir esta tarefa e todos os anexos permanentemente?')">
                        @csrf @method('DELETE')
                        <button class="w-full rounded border border-rose-700/50 py-1.5 text-sm text-rose-400 hover:bg-rose-900/30">Excluir Tarefa</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
