@props(['title' => null])

<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    {{-- CSS compilado (Vite). As variáveis abaixo reproduzem exatamente a paleta
         que antes era passada para o tailwind.config do CDN. --}}
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --ink-900: 15 17 21;    /* #0f1115 */
            --ink-800: 22 26 33;    /* #161a21 */
            --ink-700: 29 34 43;    /* #1d222b */
            --ink-600: 39 45 57;    /* #272d39 */
            --ink-500: 107 107 107;
            --text-primary: 226 232 240;   /* slate-200 */
            --text-secondary: 203 213 225; /* slate-300 */
            --text-tertiary: 148 163 184;  /* slate-400 */
        }
    </style>
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
