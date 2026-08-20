<x-app-layout title="Meu Perfil">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white tracking-wide">Meu Perfil</h1>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="bg-ink-800 rounded-xl border border-ink-600 p-6 space-y-6 shadow-xl">
            @csrf
            @method('PATCH')

            {{-- Avatar --}}
            <div class="flex items-center gap-6">
                <div class="relative group h-24 w-24">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-24 w-24 rounded-full object-cover ring-4 ring-ink-700">
                    @else
                        <span class="grid place-items-center rounded-full font-semibold text-white ring-4 ring-ink-700 h-24 w-24 text-2xl"
                              style="background: {{ $user->avatar_color ?? '#6366f1' }}">
                            {{ $user->initials() }}
                        </span>
                    @endif
                    <label class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                        <span class="text-xs font-medium text-white">Alterar</span>
                        <input type="file" name="avatar" accept="image/*" class="hidden">
                    </label>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-white">Sua Foto</h3>
                    <p class="text-sm text-slate-400">Clique na imagem para enviar uma foto. JPG, PNG ou WEBP. Máx 5MB.</p>
                </div>
            </div>

            {{-- Cor do Avatar (se não tiver foto) --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Cor do Ícone</label>
                <input type="color" name="avatar_color" value="{{ old('avatar_color', $user->avatar_color ?? '#6366f1') }}" class="h-10 w-20 rounded border border-ink-600 bg-ink-900 p-1 cursor-pointer">
                <p class="text-xs text-slate-500 mt-1">Usada como fundo se você não tiver uma foto enviada.</p>
            </div>

            <hr class="border-ink-700">

            {{-- Nome --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Nome completo</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-2 px-3 text-slate-200 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @error('name') <p class="text-rose-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-2 px-3 text-slate-200 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @error('email') <p class="text-rose-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <hr class="border-ink-700">

            {{-- Senha --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Nova Senha</label>
                <input type="password" name="password" class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-2 px-3 text-slate-200 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                <p class="text-xs text-slate-500 mt-1">Deixe em branco para não alterar.</p>
                @error('password') <p class="text-rose-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Confirmar Senha</label>
                <input type="password" name="password_confirmation" class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-2 px-3 text-slate-200 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
            </div>

            <hr class="border-ink-700">

            {{-- Notificações (PWA / Push) --}}
            <div x-data="notificationsSetup()">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-white">Notificações no Dispositivo (Push)</h3>
                    <button type="button" @click="subscribe()" :disabled="subscribing" 
                            class="rounded bg-ink-700 px-3 py-1.5 text-xs font-semibold text-brand-400 hover:bg-ink-600 border border-ink-600 transition disabled:opacity-50">
                        <span x-text="subscribing ? 'Ativando...' : (isSubscribed ? 'Renovar Permissão' : 'Ativar neste Aparelho')"></span>
                    </button>
                </div>
                <p class="text-sm text-slate-400 mb-6">Permita que o Strasa envie notificações para o seu celular ou computador. No iPhone, você precisa adicionar este site à Tela de Início primeiro.</p>

                <div class="space-y-4 bg-ink-900/30 p-4 rounded-lg border border-ink-700/50">
                    @php $settings = $user->notification_settings ?? []; @endphp

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Resumo do Dia</label>
                            <p class="text-xs text-slate-500">Notificar quantidade de tarefas que você tem para hoje.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="time" name="notification_settings[daily_time]" value="{{ $settings['daily_time'] ?? '08:00' }}" class="rounded bg-ink-900 border border-ink-600 text-slate-300 text-sm px-2 py-1 focus:ring-brand-500">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="notification_settings[daily_enabled]" value="1" {{ !empty($settings['daily_enabled']) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-ink-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                            </label>
                        </div>
                    </div>

                    <hr class="border-ink-700/50">

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Postagens e Agendamentos</label>
                            <p class="text-xs text-slate-500">Notificar publicações sob sua responsabilidade hoje.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="time" name="notification_settings[publish_time]" value="{{ $settings['publish_time'] ?? '10:00' }}" class="rounded bg-ink-900 border border-ink-600 text-slate-300 text-sm px-2 py-1 focus:ring-brand-500">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="notification_settings[publish_enabled]" value="1" {{ !empty($settings['publish_enabled']) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-ink-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                            </label>
                        </div>
                    </div>

                    <hr class="border-ink-700/50">

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Lembrete de Publicação (5 min)</label>
                            <p class="text-xs text-slate-500">Notificar 5 minutos antes do horário marcado no quadro.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="notification_settings[publish_time_reminder_enabled]" value="1" {{ !empty($settings['publish_time_reminder_enabled']) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-ink-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                            </label>
                        </div>
                    </div>

                    <hr class="border-ink-700/50">

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">E-mail de Briefing Diário</label>
                            <p class="text-xs text-slate-500">Receber um resumo das suas tarefas todos os dias às 09:00.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="notification_settings[daily_briefing_email_enabled]" value="1" {{ !empty($settings['daily_briefing_email_enabled']) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-ink-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                            </label>
                        </div>
                    </div>

                    <hr class="border-ink-700/50">

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Tema do Sistema</label>
                            <p class="text-xs text-slate-500">Escolha entre Claro, Escuro ou Automático.</p>
                        </div>
                        <div>
                            <select name="notification_settings[theme]" class="rounded bg-[#2a2b2d] border border-ink-600 text-sm text-slate-300 p-2 focus:ring-brand-500">
                                <option value="system" {{ ($settings['theme'] ?? 'system') === 'system' ? 'selected' : '' }}>Automático (Sistema)</option>
                                <option value="dark" {{ ($settings['theme'] ?? '') === 'dark' ? 'selected' : '' }}>Escuro</option>
                                <option value="light" {{ ($settings['theme'] ?? '') === 'light' ? 'selected' : '' }}>Claro</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-ink-800 transition">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function notificationsSetup() {
            return {
                isSubscribed: false,
                subscribing: false,
                
                init() {
                    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                        console.warn('Push messaging is not supported.');
                        return;
                    }
                    
                    navigator.serviceWorker.ready.then(registration => {
                        registration.pushManager.getSubscription().then(subscription => {
                            this.isSubscribed = !(subscription === null);
                        });
                    });
                },
                
                subscribe() {
                    this.subscribing = true;
                    
                    navigator.serviceWorker.ready.then(registration => {
                        const applicationServerKey = this.urlB64ToUint8Array('{{ config('webpush.vapid.public_key') }}');
                        
                        registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: applicationServerKey
                        })
                        .then(subscription => {
                            return fetch('{{ route('push.subscribe') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(subscription)
                            });
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Bad status code from server.');
                            this.isSubscribed = true;
                            alert('Notificações ativadas neste dispositivo com sucesso!');
                        })
                        .catch(err => {
                            console.error('Failed to subscribe the user: ', err);
                            alert('Erro ao ativar notificações. Verifique as permissões do navegador.');
                        })
                        .finally(() => {
                            this.subscribing = false;
                        });
                    });
                },
                
                urlB64ToUint8Array(base64String) {
                    const padding = '='.repeat((4 - base64String.length % 4) % 4);
                    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
                    const rawData = window.atob(base64);
                    const outputArray = new Uint8Array(rawData.length);
                    for (let i = 0; i < rawData.length; ++i) {
                        outputArray[i] = rawData.charCodeAt(i);
                    }
                    return outputArray;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
