<x-app-layout title="Aprovação · {{ $client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            @if($client->logo_url)
                <img src="{{ $client->logo_url }}" class="h-8 w-8 rounded object-cover ring-1 ring-white/10" alt="">
            @else
                <span class="grid h-8 w-8 place-items-center rounded text-xs font-bold text-slate-200"
                      style="background: {{ $client->color ?? '#475569' }}">{{ \Illuminate\Support\Str::substr($client->name, 0, 1) }}</span>
            @endif
            <div>
                <h1 class="text-base font-semibold text-slate-200">{{ $client->name }}</h1>
                <p class="text-[11.5px] text-slate-400">Painel de aprovação</p>
            </div>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6">

        {{-- Chave de acesso, mensagem pronta e preferências --}}
        @include('clients.partials.approval-portal')

        {{-- Histórico de respostas deste cliente --}}
        <div class="rounded-xl border border-ink-600 bg-ink-800/85 backdrop-blur-md p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-200 mb-4">
                Peças enviadas
                @if($aprovacoes->isNotEmpty())
                    <span class="ml-1 text-[11.5px] font-normal text-slate-500">({{ $aprovacoes->count() }} mais recentes)</span>
                @endif
            </h2>

            @if($aprovacoes->isEmpty())
                <div class="rounded-lg border border-dashed border-ink-600 bg-ink-900/40 p-8 text-center">
                    <p class="text-sm text-slate-400">
                        Nenhuma peça enviada para este cliente ainda.<br>
                        Arraste um card para a coluna de aprovação, ou use o botão dentro da tarefa.
                    </p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($aprovacoes as $aprovacao)
                        @php
                            $cores = match($aprovacao->status) {
                                'approved' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
                                'rejected' => 'border-rose-500/30 bg-rose-500/10 text-rose-400',
                                default    => 'border-amber-500/30 bg-amber-500/10 text-amber-400',
                            };
                        @endphp

                        <div class="flex flex-col gap-3 rounded-lg border border-ink-700 bg-ink-900/40 p-3 sm:flex-row sm:items-start">
                            <div class="flex-1 min-w-0">
                                @if($aprovacao->task)
                                    <a href="{{ route('projects.board', $aprovacao->task->project_id) }}"
                                       class="text-sm font-medium text-slate-100 hover:text-brand-400 transition">
                                        {{ $aprovacao->task->title }}
                                    </a>
                                @else
                                    <span class="text-sm italic text-slate-500">Tarefa removida</span>
                                @endif

                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px] text-slate-500">
                                    <span>{{ $aprovacao->round > 1 ? $aprovacao->round.'ª versão' : '1ª versão' }}</span>
                                    <span>enviado {{ $aprovacao->submitted_at?->diffForHumans() }}</span>
                                    @if($aprovacao->submitter)
                                        <span>por {{ $aprovacao->submitter->name }}</span>
                                    @endif
                                </div>

                                @if(filled($aprovacao->feedback))
                                    <div class="mt-2 rounded border border-ink-600 bg-ink-900/70 px-3 py-2">
                                        <div class="text-[10.5px] font-semibold uppercase tracking-wider text-slate-500 mb-0.5">Comentário do cliente</div>
                                        <p class="text-[13px] text-slate-300 whitespace-pre-line">{{ $aprovacao->feedback }}</p>
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0 sm:w-40 sm:text-right">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $cores }}">
                                    {{ $aprovacao->statusLabel() }}
                                </span>
                                @if($aprovacao->responded_at)
                                    <div class="mt-1.5 text-[11.5px] text-slate-500">
                                        {{ $aprovacao->reviewer_name }}<br>
                                        {{ $aprovacao->responded_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
