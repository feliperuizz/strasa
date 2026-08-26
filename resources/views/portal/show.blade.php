@extends('portal.layout', ['title' => $task->title])

@section('styles')
    .back {
        display: inline-flex; align-items: center; gap: 7px;
        font-size: 13.5px; color: var(--muted); font-weight: 500;
        margin-bottom: 20px;
    }
    .back:hover { color: var(--text); }

    .split {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
        gap: 30px;
        align-items: start;
    }
    @media (max-width: 900px) { .split { grid-template-columns: 1fr; gap: 24px; } }

    /* ------------------ Carrossel ------------------ */
    .viewer {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
        position: sticky; top: 92px;
    }
    @media (max-width: 900px) { .viewer { position: static; } }

    .frame {
        position: relative;
        background: #0e1014;
        display: grid; place-items: center;
        min-height: 320px;
        max-height: 68vh;
    }
    .slide { display: none; width: 100%; }
    .slide.on { display: block; }
    .slide img, .slide video {
        width: 100%; max-height: 68vh; object-fit: contain;
        display: block; background: #0e1014;
    }

    .navbtn {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 40px; height: 40px; border-radius: 50%;
        background: rgba(255,255,255,0.92); border: 0;
        display: grid; place-items: center; cursor: pointer;
        font-size: 18px; line-height: 1; color: #14161a;
        box-shadow: 0 3px 14px rgba(0,0,0,0.32);
        transition: transform 0.2s var(--ease);
        z-index: 2;
    }
    .navbtn:hover { transform: translateY(-50%) scale(1.07); }
    .navbtn.prev { left: 12px; }
    .navbtn.next { right: 12px; }
    .counter {
        position: absolute; top: 12px; right: 12px; z-index: 2;
        background: rgba(12,15,20,0.76); color: #fff;
        font-size: 12px; font-weight: 600;
        padding: 5px 11px; border-radius: 100px;
        backdrop-filter: blur(6px);
    }

    .dots {
        display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;
        padding: 13px; border-top: 1px solid var(--line);
    }
    .dot {
        width: 46px; height: 46px; border-radius: 8px; overflow: hidden;
        border: 2px solid transparent; cursor: pointer; background: #eef0f3;
        padding: 0; transition: border-color 0.2s;
    }
    .dot img { width: 100%; height: 100%; object-fit: cover; }
    .dot.on { border-color: var(--accent); }
    .dot .vid {
        width: 100%; height: 100%; display: grid; place-items: center;
        font-size: 15px; background: #22262e; color: #fff;
    }

    /* ------------------ Painel lateral ------------------ */
    .panel {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 24px;
        margin-bottom: 18px;
    }
    .panel h2 {
        font-size: 12px; font-weight: 700; letter-spacing: 0.1em;
        text-transform: uppercase; color: var(--dim); margin-bottom: 14px;
    }
    .title-block h1 {
        font-size: clamp(20px, 2.6vw, 25px); font-weight: 800;
        letter-spacing: -0.03em; line-height: 1.25; margin-bottom: 10px;
    }
    .metaline {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        font-size: 13px; color: var(--muted); margin-bottom: 4px;
    }
    .chip {
        background: var(--bg); border: 1px solid var(--line);
        border-radius: 100px; padding: 4px 11px;
        font-size: 12px; font-weight: 600; color: var(--muted);
    }

    .copy {
        font-size: 15px; line-height: 1.72; color: #2b2f36;
        white-space: pre-wrap; word-wrap: break-word;
    }
    .copy:empty::after { content: 'Sem texto para esta peça.'; color: var(--dim); }

    .checklist { list-style: none; display: grid; gap: 10px; }
    .checklist li {
        display: flex; align-items: flex-start; gap: 10px;
        font-size: 14.5px; line-height: 1.5;
    }
    .checklist .box {
        width: 18px; height: 18px; border-radius: 5px; flex-shrink: 0;
        border: 1.5px solid var(--line-strong); margin-top: 2px;
        display: grid; place-items: center; font-size: 11px; color: #fff;
    }
    .checklist .box.done { background: var(--ok); border-color: var(--ok); }
    .checklist li.done span { color: var(--muted); text-decoration: line-through; }

    /* ------------------ Decisão ------------------ */
    .decision { border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); padding: 24px; margin-bottom: 18px; }
    .decision .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 11px; margin-top: 4px; }
    @media (max-width: 420px) { .decision .actions { grid-template-columns: 1fr; } }
    .hidden { display: none; }

    .verdict {
        border-radius: var(--radius); padding: 20px 22px; margin-bottom: 18px;
        border: 1px solid;
    }
    .verdict.ok { background: var(--ok-bg); border-color: rgba(18,128,92,0.22); }
    .verdict.no { background: var(--warn-bg); border-color: rgba(180,65,58,0.22); }
    .verdict b { display: block; font-size: 15px; margin-bottom: 5px; }
    .verdict small { font-size: 13px; color: var(--muted); }
    .verdict .note { margin-top: 10px; font-size: 14px; white-space: pre-wrap; }

    /* ------------------ Comentários ------------------ */
    .thread { display: flex; flex-direction: column; gap: 13px; margin-bottom: 18px; }
    .msg { display: flex; gap: 11px; }
    .msg .av {
        width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
        display: grid; place-items: center;
        font-size: 12px; font-weight: 700; color: #fff;
        background: var(--dim);
    }
    .msg.mine .av { background: var(--accent); }
    .msg .bubble {
        background: var(--bg); border: 1px solid var(--line);
        border-radius: 11px; padding: 10px 13px; flex: 1; min-width: 0;
    }
    .msg .bubble b { display: block; font-size: 13px; font-weight: 700; margin-bottom: 3px; }
    .msg .bubble p { font-size: 14px; line-height: 1.55; white-space: pre-wrap; word-wrap: break-word; }
    .msg .bubble time { display: block; font-size: 11.5px; color: var(--dim); margin-top: 5px; }
    .no-msgs { font-size: 14px; color: var(--dim); padding: 6px 0 14px; }
@endsection

@section('content')
    @php
        $nomeSalvo = session('portal_reviewer_name', '');
        $pendente = $approval->isPending();
    @endphp

    <a class="back" href="{{ route('portal.index', $portal->token) }}">← Voltar para a lista</a>

    @if($errors->any())
        <div class="errbox">{{ $errors->first() }}</div>
    @endif

    <div class="split">

        {{-- ---------------- Coluna da arte ---------------- --}}
        <div>
            <div class="viewer">
                <div class="frame" id="frame">
                    @forelse($midias as $i => $midia)
                        <div class="slide {{ $i === 0 ? 'on' : '' }}" data-slide="{{ $i }}">
                            @if($midia->is_image)
                                <img src="{{ route('portal.media', [$portal->token, $midia->id]) }}"
                                     alt="{{ $task->title }} — {{ $i + 1 }}">
                            @else
                                <video controls preload="metadata"
                                       src="{{ route('portal.media', [$portal->token, $midia->id]) }}"></video>
                            @endif
                        </div>
                    @empty
                        <div style="color:#8b93a1;font-size:14px;padding:60px 20px;text-align:center">
                            Esta peça ainda não tem arquivo anexado.
                        </div>
                    @endforelse

                    @if($midias->count() > 1)
                        <button class="navbtn prev" type="button" data-nav="-1" aria-label="Anterior">‹</button>
                        <button class="navbtn next" type="button" data-nav="1" aria-label="Próxima">›</button>
                        <span class="counter"><span id="atual">1</span> / {{ $midias->count() }}</span>
                    @endif
                </div>

                @if($midias->count() > 1)
                    <div class="dots">
                        @foreach($midias as $i => $midia)
                            <button class="dot {{ $i === 0 ? 'on' : '' }}" type="button" data-goto="{{ $i }}">
                                @if($midia->is_image)
                                    <img src="{{ route('portal.media', [$portal->token, $midia->id]) }}" alt="" loading="lazy">
                                @else
                                    <span class="vid">▶</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ---------------- Coluna do conteúdo ---------------- --}}
        <div>
            <div class="panel title-block">
                <h1>{{ $task->title }}</h1>
                <div class="metaline">
                    @if($task->contentTypeLabel())
                        <span class="chip">{{ $task->contentTypeLabel() }}</span>
                    @endif
                    @if($task->publish_date)
                        <span class="chip">Publicação {{ $task->publish_date->format('d/m/Y') }}</span>
                    @endif
                    @if($approval->round > 1)
                        <span class="chip">{{ $approval->round }}ª versão</span>
                    @endif
                </div>
            </div>

            @if(filled($task->description))
                <div class="panel">
                    <h2>Texto da publicação</h2>
                    <div class="copy">{{ $task->description }}</div>
                </div>
            @endif

            @if($task->items->isNotEmpty())
                <div class="panel">
                    <h2>Checklist</h2>
                    <ul class="checklist">
                        @foreach($task->items as $item)
                            <li class="{{ $item->is_completed ? 'done' : '' }}">
                                <span class="box {{ $item->is_completed ? 'done' : '' }}">
                                    {{ $item->is_completed ? '✓' : '' }}
                                </span>
                                <span>{{ $item->description }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Decisão ou veredito já registrado --}}
            @if($pendente)
                <div class="decision">
                    <h2 style="font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--dim);margin-bottom:14px">
                        Sua decisão
                    </h2>

                    {{-- Aprovar --}}
                    <form method="POST" action="{{ route('portal.approve', [$portal->token, $approval->id]) }}" id="form-aprovar">
                        @csrf
                        <div class="field">
                            <label for="nome">Seu nome</label>
                            <input class="input" type="text" id="nome" name="reviewer_name"
                                   value="{{ old('reviewer_name', $nomeSalvo) }}"
                                   placeholder="Quem está aprovando?" maxlength="120" required>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn btn-ok">✓ Aprovar</button>
                            <button type="button" class="btn btn-warn" id="btn-ajuste">Pedir ajuste</button>
                        </div>
                    </form>

                    {{-- Pedir ajuste --}}
                    <form method="POST" action="{{ route('portal.reject', [$portal->token, $approval->id]) }}"
                          id="form-ajuste" class="hidden" style="margin-top:18px">
                        @csrf
                        <input type="hidden" name="reviewer_name" id="nome-espelho">

                        <div class="field">
                            <label for="feedback">O que precisa mudar? (opcional)</label>
                            <textarea class="textarea" id="feedback" name="feedback" maxlength="2000"
                                      placeholder="Ex.: trocar a cor do fundo, ajustar o texto do segundo slide…"></textarea>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn btn-warn">Enviar pedido de ajuste</button>
                            <button type="button" class="btn btn-plain" id="btn-cancelar">Cancelar</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="verdict {{ $approval->isApproved() ? 'ok' : 'no' }}">
                    <b>{{ $approval->isApproved() ? '✓ Peça aprovada' : 'Ajuste solicitado' }}</b>
                    <small>
                        por {{ $approval->reviewer_name }} ·
                        {{ $approval->responded_at?->format('d/m/Y \à\s H:i') }}
                    </small>
                    @if(filled($approval->feedback))
                        <div class="note">{{ $approval->feedback }}</div>
                    @endif
                </div>
            @endif

            {{-- Comentários --}}
            <div class="panel">
                <h2>Comentários</h2>

                @if($comentarios->isEmpty())
                    <p class="no-msgs">Nenhum comentário ainda. Escreva abaixo o que quiser registrar.</p>
                @else
                    <div class="thread">
                        @foreach($comentarios as $comentario)
                            <div class="msg {{ $comentario->is_from_client ? 'mine' : '' }}">
                                <span class="av">
                                    {{ mb_strtoupper(mb_substr($comentario->authorName(), 0, 1)) }}
                                </span>
                                <div class="bubble">
                                    <b>{{ $comentario->authorName() }}</b>
                                    <p>{{ $comentario->body }}</p>
                                    <time>{{ $comentario->created_at?->format('d/m/Y \à\s H:i') }}</time>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('portal.comment', [$portal->token, $approval->id]) }}">
                    @csrf
                    <div class="field">
                        <label for="nome-comentario">Seu nome</label>
                        <input class="input" type="text" id="nome-comentario" name="reviewer_name"
                               value="{{ old('reviewer_name', $nomeSalvo) }}" maxlength="120" required>
                    </div>
                    <div class="field">
                        <textarea class="textarea" name="body" maxlength="2000" required
                                  placeholder="Escreva um comentário para a equipe…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-accent">Enviar comentário</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    /* ---------------- Carrossel ---------------- */
    var slides = Array.prototype.slice.call(document.querySelectorAll('.slide'));
    var dots   = Array.prototype.slice.call(document.querySelectorAll('.dot'));
    var atual  = document.getElementById('atual');
    var indice = 0;

    function mostrar(novo) {
        if (!slides.length) return;

        indice = (novo + slides.length) % slides.length;

        slides.forEach(function (s, i) {
            s.classList.toggle('on', i === indice);

            // Pausa vídeo que saiu de vista, senão o áudio continua tocando.
            var video = s.querySelector('video');
            if (video && i !== indice) { video.pause(); }
        });

        dots.forEach(function (d, i) { d.classList.toggle('on', i === indice); });

        if (atual) { atual.textContent = indice + 1; }
    }

    document.querySelectorAll('[data-nav]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            mostrar(indice + parseInt(btn.dataset.nav, 10));
        });
    });

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            mostrar(parseInt(dot.dataset.goto, 10));
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.target.matches('input, textarea')) return;
        if (e.key === 'ArrowLeft')  { mostrar(indice - 1); }
        if (e.key === 'ArrowRight') { mostrar(indice + 1); }
    });

    /* ---------------- Alternância aprovar / pedir ajuste ---------------- */
    var formAprovar = document.getElementById('form-aprovar');
    var formAjuste  = document.getElementById('form-ajuste');
    var btnAjuste   = document.getElementById('btn-ajuste');
    var btnCancelar = document.getElementById('btn-cancelar');
    var nome        = document.getElementById('nome');
    var espelho     = document.getElementById('nome-espelho');

    if (btnAjuste) {
        btnAjuste.addEventListener('click', function () {
            // O nome é pedido uma vez só; o formulário de ajuste reaproveita.
            if (!nome.value.trim()) {
                nome.focus();
                nome.reportValidity();
                return;
            }

            espelho.value = nome.value.trim();
            formAprovar.classList.add('hidden');
            formAjuste.classList.remove('hidden');
            document.getElementById('feedback').focus();
        });
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', function () {
            formAjuste.classList.add('hidden');
            formAprovar.classList.remove('hidden');
        });
    }

    /* Evita duplo envio em conexão lenta. */
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('button[type="submit"]').forEach(function (b) {
                b.disabled = true;
                b.textContent = 'Enviando…';
            });
        });
    });
})();
</script>
@endsection
