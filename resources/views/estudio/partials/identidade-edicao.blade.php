{{--
    Logo e identidade SÓ deste vídeo (a do cliente continua igual).
    $modo = 'autosave' (rascunho: salva sozinho via salvar() do uploader)
          | 'form'     (aba Plano: vai junto no formulário, campos identidade[...])
    Campo vazio = vale o da identidade do cliente.
--}}
@php
    $ajustes = (array) $edicao->opcao('identidade', []);
    $comLogo = (bool) $edicao->opcao('logo', true);
    $cores = ['cor_destaque' => 'Destaque', 'cor_primaria' => 'Primária', 'cor_texto' => 'Texto', 'cor_fundo' => 'Fundo'];
    $sel = 'w-full rounded-lg border border-ink-600 bg-ink-900 px-2 py-1.5 text-[12.5px] text-slate-200';
    $form = $modo === 'form';
@endphp

<div class="rounded-xl border border-ink-600 bg-ink-900/40 p-3"
     x-data="{
        v: @js((object) $ajustes),
        logo: @js($comLogo),
        aberto: @js((bool) $ajustes),
        padrao: @js(['cor_destaque' => $perfil->cor_destaque, 'cor_primaria' => $perfil->cor_primaria, 'cor_texto' => $perfil->cor_texto, 'cor_fundo' => $perfil->cor_fundo]),
        mudou() { @if(! $form) salvar({ identidade: this.v, opcoes: { logo: this.logo } }); @endif },
     }">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <label class="inline-flex items-center gap-2 text-[13px] text-slate-200">
            <input type="checkbox" @if($form) name="logo" value="1" @endif x-model="logo" @change="mudou()" class="rounded border-ink-500 bg-ink-900 text-brand-600">
            Logo no vídeo
            <span class="text-[11.5px] text-slate-500" x-show="! logo">— sem logo no canto nem no cartão final</span>
        </label>
        <button type="button" @click="aberto = ! aberto" class="text-[12px] text-brand-400 hover:text-brand-300">
            <span x-text="aberto ? 'Fechar' : 'Mudar a identidade só neste vídeo'"></span>
            <span x-show="Object.keys(v).length && ! aberto" class="ml-1 rounded-full bg-brand-500/20 px-1.5 text-[10.5px] text-brand-300" x-text="Object.keys(v).length + ' mudança(s)'"></span>
        </button>
    </div>

    <div x-show="aberto" x-cloak class="mt-3 space-y-3 border-t border-ink-600 pt-3">
        <p class="text-[11.5px] text-slate-500">Vale só para este vídeo. Em branco = igual à identidade do cliente.
            <a href="{{ route('clients.estudio.perfil', $edicao->client) }}" target="_blank" class="text-brand-400 hover:text-brand-300">Mudar a identidade do cliente para sempre →</a></p>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach($cores as $campo => $rotulo)
                <div>
                    <span class="mb-1 block text-[11px] text-slate-400">{{ $rotulo }}</span>
                    <div class="flex items-center gap-1.5 rounded-lg border border-ink-600 bg-ink-900 p-1">
                        <input type="color" :value="v.{{ $campo }} || padrao.{{ $campo }}" @change="v.{{ $campo }} = $event.target.value.toUpperCase(); mudou()" class="h-7 w-8 cursor-pointer rounded border-0 bg-transparent p-0">
                        <span class="flex-1 font-mono text-[11px]" :class="v.{{ $campo }} ? 'text-slate-200' : 'text-slate-500'" x-text="v.{{ $campo }} || 'do cliente'"></span>
                        <button type="button" x-show="v.{{ $campo }}" @click="delete v.{{ $campo }}; mudou()" class="px-1 text-slate-500 hover:text-rose-400" title="Voltar ao do cliente">↺</button>
                        @if($form)<input type="hidden" name="identidade[{{ $campo }}]" :value="v.{{ $campo }} || ''">@endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            @php
                $selects = [
                    'fonte_titulo' => ['Fonte', array_combine(\App\Models\VideoProfile::FONTES, \App\Models\VideoProfile::FONTES)],
                    'legenda_estilo' => ['Estilo da legenda', collect(\App\Models\VideoProfile::ESTILOS_LEGENDA)->map(fn ($e) => $e['nome'])->all()],
                    'legenda_posicao' => ['Posição da legenda', ['baixo' => 'Embaixo', 'centro' => 'Centro', 'alto' => 'Em cima']],
                    'legenda_caixa_alta' => ['Caixa alta', ['1' => 'Sim', '0' => 'Não']],
                    'logo_posicao' => ['Posição da logo', \App\Models\VideoProfile::POSICOES_LOGO],
                    'cor_grade' => ['Cor da imagem', \App\Models\VideoProfile::GRADES],
                ];
            @endphp
            @foreach($selects as $campo => [$rotulo, $opcoes])
                <label class="block">
                    <span class="mb-1 block text-[11px] text-slate-400">{{ $rotulo }}</span>
                    <select @if($form) name="identidade[{{ $campo }}]" @endif x-model="v.{{ $campo }}" @change="if (! v.{{ $campo }}) delete v.{{ $campo }}; mudou()" class="{{ $sel }}">
                        <option value="">Do cliente</option>
                        @foreach($opcoes as $valor => $nome)<option value="{{ $valor }}">{{ $nome }}</option>@endforeach
                    </select>
                </label>
            @endforeach
        </div>
    </div>
</div>
