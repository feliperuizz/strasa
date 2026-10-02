{{-- Uma peça já respondida no painel do cliente: aprovada (com o horário
     em que vai ao ar ou foi ao ar) ou com ajuste pedido. Horários no fuso de
     Brasília; $postagem vem de Task::situacaoDePostagem(). --}}
@php
    $task = $aprovacao->task;
    $capa = $task->approvalMedia()->firstWhere('is_image', true);
    $respondida = $aprovacao->responded_at?->copy()->setTimezone('America/Sao_Paulo');
    $quando = $postagem['quando'] ?? null;
    $redes = filled($postagem['redes'] ?? null) ? ' · '.$postagem['redes'] : '';

    [$selo, $classe, $agenda] = match (true) {
        ! $aprovacao->isApproved() => ['Ajuste pedido', 'badge-rejected', null],
        $postagem['estado'] === 'publicado' => ['Publicado', 'badge-approved', ($quando ? 'Publicado em '.$quando->format('d/m \à\s H:i') : 'Publicado').$redes],
        $postagem['estado'] === 'agendado' => ['Agendado', 'badge-scheduled', ($quando ? 'Agendado para '.$quando->format('d/m \à\s H:i') : 'Agendado').$redes],
        default => ['Aprovado', 'badge-approved', $quando ? 'Previsto para '.$quando->format('d/m \à\s H:i') : 'Aguardando data de publicação'],
    };
@endphp

<a class="answered-row" href="{{ route('portal.show', [$portal->token, $aprovacao->id]) }}">
    @if($capa)
        <img class="mini" src="{{ route('portal.media', [$portal->token, $capa->id, 'v' => 'mini']) }}"
             alt="" loading="lazy" decoding="async">
    @else
        <span class="mini"></span>
    @endif

    <span class="txt">
        <b>{{ $task->title }}</b>
        @if($agenda)
            <span class="agenda">{{ $agenda }}</span>
        @endif
        <small>
            {{ $aprovacao->isApproved() ? 'Aprovado' : 'Pedido' }} por {{ $aprovacao->reviewer_name }}
            @if($respondida) · {{ $respondida->format('d/m/Y \à\s H:i') }} @endif
        </small>
    </span>

    <span class="badge {{ $classe }}">{{ $selo }}</span>
</a>
