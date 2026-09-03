{{--
    Painel de flags do card.

    Existe UM por quadro, ancorado no <body> e posicionado por JS. Foi feito
    assim porque as colunas do Kanban têm overflow-y-auto: um painel dentro do
    card seria recortado pela borda da coluna. De quebra, é um nó no DOM em vez
    de um por card.

    Só as flags padrão podem ser aplicadas. Flags antigas, criadas antes desse
    padrão, aparecem apenas nos cards onde já estão — para poderem ser tiradas.
--}}
<div id="painel-flags"
     class="fixed z-[60] hidden w-64 rounded-xl border border-ink-600 bg-ink-800 shadow-2xl"
     data-url-toggle="{{ url('/tasks') }}"
     data-url-tags="{{ url('/tags') }}">

    <div class="flex items-center justify-between border-b border-ink-700 px-3 py-2">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Flags</span>
        <button type="button" data-fechar-flags class="text-slate-500 hover:text-slate-200 text-lg leading-none">&times;</button>
    </div>

    <div id="painel-flags-lista" class="max-h-72 overflow-y-auto py-1"></div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    // Os elementos são buscados sob demanda, e não no carregamento. Se este
    // script rodar antes do painel existir no DOM, a função continua sendo
    // definida e o clique funciona assim que o elemento aparece.
    var painel, lista;
    var ligado = false;

    var flags = @json($tags);
    var taskAtual = null;
    var botaoAtual = null;

    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function elementos() {
        painel = document.getElementById('painel-flags');
        if (!painel) { return false; }

        lista = document.getElementById('painel-flags-lista');

        if (!ligado) {
            painel.querySelector('[data-fechar-flags]').addEventListener('click', fechar);
            ligado = true;
        }

        return true;
    }

    function pedir(url, metodo, corpo) {
        return fetch(url, {
            method: metodo,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: corpo ? JSON.stringify(corpo) : undefined
        }).then(function (res) {
            return res.json().catch(function () { return {}; })
                .then(function (dados) { return { ok: res.ok, dados: dados }; });
        });
    }

    /* ---------------- Estado das flags do card aberto ---------------- */
    function idsDoCard() {
        if (!botaoAtual) { return []; }
        var bruto = botaoAtual.dataset.flagsAtuais || '';
        return bruto ? bruto.split(',').filter(Boolean).map(Number) : [];
    }

    function gravarIdsNoCard(ids) {
        if (botaoAtual) { botaoAtual.dataset.flagsAtuais = ids.join(','); }
    }

    /** Reescreve os selos coloridos do card. */
    function redesenharCard(ids) {
        var alvo = document.querySelector('[data-flags="' + taskAtual + '"]');
        if (!alvo) { return; }

        alvo.innerHTML = '';

        ids.forEach(function (id) {
            var flag = flags.find(function (f) { return f.id === id; });
            if (!flag) { return; }

            var selo = document.createElement('span');
            selo.className = 'rounded px-1.5 py-0.5 text-[10px] font-medium';
            selo.style.background = flag.color + '22';
            selo.style.color = flag.color;
            selo.textContent = '#' + flag.name;
            alvo.appendChild(selo);
        });
    }

    /* ---------------- Desenho do painel ---------------- */
    function desenharLista() {
        var aplicadas = idsDoCard();
        lista.innerHTML = '';

        // As padrão sempre; as antigas só quando já estão neste card, senão
        // ficariam presas — visíveis no card e sem como tirar.
        var visiveis = flags.filter(function (f) {
            return f.padrao || (f.id && aplicadas.indexOf(f.id) !== -1);
        });

        if (!visiveis.length) {
            lista.innerHTML = '<p class="px-3 py-4 text-center text-[12px] text-slate-500">Nenhuma flag disponível.</p>';
            return;
        }

        visiveis.forEach(function (flag) {
            var ativa = flag.id && aplicadas.indexOf(flag.id) !== -1;

            var linha = document.createElement('div');
            linha.className = 'group flex items-center gap-2 px-2.5 py-1.5 hover:bg-ink-700/50';

            var alternar = document.createElement('button');
            alternar.type = 'button';
            alternar.className = 'flex flex-1 items-center gap-2 text-left min-w-0';
            alternar.innerHTML =
                '<span class="grid h-4 w-4 shrink-0 place-items-center rounded border ' +
                (ativa ? 'border-transparent' : 'border-ink-500') + '" ' +
                'style="' + (ativa ? 'background:' + flag.color : '') + '">' +
                (ativa ? '<svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' : '') +
                '</span>' +
                '<span class="h-2 w-2 shrink-0 rounded-full" style="background:' + flag.color + '"></span>' +
                '<span class="truncate text-[13px] ' + (ativa ? 'text-slate-100' : 'text-slate-300') + '">' + flag.name + '</span>' +
                (!flag.padrao ? '<span class="ml-auto shrink-0 text-[9.5px] uppercase tracking-wide text-amber-600/80">antiga</span>' : '');

            alternar.addEventListener('click', function () { alternarFlag(flag); });
            linha.appendChild(alternar);

            // Só as antigas podem ser excluídas: apagar uma padrão não
            // adiantaria, ela voltaria na lista como sugestão.
            if (!flag.padrao && flag.id) {
                var excluir = document.createElement('button');
                excluir.type = 'button';
                excluir.title = 'Excluir esta flag de todos os cards';
                excluir.className = 'shrink-0 text-slate-600 opacity-0 transition hover:text-rose-400 group-hover:opacity-100';
                excluir.innerHTML = '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>';
                excluir.addEventListener('click', function (e) {
                    e.stopPropagation();
                    excluirFlag(flag);
                });
                linha.appendChild(excluir);
            }

            lista.appendChild(linha);
        });
    }

    /* ---------------- Ações ---------------- */
    function alternarFlag(flag) {
        // Sempre por NOME: cobre tanto a flag que já existe quanto a padrão
        // que ainda não virou registro. O servidor cria se precisar e devolve
        // o id definitivo.
        var url = painel.dataset.urlToggle + '/' + taskAtual + '/flags';

        pedir(url, 'POST', { name: flag.name, color: flag.color }).then(function (r) {
            if (!r.ok) {
                alert(r.dados.message || 'Não foi possível alterar a flag.');
                return;
            }

            var salva = r.dados.tag;
            var registro = flags.find(function (f) { return f.name === salva.name; });
            if (registro) {
                registro.id = salva.id;
                registro.color = salva.color;
                registro.sugestao = false;
            }

            var ids = idsDoCard();
            if (r.dados.aplicada) {
                ids.push(salva.id);
            } else {
                ids = ids.filter(function (id) { return id !== salva.id; });
            }

            gravarIdsNoCard(ids);
            redesenharCard(ids);
            desenharLista();
        });
    }

    function excluirFlag(flag) {
        if (!confirm('Excluir a flag "' + flag.name + '"? Ela sai de todos os cards que a usam.')) {
            return;
        }

        pedir(painel.dataset.urlTags + '/' + flag.id, 'DELETE').then(function (r) {
            if (!r.ok) {
                alert(r.dados.message || 'Não foi possível excluir a flag.');
                return;
            }

            flags = flags.filter(function (f) { return f.id !== flag.id; });

            // Some de todos os cards do quadro, não só do que está aberto.
            document.querySelectorAll('[data-abrir-flags]').forEach(function (botao) {
                var ids = (botao.dataset.flagsAtuais || '').split(',').filter(Boolean).map(Number);
                if (ids.indexOf(flag.id) === -1) { return; }

                ids = ids.filter(function (id) { return id !== flag.id; });
                botao.dataset.flagsAtuais = ids.join(',');

                var anterior = taskAtual;
                taskAtual = botao.dataset.abrirFlags;
                redesenharCard(ids);
                taskAtual = anterior;
            });

            desenharLista();
        });
    }

    /* ---------------- Abrir e fechar ---------------- */
    window.abrirPainelFlags = function (botao, taskId) {
        if (!elementos()) {
            console.warn('Painel de flags não encontrado nesta página.');
            return;
        }

        // Clicar no mesmo botão fecha.
        if (taskAtual === String(taskId) && !painel.classList.contains('hidden')) {
            fechar();
            return;
        }

        botaoAtual = botao;
        taskAtual = String(taskId);

        painel.classList.remove('hidden');
        desenharLista();
        posicionar(botao);
    };

    /** Abre ao lado do card, virando para dentro da tela quando não cabe. */
    function posicionar(botao) {
        var r = botao.getBoundingClientRect();
        var largura = painel.offsetWidth;
        var altura = painel.offsetHeight;
        var folga = 8;

        var esquerda = r.right + folga;
        if (esquerda + largura > window.innerWidth - folga) {
            esquerda = r.left - largura - folga;
        }
        if (esquerda < folga) { esquerda = folga; }

        var topo = r.top;
        if (topo + altura > window.innerHeight - folga) {
            topo = window.innerHeight - altura - folga;
        }
        if (topo < folga) { topo = folga; }

        painel.style.left = esquerda + 'px';
        painel.style.top = topo + 'px';
    }

    function fechar() {
        if (!painel) { return; }
        painel.classList.add('hidden');
        taskAtual = null;
        botaoAtual = null;
    }

    document.addEventListener('click', function (e) {
        if (!painel || painel.classList.contains('hidden')) { return; }
        if (painel.contains(e.target) || e.target.closest('[data-abrir-flags]')) { return; }
        fechar();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { fechar(); }
    });

    // O painel é fixo na tela; se a coluna rolar, ele descolaria do card.
    window.addEventListener('scroll', fechar, true);
    window.addEventListener('resize', fechar);
})();
</script>
@endpush
