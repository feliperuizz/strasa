<x-app-layout title="Equipe">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-base font-semibold text-white">Equipe da Agência</h1>
            @if(auth()->user()->isAdmin())
                <button x-data @click="$dispatch('open-modal', 'invite-modal')" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-500">＋ Adicionar</button>
            @endif
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 max-w-5xl mx-auto space-y-8">
        
        {{-- Usuários Ativos --}}
        <div>
            <h2 class="text-lg font-medium text-white mb-4">Membros Ativos</h2>
            <div class="overflow-x-auto rounded-xl border border-ink-600 bg-ink-800">
                <table class="w-full text-left text-sm text-slate-300 min-w-[600px]">
                    <thead class="border-b border-ink-600 bg-ink-900/50 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nome</th>
                            <th class="px-4 py-3 font-medium">E-mail</th>
                            <th class="px-4 py-3 font-medium">Desempenho</th>
                            <th class="px-4 py-3 font-medium text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @foreach($members as $user)
                            <tr class="hover:bg-ink-700/50">
                                <td class="px-4 py-3 flex items-center gap-3">
                                    <x-avatar :user="$user" :size="8" />
                                    <span class="font-medium text-white">{{ $user->name }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="text-xs text-slate-400">
                                            <span class="text-white">{{ $user->tasks_total - $user->tasks_completed }}</span> pendentes
                                        </div>
                                        <div class="text-xs text-slate-600">&bull;</div>
                                        <div class="text-xs text-emerald-400">
                                            {{ $user->tasks_completed }} concluídas
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 flex items-center justify-end gap-3">
                                    <span class="rounded bg-ink-900 px-2 py-1 text-xs text-slate-300 border border-ink-600">{{ $user->roleLabel() }}</span>
                                    @if($user->id !== auth()->id() && auth()->user()->isAdmin())
                                        <button class="text-brand-400 hover:text-brand-300 text-xs"
                                                @click="$dispatch('open-edit-member', { id: {{ $user->id }}, name: '{{ addslashes($user->name) }}', email: '{{ addslashes($user->email) }}', role: '{{ $user->role }}' })">
                                            Editar
                                        </button>
                                        <form method="POST" action="{{ route('team.members.destroy', $user) }}" onsubmit="return confirm('Remover este membro?')">
                                            @csrf @method('DELETE')
                                            <button class="text-rose-400 hover:text-rose-300 text-xs">Remover</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Modal de Adicionar (Alpine.js) --}}
    @if(auth()->user()->isAdmin())
        <div x-data="{ open: false }" 
             @open-modal.window="if ($event.detail === 'invite-modal') open = true"
             x-show="open" 
             style="display: none;" 
             class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-ink-900/80 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="open" 
                         x-transition:enter="ease-out duration-300" 
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave="ease-in duration-200" 
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         @click.outside="open = false"
                         class="relative transform overflow-hidden rounded-xl border border-ink-600 bg-ink-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        
                        <form method="POST" action="{{ route('team.invitations.store') }}">
                            @csrf
                            <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <h3 class="text-lg font-semibold leading-6 text-white" id="modal-title">Adicionar membro</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="mb-1 block text-sm text-slate-300">Nome</label>
                                        <input type="text" name="name" required placeholder="Nome do membro"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm text-slate-300">E-mail</label>
                                        <input type="email" name="email" required placeholder="email@exemplo.com"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm text-slate-300">Senha</label>
                                        <input type="password" name="password" required placeholder="••••••••" minlength="8"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm text-slate-300">Função</label>
                                        <select name="role" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            @foreach(\App\Models\User::ROLES as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-ink-600 bg-ink-800/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 sm:ml-3 sm:w-auto">Adicionar</button>
                                <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg border border-ink-600 bg-ink-800 px-3 py-2 text-sm font-semibold text-slate-300 shadow-sm hover:bg-ink-700 sm:mt-0 sm:w-auto">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de Editar (Alpine.js) --}}
    @if(auth()->user()->isAdmin())
        <div x-data="{ 
                open: false, 
                id: null, 
                name: '', 
                email: '', 
                role: '' 
             }" 
             @open-edit-member.window="open = true; id = $event.detail.id; name = $event.detail.name; email = $event.detail.email; role = $event.detail.role"
             x-show="open" 
             style="display: none;" 
             class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-ink-900/80 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="open" 
                         x-transition:enter="ease-out duration-300" 
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave="ease-in duration-200" 
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         @click.outside="open = false"
                         class="relative transform overflow-hidden rounded-xl border border-ink-600 bg-ink-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        
                        <form method="POST" :action="`{{ url('team/members') }}/${id}`">
                            @csrf
                            @method('PATCH')
                            <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <h3 class="text-lg font-semibold leading-6 text-white" id="modal-title">Editar membro</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="mb-1 block text-sm text-slate-300">Nome</label>
                                        <input type="text" name="name" x-model="name" required placeholder="Nome do membro"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm text-slate-300">E-mail</label>
                                        <input type="email" name="email" x-model="email" required placeholder="email@exemplo.com"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm text-slate-300">Senha (Opcional - preencha para alterar)</label>
                                        <input type="password" name="password" placeholder="••••••••" minlength="8"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm text-slate-300">Função</label>
                                        <select name="role" x-model="role" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            @foreach(\App\Models\User::ROLES as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-ink-600 bg-ink-800/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 sm:ml-3 sm:w-auto">Salvar</button>
                                <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg border border-ink-600 bg-ink-800 px-3 py-2 text-sm font-semibold text-slate-300 shadow-sm hover:bg-ink-700 sm:mt-0 sm:w-auto">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
