{{--
    Overlay global de progresso de upload.

    Usado tanto pelos formulários normais (foto de perfil, logo do cliente,
    comprovante do financeiro) quanto pelo envio de anexos das tarefas.
    Não depende do Alpine: é JS puro, então já funciona antes do Alpine carregar.
--}}
<div id="upload-overlay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 p-4">
    <div class="w-full max-w-sm rounded-xl border border-ink-600 bg-ink-800 p-5 shadow-2xl">
        <div class="flex items-center gap-3">
            <svg id="upload-overlay-spinner" class="h-5 w-5 shrink-0 animate-spin text-brand-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
            </svg>
            <div class="min-w-0 flex-1">
                <p id="upload-overlay-title" class="text-sm font-medium text-slate-200">Enviando arquivo…</p>
                <p id="upload-overlay-file" class="truncate text-xs text-slate-400"></p>
            </div>
            <span id="upload-overlay-percent" class="shrink-0 text-sm font-semibold tabular-nums text-slate-300">0%</span>
        </div>

        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-ink-900">
            <div id="upload-overlay-bar" class="h-full w-0 rounded-full bg-brand-500 transition-all duration-200"></div>
        </div>

        <p id="upload-overlay-hint" class="mt-2 text-[11px] text-slate-500">Não feche a página até o envio terminar.</p>
    </div>
</div>

<script>
(function () {
    var el = {};
    function refs() {
        if (!el.root) {
            el.root = document.getElementById('upload-overlay');
            el.title = document.getElementById('upload-overlay-title');
            el.file = document.getElementById('upload-overlay-file');
            el.percent = document.getElementById('upload-overlay-percent');
            el.bar = document.getElementById('upload-overlay-bar');
            el.hint = document.getElementById('upload-overlay-hint');
            el.spinner = document.getElementById('upload-overlay-spinner');
        }
        return el;
    }

    /** Lista legível dos arquivos escolhidos no formulário. */
    function describeFiles(form) {
        var names = [];
        var total = 0;
        form.querySelectorAll('input[type="file"]').forEach(function (input) {
            Array.prototype.forEach.call(input.files || [], function (f) {
                names.push(f.name);
                total += f.size;
            });
        });
        if (!names.length) return null;
        var label = names.length === 1 ? names[0] : names.length + ' arquivos';
        if (total > 0) label += ' · ' + (total / 1048576).toFixed(1) + ' MB';
        return label;
    }

    var overlay = {
        show: function (label) {
            var r = refs();
            r.title.textContent = 'Enviando arquivo…';
            r.file.textContent = label || '';
            r.hint.textContent = 'Não feche a página até o envio terminar.';
            r.spinner.classList.remove('hidden');
            r.bar.classList.remove('bg-rose-500');
            r.bar.classList.add('bg-brand-500');
            this.setPercent(0);
            r.root.classList.remove('hidden');
            r.root.classList.add('flex');
        },
        setPercent: function (value) {
            var r = refs();
            var pct = Math.max(0, Math.min(100, Math.round(value)));
            r.bar.style.width = pct + '%';
            r.percent.textContent = pct + '%';
        },
        /** 100% enviado, mas o servidor ainda está mandando pro bucket/Drive. */
        processing: function () {
            var r = refs();
            this.setPercent(100);
            r.title.textContent = 'Processando…';
            r.hint.textContent = 'Salvando o arquivo no armazenamento.';
        },
        fail: function (message) {
            var r = refs();
            r.title.textContent = 'Falha no envio';
            r.file.textContent = message || 'Tente novamente.';
            r.hint.textContent = 'Você pode fechar esta mensagem e tentar de novo.';
            r.spinner.classList.add('hidden');
            r.bar.classList.remove('bg-brand-500');
            r.bar.classList.add('bg-rose-500');
            setTimeout(overlay.hide, 4000);
        },
        hide: function () {
            var r = refs();
            r.root.classList.add('hidden');
            r.root.classList.remove('flex');
        },

        /**
         * Envia um <form> por XHR mostrando o progresso real do upload.
         * Retorna uma Promise com o XHR concluído.
         */
        sendForm: function (form, options) {
            options = options || {};
            var self = this;
            self.show(describeFiles(form));

            return new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open(form.method && form.method.toUpperCase() !== 'GET' ? 'POST' : 'GET', form.action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                if (options.json) xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var pct = (e.loaded / e.total) * 100;
                        if (pct >= 100) self.processing();
                        else self.setPercent(pct);
                    }
                });
                xhr.upload.addEventListener('load', function () { self.processing(); });

                xhr.addEventListener('load', function () {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        resolve(xhr);
                    } else if (xhr.status === 422) {
                        self.fail('Arquivo inválido ou grande demais.');
                        reject(xhr);
                    } else {
                        self.fail('Erro ' + xhr.status + ' ao enviar.');
                        reject(xhr);
                    }
                });
                xhr.addEventListener('error', function () {
                    self.fail('Sem conexão com o servidor.');
                    reject(xhr);
                });
                xhr.addEventListener('abort', function () {
                    self.hide();
                    reject(xhr);
                });

                xhr.send(new FormData(form));
            });
        },
    };

    window.uploadOverlay = overlay;

    /**
     * Formulários "normais" com arquivo (perfil, cliente, financeiro) passam a
     * subir por XHR só pra poder mostrar o progresso; no fim seguimos para a
     * URL do redirect, igual a um submit comum.
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
        if (!describeFiles(form)) return;

        event.preventDefault();

        overlay.sendForm(form).then(function (xhr) {
            window.location.href = xhr.responseURL || window.location.href;
        }).catch(function () { /* mensagem já exibida no overlay */ });
    });
})();
</script>
