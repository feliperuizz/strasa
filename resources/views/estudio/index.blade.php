<x-app-layout title="Estúdio de vídeo">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-slate-200">Estúdio de vídeo</h1>
                <p class="text-[11.5px] text-slate-400">Você envia o material, o Claude edita com a identidade de cada cliente.</p>
            </div>
            <a href="{{ route('estudio.biblioteca') }}" class="rounded-lg border border-ink-600 px-3 py-1.5 text-sm text-slate-300 hover:bg-ink-700">♫ Biblioteca de músicas</a>
        </div>
    </x-slot>

    <div class="space-y-6 p-4 sm:p-6">
        @include('estudio.partials.motor', ['motor' => $motor])

        @if($diagnostico && ($diagnostico['erros'] || $diagnostico['avisos']) && auth()->user()->isManager())
            <details class="rounded-2xl border {{ $diagnostico['erros'] ? 'border-rose-500/30 bg-rose-500/5' : 'border-amber-500/30 bg-amber-500/5' }} p-4" @if($diagnostico['erros']) open @endif>
                <summary class="cursor-pointer select-none text-sm font-semibold {{ $diagnostico['erros'] ? 'text-rose-300' : 'text-amber-300' }}">
                    Diagnóstico do servidor: {{ $diagnostico['erros'] }} problema(s), {{ $diagnostico['avisos'] }} aviso(s)
                    <span class="font-normal text-slate-500">· {{ \Carbon\Carbon::parse($diagnostico['quando'])->diffForHumans() }}</span>
                </summary>
                <ul class="mt-3 space-y-2">
                    @foreach($diagnostico['itens'] as [$grupo, $nome, $st, $detalhe, $resolver])
                        @continue($st === 'ok')
                        <li class="text-[12.5px]">
                            <span class="{{ $st === 'erro' ? 'text-rose-400' : 'text-amber-400' }}">{{ $st === 'erro' ? '✗' : '!' }}</span>
                            <span class="text-slate-300">{{ $grupo }} · {{ $nome }}:</span> <span class="text-slate-400">{{ $detalhe }}</span>
                            @if($resolver)<p class="ml-4 text-[12px] text-slate-500">→ {{ $resolver }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif

        <section>
            <h2 class="mb-3 text-sm font-semibold text-slate-200">Começar uma edição para…</h2>
            <div class="flex flex-wrap gap-2">
                @forelse($clientes as $c)
                    <a href="{{ route('clients.estudio', $c) }}" class="inline-flex items-center gap-2 rounded-full border border-ink-600 bg-ink-800/70 py-1 pl-1 pr-3 text-[13px] text-slate-300 transition hover:border-brand-500/60 hover:text-white">
                        @if($c->logo_url)
                            <img src="{{ $c->logo_url }}" class="h-6 w-6 rounded-full object-cover" alt="">
                        @else
                            <span class="grid h-6 w-6 place-items-center rounded-full text-[10px] font-bold text-white" style="background: {{ $c->color ?? '#475569' }}">{{ \Illuminate\Support\Str::substr($c->name, 0, 1) }}</span>
                        @endif
                        {{ $c->name }}
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Cadastre um cliente primeiro.</p>
                @endforelse
            </div>
        </section>

        @if($rascunhos->isNotEmpty())
            <section>
                <h2 class="mb-2 text-sm font-semibold text-slate-200">Rascunhos <span class="font-normal text-slate-500">(arquivos enviados, ainda não editados)</span></h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($rascunhos as $r)
                        <a href="{{ route('estudio.edicoes.show', $r) }}" class="rounded-lg border border-dashed border-ink-500 px-3 py-1.5 text-[12.5px] text-slate-400 hover:text-slate-200">{{ $r->client?->name }} · {{ $r->titulo }}</a>
                    @endforeach
                </div>
            </section>
        @endif

        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-200">Edições recentes</h2>
                @if($emFila)<span class="text-[12px] text-violet-300">{{ $emFila }} em processamento</span>@endif
            </div>
            <div class="grid gap-2 xl:grid-cols-2">
                @forelse($edicoes as $edicao)
                    @include('estudio.partials.cartao-edicao', ['edicao' => $edicao, 'mostrarCliente' => true])
                @empty
                    <div class="rounded-2xl border border-dashed border-ink-600 bg-ink-900/40 p-10 text-center xl:col-span-2">
                        <p class="text-sm text-slate-300">Nenhuma edição ainda.</p>
                        <p class="mt-1 text-[12.5px] text-slate-500">Escolha um cliente acima → um estilo → envie os vídeos. O resto é com o Claude.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
