{{--
    Excluir uma edição em qualquer estado (inclusive travada ou processando).
    $edicao; $icone = true para só a lixeira (cartão da lista).
--}}
@php $icone ??= false; @endphp
<div x-data="{ aberto: false }" class="relative" @click.stop>
    <button type="button" @click.prevent="aberto = true"
            class="{{ $icone ? 'grid h-7 w-7 place-items-center rounded-lg bg-ink-900/90 text-slate-400 ring-1 ring-white/10 hover:text-rose-400' : 'rounded-lg px-2 py-1 text-[12px] text-slate-500 hover:bg-ink-800 hover:text-rose-400' }}"
            title="Excluir edição">
        @if($icone)
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/></svg>
        @else
            Excluir
        @endif
    </button>
    <div x-show="aberto" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4" @click.self="aberto = false" @keydown.escape.window="aberto = false">
        <form method="POST" action="{{ route('estudio.edicoes.destroy', $edicao) }}" class="w-full max-w-sm rounded-2xl border border-ink-600 bg-ink-800 p-5 text-left shadow-2xl">
            @csrf @method('DELETE')
            <h3 class="text-sm font-semibold text-slate-100">Excluir “{{ \Illuminate\Support\Str::limit($edicao->titulo, 60) }}”?</h3>
            <p class="mt-2 text-[12.5px] text-slate-400">Apaga do servidor os arquivos enviados, as prévias e o vídeo final. Não dá para desfazer.
                @if($edicao->emAndamento())<span class="mt-1 block text-amber-300/90">Ela está processando agora: o processamento é interrompido.</span>@endif
            </p>
            @if($edicao->noDrive())
                <label class="mt-3 flex items-center gap-2 text-[12.5px] text-slate-300">
                    <input type="checkbox" name="apagar_drive" value="1" class="rounded border-ink-500 bg-ink-900 text-rose-600">
                    Apagar também a cópia do Google Drive
                </label>
            @endif
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="aberto = false" class="rounded-lg px-3 py-1.5 text-[13px] text-slate-300 hover:bg-ink-700">Cancelar</button>
                <button class="rounded-lg bg-rose-600 px-3 py-1.5 text-[13px] font-semibold text-white hover:bg-rose-500">Excluir</button>
            </div>
        </form>
    </div>
</div>
