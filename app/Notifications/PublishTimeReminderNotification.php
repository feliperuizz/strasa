<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PublishTimeReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        $projectUrl = route('projects.board', $this->task->project_id);

        return (new WebPushMessage)
            ->title('Publicação em 5 minutos!')
            ->icon(asset('icon-192.png'))
            ->body("A tarefa '{$this->task->title}' está programada para agora. Prepare-se!")
            ->action('Acessar Quadro', $projectUrl)
            ->data(['url' => $projectUrl]);
    }
}
