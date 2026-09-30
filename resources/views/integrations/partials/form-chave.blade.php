{{--
    Formulário de chave (criar e editar). $chave = null ao criar.
    Os valores antigos (old) só valem para o formulário de criação, para um
    erro ao criar não sujar os formulários de edição das outras chaves.
--}}
@php
    $criando = $chave === null;
    $valor = fn (string $campo, $padrao) => $criando ? old($campo, $padrao) : $padrao;
    $escopo = $valor('escopo', $chave && ! $chave->all_clients ? 'selecionados' : 'todos');
    $selecionados = collect($valor('clientes', $chave ? $chave->clients->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
@endphp
<form method="POST" action="{{ $acao }}" x-data="{ escopo: '{{ $escopo }}' }" class="space-y-4">
    @csrf
    @if($metodo !== 'POST')
        @method($metodo)
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="mb-1 block text-xs font-semibold text-slate-300">Nome da integração</span>
            <input type="text" name="nome" required maxlength="100" value="{{ $valor('nome', $chave?->name) }}" placeholder="Ex.: Postador Automático — Empresa X"
                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:outline-none">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-semibold text-slate-300">Validade <span class="font-normal text-slate-500">(opcional)</span></span>
            <input type="date" name="expira_em" value="{{ $valor('expira_em', $chave?->expires_at?->format('Y-m-d')) }}"
                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none [color-scheme:dark]">
        </label>
    </div>

    <fieldset>
        <legend class="mb-1.5 text-xs font-semibold text-slate-300">Quais clientes esta chave pode ver</legend>
        <div class="flex flex-wrap gap-2">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition"
                   :class="escopo === 'todos' ? 'border-brand-500 bg-brand-500/10 text-slate-100' : 'border-ink-600 text-slate-400'">
                <input type="radio" name="escopo" value="todos" x-model="escopo" class="text-brand-500 focus:ring-brand-500"> Todos os clientes
            </label>
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition"
                   :class="escopo === 'selecionados' ? 'border-brand-500 bg-brand-500/10 text-slate-100' : 'border-ink-600 text-slate-400'">
                <input type="radio" name="escopo" value="selecionados" x-model="escopo" class="text-brand-500 focus:ring-brand-500"> Só os escolhidos
            </label>
        </div>
        <div x-show="escopo === 'selecionados'" x-cloak class="mt-3 grid max-h-56 gap-1.5 overflow-y-auto rounded-lg border border-ink-700 bg-ink-900/60 p-3 sm:grid-cols-2">
            @forelse($clientes as $c)
                <label class="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-sm text-slate-300 hover:bg-ink-800">
                    <input type="checkbox" name="clientes[]" value="{{ $c->id }}" @checked(in_array($c->id, $selecionados, true))
                           class="rounded border-ink-600 bg-ink-900 text-brand-500 focus:ring-brand-500">
                    <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $c->color ?? '#64748b' }}"></span>
                    <span class="min-w-0">{{ $c->name }}</span>
                </label>
            @empty
                <p class="text-xs text-slate-500">Nenhum cliente ativo.</p>
            @endforelse
        </div>
    </fieldset>

    <label class="block">
        <span class="mb-1 block text-xs font-semibold text-slate-300">IPs permitidos <span class="font-normal text-slate-500">(opcional — deixe vazio para aceitar qualquer IP)</span></span>
        <textarea name="ips" rows="2" placeholder="Ex.: 203.0.113.10, 198.51.100.0/24"
                  class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 font-mono text-[13px] text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:outline-none">{{ $valor('ips', $chave?->allowed_ips) }}</textarea>
        <span class="mt-1 block text-[11.5px] text-slate-500">Se o parceiro informar os IPs fixos dos servidores dele, coloque aqui: mesmo que a chave vaze, ninguém de fora consegue usar.</span>
    </label>

    <label class="flex cursor-pointer items-start gap-2 text-sm text-slate-300">
        <input type="hidden" name="concluir_ao_publicar" value="0">
        <input type="checkbox" name="concluir_ao_publicar" value="1" @checked((bool) $valor('concluir_ao_publicar', $chave ? $chave->auto_complete : true))
               class="mt-0.5 rounded border-ink-600 bg-ink-900 text-brand-500 focus:ring-brand-500">
        <span>Quando o parceiro avisar que publicou, marcar o card como publicado e mover para a coluna de concluído</span>
    </label>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">{{ $botao }}</button>
        @if($criando)
            <span class="text-[11.5px] text-slate-500">A chave aparece uma única vez, logo depois de criar.</span>
        @endif
    </div>
</form>
