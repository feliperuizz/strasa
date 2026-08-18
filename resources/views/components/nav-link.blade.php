@props(['active' => false])

<a {{ $attributes->merge(['class' =>
    'flex items-center gap-2 rounded-md px-3 py-2 font-medium transition '.
    ($active
        ? 'bg-ink-700 text-white'
        : 'text-slate-300 hover:bg-ink-700 hover:text-white')
]) }}>
    {{ $slot }}
</a>
