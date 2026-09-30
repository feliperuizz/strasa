<x-app-layout title="Integrações">
    <x-slot name="header">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-200 tracking-wide">Integrações</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-0.5">API de postagem automática: chaves de acesso, documentação e retornos do sistema parceiro.</p>
        </div>
    </x-slot>

    @php
        $mensagemParceiro = "Olá! Seguem os dados para integrar com o STRASA (API de postagem automática):\n\n"
            ."• Documentação interativa (Swagger): {$raiz}/api/docs\n"
            ."• Guia completo para a IA/desenvolvedor: {$raiz}/api/docs.md\n"
            ."• Especificação OpenAPI: {$raiz}/api/openapi.json\n"
            ."• URL base da API: {$raiz}/api/v1\n\n"
            ."A chave de acesso (str_...) vai em mensagem separada. Guardem só no servidor: ela não pode ser recuperada, só substituída.";
    @endphp

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">

        @if($errors->any())
            <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                <div class="font-semibold">Não deu para salvar:</div>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Chave recém-criada: aparece UMA vez --}}
        @if($chaveCriada)
            <section class="rounded-xl border-2 border-emerald-500/40 bg-emerald-500/[0.07] p-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-500/20 text-emerald-300">✓</span>
                    <h2 class="font-semibold text-emerald-200">
                        {{ !empty($chaveCriada['renovada']) ? 'Nova chave gerada para' : 'Chave criada:' }} {{ $chaveCriada['nome'] }}
                    </h2>
                </div>
                <p class="mt-2 text-sm text-slate-300">
                    <strong class="text-amber-300">Copie agora:</strong> por segurança ela não aparece de novo (o STRASA guarda só uma impressão digital dela).
                    @if(!empty($chaveCriada['renovada'])) A chave anterior já parou de funcionar. @endif
                </p>
                <div class="mt-3 flex flex-wrap items-stretch gap-2">
                    <code class="min-w-0 flex-1 select-all break-all rounded-lg border border-ink-600 bg-ink-900 px-3 py-2.5 font-mono text-[13px] text-slate-100">{{ $chaveCriada['segredo'] }}</code>
                    <button type="button" data-copiar="{{ $chaveCriada['segredo'] }}" onclick="copiarTexto(this)"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Copiar chave</button>
                </div>
                <p class="mt-3 text-[12.5px] text-slate-400">Mande a chave ao parceiro por um canal diferente do da mensagem com os links (ex.: links por e-mail, chave por WhatsApp).</p>
            </section>
        @endif

        {{-- Para o parceiro --}}
        <section class="rounded-xl border border-ink-600 bg-ink-800 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-200">Documentação para o parceiro</h2>
                    <p class="mt-0.5 text-sm text-slate-400">É isto que você envia para a empresa do sistema de postagem (a IA deles implementa a partir do guia).</p>
                </div>
                <button type="button" data-copiar="{{ $mensagemParceiro }}" onclick="copiarTexto(this)"
                        class="rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-brand-500">Copiar mensagem para o parceiro</button>
            </div>

            <dl class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach([
                    ['Documentação interativa (Swagger)', $raiz.'/api/docs'],
                    ['Guia completo (para IA/desenvolvedor)', $raiz.'/api/docs.md'],
                    ['Especificação OpenAPI 3.1', $raiz.'/api/openapi.json'],
                    ['URL base da API', $raiz.'/api/v1'],
                ] as [$rotulo, $link])
                    <div class="flex min-w-0 items-center gap-2 rounded-lg border border-ink-700 bg-ink-900/60 px-3 py-2">
                        <div class="min-w-0 flex-1">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $rotulo }}</dt>
                            <dd class="truncate font-mono text-[12.5px] text-slate-200">
                                @if(str_ends_with($link, '/api/v1'))
                                    {{ $link }}
                                @else
                                    <a href="{{ $link }}" target="_blank" rel="noopener" class="hover:text-brand-300 hover:underline">{{ $link }}</a>
                                @endif
                            </dd>
                        </div>
                        <button type="button" data-copiar="{{ $link }}" onclick="copiarTexto(this)"
                                class="shrink-0 rounded-md px-2 py-1 text-[11px] font-medium text-slate-400 hover:bg-ink-700 hover:text-slate-200">Copiar</button>
                    </div>
                @endforeach
            </dl>

            <p class="mt-3 text-[12.5px] leading-relaxed text-slate-500">
                Não é preciso informar IP: a integração usa o domínio acima, com HTTPS. O que vai para o parceiro são os posts
                que estão na coluna marcada como <strong class="text-slate-300">fila de postagem</strong> (menu ⋯ da coluna, no quadro),
                com data e hora definidas. Legenda, data/hora e as mídias na ordem do carrossel. A anotação interna, comentários e checklist nunca saem.
            </p>
        </section>

        {{-- Fila de postagem agora --}}
        <section class="rounded-xl border border-ink-600 bg-ink-800 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold text-slate-200">Fila de postagem agora</h2>
                <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-300">
                    {{ $prontos }} {{ $prontos === 1 ? 'post pronto' : 'posts prontos' }} para o parceiro
                </span>
            </div>
            @if($travados->isEmpty())
                <p class="mt-2 text-sm text-slate-500">Nenhum post travado. Tudo o que está na fila tem data e hora.</p>
            @else
                <p class="mt-2 text-sm text-amber-300">Estes estão na fila, mas <strong>não vão</strong> para o parceiro até ganharem data e hora:</p>
                <ul class="mt-2 divide-y divide-ink-700/70 overflow-hidden rounded-lg border border-ink-700">
                    @foreach($travados as $t)
                        @php $motivo = \App\Http\Resources\Api\PostResource::motivoNaoPronto($t); @endphp
                        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2.5 text-sm">
                            <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $t->client?->color ?? '#64748b' }}"></span>
                            <a href="{{ route('tasks.show', $t) }}" @click.prevent="$dispatch('open-task-modal', '{{ route('tasks.show', $t) }}')"
                               class="min-w-0 flex-1 font-medium text-slate-200 hover:text-brand-300">{{ $t->title ?: 'Sem título' }}</a>
                            <span class="text-xs text-slate-500">{{ $t->client?->name }}</span>
                            <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[11px] font-semibold text-amber-300">
                                {{ ['missing_publish_date' => 'sem data', 'missing_publish_time' => 'sem horário'][$motivo] ?? $motivo }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Chaves --}}
        <section class="rounded-xl border border-ink-600 bg-ink-800" x-data="{ nova: {{ $chaves->isEmpty() || $errors->any() ? 'true' : 'false' }} }">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-700 px-5 py-4">
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-200">Chaves de acesso</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Uma chave por sistema parceiro. Revogar corta o acesso na hora.</p>
                </div>
                <button type="button" @click="nova = !nova" class="rounded-lg border border-ink-600 px-3.5 py-2 text-sm font-medium text-slate-200 hover:bg-ink-700">＋ Nova chave</button>
            </div>

            {{-- Nova chave --}}
            <div x-show="nova" x-cloak class="border-b border-ink-700 bg-ink-900/40 px-5 py-5">
                @include('integrations.partials.form-chave', [
                    'acao' => route('integrations.store'),
                    'metodo' => 'POST',
                    'chave' => null,
                    'botao' => 'Criar chave',
                ])
            </div>

            @forelse($chaves as $chave)
                @php
                    $situacao = $chave->situacao();
                    $tomSituacao = [
                        'ativa' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                        'revogada' => 'border-rose-500/30 bg-rose-500/10 text-rose-300',
                        'expirada' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
                    ][$situacao];
                @endphp
                <div class="border-b border-ink-700/70 px-5 py-4 last:border-0 {{ $situacao !== 'ativa' ? 'opacity-70' : '' }}" x-data="{ editando: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-slate-100">{{ $chave->name }}</span>
                                <span class="rounded-full border px-2 py-0.5 text-[10.5px] font-semibold uppercase tracking-wide {{ $tomSituacao }}">{{ $situacao }}</span>
                            </div>
                            <div class="mt-1 font-mono text-[12.5px] text-slate-400">{{ $chave->token_prefix }}••••••••</div>
                        </div>
                        @if($situacao === 'ativa' || $situacao === 'expirada')
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button type="button" @click="editando = !editando" class="rounded-md px-2.5 py-1.5 text-xs font-medium text-brand-300 hover:bg-ink-700">Editar</button>
                                <form method="POST" action="{{ route('integrations.regenerate', $chave) }}"
                                      onsubmit="return confirm('Gerar uma chave nova para {{ addslashes($chave->name) }}?\n\nA chave atual para de funcionar na hora: o parceiro precisa trocar pela nova.')">
                                    @csrf
                                    <button class="rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-ink-700">Gerar nova chave</button>
                                </form>
                                <form method="POST" action="{{ route('integrations.revoke', $chave) }}"
                                      onsubmit="return confirm('Revogar a chave {{ addslashes($chave->name) }}?\n\nO sistema parceiro perde o acesso na hora. Não dá para desfazer (depois é só criar outra).')">
                                    @csrf
                                    <button class="rounded-md px-2.5 py-1.5 text-xs font-medium text-rose-300 hover:bg-rose-500/10">Revogar</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('integrations.destroy', $chave) }}" onsubmit="return confirm('Excluir a chave {{ addslashes($chave->name) }}? O histórico de postagens nos cards continua.')">
                                @csrf @method('DELETE')
                                <button class="rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-400 hover:bg-ink-700 hover:text-rose-300">Excluir</button>
                            </form>
                        @endif
                    </div>

                    <dl class="mt-3 grid gap-x-6 gap-y-2 text-[12.5px] sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-[10.5px] font-semibold uppercase tracking-wider text-slate-500">Clientes</dt>
                            <dd class="mt-0.5 text-slate-300">
                                @if($chave->all_clients)
                                    Todos os clientes
                                @else
                                    <span class="flex flex-wrap gap-1">
                                        @forelse($chave->clients as $c)
                                            <span class="inline-flex items-center gap-1 rounded bg-ink-900 px-1.5 py-0.5 text-[11.5px]">
                                                <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $c->color ?? '#64748b' }}"></span>{{ $c->name }}
                                            </span>
                                        @empty
                                            <span class="text-amber-300">nenhum</span>
                                        @endforelse
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold uppercase tracking-wider text-slate-500">IPs permitidos</dt>
                            <dd class="mt-0.5 break-all text-slate-300">{{ $chave->allowed_ips ?: 'Qualquer IP' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold uppercase tracking-wider text-slate-500">Validade</dt>
                            <dd class="mt-0.5 text-slate-300">{{ $chave->expires_at ? 'até '.$chave->expires_at->format('d/m/Y') : 'Sem validade' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold uppercase tracking-wider text-slate-500">Último uso</dt>
                            <dd class="mt-0.5 text-slate-300">
                                @if($chave->last_used_at)
                                    {{ $chave->last_used_at->diffForHumans() }} <span class="text-slate-500">· {{ $chave->last_used_ip }}</span>
                                @else
                                    <span class="text-slate-500">Ainda não usada</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                    <p class="mt-2 text-[11.5px] text-slate-500">
                        {{ $chave->auto_complete ? 'Ao receber "publicado", o card vai para a coluna de concluído.' : 'Ao receber "publicado", o card não é movido.' }}
                        Criada {{ $chave->created_at->format('d/m/Y') }}{{ $chave->creator ? ' por '.$chave->creator->name : '' }}.
                    </p>

                    <div x-show="editando" x-cloak class="mt-4 rounded-lg border border-ink-700 bg-ink-900/40 p-4">
                        @include('integrations.partials.form-chave', [
                            'acao' => route('integrations.update', $chave),
                            'metodo' => 'PATCH',
                            'chave' => $chave,
                            'botao' => 'Salvar alterações',
                        ])
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-500">Nenhuma chave ainda. Crie a primeira acima.</p>
            @endforelse
        </section>

        {{-- Retornos do parceiro --}}
        <section class="rounded-xl border border-ink-600 bg-ink-800">
            <div class="border-b border-ink-700 px-5 py-4">
                <h2 class="font-semibold text-slate-200">Últimos retornos do sistema parceiro</h2>
                <p class="mt-0.5 text-xs text-slate-500">O que ele informou pelo webhook: agendado, publicado, falhou. Também aparece no card de cada post.</p>
            </div>
            @if($retornos->isEmpty())
                <p class="px-5 py-6 text-sm text-slate-500">Nenhum retorno recebido ainda.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="tabela-celular w-full text-left text-sm">
                        <thead class="border-b border-ink-700 bg-ink-900/40 text-[11px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-2.5 font-semibold">Post</th>
                                <th class="px-4 py-2.5 font-semibold">Rede</th>
                                <th class="px-4 py-2.5 font-semibold">Situação</th>
                                <th class="px-4 py-2.5 font-semibold">Quando</th>
                                <th class="px-5 py-2.5 font-semibold">Detalhe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700/60">
                            @foreach($retornos as $r)
                                <tr>
                                    <td class="px-5 py-3">
                                        @if($r->task)
                                            <a href="{{ route('tasks.show', $r->task) }}" @click.prevent="$dispatch('open-task-modal', '{{ route('tasks.show', $r->task) }}')"
                                               class="font-medium text-slate-200 hover:text-brand-300">{{ $r->task->title ?: 'Sem título' }}</a>
                                            <div class="text-[11.5px] text-slate-500">{{ $r->task->client?->name }}</div>
                                        @else
                                            <span class="text-slate-500">(post excluído)</span>
                                        @endif
                                    </td>
                                    <td data-rotulo="Rede" class="cel-metade px-4 py-3 text-slate-300">{{ $r->redeLabel() }}</td>
                                    <td data-rotulo="Situação" class="cel-metade px-4 py-3">
                                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $r->tom() }}">{{ $r->statusLabel() }}</span>
                                    </td>
                                    <td data-rotulo="Quando" class="px-4 py-3 text-xs text-slate-400">
                                        {{ $r->updated_at->copy()->setTimezone('America/Sao_Paulo')->format('d/m H:i') }}
                                        <span class="text-slate-600">· {{ $r->apiToken?->name ?? 'chave excluída' }}</span>
                                    </td>
                                    <td data-rotulo="Detalhe" class="px-5 py-3 text-xs">
                                        @if($r->status === 'failed' && $r->error_message)
                                            <span class="text-rose-300">{{ $r->error_message }}</span>
                                        @elseif($r->permalink)
                                            <a href="{{ $r->permalink }}" target="_blank" rel="noopener" class="break-all text-brand-300 hover:underline">Ver post publicado ↗</a>
                                        @elseif($r->scheduled_for)
                                            <span class="text-slate-400">para {{ $r->scheduled_for->copy()->setTimezone('America/Sao_Paulo')->format('d/m H:i') }}</span>
                                        @else
                                            <span class="text-slate-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    @push('scripts')
    <script>
        // Copia o texto do data-copiar do botão; sem clipboard API (ou se o
        // navegador negar a permissão), cai no execCommand.
        window.copiarTexto = function (botao) {
            var texto = botao.getAttribute('data-copiar');
            // Guarda o rótulo na primeira vez: dois cliques seguidos não
            // podem deixar o botão preso em "Copiado!".
            var original = botao.dataset.rotulo || (botao.dataset.rotulo = botao.textContent);
            var feito = function () {
                botao.textContent = 'Copiado!';
                setTimeout(function () { botao.textContent = original; }, 1600);
            };
            var semApi = function () {
                var area = document.createElement('textarea');
                area.value = texto;
                document.body.appendChild(area);
                area.select();
                try { document.execCommand('copy'); feito(); } catch (e) { /* segue selecionado */ }
                document.body.removeChild(area);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(texto).then(feito, semApi);
                return;
            }
            semApi();
        };
    </script>
    @endpush
</x-app-layout>
