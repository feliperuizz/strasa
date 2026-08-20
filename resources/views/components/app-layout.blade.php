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
    <link rel="icon" type="image/png" href="{{ asset('strasafavicon.png') }}">

    {{-- Tailwind via CDN (sem Node em produção). Para build estático, ver README. --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>
    <style>
        /* Oculta os botões originais e barra do viewer, deixando escuro igual Asana */
        .viewer-backdrop { background-color: rgba(30, 30, 30, 0.95); }
        .viewer-button { display: none !important; }
        
        /* Quill Dark/Light Theme Adjustments */
        .ql-toolbar.ql-snow, .ql-container.ql-snow {
            border-color: var(--ink-700, #363638) !important;
        }
        .ql-toolbar.ql-snow {
            background-color: var(--ink-800, #2a2b2d);
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }
        .ql-container.ql-snow {
            background-color: var(--ink-900, #1e1e1e);
            border-bottom-left-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
        .ql-snow .ql-stroke { stroke: var(--text-primary, #e2e8f0) !important; }
        .ql-snow .ql-fill, .ql-snow .ql-stroke.ql-fill { fill: var(--text-primary, #e2e8f0) !important; }
        .ql-snow .ql-picker { color: var(--text-primary, #e2e8f0) !important; }
        .ql-editor.ql-blank::before { color: var(--text-secondary, #94a3b8) !important; }
        .ql-editor { min-height: 120px; font-family: inherit; font-size: 0.875rem; }
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
                            header.className = 'asana-header absolute top-0 left-0 w-full flex items-center justify-between px-6 py-4 text-white z-50 pointer-events-none font-sans';
                            header.innerHTML = `
                                <div class="flex flex-col pointer-events-auto flex-1">
                                    <span class="text-sm font-medium image-title truncate max-w-sm"></span>
                                </div>
                                <div class="flex items-center gap-1 pointer-events-auto bg-[#2a2b2d] rounded-md border border-[#3f4145] p-1 shadow-lg">
                                    <button onclick="window.taskViewer.zoom(-0.1)" class="p-1.5 text-slate-300 hover:text-white hover:bg-[#3f4145] rounded" title="Reduzir"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg></button>
                                    <button onclick="window.taskViewer.zoom(0.1)" class="p-1.5 text-slate-300 hover:text-white hover:bg-[#3f4145] rounded" title="Ampliar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>
                                    <div class="w-px h-4 bg-[#3f4145] mx-1"></div>
                                    <button onclick="window.taskViewer.reset()" class="p-1.5 text-slate-300 hover:text-white hover:bg-[#3f4145] rounded" title="Ajustar à tela"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg></button>
                                </div>
                                <div class="flex items-center justify-end gap-6 pointer-events-auto flex-1">
                                    <a href="" download class="image-download flex items-center gap-2 text-sm font-medium text-slate-300 hover:text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Fazer o download
                                    </a>
                                    <button onclick="window.taskViewer.hide()" class="p-1.5 text-slate-400 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
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
            --ink-900: #f1f5f9;
            --ink-800: #ffffff;
            --ink-700: #e2e8f0;
            --ink-600: #cbd5e1;
            --ink-500: #94a3b8;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-tertiary: #64748b;
        }
        html.dark {
            /* Tema Escuro (Padrão) */
            --ink-900: #1e1e1e;
            --ink-800: #2a2b2d;
            --ink-700: #363638;
            --ink-600: #454545;
            --ink-500: #6b6b6b;
            --text-primary: #e2e8f0;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
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
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                ink: { 
                    900: 'var(--ink-900)', 
                    800: 'var(--ink-800)', 
                    700: 'var(--ink-700)', 
                    600: 'var(--ink-600)', 
                    500: 'var(--ink-500)' 
                },
                slate: {
                    200: 'var(--text-primary)',
                    300: 'var(--text-secondary)',
                    400: 'var(--text-tertiary)',
                    500: 'var(--ink-500)',
                    600: 'var(--ink-600)',
                },
                brand: { 400:'#818cf8', 500:'#6366f1', 600:'#4f46e5' },
            } } }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#111111">
    <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
            <img src="{{ asset('strasalogo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
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
            <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Clientes
            </x-nav-link>
            @if(auth()->user()->isAdmin())
                <x-nav-link :href="route('team.index')" :active="request()->routeIs('team.*')">
                    <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Equipe
                </x-nav-link>
                <x-nav-link :href="route('financial.index')" :active="request()->routeIs('financial.*')">
                    <svg class="w-4 h-4 mr-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Financeiro
                </x-nav-link>
            @endif
        </nav>

        <div class="px-4 pt-4 pb-1 flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Clientes</span>
            @can('create', \App\Models\Client::class)
                <a href="{{ route('clients.create') }}" class="text-slate-500 hover:text-white" title="Novo cliente">＋</a>
            @endcan
        </div>

        <div class="px-2 pb-6 space-y-0.5" id="sidebar-client-list">
            @forelse($sidebarClients as $client)
                <div x-data="{ open: {{ request()->is('clients/'.$client->id.'*') || $client->projects->contains('id', request()->route('project')?->id) ? 'true' : 'false' }} }"
                     data-client-id="{{ $client->id }}"
                     class="sidebar-client-item">
                    <button @click="open = !open"
                            class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-slate-300 hover:bg-ink-700">
                        <span class="text-slate-500" x-text="open ? '▾' : '▸'"></span>
                        @if($client->logo_url)
                            <img src="{{ $client->logo_url }}" class="h-5 w-5 rounded object-cover" alt="{{ $client->name }}">
                        @else
                            <span class="grid h-5 w-5 place-items-center rounded text-[10px] font-bold text-white shadow-sm" style="{{ $client->background_style ?: ('background: ' . ($client->color ?? '#'.substr(md5($client->name),0,6))) }}">
                                {{ \Illuminate\Support\Str::substr($client->name,0,1) }}
                            </span>
                        @endif
                        <span class="truncate flex-1">{{ $client->name }}</span>
                    </button>
                    <div x-show="open" x-cloak class="ml-7 space-y-0.5 border-l border-ink-600 pl-2">
                        @foreach($client->projects as $project)
                            <a href="{{ route('projects.board', $project) }}"
                               class="block truncate rounded px-2 py-1 text-[13px] {{ (int) optional(request()->route('project'))->id === $project->id ? 'bg-ink-600 text-white' : 'text-slate-400 hover:text-white hover:bg-ink-700' }}">
                                {{ $project->name }}
                            </a>
                        @endforeach
                        <a href="{{ route('clients.calendar', $client) }}" class="block rounded px-2 py-1 text-[12px] text-slate-500 hover:text-brand-400">▤ Calendário</a>
                        @can('create', \App\Models\Project::class)
                            <a href="{{ route('projects.create', $client) }}" class="block rounded px-2 py-1 text-[12px] text-slate-500 hover:text-brand-400">＋ Novo projeto</a>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="px-2 py-2 text-xs text-slate-500">Nenhum cliente ainda.</p>
            @endforelse
        </div>
    </aside>

    {{-- ============================ CONTEÚDO ============================ --}}
    <div class="flex min-w-0 flex-1 flex-col relative transition-all duration-300" style="{{ $activeClient?->background_style }}">
        <header class="flex items-center gap-3 border-b border-ink-600/70 bg-ink-800/80 px-4 py-3 backdrop-blur-md sticky top-0 z-20">
            <button @click="sidebar = !sidebar" class="hidden lg:block rounded p-1.5 text-slate-400 hover:bg-ink-700 hover:text-white">☰</button>
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
                    <button @click="menu=!menu" class="flex items-center gap-2 text-sm text-slate-300 hover:text-white focus:outline-none">
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

        <main class="flex-1 overflow-auto pb-24 lg:pb-0">
            {{ $slot }}
        </main>
    </div>
</div>

{{-- Modal/Slideover Global de Tarefas --}}
<div x-data="taskModal" @open-task-modal.window="open($event.detail)">
    <div x-show="isOpen" x-cloak class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
    <div x-show="isOpen" x-cloak
         class="fixed inset-y-0 right-0 z-50 w-full sm:max-w-2xl bg-[#1e1e1e] shadow-2xl ring-1 ring-white/10 transition-transform"
         x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">
        <div class="h-full" x-html="content"></div>
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

    window.completeTask = function(btn, taskId, e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        btn.innerHTML = `<svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`;
        
        fetch(`{{ url('/tasks') }}/${taskId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        }).then(res => {
            if(res.ok) {
                // Se estiver no board kanban, tenta mover o card
                const columns = document.querySelectorAll('.kanban-list');
                const card = btn.closest('.task-card');
                if(columns.length > 0 && card) {
                    columns[columns.length - 1].appendChild(card);
                } else if (card) {
                    if (window.saveScrollPositions) window.saveScrollPositions();
                    setTimeout(() => window.location.reload(), 300);
                }
            } else {
                alert('Erro ao completar tarefa. Tente recarregar a página.');
            }
        }).catch(() => {
            alert('Erro de conexão ao completar tarefa.');
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
                open(url) {
                    this.isOpen = true;
                    this.content = '<div class="flex h-full items-center justify-center text-slate-500">Carregando...</div>';
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(res => res.text()).then(html => this.content = html);
                },
                closeModal() {
                    this.isOpen = false;
                    if (window.saveScrollPositions) window.saveScrollPositions();
                    setTimeout(() => window.location.reload(), 150);
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
                    const form = event.target; const formData = new FormData(form);
                    this.uploading = true;
                    fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: formData })
                    .then(res => res.json()).then(data => {
                        this.uploading = false;
                        this.$dispatch('open-task-modal', `{{ url('/tasks') }}/${this.action.split('/').pop()}/edit`);
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
    document.addEventListener('DOMContentLoaded', () => {
        const sidebarList = document.getElementById('sidebar-client-list');
        if (sidebarList && typeof Sortable !== 'undefined') {
            new Sortable(sidebarList, {
                animation: 150,
                delay: 150,
                delayOnTouchOnly: true,
                onEnd: function (evt) {
                    let order = [];
                    sidebarList.querySelectorAll('.sidebar-client-item').forEach(el => {
                        if(el.dataset.clientId) {
                            order.push(el.dataset.clientId);
                        }
                    });
                    
                    fetch('{{ route('profile.sidebar-order') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ order: order })
                    });
                }
            });
        }
    });
</script>

{{-- ============================ MOBILE BOTTOM NAVIGATION ============================ --}}
<nav class="lg:hidden fixed bottom-6 inset-x-4 z-50">
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

</body>
</html>
