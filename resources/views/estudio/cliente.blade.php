<x-app-layout title="Estúdio · {{ $client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                @if($client->logo_url)
                    <img src="{{ $client->logo_url }}" class="h-8 w-8 rounded object-cover ring-1 ring-white/10" alt="">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded text-xs font-bold text-slate-200" style="background: {{ $client->color ?? '#475569' }}">{{ \Illuminate\Support\Str::substr($client->name, 0, 1) }}</span>
                @endif
                <div>
                    <h1 class="text-base font-semibold text-slate-200">{{ $client->name }}</h1>
                    <p class="text-[11.5px] text-slate-400">Estúdio de vídeo</p>
                </div>
            </div>
            <a href="{{ route('estudio.index') }}" class="text-sm text-slate-400 hover:text-slate-200">Todas as edições →</a>
        </div>
    </x-slot>

    <div class="space-y-6 p-4 sm:p-6" x-data="{ escolhido: null, titulo: '' }">
        @include('estudio.partials.motor', ['motor' => $motor])

        <div class="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]">
            {{-- Identidade: o celular mostra como a marca aparece no vídeo --}}
            <aside class="space-y-4">
                <div class="rounded-2xl border border-ink-600 bg-ink-800/70 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-200">Identidade de vídeo</h2>
                        @can('update', $client)
                            <a href="{{ route('clients.estudio.perfil', $client) }}" class="text-[12px] font-medium text-brand-400 hover:text-brand-300">Editar</a>
                        @endcan
                    </div>
                    @include('estudio.partials.celular', ['perfil' => $perfil, 'client' => $client])
                    <div class="mt-4 flex items-center gap-1.5">
                        @foreach(['cor_primaria', 'cor_destaque', 'cor_texto', 'cor_fundo'] as $c)
                            <span class="h-6 flex-1 rounded-md ring-1 ring-white/10" style="background: {{ $perfil->$c }}" title="{{ $perfil->$c }}"></span>
                        @endforeach
                    </div>
                    <p class="mt-2 text-[11.5px] text-slate-500">{{ $perfil->fonteTituloEfetiva() }} · legenda {{ \App\Models\VideoProfile::ESTILOS_LEGENDA[$perfil->legenda_estilo]['nome'] ?? $perfil->legenda_estilo }} · {{ \App\Models\VideoProfile::GRADES[$perfil->cor_grade] ?? '' }}</p>
                    @unless($perfil->exists)
                        <p class="mt-3 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-[12px] text-amber-300">Ainda com o padrão. Defina cores, fonte e chamada do cliente antes da primeira edição.</p>
                    @endunless
                </div>

                <div class="rounded-2xl border border-ink-600 bg-ink-800/70 p-4">
                    <h2 class="text-sm font-semibold text-slate-200">Caderno de aprendizados</h2>
                    <p class="mt-1 text-[12px] text-slate-500">O que a equipe já corrigiu nas edições deste cliente. O Claude segue os aprovados em toda edição nova.</p>
                    <ul class="mt-3 space-y-1.5">
                        @forelse($aprendizados->take(6) as $a)
                            <li class="flex gap-2 text-[12.5px] {{ $a->status === 'aplicado' ? 'text-slate-300' : 'text-amber-300/90' }}">
                                <span>{{ $a->status === 'aplicado' ? '✓' : '•' }}</span><span>{{ $a->texto }}</span>
                            </li>
                        @empty
                            <li class="text-[12px] italic text-slate-500">Nada ainda. Os pedidos de ajuste viram aprendizados.</li>
                        @endforelse
                    </ul>
                    @if($aprendizados->count() > 6 || $aprendizados->where('status', 'pendente')->isNotEmpty())
                        <a href="{{ route('clients.estudio.perfil', $client) }}#aprendizados" class="mt-2 inline-block text-[12px] text-brand-400 hover:text-brand-300">
                            Ver todos{{ $aprendizados->where('status', 'pendente')->count() ? ' ('.$aprendizados->where('status', 'pendente')->count().' para revisar)' : '' }} →
                        </a>
                    @endif
                </div>
            </aside>

            <main class="min-w-0 space-y-6">
                @can('update', $client)
                    <section>
                        <h2 class="mb-3 text-sm font-semibold text-slate-200">Nova edição — escolha o estilo</h2>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($estilos as $chave => $e)
                                <button type="button" @click="escolhido = escolhido === '{{ $chave }}' ? null : '{{ $chave }}'"
                                        :class="escolhido === '{{ $chave }}' ? 'ring-2 bg-ink-800' : 'hover:border-ink-500 hover:bg-ink-800'"
                                        style="--tw-ring-color: {{ $e['cor'] }}"
                                        class="group relative overflow-hidden rounded-2xl border border-ink-600 bg-ink-800/60 p-4 text-left transition">
                                    <span class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full opacity-[0.12] blur-xl transition group-hover:opacity-25" style="background: {{ $e['cor'] }}"></span>
                                    <span class="grid h-10 w-10 place-items-center rounded-xl ring-1 ring-white/10" style="color: {{ $e['cor'] }}; background: {{ $e['cor'] }}1a">
                                        @include('estudio.partials.estilo-icone', ['icone' => $e['icone'], 'classe' => 'h-5 w-5'])
                                    </span>
                                    <p class="mt-3 text-sm font-semibold text-slate-100">{{ $e['nome'] }}</p>
                                    <p class="mt-1 text-[12.5px] leading-snug text-slate-400">{{ $e['resumo'] }}</p>
                                    <p class="mt-2 text-[11.5px] italic text-slate-500">Ex.: {{ $e['exemplo'] }}</p>
                                    <p class="mt-2 text-[11px] text-slate-500">{{ $e['duracao'][0] }}–{{ $e['duracao'][1] }} s · {{ $e['fala'] ? 'com fala e legenda' : 'com música' }}</p>
                                </button>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('estudio.edicoes.store', $client) }}" x-show="escolhido" x-cloak x-transition
                              class="mt-3 flex flex-col gap-3 rounded-2xl border border-ink-600 bg-ink-900/60 p-4 sm:flex-row sm:items-end">
                            @csrf
                            <input type="hidden" name="estilo" :value="escolhido">
                            <div class="flex-1">
                                <label class="mb-1 block text-xs font-medium text-slate-400">Nome da edição (opcional)</label>
                                <input name="titulo" x-model="titulo" maxlength="160" placeholder="Ex.: Tour da barbearia — novembro"
                                       class="w-full rounded-lg border border-ink-600 bg-ink-800 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                            </div>
                            <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                                Começar e enviar os arquivos →
                            </button>
                        </form>
                    </section>
                @endcan

                <section>
                    <h2 class="mb-3 text-sm font-semibold text-slate-200">Edições deste cliente</h2>
                    @forelse($edicoes as $edicao)
                        <div class="mb-2">@include('estudio.partials.cartao-edicao', ['edicao' => $edicao])</div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-ink-600 bg-ink-900/40 p-8 text-center text-sm text-slate-400">
                            Nenhuma edição ainda. Escolha um estilo acima, envie os vídeos e o Claude edita com a cara do cliente.
                        </div>
                    @endforelse
                </section>
            </main>
        </div>
    </div>
</x-app-layout>
