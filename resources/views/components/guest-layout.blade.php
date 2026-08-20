@props(['title' => null])

<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: {
        ink:{900:'#0f1115',800:'#161a21',700:'#1d222b',600:'#272d39'},
        brand:{400:'#818cf8',500:'#6366f1',600:'#4f46e5'} } } } }</script>
</head>
<body class="h-full bg-ink-900 text-slate-200">
<div class="flex min-h-full items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center justify-center gap-2">
            <div class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 font-bold text-white">S</div>
            <span class="text-lg font-semibold text-slate-200">{{ config('app.name') }}</span>
        </div>
        <div class="rounded-2xl border border-ink-600 bg-ink-800 p-6 shadow-xl">
            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
