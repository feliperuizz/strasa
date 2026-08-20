<x-app-layout title="Calendário · {{ $project ? $project->name : $client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if($client->logo_url)
                    <img src="{{ $client->logo_url }}" alt="{{ $client->name }}" class="h-6 w-6 rounded-md object-cover ring-1 ring-ink-600">
                @else
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-ink-600 text-[10px] font-bold text-white ring-1 ring-ink-600" style="background-color: {{ $client->color ?? '#64748b' }}">{{ substr($client->name, 0, 2) }}</span>
                @endif
                <a href="{{ route('clients.show', $client) }}" class="text-sm font-semibold text-slate-200 hover:text-slate-200">{{ $client->name }}</a>
                <span class="text-slate-500">/</span>
                <div class="relative" x-data="{ menu: false }">
                    <button @click="menu = !menu" class="text-sm text-slate-400 hover:text-slate-200 flex items-center gap-1">
                        {{ $project ? $project->name : 'Todos os projetos' }} ▾
                    </button>
                    <div x-show="menu" @click.outside="menu=false" x-cloak class="absolute left-0 mt-2 w-48 rounded-lg border border-ink-600 bg-ink-700 py-1 shadow-xl z-10">
                        <a href="{{ route('clients.calendar', $client) }}" class="block px-3 py-1.5 text-sm {{ !$project ? 'bg-ink-600 text-slate-200' : 'text-slate-300 hover:bg-ink-600' }}">Todos os projetos</a>
                        @foreach($projects as $p)
                            <a href="{{ route('projects.calendar', $p) }}" class="block px-3 py-1.5 text-sm {{ $project && $project->id === $p->id ? 'bg-ink-600 text-slate-200' : 'text-slate-300 hover:bg-ink-600' }}">{{ $p->name }}</a>
                        @endforeach
                    </div>
                </div>
                <span class="text-slate-500">/</span>
                <span class="text-sm text-slate-400">Calendário</span>
            </div>
            @if($project)
                <a href="{{ route('projects.board', $project) }}" class="rounded bg-ink-700 px-3 py-1.5 text-sm font-medium text-slate-300 hover:bg-ink-600 hover:text-slate-200">Ver Quadro</a>
            @endif
        </div>
    </x-slot>

    <div class="flex h-full flex-col p-4 sm:p-6" x-data="calendar()">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <button @click="prevMonth" class="rounded border border-ink-600 bg-ink-800 px-2 py-1 text-slate-400 hover:text-slate-200">◀</button>
                <div class="w-32 text-center text-sm font-semibold text-slate-200" x-text="monthName + ' ' + year"></div>
                <button @click="nextMonth" class="rounded border border-ink-600 bg-ink-800 px-2 py-1 text-slate-400 hover:text-slate-200">▶</button>
                <button @click="today" class="ml-2 rounded border border-ink-600 bg-ink-800 px-3 py-1 text-sm text-slate-400 hover:text-slate-200">Hoje</button>
            </div>
            
            <div class="flex items-center gap-2">
            </div>
        </div>

        <div class="flex-1 rounded-xl border border-ink-600/80 bg-ink-800/85 backdrop-blur-md flex flex-col overflow-hidden shadow-sm">
            {{-- Dias da semana --}}
            <div class="grid grid-cols-7 border-b border-ink-600 bg-ink-900/50">
                <template x-for="day in ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']">
                    <div class="px-2 py-2 text-center text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="day"></div>
                </template>
            </div>
            
            {{-- Grade --}}
            <div class="grid grid-cols-7 flex-1 auto-rows-fr bg-ink-600 gap-px border-ink-600 border-t">
                <template x-for="(day, index) in days" :key="index">
                    <div class="bg-ink-800 p-1 relative min-h-[100px] flex flex-col" :class="{'opacity-50': !day.isCurrentMonth}">
                        <div class="text-right mb-1">
                            <span class="inline-block h-6 w-6 text-center leading-6 rounded-full text-xs" 
                                  :class="{'bg-brand-500 text-white font-bold': day.isToday, 'text-slate-400': !day.isToday}" 
                                  x-text="day.date.getDate()"></span>
                        </div>
                        <div class="flex-1 space-y-1 overflow-y-auto custom-scrollbar">
                            <template x-for="event in day.events" :key="event.id">
                                <a :href="event.url" class="block rounded px-1.5 py-1 text-[11px] leading-tight hover:brightness-125 transition" 
                                   :style="'background: ' + event.color + '22; border-left: 2px solid ' + event.color">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="font-semibold" :style="'color: ' + event.color" x-text="event.is_published ? '✓ ' + event.project : event.project"></span>
                                    </div>
                                    <div class="text-slate-300 truncate" x-text="event.title"></div>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function calendar() {
            return {
                currentDate: new Date(),
                year: null,
                month: null,
                monthName: '',
                days: [],
                events: [],
                
                init() {
                    this.updateMonth();
                },
                
                updateMonth() {
                    this.year = this.currentDate.getFullYear();
                    this.month = this.currentDate.getMonth();
                    const monthNames = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                    this.monthName = monthNames[this.month];
                    this.loadEvents();
                },
                
                prevMonth() {
                    this.currentDate = new Date(this.year, this.month - 1, 1);
                    this.updateMonth();
                },
                
                nextMonth() {
                    this.currentDate = new Date(this.year, this.month + 1, 1);
                    this.updateMonth();
                },
                
                today() {
                    this.currentDate = new Date();
                    this.updateMonth();
                },
                
                async loadEvents() {
                    const firstDay = new Date(this.year, this.month, 1);
                    const lastDay = new Date(this.year, this.month + 1, 0);
                    
                    const fromStr = `${this.year}-${String(this.month + 1).padStart(2, '0')}-01`;
                    const toStr = `${this.year}-${String(this.month + 1).padStart(2, '0')}-${String(lastDay.getDate()).padStart(2, '0')}`;
                    
                    const projectParam = '{{ $project ? $project->id : '' }}';
                    let url = `{{ route('clients.calendar.events', $client) }}?from=${fromStr}&to=${toStr}`;
                    if(projectParam) url += `&project=${projectParam}`;
                    
                    try {
                        const res = await fetch(url);
                        const data = await res.json();
                        this.events = data.events;
                        this.generateGrid();
                    } catch (e) {
                        console.error('Erro ao buscar eventos', e);
                    }
                },
                
                generateGrid() {
                    const firstDay = new Date(this.year, this.month, 1);
                    const lastDay = new Date(this.year, this.month + 1, 0);
                    const startingDay = firstDay.getDay(); 
                    
                    const days = [];
                    const today = new Date();
                    
                    // Dias do mês anterior
                    const prevMonthLastDay = new Date(this.year, this.month, 0).getDate();
                    for (let i = startingDay - 1; i >= 0; i--) {
                        const d = new Date(this.year, this.month - 1, prevMonthLastDay - i);
                        days.push(this.createDayObject(d, false, today));
                    }
                    
                    // Dias deste mês
                    for (let i = 1; i <= lastDay.getDate(); i++) {
                        const d = new Date(this.year, this.month, i);
                        days.push(this.createDayObject(d, true, today));
                    }
                    
                    // Dias do próximo mês
                    const remainingDays = 42 - days.length; // 6 semanas
                    for (let i = 1; i <= remainingDays; i++) {
                        const d = new Date(this.year, this.month + 1, i);
                        days.push(this.createDayObject(d, false, today));
                    }
                    
                    this.days = days;
                },
                
                createDayObject(date, isCurrentMonth, today) {
                    const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
                    return {
                        date: date,
                        isCurrentMonth: isCurrentMonth,
                        isToday: date.toDateString() === today.toDateString(),
                        events: this.events.filter(e => e.date === dateStr)
                    };
                }
            }
        }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #343b4a; border-radius: 4px; }
    </style>
    @endpush
</x-app-layout>
