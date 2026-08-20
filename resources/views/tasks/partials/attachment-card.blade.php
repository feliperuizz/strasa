<div class="group relative flex items-center gap-3 rounded-lg border border-ink-800 bg-ink-800 p-2">
    @if($att->is_image)
        <img src="{{ Storage::disk($att->disk)->url($att->path) }}" data-url="{{ Storage::disk($att->disk)->url($att->path) }}" data-download-url="{{ route('attachments.download', $att) }}" class="h-10 w-10 rounded object-cover viewer-image cursor-pointer" alt="{{ $att->original_name }}">
    @else
        <div class="flex h-10 w-10 items-center justify-center rounded bg-ink-900">
            <span class="text-[10px] font-bold text-slate-500">{{ strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION)) }}</span>
        </div>
    @endif
    <div class="flex-1 overflow-hidden">
        @if($att->is_image)
            <span class="cursor-pointer truncate text-xs font-medium text-slate-200 hover:text-slate-200 hover:underline block" onclick="this.closest('.group').querySelector('img').click()">{{ $att->original_name }}</span>
        @else
            <a href="{{ Storage::disk($att->disk)->url($att->path) }}" target="_blank" class="truncate text-xs font-medium text-slate-200 hover:text-slate-200 hover:underline block">{{ $att->original_name }}</a>
        @endif
    </div>
    <button type="button" @click="deleteAttachment('{{ route('attachments.destroy', $att) }}', $event.target.closest('.group'))" class="absolute -right-2 -top-2 hidden rounded-full bg-rose-600 p-1 text-white hover:bg-rose-500 group-hover:block" title="Excluir">
        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
    </button>
</div>
