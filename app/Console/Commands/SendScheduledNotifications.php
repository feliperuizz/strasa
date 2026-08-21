<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendScheduledNotifications extends Command
{
    protected $signature = 'notifications:send-scheduled';
    protected $description = 'Send scheduled push notifications to users based on their preferences';

    public function handle()
    {
        $now = now()->format('H:i');

        // Buscar todos os usuários que têm push subscriptions
        $users = \App\Models\User::whereHas('pushSubscriptions')->get();

        foreach ($users as $user) {
            $settings = $user->notification_settings ?? [];

            // Resumo Diário
            if (!empty($settings['daily_enabled']) && ($settings['daily_time'] ?? '08:00') === $now) {
                // Conta tarefas atribuídas para hoje (assumindo publish_date ou due_date)
                // O Strasa tem o campo publish_date nas tarefas
                $tasksCount = \App\Models\Task::whereHas('assignees', fn($q) => $q->where('users.id', $user->id))
                    ->whereDate('publish_date', now()->toDateString())
                    ->count();

                if ($tasksCount > 0) {
                    $user->notify(new \App\Notifications\DailyTasksNotification($tasksCount));
                }
            }

            // Postagens
            if (!empty($settings['publish_enabled']) && ($settings['publish_time'] ?? '10:00') === $now) {
                $publishCount = \App\Models\Task::whereHas('assignees', fn($q) => $q->where('users.id', $user->id))
                    ->whereDate('publish_date', now()->toDateString())
                    ->whereHas('column', function ($query) {
                        $query->where('is_publish_column', true);
                    })
                    ->count();

                if ($publishCount > 0) {
                    $user->notify(new \App\Notifications\PublishTasksNotification($publishCount));
                }
            }
        }

        $this->info('Notificações agendadas processadas.');
    }
}
