@props(['network'])

@php
    $map = [
        'instagram' => ['IG', '#e1306c'],
        'facebook'  => ['FB', '#1877f2'],
        'linkedin'  => ['IN', '#0a66c2'],
        'tiktok'    => ['TT', '#000000'],
        'youtube'   => ['YT', '#ff0000'],
        'x'         => ['X',  '#1d1d1d'],
        'blog'      => ['BL', '#10b981'],
    ];
    [$label, $color] = $map[$network] ?? [strtoupper(substr($network,0,2)), '#64748b'];
@endphp

<span title="{{ $network }}"
      class="grid h-5 w-5 place-items-center rounded text-[9px] font-bold text-slate-200"
      style="background: {{ $color }}">{{ $label }}</span>
