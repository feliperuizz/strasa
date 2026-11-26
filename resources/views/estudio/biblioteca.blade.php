<x-app-layout title="Biblioteca · Estúdio">
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-2">
            <a href="{{ route('estudio.index') }}" class="text-sm text-slate-400 hover:text-slate-200">Estúdio</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-slate-200">Biblioteca: músicas e logos</h1>
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

        {{-- Logos dos componentes de motion --}}
        <section class="rounded-2xl border border-ink-600 bg-ink-800/70 p-5 lg:col-span-2" x-data="{ aberto: false }">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-slate-200">Logos ({{ $logos->count() + count($logosWeb) }})</h2>
                    <p class="text-[12px] text-slate-500">Quando o vídeo cita um app ou marca, o estúdio busca o logo sozinho: logos livres no servidor → App Store (ícone oficial) → Wikipédia → site. Os achados ficam aqui. Errou ou não achou? Envie o certo com o mesmo nome — o enviado sempre vale primeiro.</p>
                </div>
                <button type="button" @click="aberto = ! aberto" class="rounded-lg bg-brand-600 px-3 py-1.5 text-[12.5px] font-semibold text-white hover:bg-brand-500">Enviar logo</button>
            </div>

            <form x-show="aberto" x-cloak method="POST" action="{{ route('estudio.biblioteca.logo') }}" enctype="multipart/form-data" class="mt-4 grid gap-3 rounded-xl border border-ink-600 bg-ink-900/50 p-4 sm:grid-cols-[1fr_1fr_auto_auto_auto] sm:items-end">
                @csrf
                <label class="block"><span class="mb-1 block text-[11px] text-slate-400">Nome da marca (como o Claude vai escrever)</span>
                    <input name="nome" required maxlength="40" placeholder="Ex.: Magazine Luiza" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200"></label>
                <label class="block"><span class="mb-1 block text-[11px] text-slate-400">Imagem (PNG/JPG/WebP)</span>
                    <input type="file" name="arquivo" required accept="image/png,image/jpeg,image/webp" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-sm text-slate-400 file:mr-3 file:rounded file:border-0 file:bg-ink-700 file:px-2 file:py-1 file:text-xs file:text-slate-200"></label>
                <label class="block"><span class="mb-1 block text-[11px] text-slate-400">Formato</span>
                    <select name="forma" class="rounded-lg border border-ink-600 bg-ink-900 px-2 py-2 text-sm text-slate-200"><option value="app">Ícone de app (quadrado)</option><option value="logo">Logo (sobre placa clara)</option></select></label>
                <label class="block"><span class="mb-1 block text-[11px] text-slate-400">Para</span>
                    <select name="client_id" class="rounded-lg border border-ink-600 bg-ink-900 px-2 py-2 text-sm text-slate-200"><option value="">Todos os clientes</option>@foreach($clientes as $c)<option value="{{ $c->id }}">Só {{ $c->name }}</option>@endforeach</select></label>
                <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">Salvar</button>
            </form>

            <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-8">
                @foreach($logos as $l)
                    <div class="group relative rounded-xl border border-brand-500/40 bg-ink-900/60 p-2.5 text-center">
                        <img src="{{ route('estudio.biblioteca.ouvir', $l) }}" alt="" class="mx-auto h-16 w-16 rounded-2xl object-contain {{ $l->humor === 'logo' ? 'bg-zinc-100 p-1.5' : '' }}">
                        <p class="mt-1.5 truncate text-[12px] font-medium text-slate-200">{{ $l->nome }}</p>
                        <p class="truncate text-[10.5px] text-brand-300">enviado{{ $l->client ? ' · só '.$l->client->name : '' }}</p>
                        <form method="POST" action="{{ route('estudio.biblioteca.destroy', $l) }}" onsubmit="return confirm('Remover este logo?')" class="absolute right-1.5 top-1 hidden group-hover:block">
                            @csrf @method('DELETE')
                            <button class="text-[11px] text-slate-500 hover:text-rose-400" title="Remover">✕</button>
                        </form>
                    </div>
                @endforeach
                @foreach($logosWeb as $w)
                    <div class="group relative rounded-xl border border-ink-600 bg-ink-900/60 p-2.5 text-center">
                        <img src="{{ route('estudio.logos.web', $w['chave']) }}" alt="" class="mx-auto h-16 w-16 rounded-2xl object-contain {{ $w['forma'] === 'logo' ? 'bg-zinc-100 p-1.5' : '' }}">
                        <p class="mt-1.5 truncate text-[12px] font-medium text-slate-200" title="{{ $w['titulo'] }}">{{ $w['nome'] }}</p>
                        <p class="truncate text-[10.5px] text-slate-500" title="{{ $w['titulo'] }}">{{ ['appstore' => 'App Store', 'site' => 'site', 'favicon' => 'site'][$w['fonte']] ?? (str_starts_with($w['fonte'], 'wikipedia') ? 'Wikipédia' : $w['fonte']) }}</p>
                        <form method="POST" action="{{ route('estudio.logos.esquecer', $w['chave']) }}" onsubmit="return confirm('Esquecer este logo? Na próxima vez o estúdio busca de novo (ou use Enviar logo com o mesmo nome).')" class="absolute right-1.5 top-1 hidden group-hover:block">
                            @csrf @method('DELETE')
                            <button class="text-[11px] text-slate-500 hover:text-rose-400" title="Esquecer (buscar de novo)">✕</button>
                        </form>
                    </div>
                @endforeach
                @if($logos->isEmpty() && ! $logosWeb)
                    <p class="col-span-full py-4 text-center text-[12.5px] text-slate-500">Nenhum logo ainda. Eles aparecem aqui conforme os vídeos citam apps e marcas.</p>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
