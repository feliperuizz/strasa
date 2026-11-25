<x-app-layout title="Identidade de vídeo · {{ $client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
            <a href="{{ route('clients.estudio', $client) }}" class="text-sm text-slate-400 hover:text-slate-200">{{ $client->name }} · Estúdio</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-slate-200">Identidade de vídeo</h1>
        </div>
    </x-slot>

    @php
        $p = [
            'cor_primaria' => old('cor_primaria', $perfil->cor_primaria), 'cor_destaque' => old('cor_destaque', $perfil->cor_destaque),
            'cor_texto' => old('cor_texto', $perfil->cor_texto), 'cor_fundo' => old('cor_fundo', $perfil->cor_fundo),
            'fonte_titulo' => old('fonte_titulo', $perfil->fonte_titulo), 'fonte_texto' => old('fonte_texto', $perfil->fonte_texto),
            'logo_posicao' => old('logo_posicao', $perfil->logo_posicao), 'logo_tamanho' => (int) old('logo_tamanho', $perfil->logo_tamanho),
            'logo_opacidade' => (int) old('logo_opacidade', $perfil->logo_opacidade), 'legenda_estilo' => old('legenda_estilo', $perfil->legenda_estilo),
            'legenda_posicao' => old('legenda_posicao', $perfil->legenda_posicao), 'legenda_caixa_alta' => (bool) old('legenda_caixa_alta', $perfil->legenda_caixa_alta),
        ];
        $campo = 'w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none';
    @endphp

    <div class="p-4 sm:p-6" x-data="{ p: @js($p) }">
        <form method="POST" action="{{ route('clients.estudio.perfil.update', $client) }}" enctype="multipart/form-data"
              class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_260px]">
            @csrf

            <div class="space-y-5">
                <section class="rounded-2xl border border-ink-600 bg-ink-800/70 p-5">
                    <h2 class="text-sm font-semibold text-slate-200">Cores</h2>
                    <p class="mt-0.5 text-[12px] text-slate-500">Uma cor de destaque só: é ela que acende a palavra-chave, a etiqueta e o preço. Se tudo for destaque, nada se destaca.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-4">
                        @foreach(['cor_destaque' => 'Destaque', 'cor_primaria' => 'Primária (faixas, caixas)', 'cor_texto' => 'Texto', 'cor_fundo' => 'Fundo (telas cheias)'] as $c => $rotulo)
                            <label class="block">
                                <span class="mb-1 block text-xs text-slate-400">{{ $rotulo }}</span>
                                <span class="flex items-center gap-2 rounded-lg border border-ink-600 bg-ink-900 p-1.5">
                                    <input type="color" name="{{ $c }}" x-model="p.{{ $c }}" class="h-8 w-10 cursor-pointer rounded border-0 bg-transparent p-0">
                                    <input type="text" x-model="p.{{ $c }}" maxlength="7" class="w-full bg-transparent font-mono text-[12.5px] uppercase text-slate-200 focus:outline-none">
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-ink-600 bg-ink-800/70 p-5">
                    <h2 class="text-sm font-semibold text-slate-200">Fonte e legenda</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">Fonte dos títulos e da legenda</span>
                            <select name="fonte_titulo" x-model="p.fonte_titulo" class="{{ $campo }}">@foreach(\App\Models\VideoProfile::FONTES as $f)<option>{{ $f }}</option>@endforeach</select>
                        </label>
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">Fonte dos textos menores</span>
                            <select name="fonte_texto" x-model="p.fonte_texto" class="{{ $campo }}">@foreach(\App\Models\VideoProfile::FONTES as $f)<option>{{ $f }}</option>@endforeach</select>
                        </label>
                        <div class="sm:col-span-2">
                            <span class="mb-1 block text-xs text-slate-400">Fonte própria da marca (TTF/OTF) — tem prioridade nos títulos</span>
                            @if($perfil->fonte_nome)
                                <p class="mb-1 text-[12.5px] text-emerald-400">Usando “{{ $perfil->fonte_nome }}”. <label class="ml-2 inline-flex items-center gap-1 text-slate-400"><input type="checkbox" name="remover_fonte" value="1" class="rounded border-ink-500 bg-ink-900"> remover</label></p>
                            @endif
                            <input type="file" name="fonte" accept=".ttf,.otf" class="{{ $campo }} file:mr-3 file:rounded file:border-0 file:bg-ink-700 file:px-2 file:py-1 file:text-xs file:text-slate-200">
                        </div>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-4">
                        @foreach(\App\Models\VideoProfile::ESTILOS_LEGENDA as $k => $e)
                            <label class="cursor-pointer">
                                <input type="radio" name="legenda_estilo" value="{{ $k }}" x-model="p.legenda_estilo" class="peer sr-only">
                                <span class="block h-full rounded-lg border border-ink-600 bg-ink-900 p-2.5 text-[12px] text-slate-400 peer-checked:border-brand-500 peer-checked:bg-brand-500/10">
                                    <span class="block font-semibold text-slate-200">{{ $e['nome'] }}</span>{{ $e['descricao'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-5 text-[13px] text-slate-300">
                        <label class="flex items-center gap-2">Posição
                            <select name="legenda_posicao" x-model="p.legenda_posicao" class="rounded-lg border border-ink-600 bg-ink-900 px-2 py-1 text-[12.5px]"><option value="baixo">Embaixo</option><option value="centro">Centro</option><option value="alto">Em cima</option></select>
                        </label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="legenda_caixa_alta" value="1" x-model="p.legenda_caixa_alta" class="rounded border-ink-500 bg-ink-900 text-brand-600"> CAIXA ALTA</label>
                        <label class="flex items-center gap-2">Cor da imagem
                            <select name="cor_grade" class="rounded-lg border border-ink-600 bg-ink-900 px-2 py-1 text-[12.5px]">@foreach(\App\Models\VideoProfile::GRADES as $k => $g)<option value="{{ $k }}" @selected(old('cor_grade', $perfil->cor_grade) === $k)>{{ $g }}</option>@endforeach</select>
                        </label>
                    </div>
                </section>

                <section class="rounded-2xl border border-ink-600 bg-ink-800/70 p-5">
                    <h2 class="text-sm font-semibold text-slate-200">Logo no vídeo</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <span class="mb-1 block text-xs text-slate-400">Logo para vídeo (PNG com fundo transparente fica melhor)</span>
                            <input type="file" name="logo" accept="image/png,image/webp,image/jpeg" class="{{ $campo }} file:mr-3 file:rounded file:border-0 file:bg-ink-700 file:px-2 file:py-1 file:text-xs file:text-slate-200">
                            <p class="mt-1 text-[11.5px] text-slate-500">{{ $perfil->logo_path ? 'Logo de vídeo enviada.' : ($client->logo_url ? 'Sem logo própria: usa a logo do cliente.' : 'O cliente não tem logo.') }}</p>
                            @if($perfil->logo_path)<label class="mt-1 inline-flex items-center gap-1 text-[12px] text-slate-400"><input type="checkbox" name="remover_logo" value="1" class="rounded border-ink-500 bg-ink-900"> remover</label>@endif
                        </div>
                        <div class="space-y-3">
                            <label class="block"><span class="mb-1 block text-xs text-slate-400">Posição durante o vídeo</span>
                                <select name="logo_posicao" x-model="p.logo_posicao" class="{{ $campo }}">@foreach(\App\Models\VideoProfile::POSICOES_LOGO as $k => $n)<option value="{{ $k }}">{{ $n }}</option>@endforeach</select>
                            </label>
                            <label class="block text-xs text-slate-400">Tamanho: <span x-text="p.logo_tamanho + '% da largura'"></span>
                                <input type="range" name="logo_tamanho" min="6" max="40" x-model.number="p.logo_tamanho" class="mt-1 w-full accent-indigo-500"></label>
                            <label class="block text-xs text-slate-400">Opacidade: <span x-text="p.logo_opacidade + '%'"></span>
                                <input type="range" name="logo_opacidade" min="20" max="100" x-model.number="p.logo_opacidade" class="mt-1 w-full accent-indigo-500"></label>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-ink-600 bg-ink-800/70 p-5">
                    <h2 class="text-sm font-semibold text-slate-200">Assinatura e chamada</h2>
                    <p class="mt-0.5 text-[12px] text-slate-500">Vai para o cartão final e para a legenda do post. O Claude nunca inventa telefone ou endereço: usa só o que estiver aqui.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block sm:col-span-2"><span class="mb-1 block text-xs text-slate-400">Chamada final</span><input name="cta_texto" value="{{ old('cta_texto', $perfil->cta_texto) }}" maxlength="160" placeholder="Agende seu horário pelo link na bio" class="{{ $campo }}"></label>
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">Instagram</span><input name="instagram" value="{{ old('instagram', $perfil->instagram) }}" maxlength="80" placeholder="barbeariadoze" class="{{ $campo }}"></label>
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">WhatsApp</span><input name="whatsapp" value="{{ old('whatsapp', $perfil->whatsapp) }}" maxlength="40" placeholder="(11) 99999-0000" class="{{ $campo }}"></label>
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">Endereço</span><input name="endereco" value="{{ old('endereco', $perfil->endereco) }}" maxlength="160" placeholder="Rua das Flores, 123 — Centro" class="{{ $campo }}"></label>
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">Site</span><input name="site" value="{{ old('site', $perfil->site) }}" maxlength="120" class="{{ $campo }}"></label>
                    </div>
                </section>

                <section class="rounded-2xl border border-ink-600 bg-ink-800/70 p-5">
                    <h2 class="text-sm font-semibold text-slate-200">Jeito do cliente</h2>
                    <div class="mt-4 grid gap-4">
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">Vibe (em poucas palavras)</span><input name="vibe" value="{{ old('vibe', $perfil->vibe) }}" maxlength="160" placeholder="urbana, descontraída, premium sem ser chique" class="{{ $campo }}"></label>
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">Instruções permanentes para o Claude</span>
                            <textarea name="instrucoes" rows="4" maxlength="4000" placeholder="Ex.: Nunca mostrar a fachada antiga. O dono se chama Zé e gosta de ser chamado assim. Evitar gírias. Sempre terminar com o endereço." class="{{ $campo }}">{{ old('instrucoes', $perfil->instrucoes) }}</textarea></label>
                        <label class="block"><span class="mb-1 block text-xs text-slate-400">Música padrão (para os estilos com música)</span>
                            <select name="musica_padrao_id" class="{{ $campo }}"><option value="">Nenhuma</option>@foreach($musicas as $m)<option value="{{ $m->id }}" @selected(old('musica_padrao_id', $perfil->musica_padrao_id) == $m->id)>{{ $m->nome }}</option>@endforeach</select>
                        </label>
                    </div>
                </section>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('clients.estudio', $client) }}" class="rounded-lg px-4 py-2 text-sm text-slate-300 hover:text-slate-200">Voltar</a>
                    <button class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-500">Salvar identidade</button>
                </div>
            </div>

            <aside class="lg:sticky lg:top-20 lg:self-start">
                <p class="mb-2 text-center text-[11.5px] text-slate-500">Amostra (atualiza enquanto você mexe)</p>
                @include('estudio.partials.celular', ['perfil' => $perfil, 'client' => $client, 'alpine' => true])
            </aside>
        </form>

        {{-- Caderno de aprendizados --}}
        <section id="aprendizados" class="mt-8 rounded-2xl border border-ink-600 bg-ink-800/70 p-5">
            <h2 class="text-sm font-semibold text-slate-200">Caderno de aprendizados</h2>
            <p class="mt-0.5 text-[12px] text-slate-500">Correções que valem para todos os vídeos deste cliente. Os aprovados entram em toda edição nova; anotar não é mudar — você decide.</p>

            <ul class="mt-4 divide-y divide-ink-700">
                @forelse($aprendizados as $a)
                    <li class="flex items-center gap-3 py-2 text-[13px]" x-data="{ st: @js($a->status) }" x-show="st !== 'descartado'">
                        <span :class="st === 'aplicado' ? 'text-emerald-400' : 'text-amber-400'" x-text="st === 'aplicado' ? '✓' : '•'"></span>
                        <span class="flex-1 text-slate-300">{{ $a->texto }} <span class="text-[11px] text-slate-500">· {{ $a->created_at?->format('d/m/Y') }}</span></span>
                        <button type="button" x-show="st === 'pendente'" @click="fetch(@js(route('estudio.aprendizados.update', $a)), { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ status: 'aplicado' }) }).then(() => st = 'aplicado')" class="rounded bg-emerald-600/80 px-2 py-0.5 text-[11.5px] font-semibold text-white">Aprovar</button>
                        <button type="button" @click="fetch(@js(route('estudio.aprendizados.update', $a)), { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ status: 'descartado' }) }).then(() => st = 'descartado')" class="text-[11.5px] text-slate-500 hover:text-rose-400">Descartar</button>
                    </li>
                @empty
                    <li class="py-3 text-[12.5px] italic text-slate-500">Vazio. Ao pedir ajustes nas prévias, o Claude sugere o que vale guardar.</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('clients.estudio.aprendizados.store', $client) }}" class="mt-3 flex gap-2">
                @csrf
                <input name="texto" required maxlength="500" placeholder="Anotar à mão: ex. “zoom mais sutil, o cliente acha exagerado”" class="{{ $campo }}">
                <button class="shrink-0 rounded-lg border border-ink-600 px-3 py-2 text-sm text-slate-300 hover:bg-ink-700">Anotar</button>
            </form>
        </section>
    </div>
</x-app-layout>
