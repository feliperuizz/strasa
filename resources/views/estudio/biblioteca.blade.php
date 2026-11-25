<x-app-layout title="Biblioteca de músicas · Estúdio">
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-2">
            <a href="{{ route('estudio.index') }}" class="text-sm text-slate-400 hover:text-slate-200">Estúdio</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-slate-200">Biblioteca de músicas</h1>
        </div>
    </x-slot>

    <div class="grid gap-6 p-4 sm:p-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <section class="rounded-2xl border border-ink-600 bg-ink-800/70 p-5">
            <h2 class="text-sm font-semibold text-slate-200">Músicas ({{ $musicas->count() }})</h2>
            <ul class="mt-4 divide-y divide-ink-700">
                @forelse($musicas as $m)
                    <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13.5px] font-medium text-slate-200">{{ $m->nome }}
                                @if($m->client)<span class="ml-1 rounded-full bg-violet-500/15 px-2 py-0.5 text-[10.5px] text-violet-300">só {{ $m->client->name }}</span>@endif
                            </p>
                            <p class="text-[11.5px] text-slate-500">{{ $m->duracaoTexto() }}{{ $m->bpm ? ' · '.round($m->bpm).' BPM' : '' }}{{ $m->humor ? ' · '.$m->humor : '' }} · licença: {{ $m->licenca }}</p>
                        </div>
                        <audio controls preload="none" src="{{ route('estudio.biblioteca.ouvir', $m) }}" class="h-8 w-full sm:w-56"></audio>
                        <form method="POST" action="{{ route('estudio.biblioteca.destroy', $m) }}" onsubmit="return confirm('Remover esta música? Edições que a usam ficam sem música.')">
                            @csrf @method('DELETE')
                            <button class="text-[12px] text-slate-500 hover:text-rose-400">Remover</button>
                        </form>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-500">Nenhuma música ainda. Os estilos "Tour do espaço", "Antes e depois" e "Oferta" ficam muito melhores com música.</li>
                @endforelse
            </ul>
        </section>

        <aside class="space-y-4">
            <form method="POST" action="{{ route('estudio.biblioteca.store') }}" enctype="multipart/form-data" class="space-y-3 rounded-2xl border border-ink-600 bg-ink-800/70 p-5">
                @csrf
                <h2 class="text-sm font-semibold text-slate-200">Adicionar música</h2>
                <input type="file" name="arquivo" required accept="audio/*" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-400 file:mr-3 file:rounded file:border-0 file:bg-ink-700 file:px-2 file:py-1 file:text-xs file:text-slate-200">
                <input name="nome" maxlength="120" placeholder="Nome (opcional)" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200">
                <input name="humor" maxlength="120" placeholder="Clima: animada, urbana, calma…" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200">
                <input name="licenca" required maxlength="255" value="{{ old('licenca') }}" placeholder="Fonte e licença (obrigatório)" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200">
                <select name="client_id" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200">
                    <option value="">Para todos os clientes</option>
                    @foreach($clientes as $c)<option value="{{ $c->id }}">Só para {{ $c->name }}</option>@endforeach
                </select>
                <button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">Enviar</button>
                <p class="text-[11.5px] text-slate-500">O andamento (BPM) é medido sozinho: é ele que faz os cortes caírem na batida.</p>
            </form>

            <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-[12.5px] text-amber-200/90">
                <p class="font-semibold text-amber-300">Sobre direitos</p>
                <p class="mt-1">Música famosa sem licença faz o Instagram silenciar o áudio ou derrubar o post — e pode gerar problema para o cliente. Use bancos com licença comercial (YouTube Audio Library, Pixabay Music, Artlist, Epidemic Sound…) e anote a fonte.</p>
                <p class="mt-2">Alternativa segura: postar o vídeo sem música e escolher um áudio da biblioteca do próprio Instagram na hora de publicar.</p>
            </div>
        </aside>
    </div>
</x-app-layout>
