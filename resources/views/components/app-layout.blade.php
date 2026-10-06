@props(['title' => null, 'client' => null])

@php
    $activeClient = $client ?? (isset($project) && $project && $project->client ? $project->client : null);
@endphp

<!DOCTYPE html>
<html lang="pt-BR" class="h-[100dvh]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v={{ config('app.icon_version') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ config('app.icon_version') }}">

    {{-- CSS compilado (Tailwind buildado por Vite). Substitui o antigo cdn.tailwindcss.com,
         que baixava ~300KB de JS e compilava o CSS no navegador a cada page load. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://cdn.quilljs.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- Quill Editor (defer: só é usado pelo Alpine, que também é defer) -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script defer src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>
    <style>
        /* Oculta os botões originais e barra do viewer, deixando escuro igual Asana */
        .viewer-backdrop { background-color: rgba(30, 30, 30, 0.95); }
        .viewer-button { display: none !important; }
        
        /* Quill Dark/Light Theme Adjustments */
        .ql-toolbar.ql-snow, .ql-container.ql-snow {
            border-color: rgb(var(--ink-700)) !important;
        }
        .ql-toolbar.ql-snow {
            background-color: rgb(var(--ink-800));
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }
        .ql-container.ql-snow {
            background-color: rgb(var(--ink-900));
            border-bottom-left-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
        .ql-snow .ql-stroke { stroke: rgb(var(--text-primary)) !important; }
        .ql-snow .ql-fill, .ql-snow .ql-stroke.ql-fill { fill: rgb(var(--text-primary)) !important; }
        .ql-snow .ql-picker { color: rgb(var(--text-primary)) !important; }
        .ql-editor.ql-blank::before { color: rgb(var(--text-secondary)) !important; font-style: normal; }
        .ql-editor { min-height: 120px; font-family: inherit; font-size: 0.875rem; color: rgb(var(--text-primary)); }
    </style>
    <script>
        window.initTaskViewer = function(el) {
            setTimeout(() => {
                if (window.taskViewer) window.taskViewer.destroy();
                window.taskViewer = new Viewer(el, {
                    url: 'data-url',
                    filter(image) { return image.classList.contains('viewer-image'); },
                    toolbar: false,
                    navbar: false,
                    title: false,
                    button: false,
                    backdrop: true,
                    viewed(event) {
                        const container = window.taskViewer.viewer;
                        let header = container.querySelector('.asana-header');
                        const originalImage = event.detail.originalImage;
                        const imgName = originalImage.alt || 'Imagem';
                        const downloadUrl = originalImage.dataset.downloadUrl;

                        if (!header) {
                            header = document.createElement('div');
                            header.className = 'asana-header absolute top-0 left-0 w-full flex items-center justify-between px-6 py-4 text-slate-200 z-50 pointer-events-none font-sans';
                            header.innerHTML = `
                                <div class="flex flex-col pointer-events-auto flex-1">
                                    <span class="text-sm font-medium image-title truncate max-w-sm"></span>
                                </div>
                                <div class="flex items-center gap-1 pointer-events-auto bg-ink-800 rounded-md border border-ink-700 p-1 shadow-lg">
                                    <button onclick="window.taskViewer.zoom(-0.1)" class="p-1.5 text-slate-300 hover:text-slate-200 hover:bg-ink-700 rounded" title="Reduzir"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg></button>
                                    <button onclick="window.taskViewer.zoom(0.1)" class="p-1.5 text-slate-300 hover:text-slate-200 hover:bg-ink-700 rounded" title="Ampliar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>
                                    <div class="w-px h-4 bg-ink-700 mx-1"></div>
                                    <button onclick="window.taskViewer.reset()" class="p-1.5 text-slate-300 hover:text-slate-200 hover:bg-ink-700 rounded" title="Ajustar à tela"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg></button>
                                </div>
                                <div class="flex items-center justify-end gap-6 pointer-events-auto flex-1">
                                    <a href="" download class="image-download flex items-center gap-2 text-sm font-medium text-slate-300 hover:text-slate-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Fazer o download
                                    </a>
                                    <button onclick="window.taskViewer.hide()" class="p-1.5 text-slate-400 hover:text-slate-200 hover:bg-white/10 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            `;
                            container.appendChild(header);
                        }
                        
                        container.querySelector('.image-title').textContent = imgName;
                        container.querySelector('.image-download').href = downloadUrl;
                    }
                });
            }, 100);
        };
    </script>
    <style>
        :root {
            /* Tema Claro */
            --ink-900: 241 245 249; /* #f1f5f9 */
            --ink-800: 255 255 255; /* #ffffff */
            --ink-700: 226 232 240; /* #e2e8f0 */
            --ink-600: 203 213 225; /* #cbd5e1 */
            --ink-500: 148 163 184; /* #94a3b8 */
            --text-primary: 30 41 59; /* #1e293b */
            --text-secondary: 71 85 105; /* #475569 */
            --text-tertiary: 100 116 139; /* #64748b */
        }
        .dark {
            /* Tema Escuro (Padrão) */
            --ink-900: 30 30 30; /* #1e1e1e */
            --ink-800: 42 43 45; /* #2a2b2d */
            --ink-700: 54 54 56; /* #363638 */
            --ink-600: 69 69 69; /* #454545 */
            --ink-500: 107 107 107; /* #6b6b6b */
            --text-primary: 226 232 240; /* #e2e8f0 */
            --text-secondary: 203 213 225; /* #cbd5e1 */
            --text-tertiary: 148 163 184; /* #94a3b8 */
        }
    </style>
    @php
        $userTheme = auth()->check() ? (auth()->user()->notification_settings['theme'] ?? 'system') : 'system';
    @endphp
    <script>
        (function() {
            let theme = '{{ $userTheme }}';
            if (theme === 'system') {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            } else if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#111111">

    <!-- Service Worker Registration for PWA / WebPush -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ asset("sw.js") }}').then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="h-[100dvh] bg-ink-900 text-slate-200 antialiased font-sans">
<div class="flex h-[100dvh]" x-data="{ sidebar: window.innerWidth >= 1024 }">

    {{-- Mobile Overlay (Desativado, Sidebar agora é apenas Desktop) --}}
    <!-- div x-show="sidebar" class="fixed inset-0 z-20 bg-black/50 lg:hidden backdrop-blur-sm" @click="sidebar = false" x-cloak></div -->

    {{-- ============================ SIDEBAR ============================ --}}
    <aside x-show="sidebar" x-cloak
           class="hidden lg:block fixed inset-y-0 left-0 z-30 w-64 shrink-0 overflow-y-auto border-r border-ink-600 bg-ink-800 lg:static">
        <div class="flex items-center justify-center px-4 py-4 border-b border-ink-600">
            <img src="{{ asset('strasalogo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain dark:invert-0 invert">
        </div>

        <nav class="px-2 py-3 text-sm space-y-1">
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Início
            </x-nav-link>
            <x-nav-link :href="route('my-tasks')" :active="request()->routeIs('my-tasks')">
                <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                Minhas Tarefas
            </x-nav-link>
            <x-nav-link :href="route('approvals.index')" :active="request()->routeIs('approvals.*')">
                <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Aprovações
                @if(($aguardandoAprovacao ?? 0) > 0)
                    <span class="ml-auto rounded-full bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-400">{{ $aguardandoAprovacao }}</span>
                @endif
            </x-nav-link>
            <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Clientes
            </x-nav-link>
            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                <x-nav-link :href="route('demands.index')" :active="request()->routeIs('demands.*')">
                    <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-6 0h.01M12 16h3m-6 0h.01"></path></svg>
                    Demandas
                </x-nav-link>
            @endif
            @if(auth()->user()->isAdmin())
                <x-nav-link :href="route('team.index')" :active="request()->routeIs('team.*')">
                    <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Equipe
                </x-nav-link>
                <x-nav-link :href="route('activity-log.index')" :active="request()->routeIs('activity-log.*')">
                    <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Atividades
                </x-nav-link>
                <x-nav-link :href="route('integrations.index')" :active="request()->routeIs('integrations.*')">
                    <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                    Integrações
                </x-nav-link>
                <x-nav-link :href="route('financial.index')" :active="request()->routeIs('financial.*')">
                    <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Financeiro
                </x-nav-link>
            @endif
        </nav>

        <div class="px-4 pt-4 pb-1 flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500" title="Arraste para ordenar do seu jeito">Clientes</span>
            <div class="flex items-center gap-2.5">
                <button type="button" onclick="window.barraLateral.novaPasta()" class="text-slate-500 hover:text-slate-200" title="Nova pasta">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM12 11v4m-2-2h4"/>
                    </svg>
                </button>
                @can('create', \App\Models\Client::class)
                    <a href="{{ route('clients.create') }}" class="text-slate-500 hover:text-slate-200" title="Novo cliente">＋</a>
                @endcan
            </div>
        </div>

        {{-- Ordem e pastas são de cada usuário: arraste clientes (e pastas)
             para ordenar, e para dentro/fora das pastas. --}}
        <div class="px-2 pb-6 space-y-0.5" id="sidebar-client-list">
            @forelse($sidebarItens as $item)
                @if($item['tipo'] === 'pasta')
                    @include('partials.barra-pasta', ['pasta' => $item])
                @else
                    @include('partials.barra-cliente', ['client' => $item['cliente']])
                @endif
            @empty
                <p class="px-2 py-2 text-xs text-slate-500">Nenhum cliente ainda.</p>
            @endforelse
        </div>
        <template id="modelo-pasta-barra">
            @include('partials.barra-pasta', ['pasta' => ['id' => '', 'nome' => '', 'aberta' => true, 'clientes' => collect()]])
        </template>
    </aside>

    {{-- ============================ CONTEÚDO ============================ --}}
    <div class="flex min-w-0 flex-1 flex-col relative transition-all duration-300 {{ !empty($activeClient?->background_style) ? 'dark' : '' }}" style="{{ $activeClient?->background_style }}">
        <header class="flex items-center gap-3 border-b border-ink-600/70 bg-ink-800/80 px-4 py-3 backdrop-blur-md sticky top-0 z-20">
            <button @click="sidebar = !sidebar" class="hidden lg:block rounded p-1.5 text-slate-400 hover:bg-ink-700 hover:text-slate-200">☰</button>
            <div class="min-w-0 flex-1 flex items-center justify-between">
                <div class="min-w-0">{{ $header ?? '' }}</div>
                
                {{-- Busca Global --}}
                <div x-data="globalSearch" class="relative hidden sm:block w-64 mr-2" @click.outside="close()">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-2 h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="open = true" @keydown.escape="close()" placeholder="Buscar tarefas..." class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-1.5 pl-9 pr-3 text-sm text-slate-200 placeholder-slate-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <svg x-show="loading" class="absolute right-2.5 top-2 h-4 w-4 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>

                    <div x-show="open && (results.length > 0 || query.length > 0)" x-cloak
                         class="absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto rounded-lg border border-ink-600 bg-ink-800 py-2 shadow-2xl z-50">
                        <div x-show="results.length === 0 && !loading && query.length > 0" class="px-4 py-3 text-sm text-slate-500">
                            Nenhuma tarefa encontrada.
                        </div>
                        <template x-for="task in results" :key="task.id">
                            <button @click="openTask(task)" class="w-full px-4 py-2 text-left hover:bg-ink-700 focus:bg-ink-700 outline-none transition group">
                                <div class="text-sm font-medium text-slate-200 group-hover:text-brand-400" x-text="task.title"></div>
                                <div class="text-xs text-slate-500 mt-0.5" x-text="task.client + ' · ' + task.project"></div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3" x-data="{ menu:false }">
                <div class="relative">
                    <button @click="menu=!menu" class="flex items-center gap-2 text-sm text-slate-300 hover:text-slate-200 focus:outline-none">
                        <x-avatar :user="auth()->user()" />
                        <span class="hidden sm:inline">{{ auth()->user()->name }} ▾</span>
                    </button>
                    <div x-show="menu" x-cloak @click.outside="menu=false"
                         class="absolute right-0 mt-2 w-44 rounded-lg border border-ink-600 bg-ink-700 py-1 text-sm shadow-xl z-40">
                        <div class="px-3 py-2 text-xs text-slate-400">{{ auth()->user()->roleLabel() }}</div>
                        <a href="{{ route('profile.edit') }}" class="block px-3 py-2 hover:bg-ink-600">Meu Perfil</a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('team.index') }}" class="block px-3 py-2 hover:bg-ink-600">Time</a>
                            <a href="{{ route('financial.index') }}" class="block px-3 py-2 hover:bg-ink-600">Financeiro</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="block w-full px-3 py-2 text-left text-rose-400 hover:bg-ink-600">Sair</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <x-flash />

        {{-- pb-28: a hotbar ocupa os 96px de baixo (bottom-6 + ~72px de altura);
             com pb-24 o conteúdo encostava exatamente na barra, sem folga. --}}
        {{-- overflow-x-hidden: o main só rola para baixo. O que precisa de
             rolagem lateral (quadro, tabelas) tem a própria caixa. --}}
        <main class="flex-1 overflow-y-auto overflow-x-hidden pb-28 lg:pb-0">
            {{ $slot }}
        </main>
    </div>
</div>

{{-- Modal/Slideover Global de Tarefas --}}
<div x-data="taskModal" @open-task-modal.window="open($event.detail)">
    <div x-show="isOpen" x-cloak class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
    <div x-show="isOpen" x-cloak
         class="fixed inset-y-0 right-0 z-50 w-full sm:max-w-2xl bg-ink-900 shadow-2xl ring-1 ring-white/10 transition-transform"
         x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">
        <div class="h-full" x-html="content"></div>
    </div>
</div>

{{-- Player de vídeo global. z-[60] para ficar acima do slideover (z-50), que é
     de onde o vídeo é aberto. O streaming aceita Range, então dá para arrastar
     a linha do tempo sem baixar o arquivo inteiro. --}}
<div x-data="{
        open: false, url: '', name: '', download: '',
        abrir(dados) {
            this.url = dados.url;
            this.name = dados.name || 'Vídeo';
            this.download = dados.download || dados.url;
            this.open = true;
            this.$nextTick(() => {
                const p = this.$refs.player;
                if (p) p.play().catch(() => {});
            });
        },
        fechar() {
            const p = this.$refs.player;
            if (p) { p.pause(); p.removeAttribute('src'); p.load(); }
            this.open = false;
            this.url = '';
        }
     }"
     @open-video.window="abrir($event.detail)"
     @keydown.escape.window="if (open) fechar()">
    <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4"
         @click.self="fechar()">
        <div class="w-full max-w-4xl">
            <div class="mb-2 flex items-center justify-between gap-4">
                <span class="truncate text-sm font-medium text-slate-200" x-text="name"></span>
                <div class="flex shrink-0 items-center gap-4">
                    <a :href="download" download class="text-xs font-medium text-slate-300 hover:text-slate-200">Baixar</a>
                    <button type="button" @click="fechar()" class="rounded p-1 text-slate-400 hover:bg-white/10 hover:text-slate-200" title="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            <video x-ref="player" :src="url" controls playsinline preload="metadata"
                   class="max-h-[80vh] w-full rounded-lg bg-black shadow-2xl"></video>
        </div>
    </div>
</div>

<script>
    // Sistema inteligente e à prova de falhas de persistência e restauração de scroll
    (function() {
        const scrollKey = 'strasa_scroll_pos_' + window.location.pathname;

        window.saveScrollPositions = function() {
            try {
                const pos = {};
                const kanban = document.getElementById('kanban-scroll-container') || document.querySelector('.overflow-x-auto');
                if (kanban) {
                    pos.kanbanLeft = kanban.scrollLeft;
                    pos.kanbanTop = kanban.scrollTop;
                }
                const main = document.querySelector('main');
                if (main) {
                    pos.mainTop = main.scrollTop;
                    pos.mainLeft = main.scrollLeft;
                }
                pos.winX = window.scrollX || window.pageXOffset || 0;
                pos.winY = window.scrollY || window.pageYOffset || 0;

                localStorage.setItem(scrollKey, JSON.stringify(pos));
            } catch (e) {}
        };

        window.restoreScrollPositions = function() {
            try {
                const raw = localStorage.getItem(scrollKey);
                if (!raw) return;
                const pos = JSON.parse(raw);

                const kanban = document.getElementById('kanban-scroll-container') || document.querySelector('.overflow-x-auto');
                if (kanban && pos.kanbanLeft !== undefined && pos.kanbanLeft > 0) {
                    kanban.scrollLeft = pos.kanbanLeft;
                }
                const main = document.querySelector('main');
                if (main && pos.mainTop !== undefined && pos.mainTop > 0) {
                    main.scrollTop = pos.mainTop;
                }
                if (pos.winX || pos.winY) {
                    window.scrollTo(pos.winX || 0, pos.winY || 0);
                }
            } catch (e) {}
        };

        // Salva continuamente enquanto o usuário rola horizontalmente ou verticalmente (captura profunda)
        document.addEventListener('scroll', function(e) {
            window.saveScrollPositions();
        }, { capture: true, passive: true });

        // Salva antes de descarregar a página
        window.addEventListener('beforeunload', window.saveScrollPositions);

        // Restauração com retentativas inteligentes para aguardar o cálculo do layout das colunas
        function applyScrollWithRetry(attempts) {
            window.restoreScrollPositions();
            if (attempts > 0) {
                requestAnimationFrame(() => {
                    setTimeout(() => applyScrollWithRetry(attempts - 1), 60);
                });
            }
        }

        // Executa restauração nos momentos críticos de montagem do DOM
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            applyScrollWithRetry(12);
        } else {
            document.addEventListener('DOMContentLoaded', () => applyScrollWithRetry(12));
        }
        window.addEventListener('load', () => applyScrollWithRetry(12));
        document.addEventListener('alpine:init', () => applyScrollWithRetry(12));
    })();

    /**
     * Seletor de flags do slideover da tarefa.
     *
     * Fica aqui, e nao no proprio slideover, porque ele e carregado por AJAX:
     * um <script> injetado por innerHTML nao executa, e a pilha de scripts
     * do Blade nao chega ao layout. Os dados vem pelo x-data do partial.
     */
    window.seletorDeFlags = function (todas, cores) {
        return {
            abrir: false,
            nome: '',
            cores: cores,
            corEscolhida: cores[0],
            todas: todas,

            /** As que ainda nao estao neste card. */
            get disponiveis() {
                var jaNoCard = Array.prototype.slice
                    .call(document.querySelectorAll('#tags-container input[name="tags[]"]'))
                    .map(function (i) { return i.value.split('|')[0].toLowerCase(); });

                return this.todas.filter(function (f) {
                    return jaNoCard.indexOf(f.name.toLowerCase()) === -1;
                });
            },

            /**
             * Exclui a flag da empresa inteira. Some de todos os cards, entao
             * confirma antes — e tira tambem o selo deste card, se estiver
             * aplicada, para a tela nao mostrar algo que ja nao existe.
             */
            excluir: function (flag) {
                if (!flag.id) { return; }

                if (!confirm('Excluir a flag "' + flag.name + '"? Ela sai de todos os cards que a usam.')) {
                    return;
                }

                var self = this;

                fetch('{{ url('/tags') }}/' + flag.id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (res) { return res.json().catch(function () { return {}; })
                    .then(function (d) { return { ok: res.ok, dados: d }; }); })
                .then(function (r) {
                    if (!r.ok) {
                        alert(r.dados.message || 'Não foi possível excluir a flag.');
                        return;
                    }

                    self.todas = self.todas.filter(function (f) { return f.id !== flag.id; });

                    // Se estava aplicada neste card, tira o selo tambem.
                    document.querySelectorAll('#tags-container input[name="tags[]"]').forEach(function (campo) {
                        if (campo.value.split('|')[0].toLowerCase() === flag.name.toLowerCase()) {
                            campo.parentElement.remove();
                        }
                    });
                })
                .catch(function () { alert('Falha de conexão ao excluir a flag.'); });
            },

            criar: function () {
                var nome = this.nome.trim();
                if (!nome) { return; }

                // Se ja existe uma flag com esse nome, reaproveita a cor dela
                // em vez de criar uma duplicata com cor diferente.
                var igual = this.todas.find(function (f) {
                    return f.name.toLowerCase() === nome.toLowerCase();
                });

                if (igual) {
                    this.adicionarSelo(igual.name, igual.color);
                } else {
                    this.adicionarSelo(nome, this.corEscolhida);
                    this.todas.push({ id: null, name: nome, color: this.corEscolhida, sugestao: false, padrao: false });
                }

                this.nome = '';
                this.abrir = false;
            },

            aplicar: function (flag) {
                this.adicionarSelo(flag.name, flag.color);
                this.abrir = false;
            },

            /**
             * O formulario da tarefa salva as flags como "nome|cor" em inputs
             * escondidos; o autosave dispara no evento de change.
             */
            adicionarSelo: function (nome, cor) {
                var container = document.getElementById('tags-container');
                if (!container) { return; }

                var div = document.createElement('div');
                div.className = 'inline-flex items-center gap-1 rounded bg-ink-800 px-2 py-1 text-xs text-slate-300 border border-ink-600';

                var ponto = document.createElement('span');
                ponto.className = 'w-2 h-2 rounded-full';
                ponto.style.background = cor;

                var campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = 'tags[]';
                campo.value = nome + '|' + cor;

                var remover = document.createElement('button');
                remover.type = 'button';
                remover.className = 'text-slate-500 hover:text-rose-400 ml-1';
                remover.innerHTML = '&times;';
                remover.addEventListener('click', function () {
                    div.remove();
                    avisarFormulario();
                });

                div.appendChild(ponto);
                div.appendChild(document.createTextNode(' ' + nome + ' '));
                div.appendChild(campo);
                div.appendChild(remover);
                container.appendChild(div);

                avisarFormulario();
            },
        };

        function avisarFormulario() {
            var form = document.getElementById('task-auto-form');
            if (form) { form.dispatchEvent(new Event('change', { bubbles: true })); }
        }
    };

    /**
     * Trata sessao expirada em qualquer chamada AJAX.
     *
     * O token CSRF fica gravado no HTML no momento em que a pagina carrega.
     * Se a aba ficar aberta ate a sessao expirar, esse token vira invalido e
     * o servidor responde 419 — que sem tratamento aparece como um erro
     * tecnico incompreensivel no meio de um arrastar de card.
     *
     * @return {boolean} true se tratou (quem chamou deve parar por aqui)
     */
    window.sessaoExpirou = function (status) {
        if (status !== 419) { return false; }

        alert('Sua sessão expirou por inatividade.' + String.fromCharCode(10, 10) + 'A página vai recarregar para você entrar de novo. Nada do que já estava salvo se perde.');
        window.location.reload();

        return true;
    };

    /**
     * Recarrega so as regioes marcadas com data-recarga-suave="nome", buscando
     * a propria pagina de novo e trocando o miolo de cada uma. E o que as
     * listas (Demandas, Minhas Tarefas) usam ao fechar um card: a tela
     * atualiza sem piscar e sem perder o que estava recolhido ou rolado.
     * Rejeita quando a pagina nao tem regiao marcada, para quem chama cair
     * no recarregamento normal.
     */
    window.recargaSuave = function () {
        var regioes = document.querySelectorAll('[data-recarga-suave]');
        if (!regioes.length) {
            return Promise.reject(new Error('pagina sem regiao de recarga suave'));
        }

        return fetch(window.location.href, { credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
            .then(function (res) {
                if (res.status === 419 || res.redirected && /\/login/.test(res.url)) { throw new Error('__sessao__'); }
                if (!res.ok) { throw new Error('HTTP ' + res.status); }
                return res.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var trocadas = 0;

                regioes.forEach(function (atual) {
                    var nome = atual.getAttribute('data-recarga-suave');
                    var nova = doc.querySelector('[data-recarga-suave="' + nome + '"]');
                    if (!nova) { return; }
                    // O Alpine observa o DOM e inicializa o que entrar aqui.
                    atual.innerHTML = nova.innerHTML;
                    trocadas++;
                });

                if (!trocadas) { throw new Error('regiao nao veio na resposta'); }
            });
    };

    /**
     * Bloco que abre/recolhe e lembra o estado no navegador — o nome de cada
     * pessoa em Demandas, por exemplo. A chave e por pagina, entao recolher
     * alguem em Demandas nao mexe em outra tela.
     */
    window.grupoRecolhivel = function (chave) {
        var armazem = 'strasa:recolhido:' + window.location.pathname + ':' + chave;

        return {
            aberto: true,
            init() {
                try { this.aberto = localStorage.getItem(armazem) !== '1'; } catch (e) { /* sem storage: fica aberto */ }
            },
            alternar() {
                this.aberto = !this.aberto;
                try {
                    if (this.aberto) { localStorage.removeItem(armazem); } else { localStorage.setItem(armazem, '1'); }
                } catch (e) { /* sem storage: so nao lembra */ }
            }
        };
    };

    // Atualiza um card do quadro no lugar, sem recarregar a pagina. Se a
    // tarefa mudou de coluna vai para a certa; se foi excluida, some.
    // Qualquer falha cai no recarregamento antigo, que sempre funcionou.
    window.atualizarCardDoQuadro = function (taskId) {
        var recarregar = function () {
            if (window.saveScrollPositions) window.saveScrollPositions();
            window.location.reload();
        };

        fetch(`{{ url('/tasks') }}/${taskId}/card`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (res.status === 404) { return { removido: true }; }
            if (!res.ok) { throw new Error('HTTP ' + res.status); }
            return res.json();
        })
        .then(data => {
            var atual = document.querySelector(`.task-card[data-id="${taskId}"]`);

            if (data.removido) {
                if (atual) { atual.remove(); }
                window.dispatchEvent(new CustomEvent('kanban-recontar'));
                return;
            }

            var lista = document.querySelector(`.kanban-list[data-column-id="${data.column_id}"]`);
            var molde = document.createElement('template');
            molde.innerHTML = (data.html || '').trim();
            var novo = molde.content.firstElementChild;

            if (!novo || !lista) { throw new Error('card ou coluna nao encontrados'); }

            if (atual && atual.parentElement === lista) {
                atual.replaceWith(novo);
            } else {
                if (atual) { atual.remove(); }
                lista.appendChild(novo);
            }

            window.dispatchEvent(new CustomEvent('kanban-recontar'));
        })
        .catch(recarregar);
    };

    /**
     * Caixinha que pede um texto (motivo da rejeição, nome de pasta...).
     * Devolve uma Promise com o texto, ou null se a pessoa cancelar.
     * Ctrl+Enter confirma (Enter, com linhaUnica), Esc cancela. Montada na
     * hora, sem Alpine: o quadro chama de dentro do SortableJS.
     *
     * Opções: titulo, ajuda, exemplo, botao, erro, valor (texto inicial),
     * linhaUnica (campo de uma linha), maximo (caracteres), tom ('brand'
     * para o botão azul; o padrão é vermelho, de rejeição).
     */
    window.pedirMotivo = function (opcoes) {
        opcoes = opcoes || {};

        return new Promise(function (resolver) {
            var classeCampo = 'mt-3 block w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-brand-500 focus:outline-none focus:ring-0';
            var fundo = document.createElement('div');
            fundo.className = 'fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4';
            fundo.innerHTML =
                '<div class="w-full max-w-md rounded-xl border border-ink-600 bg-ink-800 p-5 shadow-2xl" role="dialog" aria-modal="true">'
                + '<h3 data-titulo class="text-base font-semibold text-slate-100"></h3>'
                + '<p data-ajuda class="mt-1 text-sm text-slate-400"></p>'
                + (opcoes.linhaUnica
                    ? '<input data-campo type="text" autocomplete="off" class="' + classeCampo + '">'
                    : '<textarea data-campo rows="3" class="resize-y ' + classeCampo + '"></textarea>')
                + '<p data-erro class="mt-1 hidden text-xs text-rose-300"></p>'
                + '<div class="mt-4 flex flex-wrap justify-end gap-2">'
                + '<button type="button" data-cancelar class="rounded-lg border border-ink-600 px-3.5 py-2 text-sm font-medium text-slate-300 hover:bg-ink-700">Cancelar</button>'
                + '<button type="button" data-confirmar class="rounded-lg px-3.5 py-2 text-sm font-semibold text-white '
                + (opcoes.tom === 'brand' ? 'bg-brand-600 hover:bg-brand-500' : 'bg-rose-600 hover:bg-rose-500') + '"></button>'
                + '</div></div>';

            var pegar = function (s) { return fundo.querySelector('[data-' + s + ']'); };
            pegar('titulo').textContent = opcoes.titulo || 'Motivo';
            pegar('ajuda').textContent = opcoes.ajuda || '';
            if (!opcoes.ajuda) { pegar('ajuda').classList.add('hidden'); }
            pegar('campo').placeholder = opcoes.exemplo || '';
            pegar('campo').value = opcoes.valor || '';
            if (opcoes.maximo) { pegar('campo').maxLength = opcoes.maximo; }
            pegar('erro').textContent = opcoes.erro || 'Escreva o motivo para continuar.';
            pegar('confirmar').textContent = opcoes.botao || 'Confirmar';

            var fechar = function (valor) {
                document.removeEventListener('keydown', teclas, true);
                fundo.remove();
                resolver(valor);
            };
            var confirmar = function () {
                var texto = pegar('campo').value.trim();
                if (!texto) {
                    pegar('erro').classList.remove('hidden');
                    pegar('campo').focus();
                    return;
                }
                fechar(texto);
            };
            var teclas = function (e) {
                if (e.key === 'Escape') { e.preventDefault(); fechar(null); }
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey || opcoes.linhaUnica)) { e.preventDefault(); confirmar(); }
            };

            pegar('confirmar').addEventListener('click', confirmar);
            pegar('cancelar').addEventListener('click', function () { fechar(null); });
            fundo.addEventListener('mousedown', function (e) { if (e.target === fundo) { fechar(null); } });
            document.addEventListener('keydown', teclas, true);

            document.body.appendChild(fundo);
            setTimeout(function () { pegar('campo').focus(); pegar('campo').select(); }, 30);
        });
    };

    /**
     * Move o card de coluna no servidor. Se a coluna de destino exige motivo
     * (ex.: "Rejeitado"), pergunta e repete o pedido com ele. Antes o servidor
     * pedia o motivo e a tela só mostrava um erro, sem ter onde escrever.
     *
     * Resolve com a resposta; rejeita com Error('__cancelado__') se a pessoa
     * desistir na caixinha, Error('__sessao__') se a sessão expirou, ou com a
     * mensagem do servidor.
     */
    window.moverCardNoServidor = function (taskId, corpo) {
        var enviar = function (dados) {
            return fetch(`{{ url('/tasks') }}/${taskId}/move`, {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(dados)
            }).then(function (res) {
                if (res.status === 419) { throw new Error('__sessao__'); }

                return res.json().catch(function () { return {}; }).then(function (j) {
                    if (res.status === 422 && j.requires_reason) {
                        return window.pedirMotivo({
                            titulo: 'Por que está indo para "' + (j.column_name || 'Rejeitado') + '"?',
                            ajuda: 'O motivo fica registrado no card e no histórico.',
                            exemplo: 'Ex.: o cliente pediu ajuste e não vamos seguir com esta postagem.',
                            botao: 'Mover card'
                        }).then(function (motivo) {
                            if (motivo === null) { throw new Error('__cancelado__'); }
                            return enviar(Object.assign({}, dados, { rejection_reason: motivo }));
                        });
                    }
                    if (!res.ok || j.ok === false) {
                        throw new Error(j.message || ('HTTP ' + res.status));
                    }
                    return j;
                });
            });
        };

        return enviar(corpo);
    };

    /**
     * Checklist do card, no estilo do Trello: linha limpa com prazo e foto do
     * responsavel a direita, texto que vira editor ao clicar, compositor que
     * so pergunta o nome. Mora aqui pelo mesmo motivo do seletorDeFlags — o
     * slideover chega por AJAX. Os dados vem no x-data do partial.
     */
    window.checklistDoCard = function (cfg) {
        return {
            items: cfg.items || [],
            membros: cfg.membros || [],
            urlCriar: cfg.urlCriar,
            urlItens: cfg.urlItens,
            hoje: cfg.hoje,

            // Compositor de item novo.
            compondo: false,
            novoTexto: '',
            salvando: false,

            // Edicao do texto de um item existente.
            editandoId: null,
            textoEdicao: '',

            // Um popover aberto por vez: { tipo: 'membro' | 'data' | 'menu', id }.
            popover: null,
            filtroMembro: '',

            cabecalhos() {
                return {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                };
            },

            get progress() {
                if (!this.items.length) { return 0; }
                return Math.round(this.items.filter(i => i.is_completed).length / this.items.length * 100);
            },

            get amanha() {
                var p = this.hoje.split('-').map(Number);
                var d = new Date(p[0], p[1] - 1, p[2] + 1);
                return this.iso(d);
            },

            iso(d) {
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            },

            /* ---------------- Compositor ---------------- */

            abrirCompositor() {
                this.compondo = true;
                this.editandoId = null;
                this.fecharPopover();
                this.focar('[data-novo-item]');
            },

            fecharCompositor() {
                this.compondo = false;
                this.novoTexto = '';
            },

            async adicionar() {
                var texto = this.novoTexto.trim();
                if (!texto || this.salvando) { return; }

                this.salvando = true;
                try {
                    var res = await fetch(this.urlCriar, {
                        method: 'POST',
                        headers: this.cabecalhos(),
                        body: JSON.stringify({ description: texto })
                    });
                    var data = await res.json().catch(() => ({}));

                    if (res.ok && data.item) {
                        this.items.push(data.item);
                        this.novoTexto = '';
                        // Fica aberto para o proximo item, como no Trello.
                        this.focar('[data-novo-item]');
                    } else if (!window.sessaoExpirou(res.status)) {
                        alert(data.message || 'Não foi possível adicionar o item.');
                    }
                } catch (e) {
                    alert('Erro de conexão ao adicionar o item.');
                } finally {
                    this.salvando = false;
                }
            },

            /* ---------------- Edicao do texto ---------------- */

            editar(item) {
                this.editandoId = item.id;
                this.textoEdicao = item.description;
                this.compondo = false;
                this.fecharPopover();
                this.focar('[data-edicao="' + item.id + '"]', true);
            },

            salvarEdicao(item) {
                var texto = this.textoEdicao.trim();
                if (texto && texto !== item.description) {
                    item.description = texto;
                    this.salvar(item, { description: texto });
                }
                this.editandoId = null;
            },

            cancelarEdicao() {
                this.editandoId = null;
            },

            /* ---------------- Persistencia ---------------- */

            async salvar(item, campos) {
                try {
                    var res = await fetch(this.urlItens + '/' + item.id, {
                        method: 'PATCH',
                        headers: this.cabecalhos(),
                        body: JSON.stringify(campos)
                    });
                    var data = await res.json().catch(() => ({}));

                    if (res.ok && data.item) {
                        Object.assign(item, data.item);
                    } else if (!window.sessaoExpirou(res.status)) {
                        alert(data.message || 'Não foi possível salvar o item.');
                    }
                } catch (e) {
                    alert('Erro de conexão ao salvar o item.');
                }
            },

            alternar(item) {
                item.is_completed = !item.is_completed;
                this.salvar(item, { is_completed: item.is_completed });
            },

            excluir(item) {
                if (!confirm('Excluir este item do checklist?')) { return; }
                this.fecharPopover();
                this.items = this.items.filter(i => i.id !== item.id);
                fetch(this.urlItens + '/' + item.id, { method: 'DELETE', headers: this.cabecalhos() });
            },

            /* ---------------- Responsavel ---------------- */

            membro(id) {
                if (!id) { return null; }
                return this.membros.find(m => m.id === Number(id)) || null;
            },

            get membrosFiltrados() {
                var f = this.filtroMembro.trim().toLowerCase();
                return f ? this.membros.filter(m => m.name.toLowerCase().indexOf(f) !== -1) : this.membros;
            },

            definirMembro(item, id) {
                var novo = id ? Number(id) : null;
                if (novo !== item.assignee_id) {
                    item.assignee_id = novo;
                    this.salvar(item, { assignee_id: novo });
                }
                this.fecharPopover();
            },

            /* ---------------- Prazo ---------------- */

            definirData(item, valor) {
                var nova = valor || null;
                if (nova !== item.due_date) {
                    item.due_date = nova;
                    this.salvar(item, { due_date: nova });
                }
                this.fecharPopover();
            },

            /** "2026-09-18" -> "18 de set." */
            dataCurta(isoData) {
                if (!isoData) { return ''; }
                var p = String(isoData).slice(0, 10).split('-').map(Number);
                return new Date(p[0], p[1] - 1, p[2]).toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' });
            },

            atrasado(item) {
                return !!item.due_date && !item.is_completed && item.due_date < this.hoje;
            },

            paraHoje(item) {
                return !!item.due_date && !item.is_completed && item.due_date === this.hoje;
            },

            /* ---------------- Popovers ---------------- */

            abrirPopover(tipo, item) {
                if (this.popoverAberto(tipo, item)) {
                    this.popover = null;
                    return;
                }
                this.popover = { tipo: tipo, id: item.id };
                this.filtroMembro = '';
                this.focar('[data-foco-popover]');
            },

            fecharPopover() {
                this.popover = null;
            },

            popoverAberto(tipo, item) {
                return !!this.popover && this.popover.tipo === tipo && this.popover.id === item.id;
            },

            // Fecha ao clicar fora, exceto se o clique foi em outro botao que
            // abre popover — senao o novo abriria e fecharia no mesmo clique.
            cliqueFora(e) {
                if (e.target.closest('[data-abre-popover]')) { return; }
                this.fecharPopover();
            },

            focar(seletor, fimDoTexto) {
                this.$nextTick(() => {
                    var el = this.$root.querySelector(seletor);
                    if (!el) { return; }
                    el.focus();
                    if (fimDoTexto && el.setSelectionRange) {
                        var n = el.value.length;
                        el.setSelectionRange(n, n);
                    }
                    if (el.type === 'date' && typeof el.showPicker === 'function') {
                        try { el.showPicker(); } catch (e) { /* sem gesto do usuario: fica so o campo */ }
                    }
                });
            }
        };
    };

    /**
     * Campo da legenda do post. Mora aqui, e nao no slideover, porque o
     * partial chega por AJAX. O texto e salvo pelo auto-save do formulario
     * (taskForm.save), entao aqui so ficam o contador e o copiar.
     */
    window.legendaDoPost = function (inicial) {
        return {
            texto: inicial || '',
            copiado: false,

            get caracteres() {
                return this.texto.length;
            },

            /**
             * Delega para o auto-save do card (taskForm.save), que ja tem
             * debounce e mostra "salvo". O form escuta 'change', e o change
             * nativo do textarea so dispara ao sair do campo — por isso o
             * evento vai na mao a cada tecla.
             */
            save() {
                var form = document.getElementById('task-auto-form');
                if (form) { form.dispatchEvent(new Event('change', { bubbles: true })); }
            },

            copiar() {
                var texto = this.texto;
                var marcar = () => {
                    this.copiado = true;
                    setTimeout(() => { this.copiado = false; }, 1600);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(texto).then(marcar).catch(() => this.copiarAntigo(texto, marcar));
                    return;
                }
                this.copiarAntigo(texto, marcar);
            },

            /** Sem clipboard API (http, navegador antigo): seleciona e copia. */
            copiarAntigo(texto, aoCopiar) {
                var campo = this.$refs.campo;
                if (!campo) { return; }
                campo.select();
                try { document.execCommand('copy'); aoCopiar(); } catch (e) { /* o texto fica selecionado */ }
                campo.setSelectionRange(campo.value.length, campo.value.length);
            }
        };
    };

    /**
     * Arrastar para ordenar os anexos de uma pasta (ou os soltos) no card.
     * A ordem define o carrossel no card e no painel do cliente. Chamado pelo
     * x-init de cada grade do slideover (que chega por AJAX, por isso mora aqui).
     */
    window.ordenarAnexos = function (lista, url) {
        if (!lista || typeof Sortable === 'undefined') { return; }
        if (lista._ordenacao) { lista._ordenacao.destroy(); }

        var itens = function () {
            return Array.prototype.slice.call(lista.querySelectorAll(':scope > [data-attachment-id]'));
        };

        lista._ordenacao = new Sortable(lista, {
            animation: 150,
            draggable: '[data-attachment-id]',
            ghostClass: 'opacity-40',
            // Fallback em vez do drag nativo: clicar na miniatura continua
            // abrindo a imagem/vídeo; só vira arraste depois de mover 4px.
            forceFallback: true,
            fallbackTolerance: 4,
            delay: 150,
            delayOnTouchOnly: true,
            filter: '[data-nao-arrasta]',
            preventOnFilter: false,

            onStart: function () {
                lista._ordemAntes = itens();
            },

            onEnd: function (evt) {
                if (evt.oldIndex === evt.newIndex) { return; }

                window.numerarCarrossel();
                if (window.taskViewer && typeof window.taskViewer.update === 'function') {
                    window.taskViewer.update();
                }

                var desfazer = function () {
                    (lista._ordemAntes || []).forEach(function (el) { lista.appendChild(el); });
                    window.numerarCarrossel();
                    if (window.taskViewer && typeof window.taskViewer.update === 'function') {
                        window.taskViewer.update();
                    }
                };

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ids: itens().map(function (el) { return parseInt(el.dataset.attachmentId, 10); }) })
                })
                .then(function (res) {
                    if (window.sessaoExpirou(res.status)) { desfazer(); return; }
                    if (!res.ok) { throw new Error('HTTP ' + res.status); }
                })
                .catch(function () {
                    desfazer();
                    alert('Não foi possível salvar a nova ordem. Tente de novo.');
                });
            }
        });
    };

    /** Renumera as bolinhas 1, 2, 3... na ordem em que aparecem no card. */
    window.numerarCarrossel = function () {
        var n = 0;
        document.querySelectorAll('#attachments-container [data-ordem-carrossel]').forEach(function (el) {
            el.textContent = ++n;
        });
    };

    window.completeTask = function(btn, taskId, e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        var iconeOriginal = btn.innerHTML;

        btn.innerHTML = `<svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`;

        fetch(`{{ url('/tasks') }}/${taskId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json().catch(() => ({}))
            .then(data => ({ ok: res.ok, status: res.status, data })))
        .then(({ ok, status, data }) => {
            if (window.sessaoExpirou(status)) { return; }

            if (!ok) {
                btn.innerHTML = iconeOriginal;
                alert(data.message || 'Erro ao concluir a tarefa. Tente recarregar a página.');
                return;
            }

            var card = btn.closest('.task-card');
            if (!card) { return; }

            // Move o card para a coluna que o SERVIDOR escolheu. Antes daqui o
            // card ia para a ultima coluna do DOM, que podia nao ser a mesma —
            // e a tela ficava mentindo ate o proximo F5.
            var destino = data.column_id
                ? document.querySelector(`.kanban-list[data-column-id="${data.column_id}"]`)
                : null;

            if (destino) {
                destino.appendChild(card);
                window.dispatchEvent(new CustomEvent('kanban-recontar'));
                return;
            }

            // Nenhuma coluna marcada como concluido: a automacao esta desligada
            // e a tarefa fica onde esta. So risca o titulo e poe o selo.
            if (!data.column_id) {
                var titulo = card.querySelector('[data-task-title]');
                if (titulo) {
                    titulo.style.textDecoration = 'line-through';
                    titulo.style.opacity = '0.6';
                }
                if (!card.querySelector('[data-selo-concluido]')) {
                    var selo = document.createElement('span');
                    selo.setAttribute('data-selo-concluido', '');
                    selo.className = 'mt-2 inline-block rounded bg-emerald-900/40 px-1.5 py-0.5 text-[10px] text-emerald-400';
                    selo.textContent = '● Publicado';
                    (card.querySelector('[data-card-body]') || card).appendChild(selo);
                }
                return;
            }

            // Fora do quadro (Minhas Tarefas, por exemplo): atualiza a lista
            // sem piscar quando a pagina permite; senao recarrega.
            var recarregar = function () {
                if (window.saveScrollPositions) { window.saveScrollPositions(); }
                window.location.reload();
            };
            if (document.querySelector('[data-recarga-suave]')) {
                setTimeout(function () { window.recargaSuave().catch(recarregar); }, 300);
                return;
            }
            setTimeout(recarregar, 300);
        })
        .catch(() => {
            btn.innerHTML = iconeOriginal;
            alert('Erro de conexão ao concluir a tarefa.');
        });
    };

    document.addEventListener('alpine:init', () => {
        Alpine.data('globalSearch', () => ({
            query: '',
            results: [],
            open: false,
            loading: false,
            
            search() {
                if (this.query.length < 2) {
                    this.results = [];
                    return;
                }
                this.loading = true;
                fetch(`{{ url('/search/tasks') }}?q=${encodeURIComponent(this.query)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    this.results = data;
                    this.loading = false;
                    this.open = true;
                }).catch(() => this.loading = false);
            },
            
            close() {
                this.open = false;
            },
            
            openTask(task) {
                this.close();
                this.$dispatch('open-task-modal', task.edit_url);
            }
        }));

        if(!Alpine.data('taskModal')) {
            Alpine.data('taskModal', () => ({
                isOpen: false,
                content: '',
                taskId: null,
                open(url) {
                    this.isOpen = true;
                    // Guarda de qual tarefa e o slideover para, ao fechar, atualizar
                    // so o card dela no quadro.
                    var m = String(url).match(/\/tasks\/(\d+)/);
                    this.taskId = m ? m[1] : null;
                    this.content = '<div class="flex h-full items-center justify-center text-slate-500">Carregando...</div>';
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(res => res.text()).then(html => this.content = html);
                },
                closeModal() {
                    this.isOpen = false;
                    var id = this.taskId;
                    this.taskId = null;

                    // No quadro, troca so o card que estava aberto: sem piscar.
                    if (id && document.getElementById('kanban-board')) {
                        window.atualizarCardDoQuadro(id);
                        return;
                    }

                    var recarregar = function () {
                        if (window.saveScrollPositions) window.saveScrollPositions();
                        window.location.reload();
                    };

                    // Listas (Demandas, Minhas Tarefas...): atualiza so o miolo
                    // marcado na pagina. Sem regiao marcada, recarrega como antes.
                    if (document.querySelector('[data-recarga-suave]')) {
                        window.recargaSuave().catch(function (err) {
                            if (err && err.message === '__sessao__') { window.sessaoExpirou(419); return; }
                            recarregar();
                        });
                        return;
                    }

                    setTimeout(recarregar, 150);
                }
            }));
        }

        if(!Alpine.data('taskForm')) {
            Alpine.data('taskForm', (actionUrl, method, isExistingTask) => ({
                action: actionUrl, method: method, isExisting: isExistingTask,
                saving: false, saved: false, uploading: false, saveTimeout: null,
                save() {
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = setTimeout(() => {
                        this.saving = true; this.saved = false;
                        const form = document.getElementById('task-auto-form');
                        const formData = new FormData(form);
                        formData.append('_method', this.method);
                        fetch(this.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: formData })
                        .then(async res => {
                            if (!res.ok) {
                                const errData = await res.json().catch(() => ({}));
                                throw new Error(errData.message || 'Erro ao salvar');
                            }
                            return res.json();
                        })
                        .then(data => {
                            this.saving = false; this.saved = true;
                            setTimeout(() => this.saved = false, 2000);
                            if (!this.isExisting && data.task && data.task.id) {
                                this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${data.task.id}/edit`);
                            }
                        }).catch((err) => { 
                            this.saving = false; 
                            alert('Erro: ' + err.message);
                        });
                    }, 500);
                },
                updateTitle(event) { this.save(); },
                uploadAttachment(event) {
                    const form = event.target;
                    this.uploading = true;

                    // Overlay com barra de progresso real (o upload pro bucket
                    // pode demorar; antes a tela só ficava parada).
                    window.uploadOverlay.sendForm(form, { json: true })
                        .then(() => {
                            this.uploading = false;
                            window.uploadOverlay.hide();
                            this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                        })
                        .catch(() => {
                            this.uploading = false;
                        });
                },
                deleteAttachment(url, element) {
                    if(!confirm('Excluir este anexo?')) return;
                    fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }, body: new URLSearchParams({ '_method': 'DELETE' }) })
                    .then(res => res.json()).then(data => element.remove());
                },
                createFolder(event) {
                    const form = event.target; const formData = new FormData(form);
                    fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: formData })
                    .then(res => res.json()).then(data => {
                        this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                    });
                },
                renameFolder(url, event, name) {
                    fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }, body: new URLSearchParams({ '_method': 'PATCH', 'name': name }) })
                    .then(res => res.json()).then(data => {
                        this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                    });
                },
                deleteFolder(url) {
                    if(!confirm('Tem certeza que deseja excluir esta pasta? Os arquivos nela não serão apagados, ficarão soltos.')) return;
                    fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }, body: new URLSearchParams({ '_method': 'DELETE' }) })
                    .then(res => res.json()).then(data => {
                        this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                    });
                },
                postComment(event) {
                    const form = event.target; const formData = new FormData(form);
                    fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: formData })
                    .then(res => res.json()).then(data => {
                        this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                    });
                },
                updateComment(url, event, body) {
                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ body })
                    });
                },
                deleteComment(url, element) {
                    if(!confirm('Excluir este comentário?')) return;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: new URLSearchParams({ '_method': 'DELETE' })
                    }).then(() => element.remove());
                },
                /** Envia a peça para o painel de aprovação do cliente. */
                enviarAprovacao(url) {
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json().then(data => ({ ok: res.ok, data })))
                    .then(({ ok, data }) => {
                        if (!ok) {
                            alert(data.message || 'Não foi possível enviar para aprovação.');
                            this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                            return;
                        }
                        this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                    })
                    .catch(() => alert('Falha de conexão ao enviar para aprovação.'));
                },

                /** Retira do painel uma peça ainda não respondida. */
                cancelarAprovacao(url) {
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: new URLSearchParams({ '_method': 'DELETE' })
                    })
                    .then(() => this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`))
                    .catch(() => alert('Falha de conexão ao retirar do painel.'));
                },

                /**
                 * Desiste da peça já respondida (ex.: ajuste pedido que não
                 * vamos fazer): some do painel do cliente, o card fica.
                 */
                excluirDoPainel(url, cliente) {
                    var quebra = String.fromCharCode(10, 10);
                    if (!confirm('Excluir esta peça do painel de aprovação de ' + cliente + '?' + quebra
                        + 'O cliente deixa de ver a peça. O card continua aqui no quadro para a equipe.')) {
                        return;
                    }
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => {
                        if (window.sessaoExpirou(res.status)) { return; }
                        return res.json().then(data => {
                            if (!res.ok) { alert(data.message || 'Não foi possível excluir do painel.'); }
                            // O selo do card no quadro some ao fechar o card
                            // (o fechamento já troca só aquele card).
                            this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                        });
                    })
                    .catch(() => alert('Falha de conexão ao excluir do painel.'));
                },

                /**
                 * Marca (ou desmarca) um comentário nosso como visível no
                 * painel do cliente. O x-data do comentário guarda o estado
                 * em `visivel`, então basta alterná-lo com a resposta.
                 */
                toggleCommentVisibility(url, elemento) {
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.ok) return;

                        const escopo = Alpine.$data(elemento);
                        if (escopo) { escopo.visivel = data.visible_to_client; }

                        // Recarrega o slideover para o selo aparecer/sumir.
                        this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
                    });
                },
                completeTaskAndClose(taskId) {
                    const btn = this.$event.currentTarget;
                    window.completeTask(btn, taskId, this.$event);
                    setTimeout(() => this.closeModal(), 300);
                }
            }));
        }
    });
</script>
@stack('scripts')
<script>
    /**
     * Barra lateral de clientes: cada usuário arrasta para ordenar, cria
     * pastas e arrasta clientes para dentro/fora delas. Tudo fica guardado
     * no próprio usuário (ver App\Support\BarraLateral).
     *
     * O arraste usa o modo "fallback" do SortableJS (eventos de mouse, não o
     * arrastar nativo do navegador): funciona igual com listas aninhadas
     * (pastas) e com os botões/links de dentro de cada item.
     */
    window.barraLateral = {
        url: '{{ route('profile.sidebar-order') }}',

        lista: function () { return document.getElementById('sidebar-client-list'); },

        opcoes: function () {
            var self = this;
            return {
                animation: 150,
                forceFallback: true,
                fallbackOnBody: true,
                fallbackTolerance: 4,
                swapThreshold: 0.65,
                delay: 150,
                delayOnTouchOnly: true,
                ghostClass: 'opacity-40',
                onStart: function () {
                    window.__barraArrastou = true;
                    // Pastas fechadas abrem durante o arraste, para dar onde soltar.
                    self.lista().classList.add('arrastando');
                },
                onEnd: function () {
                    // O clique que segue o soltar não pode abrir/fechar nada.
                    setTimeout(function () { window.__barraArrastou = false; }, 0);
                    self.lista().classList.remove('arrastando');
                    self.contar();
                    self.salvar();
                },
            };
        },

        ativar: function () {
            var lista = this.lista();
            if (!lista || typeof Sortable === 'undefined') { return; }

            Sortable.create(lista, Object.assign(this.opcoes(), {
                group: { name: 'barra-clientes', pull: true, put: true },
                draggable: '.sidebar-client-item, .sidebar-folder',
            }));

            var self = this;
            lista.querySelectorAll('[data-lista-pasta]').forEach(function (l) { self.ativarPasta(l); });
        },

        ativarPasta: function (listaDaPasta) {
            if (typeof Sortable === 'undefined' || Sortable.get(listaDaPasta)) { return; }

            Sortable.create(listaDaPasta, Object.assign(this.opcoes(), {
                // Pasta dentro de pasta, não.
                group: { name: 'barra-clientes', pull: true, put: function (para, de, arrastado) { return !arrastado.classList.contains('sidebar-folder'); } },
                draggable: '.sidebar-client-item',
            }));
        },

        /** A barra como ela está na tela, no formato guardado no usuário. */
        layout: function () {
            var itens = [];
            Array.prototype.forEach.call(this.lista().children, function (el) {
                if (el.classList.contains('sidebar-folder')) {
                    var dados = window.Alpine ? Alpine.$data(el) : null;
                    itens.push({
                        pasta: el.dataset.pasta,
                        nome: el.dataset.nome,
                        aberta: !!(dados && dados.aberta),
                        clientes: Array.prototype.map.call(
                            el.querySelectorAll('[data-lista-pasta] > .sidebar-client-item'),
                            function (c) { return parseInt(c.dataset.clientId, 10); }
                        ),
                    });
                } else if (el.classList.contains('sidebar-client-item')) {
                    itens.push(parseInt(el.dataset.clientId, 10));
                }
            });
            return itens;
        },

        salvar: function () {
            var self = this;
            clearTimeout(this._espera);
            this._espera = setTimeout(function () {
                fetch(self.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ layout: self.layout() })
                }).then(function (res) { window.sessaoExpirou(res.status); });
            }, 350);
        },

        contar: function () {
            this.lista().querySelectorAll('.sidebar-folder').forEach(function (pasta) {
                var conta = pasta.querySelector('[data-conta-pasta]');
                if (conta) { conta.textContent = pasta.querySelectorAll('[data-lista-pasta] > .sidebar-client-item').length; }
            });
        },

        novaPasta: function () {
            var self = this;
            window.pedirMotivo({
                titulo: 'Nova pasta',
                ajuda: 'Agrupe clientes na barra lateral: depois é só arrastar os clientes para dentro dela. Só você vê suas pastas.',
                exemplo: 'Ex.: Varejo, Saúde, Clientes novos',
                botao: 'Criar pasta',
                erro: 'Dê um nome para a pasta.',
                linhaUnica: true,
                maximo: 60,
                tom: 'brand'
            }).then(function (nome) {
                if (!nome) { return; }
                var modelo = document.getElementById('modelo-pasta-barra');
                var pasta = modelo.content.querySelector('.sidebar-folder').cloneNode(true);
                pasta.dataset.pasta = 'p' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
                pasta.dataset.nome = nome;
                pasta.querySelector('[data-nome-pasta]').textContent = nome;
                self.lista().insertBefore(pasta, self.lista().firstElementChild);
                self.ativarPasta(pasta.querySelector('[data-lista-pasta]'));
                self.salvar();
            });
        },

        renomear: function (pasta) {
            var self = this;
            window.pedirMotivo({
                titulo: 'Renomear pasta',
                botao: 'Salvar',
                erro: 'Dê um nome para a pasta.',
                valor: pasta.dataset.nome,
                linhaUnica: true,
                maximo: 60,
                tom: 'brand'
            }).then(function (nome) {
                if (!nome) { return; }
                pasta.dataset.nome = nome;
                pasta.querySelector('[data-nome-pasta]').textContent = nome;
                self.salvar();
            });
        },

        /** Exclui só a pasta: os clientes dela voltam para a lista, no lugar dela. */
        excluir: function (pasta) {
            var quebra = String.fromCharCode(10, 10);
            if (!confirm('Excluir a pasta "' + pasta.dataset.nome + '"?' + quebra + 'Os clientes dela continuam na barra, fora da pasta.')) { return; }
            var lista = this.lista();
            pasta.querySelectorAll('[data-lista-pasta] > .sidebar-client-item').forEach(function (cliente) {
                lista.insertBefore(cliente, pasta);
            });
            pasta.remove();
            this.salvar();
        },
    };

    document.addEventListener('DOMContentLoaded', function () { window.barraLateral.ativar(); });
</script>

{{-- ============================ MOBILE BOTTOM NAVIGATION ============================ --}}
{{-- z-30 (e não z-50): os modais/slideover/menu de contexto do card usam z-40/z-50
     e a hotbar, por vir depois no HTML, ficava por cima deles no celular. --}}
<nav class="lg:hidden fixed bottom-6 inset-x-4 z-30">
    <div class="flex items-center justify-around px-2 py-2 backdrop-blur-2xl bg-[#1c1c1e]/90 border border-white/10 rounded-full shadow-2xl">
        @php
            $navItems = [
                ['route' => 'dashboard', 'label' => 'Início', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>'],
                ['route' => 'my-tasks', 'label' => 'Tarefas', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>'],
                ['route' => 'clients.index', 'label' => 'Clientes', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>'],
            ];
            if(auth()->user()->isAdmin()) {
                $navItems[] = ['route' => 'team.index', 'label' => 'Equipe', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'];
            }
        @endphp

        @foreach($navItems as $item)
            @php
                $isActive = request()->routeIs(\Illuminate\Support\Str::before($item['route'], '.').'.*') || request()->routeIs($item['route']);
            @endphp
            <a href="{{ route($item['route']) }}" class="flex flex-col items-center justify-center w-16 h-14 transition-colors {{ $isActive ? 'bg-[#323234] rounded-full' : '' }}">
                <svg class="w-6 h-6 mb-0.5 {{ $isActive ? 'text-brand-400' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                <span class="text-[10px] font-medium {{ $isActive ? 'text-brand-400' : 'text-slate-400' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>

<x-upload-progress />

</body>
</html>
