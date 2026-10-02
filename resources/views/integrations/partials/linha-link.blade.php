{{-- Uma linha "rótulo + link + Copiar" do bloco "O que mandar para o parceiro". --}}
<div class="flex min-w-0 items-center gap-2 rounded-lg border border-ink-700 bg-ink-900/60 px-3 py-2">
    <div class="min-w-0 flex-1">
        <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $rotulo }}</dt>
        <dd class="truncate font-mono text-[12.5px] text-slate-200">
            @if($abrir ?? true)
                <a id="{{ $id }}" href="{{ $link }}" target="_blank" rel="noopener" class="hover:text-brand-300 hover:underline">{{ $link }}</a>
            @else
                <span id="{{ $id }}">{{ $link }}</span>
            @endif
        </dd>
    </div>
    <button type="button" data-copiar="{{ $link }}" data-selecionar="{{ $id }}" onclick="copiarTexto(this)"
            class="shrink-0 rounded-md px-2 py-1 text-[11px] font-medium text-slate-400 hover:bg-ink-700 hover:text-slate-200">Copiar</button>
</div>
