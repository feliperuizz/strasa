@php
    /**
     * Bloco do painel de aprovação na página do cliente.
     *
     * Reúne o que a equipe precisa no dia a dia: o link, o código, a mensagem
     * pronta para copiar, quem recebe o push e as ações de trocar/revogar
     * a chave de acesso.
     */
    $portal = $client->portal;
@endphp

<div class="mb-8 rounded-xl border border-ink-600 bg-ink-800/85 backdrop-blur-md p-5 shadow-sm">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2.5">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-500/15 text-brand-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-slate-200">Painel de aprovação do cliente</h2>
                <p class="text-[11.5px] text-slate-400">
                    Link próprio, com a marca do cliente, para aprovar ou pedir ajuste nas peças.
                </p>
            </div>
        </div>

        @if($portal)
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide
                {{ $portal->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-400 border border-rose-500/25' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $portal->is_active ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                {{ $portal->is_active ? 'Ativo' : 'Revogado' }}
            </span>
        @endif
    </div>

    @if(! $portal)
        {{-- Ainda não existe: criar --}}
        <div class="rounded-lg border border-dashed border-ink-600 bg-ink-900/50 p-6 text-center">
            <p class="text-sm text-slate-400 mb-3">
                Este cliente ainda não tem painel. Ao criar, geramos o link e um código de acesso.
            </p>
            <form method="POST" action="{{ route('clients.portal.store', $client) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500 transition">
                    Criar painel de aprovação
                </button>
            </form>
        </div>
    @else
        <div x-data="portalCliente()" class="space-y-4">

            {{-- Link e código --}}
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-ink-600 bg-ink-900/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Link do painel</div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly x-ref="link" value="{{ $portal->url }}"
                               class="flex-1 min-w-0 rounded border-0 bg-transparent p-0 text-[12.5px] text-slate-300 focus:ring-0 truncate">
                        <button type="button" @click="copiar($refs.link.value, 'link')"
                                class="shrink-0 rounded bg-ink-700 px-2 py-1 text-[11px] font-semibold text-slate-300 hover:bg-ink-600 transition">
                            <span x-text="copiado === 'link' ? 'Copiado!' : 'Copiar'"></span>
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-ink-600 bg-ink-900/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Código de acesso</div>
                    <div class="flex items-center gap-2">
                        <span class="flex-1 font-mono text-[15px] font-bold tracking-wider text-slate-100">
                            {{ $portal->access_code }}
                        </span>
                        <button type="button" @click="copiar('{{ $portal->access_code }}', 'codigo')"
                                class="shrink-0 rounded bg-ink-700 px-2 py-1 text-[11px] font-semibold text-slate-300 hover:bg-ink-600 transition">
                            <span x-text="copiado === 'codigo' ? 'Copiado!' : 'Copiar'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mensagem pronta --}}
            <div class="rounded-lg border border-ink-600 bg-ink-900/60 p-3">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        Mensagem pronta para enviar
                    </div>
                    <button type="button" @click="copiar($refs.msg.value, 'msg')"
                            class="rounded bg-brand-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-brand-500 transition">
                        <span x-text="copiado === 'msg' ? '✓ Copiado!' : 'Copiar mensagem'"></span>
                    </button>
                </div>
                <textarea x-ref="msg" readonly rows="9"
                          class="w-full rounded border-ink-700 bg-ink-800 p-2.5 text-[12.5px] leading-relaxed text-slate-300 focus:border-brand-500 focus:ring-brand-500 resize-none">{{ $portal->shareMessage() }}</textarea>
            </div>

            {{-- Preferências --}}
            <form method="POST" action="{{ route('clients.portal.update', $client) }}"
                  class="rounded-lg border border-ink-600 bg-ink-900/60 p-3 space-y-3">
                @csrf
                @method('PATCH')

                <div>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="notify_enabled" value="1" @checked($portal->notify_enabled)
                               class="rounded border-ink-500 bg-ink-700 text-brand-500 focus:ring-brand-500">
                        <span class="text-[13px] font-medium text-slate-200">
                            Avisar por notificação quando o cliente responder
                        </span>
                    </label>
                    <p class="mt-1 ml-6 text-[11.5px] text-slate-500">
                        Chega no celular de quem estiver marcado abaixo, na hora em que o cliente aprova,
                        pede ajuste ou comenta.
                    </p>
                </div>

                <div class="ml-6">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">
                        Quem recebe
                    </div>
                    <div class="grid gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($equipe as $membro)
                            <label class="flex items-center gap-2 rounded border border-ink-700 bg-ink-800/60 px-2.5 py-1.5 cursor-pointer hover:border-ink-600">
                                <input type="checkbox" name="notify_user_ids[]" value="{{ $membro->id }}"
                                       @checked(in_array($membro->id, $portal->notify_user_ids ?? []))
                                       class="rounded border-ink-500 bg-ink-700 text-brand-500 focus:ring-brand-500">
                                <span class="text-[12.5px] text-slate-300 truncate">{{ $membro->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-[11.5px] text-slate-500">
                        A pessoa precisa ter ativado as notificações no aparelho dela, no menu do perfil.
                    </p>
                </div>

                <div class="ml-6">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                        Recado no topo do painel (opcional)
                    </label>
                    <input type="text" name="welcome_message" maxlength="500"
                           value="{{ $portal->welcome_message }}"
                           placeholder="Ex.: Peças da campanha de novembro. Retorno até sexta, por favor."
                           class="w-full rounded border-ink-700 bg-ink-800 px-3 py-2 text-[13px] text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-ink-700 px-3.5 py-1.5 text-[12.5px] font-semibold text-slate-200 hover:bg-ink-600 transition">
                        Salvar preferências
                    </button>
                </div>
            </form>

            {{-- Uso e chaves --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-700 pt-3">
                <div class="text-[11.5px] text-slate-500">
                    @if($portal->last_accessed_at)
                        Último acesso {{ $portal->last_accessed_at->diffForHumans() }}
                        · {{ $portal->access_count }} {{ $portal->access_count === 1 ? 'entrada' : 'entradas' }}
                    @else
                        O cliente ainda não entrou no painel.
                    @endif
                    @if($portal->code_updated_at)
                        · código gerado {{ $portal->code_updated_at->diffForHumans() }}
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('clients.portal.rotate', $client) }}"
                          onsubmit="return confirm('Gerar um código novo? O código atual deixa de funcionar imediatamente e o cliente precisará do novo.')">
                        @csrf
                        <button type="submit" class="rounded-lg border border-ink-600 px-3 py-1.5 text-[12px] font-semibold text-slate-300 hover:bg-ink-700 transition">
                            Trocar código
                        </button>
                    </form>

                    @if($portal->is_active)
                        <form method="POST" action="{{ route('clients.portal.revoke', $client) }}"
                              onsubmit="return confirm('Revogar o acesso? O link para de abrir até você reativar.')">
                            @csrf
                            <button type="submit" class="rounded-lg border border-rose-500/30 px-3 py-1.5 text-[12px] font-semibold text-rose-400 hover:bg-rose-500/10 transition">
                                Revogar acesso
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('clients.portal.reactivate', $client) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-emerald-500/30 px-3 py-1.5 text-[12px] font-semibold text-emerald-400 hover:bg-emerald-500/10 transition">
                                Reativar acesso
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <script>
            function portalCliente() {
                return {
                    copiado: null,

                    copiar(texto, qual) {
                        var marcar = () => {
                            this.copiado = qual;
                            setTimeout(() => { this.copiado = null; }, 2000);
                        };

                        // navigator.clipboard só existe em contexto seguro (https
                        // ou localhost); o fallback cobre acesso por http.
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(texto).then(marcar);
                            return;
                        }

                        var campo = document.createElement('textarea');
                        campo.value = texto;
                        campo.style.position = 'fixed';
                        campo.style.opacity = '0';
                        document.body.appendChild(campo);
                        campo.select();
                        try { document.execCommand('copy'); marcar(); } catch (e) {}
                        document.body.removeChild(campo);
                    },
                };
            }
        </script>
    @endif
</div>
