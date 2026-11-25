{{-- Uma linha da conversa/linha do tempo da edição. Também é renderizada pelo polling (EdicaoController@status). --}}
@php
    $dados = $evento->dados ?? [];
@endphp
@switch($evento->tipo)
    @case('pedido')
        <div class="flex justify-end">
            <div class="max-w-[85%] rounded-2xl rounded-br-md bg-brand-600/90 px-3.5 py-2 text-[13px] text-white shadow-sm">
                <p class="whitespace-pre-line break-words">{{ $evento->texto }}</p>
                <p class="mt-1 text-right text-[10.5px] text-white/60">{{ $evento->user?->name ?? 'Equipe' }} · {{ $evento->created_at?->format('d/m H:i') }}</p>
            </div>
        </div>
        @break
    @case('claude')
        <div class="flex gap-2.5">
            <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-orange-400 to-rose-500 text-[11px] font-bold text-white shadow">C</span>
            <div class="max-w-[88%] rounded-2xl rounded-tl-md border border-ink-600 bg-ink-900/70 px-3.5 py-2 text-[13px] text-slate-200">
                <p class="whitespace-pre-line break-words">{{ $evento->texto }}</p>
                @if(! empty($dados['avisos']))
                    <ul class="mt-2 space-y-1 border-t border-ink-600 pt-2 text-[12px] text-amber-300/90">
                        @foreach($dados['avisos'] as $aviso)
                            <li class="flex gap-1.5"><span>⚠</span><span>{{ $aviso }}</span></li>
                        @endforeach
                    </ul>
                @endif
                @if(! empty($dados['correcoes']))
                    <details class="mt-2 border-t border-ink-600 pt-1.5 text-[11.5px] text-slate-400">
                        <summary class="cursor-pointer select-none">O sistema ajustou {{ count($dados['correcoes']) }} detalhe(s) do plano</summary>
                        <ul class="mt-1 list-disc space-y-0.5 pl-4">
                            @foreach($dados['correcoes'] as $c)<li>{{ $c }}</li>@endforeach
                        </ul>
                    </details>
                @endif
                <p class="mt-1 text-[10.5px] text-slate-500">{{ $evento->created_at?->format('d/m H:i') }}</p>
            </div>
        </div>
        @break
    @case('versao')
        <div class="flex items-center gap-2 py-0.5 text-[12px]">
            <span class="h-px flex-1 bg-emerald-500/20"></span>
            <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 font-medium text-emerald-300">▶ {{ $evento->texto }}</span>
            <span class="h-px flex-1 bg-emerald-500/20"></span>
        </div>
        @if(! empty($dados['avisos']))
            <p class="text-center text-[11.5px] text-amber-300/80">⚠ {{ implode(' ', $dados['avisos']) }}</p>
        @endif
        @break
    @case('erro')
        <div class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-[12.5px] text-rose-300">
            <span class="font-semibold">Erro:</span> {{ $evento->texto }}
            <span class="ml-1 text-[10.5px] text-rose-400/60">{{ $evento->created_at?->format('d/m H:i') }}</span>
        </div>
        @break
    @default
        <p class="text-center text-[11.5px] text-slate-500">{{ $evento->texto }} <span class="text-slate-600">· {{ $evento->created_at?->format('d/m H:i') }}</span></p>
@endswitch
