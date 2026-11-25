{{-- Faixa de saúde do motor: o que está ligado no servidor. --}}
<div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 rounded-xl border border-ink-600 bg-ink-800/60 px-4 py-2.5 text-[12px]">
    <span class="font-semibold text-slate-300">Motor</span>
    <span class="inline-flex items-center gap-1.5 {{ $motor['claude'] ? 'text-emerald-400' : 'text-rose-400' }}">
        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
        {{ $motor['claude'] ? 'Claude conectado' : 'Claude NÃO conectado — rode scripts/video/logar-claude.sh' }}
    </span>
    <span class="inline-flex items-center gap-1.5 {{ $motor['transcricao'] ? 'text-emerald-400' : 'text-amber-400' }}">
        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
        {{ $motor['transcricao'] ? 'Legenda automática' : 'Sem transcrição (sem legenda)' }}
    </span>
    @if($motor['disco'] !== null)
        <span class="inline-flex items-center gap-1.5 {{ $motor['disco'] < config('video.limites.disco_minimo_gb') ? 'text-rose-400' : ($motor['disco'] < 20 ? 'text-amber-400' : 'text-slate-400') }}">
            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
            {{ number_format($motor['disco'], 1, ',', '.') }} GB livres
        </span>
    @endif
    @if(auth()->user()->isManager())
        <form method="POST" action="{{ route('estudio.diagnostico') }}" class="ml-auto">
            @csrf
            <button class="text-slate-400 underline-offset-2 hover:text-brand-400 hover:underline">Rodar diagnóstico</button>
        </form>
    @endif
</div>
