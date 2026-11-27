{{-- Ícone de linha de cada estilo de edição. Uso: @include('estudio.partials.estilo-icone', ['icone' => 'espaco', 'classe' => 'h-6 w-6']) --}}
@php $classe = $classe ?? 'h-6 w-6'; @endphp
<svg class="{{ $classe }}" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($icone)
        @case('espaco')
            <path d="M3 21h18M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6"/><path d="M19 3l.6 1.4L21 5l-1.4.6L19 7l-.6-1.4L17 5l1.4-.6z"/>
            @break
        @case('legenda')
            <rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M9.5 15.5h5M9 18h6"/><circle cx="12" cy="9" r="2.2"/>
            @break
        @case('apresentador')
            <circle cx="12" cy="9" r="3.2"/><path d="M6.5 20a5.5 5.5 0 0111 0"/><path d="M3 5l1.5 1.5M21 5l-1.5 1.5M4 12H2.5M21.5 12H20"/><path d="M17.5 2.5l.5 1.2 1.2.5-1.2.5-.5 1.2-.5-1.2-1.2-.5 1.2-.5z"/>
            @break
        @case('antes_depois')
            <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M12 4v16"/><path d="M7 10l-1.5 2L7 14M17 10l1.5 2L17 14"/>
            @break
        @case('promo')
            <path d="M3 12V4a1 1 0 011-1h8l9 9-9 9-9-9z"/><circle cx="7.5" cy="7.5" r="1.5"/><path d="M11 14l4-4"/>
            @break
        @case('depoimento')
            <path d="M21 12a8 8 0 01-11.6 7.1L4 20.5l1.4-4.8A8 8 0 1121 12z"/><path d="M12 8.2l1 2.1 2.3.3-1.7 1.6.4 2.3-2-1.1-2 1.1.4-2.3-1.7-1.6 2.3-.3z"/>
            @break
        @case('vlog')
            <rect x="2.5" y="6" width="13" height="11" rx="2"/><path d="M15.5 10.5l5-3v9l-5-3"/><circle cx="6.5" cy="9.5" r="1"/><path d="M4 21h9"/>
            @break
        @default
            <path d="M15 10l4.5-2.3A1 1 0 0121 8.6v6.8a1 1 0 01-1.5.9L15 14"/><rect x="3" y="6" width="12" height="12" rx="2"/>
    @endswitch
</svg>
