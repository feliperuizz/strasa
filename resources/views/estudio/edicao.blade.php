<x-app-layout title="{{ $edicao->titulo }} · Estúdio" :client="$client">
    @php
        $rascunho = $edicao->status === \App\Models\VideoJob::RASCUNHO;
        $andando = $edicao->emAndamento() || $edicao->status === \App\Models\VideoJob::AGUARDANDO_LIMITE;
        $temPrevia = (bool) $edicao->previa_path;
        $plano = $edicao->plano;
        $linhaPorId = collect($previaInfo['linha'] ?? [])->keyBy('id');
        $dim = $edicao->dimensoes();
        $proporcao = $dim['largura'].'/'.$dim['altura'];
        $placeholders = [
            'espaco' => "Ex.: Barbearia recém-reformada no centro. Destacar o ambiente climatizado, a cadeira de época e o café de cortesia. Terminar com 'Agende pelo WhatsApp'.",
            'legenda' => "Ex.: O Zé (dono) explica como manter o degradê por mais tempo. Termos: degradê, pomada matte, navalhete.",
            'apresentador' => "Ex.: 3 cuidados com a barba no verão. Cada cuidado aparece na tela. Fechar com chamada para o kit de barba.",
            'antes_depois' => "Ex.: Cliente chegou com a barba sem forma; saiu com barba desenhada e degradê navalhado.",
            'promo' => "Ex.: Combo corte + barba de R$ 70 por R$ 49, só até sábado. Chamada: chame no WhatsApp.",
            'depoimento' => "Ex.: Depoimento do Carlos, advogado, cliente há 2 anos. Autorizou o uso da imagem. Nota no Google: 4,9.",
        ];
        $etapas = [
            'preparar' => 'Preparando o material',
            'transcrever' => 'Transcrevendo a fala',
            'decidir' => $claudeAtivo ? 'Claude decidindo a edição' : 'Montando a edição',
            'previa' => 'Renderizando a prévia',
        ];
        if (! $estilo['fala']) unset($etapas['transcrever']);
        if ($edicao->status === \App\Models\VideoJob::AJUSTANDO) $etapas = ['ajustar' => 'Aplicando o ajuste', 'previa' => 'Renderizando a prévia'];
        if ($edicao->status === \App\Models\VideoJob::FINALIZANDO) $etapas = ['final' => 'Gerando o vídeo final em alta'];
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl ring-1 ring-white/10" style="color: {{ $estilo['cor'] }}; background: {{ $estilo['cor'] }}1a">
                    @include('estudio.partials.estilo-icone', ['icone' => $estilo['icone'], 'classe' => 'h-5 w-5'])
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-x-2 text-[11.5px] text-slate-400">
                        <a href="{{ route('clients.estudio', $client) }}" class="hover:text-slate-200">{{ $client->name }}</a>
                        <span class="text-slate-600">/</span>
                        <span style="color: {{ $estilo['cor'] }}">{{ $estilo['nome'] }}</span>
                    </div>
                    <h1 class="truncate text-base font-semibold text-slate-200">{{ $edicao->titulo }}</h1>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2" id="status-cabecalho">
                    @include('estudio.partials.status', ['edicao' => $edicao])
                </div>
                @include('estudio.partials.excluir-edicao', ['edicao' => $edicao])
            </div>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6">

        {{-- ============================== RASCUNHO ============================== --}}
        @if($rascunho)
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]"
                 x-data="uploaderEstudio({
                     arquivos: @js($dadosArquivos),
                     urlIniciar: @js(route('estudio.arquivos.iniciar', $edicao)),
                     urlEdicao: @js(route('estudio.edicoes.update', $edicao)),
                     antesDepois: @js($edicao->estilo === 'antes_depois'),
                 })">

                <section class="space-y-4">
                    <div class="rounded-2xl border border-ink-600 bg-ink-800/70 p-4 sm:p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-200">1. Material</h2>
                            <span class="text-[11.5px] text-slate-500" x-text="resumo()"></span>
                        </div>

                        <label @dragover.prevent="arrastando = true" @dragleave.prevent="arrastando = false" @drop.prevent="arrastando = false; adicionar($event.dataTransfer.files)"
                               :class="arrastando ? 'border-brand-500 bg-brand-500/10' : 'border-ink-500 hover:border-brand-500/60 hover:bg-ink-900/40'"
                               class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-8 text-center transition">
                            <svg class="h-9 w-9 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                            <span class="text-sm font-medium text-slate-300">Arraste os vídeos e fotos aqui ou clique para escolher</span>
                            <span class="text-[11.5px] text-slate-500">Direto do celular, em qualquer tamanho. Sobe em partes: se a internet cair, continua de onde parou.</span>
                            <input type="file" multiple class="hidden" accept="video/*,image/*,.mov,.heic,.mts" @change="adicionar($event.target.files); $event.target.value = ''">
                        </label>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-[12px]" x-show="arquivos.length > 1" x-cloak>
                            <span class="text-slate-400">A ordem é a <b class="text-slate-200">cronologia</b> dos fatos. Arraste ou use as setas.</span>
                            <span class="flex items-center gap-1.5">
                                <button type="button" @click="ordenarPor('gravado_em')" class="rounded-lg border border-ink-600 px-2 py-1 text-slate-300 hover:bg-ink-700" title="Usa a data e hora que o celular gravou no arquivo">↧ Pela hora da gravação</button>
                                <button type="button" @click="ordenarPor('nome')" class="rounded-lg border border-ink-600 px-2 py-1 text-slate-300 hover:bg-ink-700">↧ Pelo nome</button>
                            </span>
                        </div>

                        <ul class="mt-2 space-y-2" x-ref="lista">
                            <template x-for="(a, i) in arquivos" :key="a.chave">
                                <li class="flex items-center gap-2.5 rounded-xl border border-ink-600 bg-ink-900/50 p-2.5" :data-id="a.id">
                                    <span class="flex shrink-0 flex-col items-center" x-show="arquivos.length > 1">
                                        <button type="button" @click="mover(i, i - 1)" :disabled="i === 0" class="px-1 text-[11px] leading-none text-slate-500 hover:text-white disabled:opacity-20" title="Subir">▲</button>
                                        <span class="cursor-grab select-none py-0.5 text-[12px] font-semibold tabular-nums text-brand-300" title="Arraste para mudar a ordem" x-text="(i + 1) + 'º'"></span>
                                        <button type="button" @click="mover(i, i + 1)" :disabled="i === arquivos.length - 1" class="px-1 text-[11px] leading-none text-slate-500 hover:text-white disabled:opacity-20" title="Descer">▼</button>
                                    </span>
                                    <div class="relative h-14 w-10 shrink-0 overflow-hidden rounded-md bg-ink-800">
                                        <template x-if="a.miniatura"><img :src="a.miniatura" class="h-full w-full object-cover" alt=""></template>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13px] font-medium text-slate-200" x-text="a.nome"></p>
                                        <p class="text-[11.5px]" :class="a.status === 'erro' ? 'text-rose-400' : 'text-slate-500'" x-text="detalhe(a)"></p>
                                        <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-ink-700" x-show="a.status === 'enviando' || a.status === 'fila'">
                                            <div class="h-full rounded-full bg-brand-500 transition-all" :style="`width: ${a.progresso || 0}%`"></div>
                                        </div>
                                    </div>
                                    <select x-show="antesDepois && a.status === 'pronto'" x-model="a.papel" @change="mudarPapel(a)"
                                            class="rounded-lg border border-ink-600 bg-ink-800 px-2 py-1 text-[12px] text-slate-200">
                                        <option value="antes">Antes</option>
                                        <option value="bruto">Processo</option>
                                        <option value="depois">Depois</option>
                                    </select>
                                    <button type="button" @click="remover(a)" class="rounded-md p-1.5 text-slate-500 hover:bg-ink-700 hover:text-rose-400" title="Remover">✕</button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-ink-600 bg-ink-900/40 p-4">
                        <h3 class="text-[12.5px] font-semibold text-slate-300">Para este estilo ficar bom</h3>
                        <ul class="mt-2 space-y-1.5">
                            @foreach($estilo['dicas_gravacao'] as $dica)
                                <li class="flex gap-2 text-[12.5px] text-slate-400"><span style="color: {{ $estilo['cor'] }}">●</span><span>{{ $dica }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="rounded-2xl border border-ink-600 bg-ink-800/70 p-4 sm:p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-200">2. Briefing</h2>
                            <span class="text-[11px] text-slate-500" x-text="salvo"></span>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-400">Nome</label>
                            <input value="{{ $edicao->titulo }}" @input.debounce.600ms="salvar({ titulo: $event.target.value })" maxlength="160"
                                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-400">O que o vídeo precisa passar? (o Claude lê isto)</label>
                            <textarea rows="6" @input.debounce.800ms="salvar({ briefing: $event.target.value })" maxlength="6000"
                                      placeholder="{{ $placeholders[$edicao->estilo] ?? '' }}"
                                      class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">{{ $edicao->briefing }}</textarea>
                            <p class="mt-1 text-[11.5px] text-slate-500">Quem aparece, o que destacar, nomes e termos difíceis, preços, a chamada final. Cores, fonte e logo já vêm da identidade do cliente.</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-400">Música {{ $estilo['musica'] === 'obrigatoria' ? '(recomendada)' : '(opcional)' }}</label>
                                <select @change="salvar({ musica_id: $event.target.value || null })" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200">
                                    <option value="">Sem música</option>
                                    @foreach($musicas as $m)
                                        <option value="{{ $m->id }}" @selected($edicao->musica_id === $m->id)>{{ $m->nome }}{{ $m->bpm ? ' · '.round($m->bpm).' BPM' : '' }}{{ $m->client_id ? ' · exclusiva' : '' }}</option>
                                    @endforeach
                                </select>
                                <a href="{{ route('estudio.biblioteca') }}" class="mt-1 inline-block text-[11.5px] text-brand-400 hover:text-brand-300">{{ $musicas->isEmpty() ? 'A biblioteca está vazia — adicionar músicas' : 'Biblioteca de músicas' }} →</a>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-400">Duração desejada</label>
                                <div class="flex items-center gap-2">
                                    @if(! empty($estilo['longo']))
                                        {{-- Vídeo longo: em minutos (guarda em segundos). --}}
                                        <input type="number" min="1" max="15" step="0.5" placeholder="{{ round($estilo['duracao'][2] / 60) }}" value="{{ $edicao->duracao_alvo ? round($edicao->duracao_alvo / 60, 1) : '' }}"
                                               @input.debounce.600ms="salvar({ duracao_alvo: $event.target.value ? Math.round(parseFloat($event.target.value) * 60) : null })"
                                               class="w-24 rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200">
                                        <span class="text-[12px] text-slate-500">minutos (até 15; vazio = o material decide)</span>
                                    @else
                                        <input type="number" min="5" max="180" placeholder="{{ $estilo['duracao'][2] }}" value="{{ $edicao->duracao_alvo }}"
                                               @input.debounce.600ms="salvar({ duracao_alvo: $event.target.value ? parseInt($event.target.value) : null })"
                                               class="w-24 rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200">
                                        <span class="text-[12px] text-slate-500">segundos (sugestão {{ $estilo['duracao'][0] }}–{{ $estilo['duracao'][1] }})</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-400">Formato</label>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach(\App\Models\VideoJob::FORMATOS as $f => $info)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="formato" value="{{ $f }}" class="peer sr-only" @checked($edicao->formato === $f) @change="salvar({ formato: '{{ $f }}' })">
                                        <span class="block rounded-lg border border-ink-600 bg-ink-900 px-2 py-2 text-center text-[12px] text-slate-400 peer-checked:border-brand-500 peer-checked:bg-brand-500/10 peer-checked:text-slate-100">
                                            <span class="block font-semibold">{{ $f }}</span>{{ $info['nome'] }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-x-5 gap-y-2 text-[13px] text-slate-300">
                            @if($estilo['legendas'])
                                <label class="inline-flex items-center gap-2"><input type="checkbox" class="rounded border-ink-500 bg-ink-900 text-brand-600" @checked($edicao->opcao('legendas', true)) @change="salvar({ opcoes: { legendas: $event.target.checked } })"> Legenda animada</label>
                            @endif
                            <label class="inline-flex items-center gap-2"><input type="checkbox" class="rounded border-ink-500 bg-ink-900 text-brand-600" @checked($edicao->opcao('cartao_final', true)) @change="salvar({ opcoes: { cartao_final: $event.target.checked } })"> Cartão final com a chamada</label>
                        </div>

                        <div class="rounded-xl border border-ink-600 bg-ink-900/40 p-3" x-data="{ narrar: @js((bool) $edicao->opcao('narracao', false)) }">
                            <label class="inline-flex items-center gap-2 text-[13px] text-slate-200">
                                <input type="checkbox" x-model="narrar" @change="salvar({ opcoes: { narracao: narrar } })" class="rounded border-ink-500 bg-ink-900 text-brand-600">
                                Narração com voz gerada
                                <span class="text-[11.5px] text-slate-500">— o Claude escreve o texto e uma voz natural narra o vídeo</span>
                            </label>
                            <div x-show="narrar" x-cloak class="mt-2.5 space-y-1.5">
                                @include('estudio.partials.voz-escolha', ['nome' => null, 'valor' => $edicao->opcao('voz'), 'vazio' => 'Voz do cliente'.(($v = \App\Models\VideoProfile::doCliente($edicao->client)->voz_narracao) ? ' ('.(\App\Video\Narracao::vozes()[$v]['nome'] ?? $v).')' : ''), 'aoMudar' => 'salvar({ voz: voz })'])
                                <p class="text-[11.5px] text-slate-500">Dica: escreva no briefing o que a narração precisa dizer (preço, diferenciais, chamada). O Claude não inventa informação.</p>
                            </div>
                        </div>

                        @include('estudio.partials.identidade-edicao', ['modo' => 'autosave'])
                    </div>

                    <form method="POST" action="{{ route('estudio.edicoes.iniciar', $edicao) }}" class="rounded-2xl border border-ink-600 bg-gradient-to-br from-ink-800 to-ink-900 p-4 sm:p-5">
                        @csrf
                        <button :disabled="enviando || !arquivos.some(a => a.status === 'pronto')"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-[15px] font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-500 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <span x-text="enviando ? 'Espere os arquivos subirem…' : '{{ $claudeAtivo ? 'Editar com o Claude' : 'Editar' }}'"></span>
                        </button>
                        <p class="mt-2 text-center text-[11.5px] text-slate-500">
                            {{ $claudeAtivo ? 'O Claude assiste ao material, decide cortes, animações e música no estilo do cliente, e entrega uma prévia para você revisar.' : 'Atenção: o Claude ainda não está conectado no servidor (scripts/video/logar-claude.sh). A edição vai parar com erro até conectar.' }}
                        </p>
                    </form>

                    <form method="POST" action="{{ route('estudio.edicoes.destroy', $edicao) }}" onsubmit="return confirm('Excluir este rascunho e os arquivos enviados?')" class="text-right">
                        @csrf @method('DELETE')
                        <button class="text-[12px] text-slate-500 hover:text-rose-400">Excluir rascunho</button>
                    </form>
                </section>
            </div>

        {{-- ============================== EM ANDAMENTO / REVISÃO / PRONTO ============================== --}}
        @else
            <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]"
                 x-data="acompanharEdicao({
                     url: @js(route('estudio.edicoes.status', $edicao)),
                     ultimo: @js((int) $edicao->eventos->max('id')),
                     andando: @js($andando),
                     status: @js($edicao->status),
                 })">

                {{-- Coluna do player --}}
                <section class="space-y-3">
                    <div class="relative mx-auto w-full {{ $dim['largura'] > $dim['altura'] ? 'max-w-full' : 'max-w-[340px]' }}">
                        <div class="relative overflow-hidden rounded-[28px] border-[6px] border-black bg-black shadow-2xl ring-1 ring-white/10" style="aspect-ratio: {{ $proporcao }}">
                            @if($temPrevia)
                                <video x-ref="video" class="h-full w-full bg-black object-contain" controls playsinline preload="metadata"
                                       src="{{ $edicao->final_path && $edicao->status === 'pronto' ? route('estudio.midia', [$edicao, 'final']).'?v='.$edicao->final_versao : route('estudio.midia', [$edicao, 'previa']).'?v='.$edicao->previa_versao }}"
                                       @if($edicao->capa_path) poster="{{ route('estudio.midia', [$edicao, 'capa']) }}" @endif></video>
                            @endif

                            <div x-show="andando" x-cloak class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-ink-900/92 p-6 text-center backdrop-blur-sm">
                                <div class="relative h-16 w-16">
                                    <svg class="h-16 w-16 -rotate-90" viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" class="text-ink-700"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-brand-500 transition-all duration-700" :stroke-dasharray="`${progresso * 0.974} 100`"/></svg>
                                    <span class="absolute inset-0 grid place-items-center text-[13px] font-bold text-slate-200" x-text="progresso + '%'"></span>
                                </div>
                                <p class="text-sm font-medium text-slate-200" x-text="mensagem || 'Na fila…'"></p>
                                <ol class="w-full max-w-[230px] space-y-1.5 text-left">
                                    @foreach($etapas as $chave => $rotulo)
                                        <li class="flex items-center gap-2 text-[12px]" :class="etapaClasse('{{ $chave }}', @js(array_keys($etapas)))">
                                            <span class="grid h-4 w-4 place-items-center rounded-full border border-current text-[9px]" x-text="etapaMarca('{{ $chave }}', @js(array_keys($etapas)))"></span>{{ $rotulo }}
                                        </li>
                                    @endforeach
                                </ol>
                                <p class="text-[11px] text-slate-500">Pode fechar esta tela: a edição continua no servidor.</p>
                            </div>
                        </div>
                    </div>

                    @if($temPrevia)
                        <div class="flex flex-wrap items-center justify-center gap-2 text-[12px]">
                            @if($edicao->final_path)
                                <a href="{{ route('estudio.midia', [$edicao, 'final']) }}?baixar=1" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 font-semibold text-white hover:bg-emerald-500">⬇ Baixar final v{{ $edicao->final_versao }}</a>
                            @endif
                            <a href="{{ route('estudio.midia', [$edicao, 'previa']) }}?baixar=1" class="rounded-lg border border-ink-600 px-3 py-1.5 text-slate-300 hover:bg-ink-800">Prévia v{{ $edicao->previa_versao }}</a>
                            @if($edicao->previa_versao > 1)
                                <span class="text-slate-500">versões:</span>
                                @for($v = $edicao->previa_versao - 1; $v >= max(1, $edicao->previa_versao - 4); $v--)
                                    @continue(! is_file($edicao->pasta()->absoluto(sprintf('renders/previa_v%02d.mp4', $v))))
                                    <button type="button" @click="$refs.video.src = @js(route('estudio.midia', [$edicao, 'previa', $v])); $refs.video.play()" class="rounded px-1.5 py-0.5 text-slate-400 hover:bg-ink-800 hover:text-slate-200">v{{ $v }}</button>
                                @endfor
                            @endif
                        </div>
                        @if($edicao->final_path && $edicao->nuvem_status)
                            <div class="flex flex-wrap items-center justify-center gap-2 text-[11.5px]">
                                @if($edicao->noDrive())
                                    <span class="text-emerald-400">☁ Salvo no Google Drive</span>
                                    <span class="truncate text-slate-500" title="{{ $edicao->nuvem_final }}">{{ $edicao->nuvem_final }}</span>
                                @elseif($edicao->nuvem_status === 'enviando')
                                    <span class="text-slate-400">☁ Enviando ao Google Drive… (depois sai do servidor)</span>
                                @else
                                    <span class="text-rose-400" title="{{ $edicao->nuvem_erro }}">☁ Não foi para o Drive (continua no servidor)</span>
                                    <form method="POST" action="{{ route('estudio.edicoes.drive', $edicao) }}">@csrf<button class="rounded border border-ink-600 px-2 py-0.5 text-slate-300 hover:bg-ink-800">Enviar de novo</button></form>
                                @endif
                            </div>
                        @endif
                    @endif

                    @if($edicao->status === 'erro')
                        <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4">
                            <p class="text-sm font-semibold text-rose-300">A edição parou</p>
                            <p class="mt-1 text-[12.5px] text-rose-200/80">{{ $edicao->erro }}</p>
                            <form method="POST" action="{{ route('estudio.edicoes.tentar', $edicao) }}" class="mt-3">
                                @csrf
                                <button class="rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-rose-500">Tentar de novo</button>
                                <span class="ml-2 text-[11.5px] text-rose-300/70">Continua de onde parou.</span>
                            </form>
                        </div>
                    @elseif($edicao->erro && $temPrevia && ! $andando)
                        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-[12.5px] text-amber-200">
                            O último pedido não deu certo: {{ $edicao->erro }} A prévia anterior continua valendo.
                        </div>
                    @endif

                    @if(in_array($edicao->status, ['na_fila', 'aguardando_limite'], true))
                        <form method="POST" action="{{ route('estudio.edicoes.cancelar', $edicao) }}" class="text-center">
                            @csrf
                            <button class="text-[12px] text-slate-500 hover:text-rose-400">Cancelar</button>
                        </form>
                    @endif

                    {{-- Material enviado --}}
                    <details class="rounded-2xl border border-ink-600 bg-ink-800/50 p-3">
                        <summary class="cursor-pointer select-none text-[12.5px] font-medium text-slate-300">Material ({{ $edicao->arquivos->count() }} arquivo(s))</summary>
                        <ul class="mt-2 space-y-1.5">
                            @foreach($edicao->arquivos as $a)
                                <li class="flex items-center gap-2 text-[12px] text-slate-400">
                                    <span class="rounded bg-ink-700 px-1.5 py-0.5 font-mono text-[10.5px] text-slate-300">{{ $a->chave() }}</span>
                                    <span class="truncate">{{ $a->nome_original }}</span>
                                    <span class="ml-auto shrink-0 text-slate-500">{{ $a->tipo === 'imagem' ? 'foto' : \App\Video\Plano\Linha::formatar((float) $a->duracao) }}{{ $a->papel !== 'bruto' ? ' · '.$a->papel : '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if($edicao->briefing)
                            <p class="mt-3 border-t border-ink-600 pt-2 text-[12px] text-slate-400 whitespace-pre-line">{{ $edicao->briefing }}</p>
                        @endif
                    </details>
                </section>

                {{-- Coluna de trabalho --}}
                <section class="min-w-0 rounded-2xl border border-ink-600 bg-ink-800/70" x-data="{ aba: '{{ $edicao->status === 'pronto' ? 'publicar' : 'conversa' }}' }">
                    <nav class="flex gap-1 border-b border-ink-600 px-3 pt-2 text-[13px]">
                        @foreach(['conversa' => 'Conversa', 'plano' => 'Plano da edição', 'publicar' => 'Aprovar e publicar'] as $aba => $rotulo)
                            <button type="button" @click="aba = '{{ $aba }}'" :class="aba === '{{ $aba }}' ? 'border-brand-500 text-slate-100' : 'border-transparent text-slate-400 hover:text-slate-200'"
                                    class="-mb-px border-b-2 px-3 py-2 font-medium transition">{{ $rotulo }}</button>
                        @endforeach
                    </nav>

                    {{-- Conversa --}}
                    <div x-show="aba === 'conversa'" class="flex h-[min(70vh,680px)] flex-col">
                        <div class="flex-1 space-y-3 overflow-y-auto p-4" x-ref="conversa" id="conversa">
                            @foreach($edicao->eventos as $evento)
                                @include('estudio.partials.evento', ['evento' => $evento])
                            @endforeach
                        </div>

                        @if($aprendizadosPendentes->isNotEmpty())
                            <div class="border-t border-ink-600 bg-amber-500/5 px-4 py-3">
                                <p class="text-[12px] font-semibold text-amber-300">O Claude anotou para os próximos vídeos deste cliente — vale?</p>
                                @foreach($aprendizadosPendentes as $ap)
                                    <div class="mt-1.5 flex items-center gap-2 text-[12.5px] text-slate-300" x-data="{ feito: null }" x-show="feito === null">
                                        <span class="flex-1">“{{ $ap->texto }}”</span>
                                        <button type="button" @click="fetch(@js(route('estudio.aprendizados.update', $ap)), { method: 'PATCH', headers: cabecalhosJson(), body: JSON.stringify({ status: 'aplicado' }) }).then(() => feito = 1)" class="rounded bg-emerald-600/80 px-2 py-0.5 text-[11.5px] font-semibold text-white hover:bg-emerald-500">Sim, sempre</button>
                                        <button type="button" @click="fetch(@js(route('estudio.aprendizados.update', $ap)), { method: 'PATCH', headers: cabecalhosJson(), body: JSON.stringify({ status: 'descartado' }) }).then(() => feito = 0)" class="rounded px-2 py-0.5 text-[11.5px] text-slate-400 hover:bg-ink-700">Só neste</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('estudio.edicoes.ajustar', $edicao) }}" class="border-t border-ink-600 p-3" x-data="{ texto: '' }">
                            @csrf
                            @if($edicao->aceitaAjustes() && $claudeAtivo && ! $edicao->limpo_em)
                                <div class="mb-2 flex flex-wrap gap-1.5">
                                    @foreach(array_merge(['em 0:05 tira essa frase', 'música mais baixa', 'legenda maior', 'zoom mais sutil', 'troca o título por…', 'mais rápido no começo'], \App\Video\Narracao::disponivel() ? [empty($plano['narracao']['falas']) ? 'coloca uma narração' : 'narração mais curta'] : []) as $sugestao)
                                        <button type="button" @click="texto = texto ? texto + '; ' + @js($sugestao) : @js($sugestao); $refs.pedido.focus()" class="rounded-full border border-ink-600 px-2.5 py-0.5 text-[11.5px] text-slate-400 hover:border-brand-500/60 hover:text-slate-200">{{ $sugestao }}</button>
                                    @endforeach
                                </div>
                                <div class="flex items-end gap-2">
                                    <textarea x-ref="pedido" name="pedido" x-model="texto" rows="2" required minlength="3" maxlength="3000"
                                              placeholder="Peça ajustes citando o tempo da prévia ou o número do item (ex.: mo03 mais lento). Junte vários pedidos numa mensagem só."
                                              @keydown.enter.prevent.exact="$el.form.requestSubmit()"
                                              class="flex-1 resize-none rounded-xl border border-ink-600 bg-ink-900 px-3 py-2 text-[13px] text-slate-200 focus:border-brand-500 focus:outline-none"></textarea>
                                    <button class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-500">Ajustar</button>
                                </div>
                            @elseif($andando)
                                <p class="py-1 text-center text-[12px] text-slate-500">Aguarde terminar para pedir ajustes.</p>
                            @elseif(! $claudeAtivo)
                                <p class="py-1 text-center text-[12px] text-slate-500">Ajuste por texto precisa do Claude no servidor. Use a aba "Plano da edição".</p>
                            @elseif($edicao->limpo_em)
                                <p class="py-1 text-center text-[12px] text-slate-500">Os brutos já foram apagados pela retenção. Para mudar, envie o material de novo.</p>
                            @endif
                        </form>
                    </div>

                    {{-- Plano --}}
                    <div x-show="aba === 'plano'" x-cloak class="p-4">
                        @if(! $plano)
                            <p class="py-10 text-center text-sm text-slate-500">O plano aparece aqui quando a edição sair.</p>
                        @else
                            <form method="POST" action="{{ route('estudio.edicoes.plano', $edicao) }}" class="space-y-5">
                                @csrf
                                @if($plano['resumo'] ?? false)
                                    <div class="rounded-xl border border-ink-600 bg-ink-900/50 p-3 text-[12.5px] text-slate-300">
                                        <span class="font-semibold text-slate-200">{{ $edicao->feito_por_claude ? 'Claude' : 'Piloto automático' }}:</span> {{ $plano['resumo'] }}
                                        @if($plano['gancho'] ?? false)<br><span class="text-slate-400">Gancho: {{ $plano['gancho'] }}</span>@endif
                                    </div>
                                @endif

                                <div>
                                    <h3 class="mb-2 text-[12.5px] font-semibold text-slate-300">Trechos <span class="font-normal text-slate-500">— clique para assistir; desmarque para tirar</span></h3>
                                    <div class="space-y-1">
                                        @foreach($plano['segmentos'] as $s)
                                            @continue($s['id'] === 'fim')
                                            @php $t = $linhaPorId[$s['id']]['seq_inicio'] ?? null; @endphp
                                            <label class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-[12.5px] hover:bg-ink-900/60" x-data="{ fora: false }" :class="fora && 'opacity-40 line-through'">
                                                <input type="checkbox" checked @change="fora = ! $event.target.checked" class="rounded border-ink-500 bg-ink-900 text-brand-600">
                                                <input type="hidden" name="segmentos_fora[]" value="{{ $s['id'] }}" :disabled="! fora" disabled>
                                                <button type="button" @click.prevent="pular({{ $t ?? 0 }})" class="w-12 shrink-0 text-left font-mono text-[11.5px] text-brand-400 hover:underline">{{ $t !== null ? \App\Video\Plano\Linha::formatar($t) : '—' }}</button>
                                                <span class="rounded bg-ink-700 px-1.5 font-mono text-[10.5px] text-slate-300">{{ $s['id'] }}</span>
                                                <span class="truncate text-slate-300">{{ $s['nota'] ?: ($s['tipo'] === 'foto' ? 'foto' : $s['arquivo'].' '.number_format($s['inicio'] ?? 0, 1, ',', '').'–'.number_format($s['fim'] ?? 0, 1, ',', '').' s') }}</span>
                                                @if(($s['transicao'] ?? 'corte') !== 'corte')<span class="ml-auto shrink-0 text-[11px] text-slate-500">{{ $s['transicao'] }}</span>@endif
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                @if($plano['elementos'])
                                    <div>
                                        <h3 class="mb-2 text-[12.5px] font-semibold text-slate-300">Animações <span class="font-normal text-slate-500">— edite o texto ou desmarque</span></h3>
                                        <div class="space-y-1">
                                            @foreach($plano['elementos'] as $el)
                                                <div class="flex items-center gap-2.5 rounded-lg px-2 py-1 text-[12.5px] hover:bg-ink-900/60" x-data="{ fora: false }" :class="fora && 'opacity-40'">
                                                    <input type="checkbox" checked @change="fora = ! $event.target.checked" class="rounded border-ink-500 bg-ink-900 text-brand-600">
                                                    <input type="hidden" name="elementos_fora[]" value="{{ $el['id'] }}" :disabled="! fora" disabled>
                                                    <button type="button" @click.prevent="pular({{ $el['inicio'] ?? 0 }})" class="w-12 shrink-0 text-left font-mono text-[11.5px] text-brand-400 hover:underline">{{ \App\Video\Plano\Linha::formatar((float) ($el['inicio'] ?? 0)) }}</button>
                                                    <span class="rounded bg-ink-700 px-1.5 font-mono text-[10.5px] text-slate-300">{{ $el['id'] }}</span>
                                                    <span class="w-28 shrink-0 truncate text-slate-500">{{ str_replace('_', ' ', $el['tipo']) }}{{ $el['tipo'] === 'sticker' ? ' · '.($el['forma'] ?? '') : '' }}</span>
                                                    @if(in_array($el['tipo'], ['titulo', 'texto', 'palavra', 'etiqueta', 'preco', 'nome', 'local', 'citacao', 'tela_cheia', 'pilula', 'comentario', 'busca', 'seguir', 'notificacao', 'marca'], true))
                                                        <input name="textos[{{ $el['id'] }}]" value="{{ $el['texto'] }}" maxlength="140" class="min-w-0 flex-1 rounded-md border border-transparent bg-transparent px-1.5 py-0.5 text-slate-200 hover:border-ink-600 focus:border-brand-500 focus:bg-ink-900 focus:outline-none">
                                                    @else
                                                        <span class="flex-1 truncate border border-transparent px-1.5 py-0.5 text-slate-400">{{ $el['texto'] ?: ($el['conceito'] ?? null) ?: collect($el['itens'] ?? $el['linhas'] ?? [])->map(fn ($i) => is_array($i) ? ($i['texto'] ?? $i['nome'] ?? $i['rotulo'] ?? '') : $i)->filter()->implode(' · ') }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(! empty($plano['narracao']['falas']))
                                    <div class="rounded-xl border border-ink-600 bg-ink-900/40 p-3">
                                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                            <label class="flex items-center gap-2 text-[12.5px] font-semibold text-slate-300">
                                                <input type="checkbox" name="narracao_ativo" value="1" @checked($edicao->opcao('narracao', false)) class="rounded border-ink-500 bg-ink-900 text-brand-600"> Narração
                                                <span class="font-normal text-slate-500">— edite o texto; apague uma fala para tirar</span>
                                            </label>
                                            <div class="w-full sm:w-80">
                                                @include('estudio.partials.voz-escolha', ['nome' => 'voz', 'valor' => $edicao->opcao('voz'), 'vazio' => 'Voz do cliente'])
                                            </div>
                                        </div>
                                        <div class="space-y-1.5">
                                            @foreach($plano['narracao']['falas'] as $f)
                                                <div class="flex items-start gap-2.5 text-[12.5px]">
                                                    <span class="mt-1.5 rounded bg-ink-700 px-1.5 font-mono text-[10.5px] text-slate-300">{{ $f['id'] }}</span>
                                                    <textarea name="narracao[{{ $f['id'] }}]" rows="2" maxlength="500" class="min-w-0 flex-1 resize-y rounded-lg border border-ink-600 bg-ink-900 px-2.5 py-1.5 text-slate-200 focus:border-brand-500 focus:outline-none">{{ $f['texto'] }}</textarea>
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="mt-1.5 text-[11px] text-slate-500">Só as falas que você mudar são geradas de novo. Números e R$ podem ir em algarismos.</p>
                                    </div>
                                @endif

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 flex items-center gap-2 text-[12.5px] font-semibold text-slate-300">
                                            <input type="checkbox" name="legendas" value="1" @checked($plano['legendas']['ativo']) class="rounded border-ink-500 bg-ink-900 text-brand-600"> Legenda
                                        </label>
                                        <textarea name="correcoes" rows="3" placeholder="Corrigir grafia, uma por linha:&#10;barbella => barbearia" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 font-mono text-[12px] text-slate-200 focus:border-brand-500 focus:outline-none"></textarea>
                                        @if(! empty($plano['legendas']['correcoes']))
                                            <p class="mt-1 text-[11px] text-slate-500">Já corrigidas: {{ collect($plano['legendas']['correcoes'])->map(fn ($p, $d) => "$d → $p")->implode(', ') }}</p>
                                        @endif
                                    </div>
                                    <div class="space-y-3">
                                        @if($edicao->musica)
                                            <div x-data="{ v: {{ (int) round(($plano['musica']['volume'] ?? 0.2) * 100) }} }">
                                                <label class="mb-1 block text-[12.5px] font-semibold text-slate-300">Volume da música: <span x-text="v + '%'"></span></label>
                                                <input type="range" name="volume_musica" min="0" max="100" x-model="v" class="w-full accent-indigo-500">
                                            </div>
                                        @endif
                                        <div>
                                            <label class="mb-1 flex items-center gap-2 text-[12.5px] font-semibold text-slate-300">
                                                <input type="checkbox" name="cartao_ativo" value="1" @checked($plano['cartao_final']['ativo']) class="rounded border-ink-500 bg-ink-900 text-brand-600"> Cartão final
                                            </label>
                                            <input name="cartao_titulo" value="{{ $plano['cartao_final']['titulo'] }}" maxlength="60" placeholder="Agende seu horário" class="mb-1.5 w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-[12.5px] text-slate-200">
                                            <textarea name="cartao_linhas" rows="2" placeholder="Uma linha por vez (@instagram, endereço…)" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-[12.5px] text-slate-200">{{ implode("\n", $plano['cartao_final']['linhas']) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                @include('estudio.partials.identidade-edicao', ['modo' => 'form'])

                                @if($edicao->aceitaAjustes() && ! $edicao->limpo_em)
                                    <div class="flex justify-end">
                                        <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">Aplicar e gerar nova prévia</button>
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>

                    {{-- Publicar --}}
                    <div x-show="aba === 'publicar'" x-cloak class="space-y-5 p-4">
                        @if(! $temPrevia)
                            <p class="py-10 text-center text-sm text-slate-500">Primeiro sai a prévia.</p>
                        @else
                            <div class="rounded-xl border border-ink-600 bg-ink-900/50 p-4">
                                <h3 class="text-sm font-semibold text-slate-200">1. Gerar o vídeo final</h3>
                                <p class="mt-1 text-[12.5px] text-slate-400">A prévia é leve (metade da resolução) para revisar rápido. O final sai dos originais em {{ $dim['largura'] }}x{{ $dim['altura'] }}, com a cor convertida, volume no padrão das redes e capa.</p>
                                @if(in_array($edicao->status, ['revisao', 'pronto'], true) && ! $edicao->limpo_em)
                                    <form method="POST" action="{{ route('estudio.edicoes.finalizar', $edicao) }}" class="mt-3">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                                            {{ $edicao->final_path ? 'Gerar final de novo (a partir da prévia v'.$edicao->previa_versao.')' : 'Aprovar prévia v'.$edicao->previa_versao.' e gerar o final' }}
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if($edicao->final_path)
                                <div class="grid gap-4 sm:grid-cols-[120px_minmax(0,1fr)]">
                                    @if($edicao->capa_path)
                                        <a href="{{ route('estudio.midia', [$edicao, 'capa']) }}?baixar=1" title="Baixar capa"><img src="{{ route('estudio.midia', [$edicao, 'capa']) }}" class="w-full rounded-lg ring-1 ring-white/10" alt="Capa"></a>
                                    @endif
                                    <div class="rounded-xl border border-ink-600 bg-ink-900/50 p-3" x-data="{ copiado: false }">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-[12.5px] font-semibold text-slate-300">Legenda do post</h3>
                                            <button type="button" @click="navigator.clipboard.writeText($refs.legenda.innerText); copiado = true; setTimeout(() => copiado = false, 1500)" class="text-[11.5px] text-brand-400 hover:text-brand-300" x-text="copiado ? 'Copiado!' : 'Copiar'"></button>
                                        </div>
                                        <p x-ref="legenda" class="mt-1.5 whitespace-pre-line text-[12.5px] text-slate-300">{{ $edicao->legenda_post ?: '—' }}</p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('estudio.edicoes.card', $edicao) }}" class="rounded-xl border border-ink-600 bg-ink-900/50 p-4" x-data="{ modo: 'novo', projeto: @js($projetos->first()?->id) }">
                                    @csrf
                                    <h3 class="text-sm font-semibold text-slate-200">2. Mandar para o card e para a aprovação do cliente</h3>
                                    @if($edicao->task_id)
                                        <p class="mt-1 text-[12.5px] text-emerald-400">Já anexado ao card #{{ $edicao->task_id }}. <a class="underline" href="{{ route('tasks.show', $edicao->task_id) }}">Abrir</a></p>
                                    @endif
                                    <div class="mt-3 flex gap-4 text-[12.5px] text-slate-300">
                                        <label class="inline-flex items-center gap-1.5"><input type="radio" value="novo" x-model="modo" class="border-ink-500 bg-ink-900 text-brand-600"> Card novo</label>
                                        <label class="inline-flex items-center gap-1.5"><input type="radio" value="existente" x-model="modo" class="border-ink-500 bg-ink-900 text-brand-600"> Card que já existe</label>
                                    </div>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2" x-show="modo === 'novo'">
                                        <select name="project_id" x-model="projeto" :disabled="modo !== 'novo'" class="rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-[12.5px] text-slate-200">
                                            @foreach($projetos as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                                        </select>
                                        <select name="column_id" :disabled="modo !== 'novo'" class="rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-[12.5px] text-slate-200">
                                            @foreach($projetos as $p)
                                                @foreach($p->columns as $c)
                                                    <option value="{{ $c->id }}" x-show="projeto == {{ $p->id }}">{{ $c->name }}</option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                        <input name="titulo" value="{{ $edicao->titulo }}" :disabled="modo !== 'novo'" maxlength="255" class="rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-[12.5px] text-slate-200 sm:col-span-2">
                                    </div>
                                    <div class="mt-3" x-show="modo === 'existente'" x-cloak>
                                        <input name="task_id" type="number" :disabled="modo !== 'existente'" placeholder="Número do card (#)" class="w-48 rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-[12.5px] text-slate-200">
                                    </div>
                                    <label class="mt-3 flex items-center gap-2 text-[12.5px] text-slate-300"><input type="checkbox" name="incluir_capa" value="1" checked class="rounded border-ink-500 bg-ink-900 text-brand-600"> Anexar a capa também</label>
                                    <button class="mt-3 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500" @if($projetos->isEmpty()) disabled title="Este cliente não tem projetos" @endif>Anexar ao card</button>
                                </form>
                            @endif

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-600 pt-4">
                                <form method="POST" action="{{ route('estudio.edicoes.duplicar', $edicao) }}" class="flex items-center gap-2">
                                    @csrf
                                    <span class="text-[12.5px] text-slate-400">Mesmo material em outro estilo:</span>
                                    <select name="estilo" class="rounded-lg border border-ink-600 bg-ink-900 px-2 py-1.5 text-[12.5px] text-slate-200">
                                        @foreach(\App\Video\Estilos::todos() as $k => $e)<option value="{{ $k }}" @selected($k === $edicao->estilo)>{{ $e['nome'] }}</option>@endforeach
                                    </select>
                                    <button class="rounded-lg border border-ink-600 px-3 py-1.5 text-[12.5px] text-slate-300 hover:bg-ink-700" @disabled($edicao->limpo_em)>Refazer</button>
                                </form>
                                @unless($andando)
                                    <form method="POST" action="{{ route('estudio.edicoes.destroy', $edicao) }}" onsubmit="return confirm('Excluir esta edição, os arquivos e os vídeos gerados?')">
                                        @csrf @method('DELETE')
                                        <button class="text-[12px] text-slate-500 hover:text-rose-400">Excluir edição</button>
                                    </form>
                                @endunless
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        @endif
    </div>

    <script>
        window.cabecalhosJson = () => ({
            'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        });

        /* Upload em pedaços, retomável. Um arquivo por vez para não sufocar o servidor nem o 4G. */
        window.uploaderEstudio = (cfg) => ({
            arquivos: cfg.arquivos.map(a => ({ ...a, chave: 'a' + a.id, progresso: 100 })),
            antesDepois: cfg.antesDepois,
            arrastando: false,
            enviando: false,
            salvo: '',
            fila: [],

            init() {
                window.addEventListener('beforeunload', (e) => { if (this.enviando) { e.preventDefault(); e.returnValue = ''; } });
                // O SortableJS vem de CDN com "defer" e pode chegar depois do Alpine: espera por ele.
                const ligar = (tentativas) => {
                    if (typeof Sortable === 'undefined') {
                        if (tentativas > 0) setTimeout(() => ligar(tentativas - 1), 250);
                        return;
                    }
                    Sortable.create(this.$refs.lista, {
                        handle: '.cursor-grab', draggable: 'li', animation: 150,
                        onEnd: (evt) => {
                            const de = evt.oldDraggableIndex, para = evt.newDraggableIndex;
                            if (de === para || de === undefined) return;
                            // Devolve o item ao lugar no DOM e deixa o Alpine redesenhar pela lista (senão os dois brigam).
                            const lis = [...evt.from.querySelectorAll(':scope > li')].filter(li => li !== evt.item);
                            evt.from.insertBefore(evt.item, lis[de] || null);
                            this.mover(de, para);
                        },
                    });
                };
                this.$nextTick(() => ligar(40));
            },

            mover(de, para) {
                if (para < 0 || para >= this.arquivos.length || de === para) return;
                const [item] = this.arquivos.splice(de, 1);
                this.arquivos.splice(para, 0, item);
                this.salvarOrdem();
            },

            ordenarPor(campo) {
                const nome = (a) => (a.nome || '').toString();
                this.arquivos.sort((a, b) => {
                    if (campo === 'gravado_em') {
                        if (a.gravado_em && b.gravado_em) return a.gravado_em < b.gravado_em ? -1 : (a.gravado_em > b.gravado_em ? 1 : 0);
                        if (a.gravado_em || b.gravado_em) return a.gravado_em ? -1 : 1;
                    }
                    return nome(a).localeCompare(nome(b), 'pt-BR', { numeric: true, sensitivity: 'base' });
                });
                if (campo === 'gravado_em' && !this.arquivos.some(a => a.gravado_em)) {
                    this.salvo = 'Os arquivos não têm hora de gravação: ordenei pelo nome.';
                }
                this.salvarOrdem();
            },

            salvarOrdem() {
                const ids = this.arquivos.map(a => a.id).filter(Boolean);
                if (ids.length) this.salvar({ ordem: ids });
            },

            resumo() {
                const prontos = this.arquivos.filter(a => a.status === 'pronto');
                const seg = prontos.reduce((s, a) => s + (parseFloat(a.duracao) || 0), 0);
                return prontos.length ? `${prontos.length} arquivo(s) · ${Math.floor(seg / 60)}:${String(Math.round(seg % 60)).padStart(2, '0')} de vídeo` : '';
            },

            detalhe(a) {
                const mb = (a.tamanho / 1048576).toFixed(a.tamanho > 1e8 ? 0 : 1) + ' MB';
                if (a.status === 'fila') return 'Na fila · ' + mb;
                if (a.status === 'enviando') return `Enviando ${a.progresso || 0}% · ${mb}` + (a.tentativa ? ` · reconectando (${a.tentativa})` : '');
                if (a.status === 'erro') return a.mensagem || 'Falhou';
                const partes = [a.tipo === 'imagem' ? 'foto' : (a.duracao ? Math.floor(a.duracao / 60) + ':' + String(Math.round(a.duracao % 60)).padStart(2, '0') : ''), a.resolucao, mb];
                if (a.hdr) partes.push('HDR');
                if (a.tipo === 'video' && !a.tem_audio) partes.push('sem som');
                return partes.filter(Boolean).join(' · ');
            },

            adicionar(lista) {
                for (const file of lista) {
                    const item = { chave: 'n' + Math.random().toString(36).slice(2), nome: file.name, tamanho: file.size, status: 'fila', progresso: 0, papel: 'bruto', file };
                    this.arquivos.push(item);
                    this.fila.push(item);
                }
                this.processar();
            },

            async processar() {
                if (this.enviando) return;
                this.enviando = true;
                while (this.fila.length) {
                    const item = this.fila.shift();
                    const atual = this.arquivos.find(a => a.chave === item.chave);
                    if (atual) await this.enviar(atual);
                }
                this.enviando = false;
            },

            async enviar(a) {
                try {
                    a.status = 'enviando';
                    const ini = await this.json(cfg.urlIniciar, { nome: a.nome, tamanho: a.tamanho });
                    a.id = ini.id;
                    let offset = ini.recebido;
                    while (offset < a.tamanho) {
                        const form = new FormData();
                        form.append('offset', offset);
                        form.append('pedaco', a.file.slice(offset, Math.min(offset + ini.pedaco, a.tamanho)), 'pedaco');
                        let resp;
                        for (let tentativa = 0; ; tentativa++) {
                            try {
                                resp = await fetch(ini.url_pedaco, { method: 'POST', body: form, headers: { 'X-CSRF-TOKEN': cabecalhosJson()['X-CSRF-TOKEN'], 'Accept': 'application/json' } });
                                if (resp.ok || resp.status === 409 || resp.status === 422) break;
                                throw new Error('HTTP ' + resp.status);
                            } catch (e) {
                                if (tentativa >= 6) throw new Error('A conexão caiu várias vezes. Tente de novo.');
                                a.tentativa = tentativa + 1;
                                await new Promise(r => setTimeout(r, Math.min(15000, 1000 * 2 ** tentativa)));
                            }
                        }
                        a.tentativa = 0;
                        const dados = await resp.json();
                        if (resp.status === 422) throw new Error(dados.message || 'Recusado pelo servidor.');
                        offset = dados.recebido;
                        a.progresso = Math.floor(offset / a.tamanho * 100);
                    }
                    const fim = await this.json(ini.url_concluir, {});
                    Object.assign(a, fim.arquivo, { progresso: 100, file: null });
                } catch (e) {
                    a.status = 'erro';
                    a.mensagem = e.message;
                }
            },

            async json(url, corpo, metodo = 'POST') {
                const r = await fetch(url, { method: metodo, headers: cabecalhosJson(), body: JSON.stringify(corpo) });
                const d = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(d.message || ('Erro ' + r.status));
                return d;
            },

            async mudarPapel(a) { await this.json(a.url_papel, { papel: a.papel }, 'PATCH').catch(e => alert(e.message)); },

            async remover(a) {
                if (a.status === 'enviando') { alert('Espere este arquivo terminar de subir.'); return; }
                if (a.url_remover) await this.json(a.url_remover, {}, 'DELETE').catch(() => null);
                this.fila = this.fila.filter(f => f.chave !== a.chave);
                this.arquivos = this.arquivos.filter(x => x.chave !== a.chave);
            },

            async salvar(dados) {
                this.salvo = 'salvando…';
                try { await this.json(cfg.urlEdicao, dados, 'PATCH'); this.salvo = 'salvo ✓'; }
                catch (e) { this.salvo = 'erro ao salvar'; }
            },
        });

        /* Acompanha a edição no servidor: progresso, mensagens novas e recarrega quando termina. */
        window.acompanharEdicao = (cfg) => ({
            andando: cfg.andando, status: cfg.status, progresso: 0, mensagem: '', etapa: null, ultimo: cfg.ultimo,

            init() {
                this.rolar();
                if (this.andando) this.consultar();
            },

            async consultar() {
                try {
                    const r = await fetch(cfg.url + '?desde=' + this.ultimo, { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    this.progresso = d.progresso; this.mensagem = d.mensagem;
                    if (d.eventos.length) {
                        const caixa = document.getElementById('conversa');
                        d.eventos.forEach(html => caixa.insertAdjacentHTML('beforeend', html));
                        this.ultimo = d.ultimo_evento;
                        this.rolar();
                    }
                    if (!d.em_andamento && this.andando) { window.location.reload(); return; }
                    this.andando = d.em_andamento;
                } catch (e) { /* rede oscilando: tenta de novo */ }
                setTimeout(() => this.consultar(), document.hidden ? 10000 : 3000);
            },

            rolar() { this.$nextTick(() => { const c = document.getElementById('conversa'); if (c) c.scrollTop = c.scrollHeight; }); },

            pular(t) { const v = this.$refs.video; if (v) { v.currentTime = t; v.play(); } },

            etapaIndice(lista) {
                const faixas = { preparar: 0, transcrever: 30, decidir: 42, previa: 70, ajustar: 0, final: 0 };
                let atual = 0;
                lista.forEach((k, i) => { if (this.progresso >= (faixas[k] ?? 0)) atual = i; });
                return atual;
            },
            etapaClasse(chave, lista) { const i = lista.indexOf(chave), a = this.etapaIndice(lista); return i < a ? 'text-emerald-400' : (i === a ? 'text-slate-100 font-semibold' : 'text-slate-500'); },
            etapaMarca(chave, lista) { const i = lista.indexOf(chave), a = this.etapaIndice(lista); return i < a ? '✓' : (i === a ? '•' : ''); },
        });
    </script>
</x-app-layout>
