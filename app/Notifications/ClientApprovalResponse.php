<?php

namespace App\Notifications;

use App\Models\TaskApproval;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Avisa os responsáveis quando o cliente responde no painel de aprovação.
 *
 * Propositalmente NÃO implementa ShouldQueue: a fila do projeto é processada
 * pelo cron a cada minuto, e uma resposta de cliente é o tipo de coisa que a
 * equipe quer saber na hora. O envio é feito dentro de try/catch pelo
 * ApprovalService, então uma falha aqui não afeta a ação do cliente.
 */
class ClientApprovalResponse extends Notification
{
    public function __construct(
        public TaskApproval $approval,
        public string $tipo,          // approved | rejected | commented
        public ?string $trecho = null
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        $task = $this->approval->task;
        $cliente = $this->approval->client->name;
        $revisor = $this->approval->reviewer_name;
        $url = route('approvals.index', ['client' => $this->approval->client_id]);

        [$titulo, $corpo] = match ($this->tipo) {
            'approved' => [
                "✅ {$cliente} aprovou",
                trim(($revisor ? "{$revisor} aprovou" : 'Aprovado').": {$task->title}"),
            ],
            'rejected' => [
                "🔴 {$cliente} pediu ajuste",
                $this->approval->feedback
                    ? "{$task->title} — ".$this->resumo($this->approval->feedback)
                    : "{$task->title} voltou para ajustes.",
            ],
            default => [
                "💬 {$cliente} comentou",
                "{$task->title} — ".$this->resumo($this->trecho ?? ''),
            ],
        };

        return (new WebPushMessage)
            ->title($titulo)
            ->icon(asset('icon-192.png'))
            ->body($corpo)
            ->action('Ver aprovações', $url)
            ->data(['url' => $url]);
    }

    private function resumo(string $texto): string
    {
        $limpo = trim(preg_replace('/\s+/', ' ', $texto));

        return mb_strlen($limpo) > 90 ? mb_substr($limpo, 0, 90).'…' : $limpo;
    }
}
