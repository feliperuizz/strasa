@extends('portal.layout', ['title' => 'Peças para aprovar'])

@section('styles')
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(258px, 1fr));
        gap: 18px;
    }
    .piece {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
        display: flex; flex-direction: column;
        transition: transform 0.25s var(--ease), box-shadow 0.25s var(--ease), border-color 0.25s;
    }
    .piece:hover {
        transform: translateY(-3px);
        border-color: var(--line-strong);
        box-shadow: 0 14px 32px -18px rgba(15, 20, 30, 0.3);
    }
    .thumb {
        aspect-ratio: 1 / 1;
        background: #eef0f3;
        position: relative;
        overflow: hidden;
    }
    .thumb img { width: 100%; height: 100%; object-fit: cover; }
    .thumb .empty {
        width: 100%; height: 100%;
        display: grid; place-items: center;
        color: var(--dim); font-size: 13px;
    }
    .thumb .count {
        position: absolute; right: 10px; top: 10px;
        background: rgba(12, 15, 20, 0.78);
        color: #fff; font-size: 11.5px; font-weight: 600;
        padding: 4px 9px; border-radius: 100px;
        backdrop-filter: blur(6px);
    }
    .piece-body { padding: 15px 16px 17px; display: flex; flex-direction: column; gap: 9px; flex: 1; }
    .piece-body .kind {
        font-size: 11px; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; color: var(--dim);
    }
    .piece-body h3 {
        font-size: 15.5px; font-weight: 700; letter-spacing: -0.015em;
        line-height: 1.35;
    }
    .piece-body .when { font-size: 12.5px; color: var(--muted); margin-top: auto; }

    .section-title {
        display: flex; align-items: center; gap: 10px;
        font-size: 13px; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; color: var(--dim);
        margin: 42px 0 16px;
    }
    .section-title::after {
        content: ''; flex: 1; height: 1px; background: var(--line);
    }
    .section-title:first-of-type { margin-top: 0; }

    .empty-state {
        background: var(--surface);
        border: 1px dashed var(--line-strong);
        border-radius: var(--radius);
        padding: 54px 30px; text-align: center;
    }
    .empty-state .big { font-size: 34px; margin-bottom: 12px; }
    .empty-state h3 { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
    .empty-state p { color: var(--muted); font-size: 14.5px; }

    /* Lista compacta das já respondidas */
    .answered { display: flex; flex-direction: column; gap: 9px; }
    .answered-row {
        display: flex; align-items: center; gap: 14px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 11px;
        padding: 11px 14px;
        transition: border-color 0.2s;
    }
    .answered-row:hover { border-color: var(--line-strong); }
    .answered-row .mini {
        width: 42px; height: 42px; border-radius: 8px;
        object-fit: cover; background: #eef0f3; flex-shrink: 0;
    }
    .answered-row .txt { flex: 1; min-width: 0; }
    .answered-row .txt b {
        display: block; font-size: 14px; font-weight: 600;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .answered-row .txt small { font-size: 12px; color: var(--muted); }
@endsection

@section('content')
    <div class="page-head">
        <h1>Peças para aprovar</h1>
        <p>Revise cada peça e aprove ou peça ajuste. Seus comentários vão direto para a nossa equipe.</p>
    </div>

    @if($portal->welcome_message)
        <div class="welcome">{{ $portal->welcome_message }}</div>
    @endif

    @if($pendentes->isEmpty() && $respondidas->isEmpty())
        <div class="empty-state">
            <div class="big">📭</div>
            <h3>Nada por aqui ainda</h3>
            <p>Quando enviarmos uma peça para aprovação, ela aparece nesta tela.</p>
        </div>
    @else

        @if($pendentes->isNotEmpty())
            <div class="section-title">Aguardando você ({{ $pendentes->count() }})</div>

            <div class="grid">
                @foreach($pendentes as $aprovacao)
                    @php
                        $task = $aprovacao->task;
                        $midias = $task->approvalMedia();
                        $capa = $midias->firstWhere('is_image', true);
                    @endphp

                    <a class="piece" href="{{ route('portal.show', [$portal->token, $aprovacao->id]) }}">
                        <div class="thumb">
                            @if($capa)
                                <img src="{{ route('portal.media', [$portal->token, $capa->id]) }}"
                                     alt="{{ $task->title }}" loading="lazy">
                            @else
                                <div class="empty">Sem prévia</div>
                            @endif

                            @if($midias->count() > 1)
                                <span class="count">{{ $midias->count() }} arquivos</span>
                            @endif
                        </div>

                        <div class="piece-body">
                            @if($task->contentTypeLabel())
                                <span class="kind">{{ $task->contentTypeLabel() }}</span>
                            @endif
                            <h3>{{ $task->title }}</h3>
                            <span class="when">
                                Enviado {{ $aprovacao->submitted_at?->diffForHumans() }}
                                @if($aprovacao->round > 1) · {{ $aprovacao->round }}ª versão @endif
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        @if($respondidas->isNotEmpty())
            <div class="section-title">Já respondidas</div>

            <div class="answered">
                @foreach($respondidas as $aprovacao)
                    @php
                        $task = $aprovacao->task;
                        $capa = $task->approvalMedia()->firstWhere('is_image', true);
                    @endphp

                    <a class="answered-row" href="{{ route('portal.show', [$portal->token, $aprovacao->id]) }}">
                        @if($capa)
                            <img class="mini" src="{{ route('portal.media', [$portal->token, $capa->id]) }}"
                                 alt="" loading="lazy">
                        @else
                            <span class="mini"></span>
                        @endif

                        <span class="txt">
                            <b>{{ $task->title }}</b>
                            <small>
                                {{ $aprovacao->reviewer_name }} ·
                                {{ $aprovacao->responded_at?->format('d/m/Y \à\s H:i') }}
                            </small>
                        </span>

                        <span class="badge {{ $aprovacao->isApproved() ? 'badge-approved' : 'badge-rejected' }}">
                            {{ $aprovacao->isApproved() ? 'Aprovado' : 'Ajuste pedido' }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

    @endif
@endsection
