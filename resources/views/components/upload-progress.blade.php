{{--
    Progresso de upload — inline, ao lado do próprio campo de arquivo.

    Não usa overlay no meio da tela: a barra aparece logo abaixo do formulário
    que está enviando (na seção de Anexos, no campo de logo do cliente, etc.).

    Serve tanto para os formulários normais (foto de perfil, logo, financeiro)
    quanto para o envio de anexos das tarefas. É JS puro, sem depender do Alpine.

    Onde a barra é colocada, em ordem de preferência:
      1. um elemento [data-upload-progress] dentro do formulário;
      2. logo depois do próprio <form>.
--}}
<script>
(function () {
    /** Lista legível dos arquivos escolhidos no formulário. */
    function descreverArquivos(form) {
        var nomes = [];
        var total = 0;
        form.querySelectorAll('input[type="file"]').forEach(function (input) {
            Array.prototype.forEach.call(input.files || [], function (f) {
                nomes.push(f.name);
                total += f.size;
            });
        });
        if (!nomes.length) return null;

        var label = nomes.length === 1 ? nomes[0] : nomes.length + ' arquivos';
        if (total > 0) label += ' · ' + formatarTamanho(total);
        return label;
    }

    function formatarTamanho(bytes) {
        var unidades = ['B', 'KB', 'MB', 'GB'];
        var i = 0;
        while (bytes >= 1024 && i < unidades.length - 1) {
            bytes /= 1024;
            i++;
        }
        return (i === 0 ? bytes : bytes.toFixed(1)) + ' ' + unidades[i];
    }

    function criarBarra(form, label) {
        var caixa = document.createElement('div');
        caixa.className = 'upload-progress mt-2 w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2';
        caixa.innerHTML = ''
            + '<div class="flex items-center justify-between gap-3">'
            +   '<span class="upload-progress-file truncate text-xs text-slate-300"></span>'
            +   '<span class="upload-progress-percent shrink-0 text-xs font-semibold tabular-nums text-slate-400">0%</span>'
            + '</div>'
            + '<div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-ink-700">'
            +   '<div class="upload-progress-bar h-full w-0 rounded-full bg-brand-500 transition-all duration-200"></div>'
            + '</div>';

        caixa.querySelector('.upload-progress-file').textContent = label || 'Enviando…';

        var alvo = form.querySelector('[data-upload-progress]');
        if (alvo) {
            alvo.innerHTML = '';
            alvo.appendChild(caixa);
        } else {
            form.insertAdjacentElement('afterend', caixa);
        }

        return caixa;
    }

    var progresso = {
        /**
         * Envia um <form> por XHR mostrando o progresso real do upload.
         * Retorna uma Promise com o XHR concluído.
         */
        sendForm: function (form, options) {
            options = options || {};

            var label = descreverArquivos(form);
            var caixa = criarBarra(form, label);
            var barra = caixa.querySelector('.upload-progress-bar');
            var percent = caixa.querySelector('.upload-progress-percent');
            var nome = caixa.querySelector('.upload-progress-file');

            var botoes = form.querySelectorAll('button[type="submit"], button:not([type])');
            botoes.forEach(function (b) { b.disabled = true; });

            function definir(pct) {
                pct = Math.max(0, Math.min(100, Math.round(pct)));
                barra.style.width = pct + '%';
                percent.textContent = pct + '%';
            }

            // 100% enviado, mas o servidor ainda está mandando pro bucket.
            function processando() {
                definir(100);
                percent.textContent = 'processando…';
            }

            function falhar(mensagem) {
                barra.classList.remove('bg-brand-500');
                barra.classList.add('bg-rose-500');
                barra.style.width = '100%';
                percent.textContent = '';
                nome.classList.remove('text-slate-300');
                nome.classList.add('text-rose-400');
                nome.textContent = mensagem;
                botoes.forEach(function (b) { b.disabled = false; });
                setTimeout(function () { caixa.remove(); }, 8000);
            }

            return new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                // Sempre POST: upload nunca é GET, e vários formulários do app
                // não declaram method="POST" (o Laravel resolve PATCH/DELETE
                // pelo campo _method, que já vai dentro do FormData).
                xhr.open('POST', form.getAttribute('action') || form.action || window.location.href);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                if (options.json) xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', function (e) {
                    if (!e.lengthComputable) return;
                    var pct = (e.loaded / e.total) * 100;
                    if (pct >= 100) processando();
                    else definir(pct);
                });
                xhr.upload.addEventListener('load', processando);

                xhr.addEventListener('load', function () {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        resolve(xhr);
                        return;
                    }
                    falhar(mensagemDeErro(xhr));
                    reject(xhr);
                });
                xhr.addEventListener('error', function () {
                    falhar('Conexão perdida durante o envio.');
                    reject(xhr);
                });
                xhr.addEventListener('abort', function () {
                    caixa.remove();
                    botoes.forEach(function (b) { b.disabled = false; });
                    reject(xhr);
                });

                xhr.send(new FormData(form));
            });
        },

        hide: function () {
            document.querySelectorAll('.upload-progress').forEach(function (el) { el.remove(); });
        },
    };

    function mensagemDeErro(xhr) {
        try {
            var corpo = JSON.parse(xhr.responseText);
            if (corpo && corpo.message) return corpo.message;
        } catch (e) { /* resposta não era JSON */ }

        if (xhr.status === 413) return 'Arquivo grande demais para o servidor aceitar.';
        if (xhr.status === 422) return 'Arquivo recusado pela validação.';
        if (xhr.status === 419) return 'Sessão expirada. Recarregue a página e tente de novo.';
        return 'Erro ' + xhr.status + ' ao enviar.';
    }

    window.uploadOverlay = progresso; // nome mantido por compatibilidade
    window.uploadProgress = progresso;

    /**
     * Formulários "normais" com arquivo (perfil, cliente, financeiro) sobem por
     * XHR só pra poder mostrar o progresso; no fim seguimos para a URL do
     * redirect, igual a um submit comum.
     *
     * Formulários já tratados por Alpine (@submit.prevent) chegam aqui com
     * defaultPrevented = true e são ignorados.
     */
    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;

        var form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.hasAttribute('data-no-upload-overlay')) return;
        if ((form.enctype || '').toLowerCase() !== 'multipart/form-data') return;

        // Só interessa quando o usuário realmente escolheu um arquivo.
        if (!descreverArquivos(form)) return;

        event.preventDefault();

        progresso.sendForm(form).then(function (xhr) {
            window.location.href = xhr.responseURL || window.location.href;
        }).catch(function () { /* a mensagem já aparece na própria barra */ });
    });
})();
</script>
