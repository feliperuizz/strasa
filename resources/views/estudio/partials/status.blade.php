<span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide {{ $edicao->statusCor() }}">
    @if($edicao->emAndamento())
        <span class="relative flex h-1.5 w-1.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-current opacity-60"></span><span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-current"></span></span>
    @endif
    {{ $edicao->statusLabel() }}
</span>
