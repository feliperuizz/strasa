<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DailyTasksNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $taskCount;

    public function __construct($taskCount)
    {
        $this->taskCount = $taskCount;
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Strasa - Resumo do Dia')
            ->icon('/icon-192.png')
            ->body("Você tem {$this->taskCount} tarefa(s) programada(s) para hoje!")
            ->action('Ver Painel', 'view_dashboard')
            ->data(['url' => url('/dashboard')]);
    }
}
