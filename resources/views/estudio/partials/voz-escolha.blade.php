{{--
    Escolha da voz da narração, com botão para ouvir a amostra.
    $nome     nome do campo no formulário (null = sem campo, só Alpine)
    $valor    voz atual (ou null)
    $vazio    rótulo da opção "sem escolha" (null = sem essa opção)
    $aoMudar  expressão Alpine chamada com a voz escolhida em `voz` (salvar sozinho)
--}}
@php
    $vozes = \App\Video\Narracao::disponiveis();
    $nome ??= null;
    $vazio ??= null;
    $aoMudar ??= null;
@endphp

@if(! $vozes)
    <p class="text-[11.5px] text-amber-300/80">A voz gerada ainda não está instalada no servidor (rode o instalador do estúdio de novo).</p>
@else
    <div class="flex items-center gap-2" x-data="{
            voz: @js($valor ?? ''),
            tocando: false,
            ouvir() {
                const id = this.voz || @js($vazio ? array_key_first($vozes) : '');
                if (! id) return;
                const a = this.$refs.amostra;
                if (this.tocando) { a.pause(); this.tocando = false; return; }
                a.src = @js(url('estudio/vozes')) + '/' + id + '/amostra';
                a.play().then(() => this.tocando = true).catch(() => alert('Amostra ainda não gerada. Rode: php artisan video:vozes'));
            },
         }">
        <select @if($nome) name="{{ $nome }}" @endif x-model="voz" @change="@if($aoMudar) {{ $aoMudar }} @endif"
                class="min-w-0 flex-1 rounded-lg border border-ink-600 bg-ink-900 px-2 py-1.5 text-[12.5px] text-slate-200">
            @if($vazio)<option value="">{{ $vazio }}</option>@endif
            @foreach($vozes as $id => $v)
                <option value="{{ $id }}">{{ $v['nome'] }} — {{ $v['descricao'] }}</option>
            @endforeach
        </select>
        <button type="button" @click="ouvir()" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-ink-600 px-2.5 py-1.5 text-[12px] text-slate-300 hover:border-brand-500 hover:text-white" title="Ouvir a voz">
            <span x-text="tocando ? '■' : '▶'"></span> Ouvir
        </button>
        <audio x-ref="amostra" @ended="tocando = false" preload="none" class="hidden"></audio>
    </div>
@endif
