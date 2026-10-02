<x-app-layout title="Integrações">
    <x-slot name="header">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-200 tracking-wide">Integrações</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-0.5">API de postagem automática: chaves de acesso, documentação e retornos do sistema parceiro.</p>
        </div>
    </x-slot>

    @php
        // Logo depois de criar/renovar, a mensagem já leva a chave: um só
        // "copiar e colar" para o parceiro (copiar a chave e depois a
        // mensagem fazia a segunda apagar a primeira da área de transferência).
        $chaveNova = $chaveCriada['segredo'] ?? null;
        $mensagemParceiro = "Olá! Seguem os dados da API do STRASA para a postagem automática.\n\n"
            ."Documentação completa, para a IA/desenvolvedor de vocês (endpoints, regras e exemplos):\n{$raiz}/api/docs.md\n\n"
            ."A mesma documentação em página interativa, para testar pelo navegador:\n{$raiz}/api/docs\n\n"
            .($chaveNova
                ? "Chave de acesso (vai no cabeçalho \"Authorization: Bearer <chave>\"):\n{$chaveNova}\n\n"
                    ."Guardem a chave só no servidor de vocês. Se ela vazar, me avisem que eu gero outra."
                : "A chave de acesso eu mando em seguida. Ela vai no cabeçalho \"Authorization: Bearer <chave>\"; guardem só no servidor de vocês.");
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
            <section class="rounded-xl border-2 border-emerald-500/40 bg-emerald-500/[0.07] p-5" data-chave-nova>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-500/20 text-emerald-300">✓</span>
                    <h2 class="font-semibold text-emerald-200">
                        {{ !empty($chaveCriada['renovada']) ? 'Nova chave gerada para' : 'Chave criada:' }} {{ $chaveCriada['nome'] }}
                    </h2>
                </div>
                <p class="mt-2 text-sm text-slate-300">
                    <strong class="text-amber-300">Copie agora:</strong> por segurança a chave só aparece desta vez.
                    Se perder, é só clicar em <strong class="text-slate-100">Gerar nova chave</strong> na lista abaixo.
                    @if(!empty($chaveCriada['renovada'])) A chave anterior já parou de funcionar. @endif
                </p>
                <label class="mt-3 block">
                    <span class="sr-only">Chave de acesso</span>
                    <textarea id="chave-nova" readonly rows="1" spellcheck="false" data-autoaltura onfocus="selecionarTudo(this)" onclick="selecionarTudo(this)"
                              class="block w-full resize-none overflow-hidden break-all rounded-lg border border-ink-600 bg-ink-900 px-3 py-2.5 font-mono text-[13px] leading-relaxed text-slate-100 focus:border-emerald-500 focus:outline-none">{{ $chaveNova }}</textarea>
                </label>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" data-copiar="{{ $mensagemParceiro }}" data-selecionar="mensagem-parceiro" onclick="copiarTexto(this)"
                            class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">Copiar mensagem para o parceiro (com a chave)</button>
                    <button type="button" data-copiar="{{ $chaveNova }}" data-selecionar="chave-nova" onclick="copiarTexto(this)"
                            class="rounded-lg border border-emerald-500/40 px-4 py-2.5 text-sm font-medium text-emerald-200 hover:bg-emerald-500/10">Copiar só a chave</button>
                </div>
                <p class="mt-3 text-[12.5px] text-slate-400">
                    A mensagem já leva o link da documentação e a chave: é só colar numa conversa privada com o responsável técnico do parceiro (não mande em grupo).
                </p>
            </section>
        @endif

        {{-- O que mandar para o parceiro --}}
        <section class="rounded-xl border border-ink-600 bg-ink-800 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold text-slate-200">O que mandar para o parceiro</h2>
                    <p class="mt-0.5 text-sm text-slate-400">
                        Só duas coisas: o link da documentação e a chave de acesso. A documentação é uma só, em dois formatos:
                        texto para a IA deles ler e implementar, e uma página interativa para uma pessoa testar clicando.
                    </p>
                </div>
                <button type="button" data-copiar="{{ $mensagemParceiro }}" data-selecionar="mensagem-parceiro" onclick="copiarTexto(this)"
                        class="rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                    {{ $chaveNova ? 'Copiar mensagem (com a chave)' : 'Copiar mensagem para o parceiro' }}
                </button>
            </div>

            <dl class="mt-4 grid gap-2 sm:grid-cols-2">
                @include('integrations.partials.linha-link', ['id' => 'link-guia', 'rotulo' => 'Documentação para a IA deles (a principal)', 'link' => $raiz.'/api/docs.md'])
                @include('integrations.partials.linha-link', ['id' => 'link-docs', 'rotulo' => 'A mesma, em página interativa (para testar)', 'link' => $raiz.'/api/docs'])
            </dl>

            <details class="mt-3">
                <summary class="cursor-pointer text-[12.5px] text-slate-400 hover:text-slate-200">Ver a mensagem que o botão copia</summary>
                <textarea id="mensagem-parceiro" readonly rows="1" data-autoaltura onclick="selecionarTudo(this)"
                          class="mt-2 block w-full resize-none overflow-hidden rounded-lg border border-ink-700 bg-ink-900/60 px-3 py-2.5 text-[13px] leading-relaxed text-slate-300 [overflow-wrap:anywhere] focus:border-brand-500 focus:outline-none">{{ $mensagemParceiro }}</textarea>
            </details>

            <details class="mt-2">
                <summary class="cursor-pointer text-[12.5px] text-slate-400 hover:text-slate-200">Detalhes técnicos (só se eles pedirem)</summary>
                <dl class="mt-2 grid gap-2 sm:grid-cols-2">
                    @include('integrations.partials.linha-link', ['id' => 'link-openapi', 'rotulo' => 'Especificação OpenAPI (ferramentas importam)', 'link' => $raiz.'/api/openapi.json'])
                    @include('integrations.partials.linha-link', ['id' => 'link-base', 'rotulo' => 'URL base da API (já está na documentação)', 'link' => $raiz.'/api/v1', 'abrir' => false])
                </dl>
            </details>

            <p class="mt-3 text-[12.5px] leading-relaxed text-slate-500">
                Não precisa de IP: eles acessam pelo endereço acima, com HTTPS. Só vão para o parceiro os posts da coluna
                <strong class="text-slate-300">Aprovado / Agendado</strong> (o card vai para lá sozinho quando o cliente aprova)
                que tenham data e horário: legenda, data/hora e as mídias na ordem do carrossel. Anotação interna, comentários e checklist nunca saem.
                O parceiro é obrigado a avisar de volta quando agendar e quando publicar.
            </p>
        </section>

        {{-- Coluna "Aprovado / Agendado" agora --}}
        <section class="rounded-xl border border-ink-600 bg-ink-800 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold text-slate-200">Aprovado / Agendado agora</h2>
                <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-300">
                    {{ $prontos }} {{ $prontos === 1 ? 'post pronto' : 'posts prontos' }} para o parceiro
                </span>
            </div>

            @if($atrasados->isEmpty() && $semRetorno->isEmpty() && $travados->isEmpty())
                <p class="mt-2 text-sm text-slate-500">Tudo em dia: os posts aprovados têm data e hora, e o parceiro deu retorno de todos.</p>
            @endif

            @if($atrasados->isNotEmpty())
                <p class="mt-3 text-sm text-rose-300">Passou do horário e o parceiro <strong>não avisou</strong> se publicou:</p>
                <ul class="mt-2 divide-y divide-ink-700/70 overflow-hidden rounded-lg border border-rose-500/30">
                    @foreach($atrasados as $t)
                        @include('integrations.partials.item-fila', ['t' => $t,
                            'selo' => 'era '.$t->horarioDePublicacao()->format('d/m H:i'),
                            'tom' => 'border-rose-500/30 bg-rose-500/10 text-rose-300'])
                    @endforeach
                </ul>
            @endif

            @if($semRetorno->isNotEmpty())
                <p class="mt-3 text-sm text-sky-300">Prontos, mas o parceiro ainda não avisou que agendou:</p>
                <ul class="mt-2 divide-y divide-ink-700/70 overflow-hidden rounded-lg border border-ink-700">
                    @foreach($semRetorno as $t)
                        @include('integrations.partials.item-fila', ['t' => $t,
                            'selo' => 'vai ao ar '.$t->horarioDePublicacao()->format('d/m H:i'),
                            'tom' => 'border-sky-500/30 bg-sky-500/10 text-sky-300'])
                    @endforeach
                </ul>
            @endif

            @if($travados->isNotEmpty())
                <p class="mt-3 text-sm text-amber-300">Estes estão aprovados, mas <strong>não vão</strong> para o parceiro até ganharem data e hora:</p>
                <ul class="mt-2 divide-y divide-ink-700/70 overflow-hidden rounded-lg border border-ink-700">
                    @foreach($travados as $t)
                        @php $motivo = \App\Http\Resources\Api\PostResource::motivoNaoPronto($t); @endphp
                        @include('integrations.partials.item-fila', ['t' => $t,
                            'selo' => ['missing_publish_date' => 'sem data', 'missing_publish_time' => 'sem horário'][$motivo] ?? $motivo,
                            'tom' => 'border-amber-500/30 bg-amber-500/10 text-amber-300'])
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
        // Seleciona todo o texto de um campo (ou de um elemento qualquer),
        // para a pessoa copiar à mão quando o navegador não deixa copiar.
        window.selecionarTudo = function (el) {
            if ('selectionStart' in el) {
                if (document.activeElement !== el) { el.focus({ preventScroll: true }); }
                el.select();
                el.setSelectionRange(0, el.value.length);
                return;
            }
            var faixa = document.createRange();
            faixa.selectNodeContents(el);
            var selecao = window.getSelection();
            selecao.removeAllRanges();
            selecao.addRange(faixa);
        };

        // Caixa de texto do tamanho do conteúdo (a chave quebra em 2-3
        // linhas no celular; a mensagem tem ~10).
        window.ajustarAltura = function (el) {
            if (!el || el.tagName !== 'TEXTAREA') { return; }
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight + el.offsetHeight - el.clientHeight) + 'px';
        };
        var ajustarTodas = function () { document.querySelectorAll('textarea[data-autoaltura]').forEach(window.ajustarAltura); };
        ajustarTodas();
        window.addEventListener('resize', ajustarTodas);
        document.querySelectorAll('details').forEach(function (d) { d.addEventListener('toggle', ajustarTodas); });

        // Copia o data-copiar do botão. Tenta a API moderna; se o navegador
        // recusar ou não tiver, o jeito antigo; se nada funcionar, deixa o
        // texto selecionado na tela (data-selecionar) para copiar à mão.
        // O botão só diz "Copiado!" quando copiou de verdade.
        window.copiarTexto = function (botao) {
            var texto = botao.getAttribute('data-copiar');
            // Guarda o rótulo na primeira vez: dois cliques seguidos não
            // podem deixar o botão preso em "Copiado!".
            var original = botao.dataset.rotulo || (botao.dataset.rotulo = botao.textContent);
            var mostrar = function (mensagem, ms) {
                botao.textContent = mensagem;
                clearTimeout(botao._volta);
                botao._volta = setTimeout(function () { botao.textContent = original; }, ms);
            };
            var copiou = function () {
                window.strasaCopiou = true;
                mostrar('Copiado!', 1800);
            };
            var jeitoAntigo = function () {
                // Receita do clipboard.js: readonly (o teclado não abre no
                // celular), fonte de 12pt (o iPhone não dá zoom) e
                // setSelectionRange (o iPhone ignora só o select()).
                var area = document.createElement('textarea');
                area.value = texto;
                area.setAttribute('readonly', '');
                area.style.cssText = 'position:absolute;left:-9999px;top:' + (window.pageYOffset || document.documentElement.scrollTop) + 'px;font-size:12pt;border:0;padding:0;margin:0';
                document.body.appendChild(area);
                area.select();
                area.setSelectionRange(0, texto.length);
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                document.body.removeChild(area);
                return ok;
            };
            var aMao = function () {
                var alvo = botao.dataset.selecionar && document.getElementById(botao.dataset.selecionar);
                if (!alvo) {
                    mostrar('Não deu para copiar', 4000);
                    return;
                }
                var caixa = alvo.closest('details');
                if (caixa) { caixa.open = true; }
                window.ajustarAltura(alvo);
                alvo.scrollIntoView({ block: 'center' });
                window.selecionarTudo(alvo);
                mostrar('Selecionei: copie à mão', 6000);
            };
            var semApi = function () {
                if (jeitoAntigo()) { copiou(); } else { aMao(); }
            };

            if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
                navigator.clipboard.writeText(texto).then(copiou, semApi);
                return;
            }
            semApi();
        };

        // Chave recém-criada ainda não copiada: avisa antes de sair da página,
        // porque ela não aparece de novo. Ctrl+C à mão também conta.
        if (document.querySelector('[data-chave-nova]')) {
            document.addEventListener('copy', function () { window.strasaCopiou = true; });
            window.addEventListener('beforeunload', function (evento) {
                if (window.strasaCopiou) { return; }
                evento.preventDefault();
                evento.returnValue = '';
            });
        }
    </script>
    @endpush
</x-app-layout>
