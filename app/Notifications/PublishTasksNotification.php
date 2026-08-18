<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PublishTasksNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $publishCount;

    /**
     * Create a new notification instance.
     */
    public function __construct($publishCount)
    {
        $this->publishCount = $publishCount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Strasa - Lembrete de Postagens')
            ->icon('/icon-192.png')
            ->body("Você tem {$this->publishCount} postagem(ns) ou agendamento(s) para cuidar hoje.")
            ->action('Ver Painel', 'view_dashboard')
            ->data(['url' => url('/dashboard')]);
    }
}
