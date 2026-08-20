@props(['user' => null, 'size' => 8])

@if($user)
    @if($user->avatar_url)
        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" title="{{ $user->name }}" class="h-{{ $size }} w-{{ $size }} rounded-full object-cover ring-2 ring-ink-800">
    @else
        <span title="{{ $user->name }}"
              class="grid place-items-center rounded-full font-semibold text-slate-200 ring-2 ring-ink-800 h-{{ $size }} w-{{ $size }} text-[11px]"
              style="background: {{ $user->avatar_color ?? '#6366f1' }}">
            {{ $user->initials() }}
        </span>
    @endif
@else
    <span class="grid place-items-center rounded-full bg-ink-600 text-slate-400 h-{{ $size }} w-{{ $size }} text-[11px] ring-2 ring-ink-800">—</span>
@endif
