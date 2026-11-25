{{-- Uma edição na lista (estúdio do cliente e painel geral). --}}
@php $info = $edicao->estiloInfo(); @endphp
<a href="{{ route('estudio.edicoes.show', $edicao) }}"
   class="group flex gap-3 rounded-xl border border-ink-600 bg-ink-800/70 p-3 transition hover:border-ink-500 hover:bg-ink-800">
    <div class="relative grid h-[84px] w-[48px] shrink-0 place-items-center overflow-hidden rounded-lg bg-ink-900 ring-1 ring-white/5" style="color: {{ $info['cor'] }}">
        @if($edicao->capa_path)
            <img src="{{ route('estudio.midia', [$edicao, 'capa']) }}" class="absolute inset-0 h-full w-full object-cover" alt="" loading="lazy">
        @else
            @include('estudio.partials.estilo-icone', ['icone' => $info['icone'], 'classe' => 'h-6 w-6'])
        @endif
        @if($edicao->emAndamento())
            <span class="absolute inset-x-0 bottom-0 h-1 bg-ink-700"><span class="block h-full bg-brand-500" style="width: {{ $edicao->progresso }}%"></span></span>
        @endif
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2">
            <p class="truncate text-sm font-semibold text-slate-200 group-hover:text-white">{{ $edicao->titulo }}</p>
            @include('estudio.partials.status', ['edicao' => $edicao])
        </div>
        <p class="mt-0.5 text-[12px]" style="color: {{ $info['cor'] }}">{{ $info['nome'] }}
            @isset($mostrarCliente)<span class="text-slate-500">· {{ $edicao->client?->name }}</span>@endisset
        </p>
        <p class="mt-1 truncate text-[11.5px] text-slate-500">
            @if($edicao->emAndamento() && $edicao->mensagem)
                {{ $edicao->mensagem }} · {{ $edicao->progresso }}%
            @else
                {{ $edicao->previa_versao ? 'prévia v'.$edicao->previa_versao : 'sem prévia' }}{{ $edicao->final_versao ? ' · final v'.$edicao->final_versao : '' }}
                · {{ $edicao->updated_at?->diffForHumans() }}{{ $edicao->creator ? ' · '.$edicao->creator->name : '' }}
            @endif
        </p>
    </div>
</a>
