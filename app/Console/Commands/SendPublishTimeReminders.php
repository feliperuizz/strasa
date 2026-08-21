<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\PublishTimeReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPublishTimeReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:publish-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notifications 5 minutes before the publish time for tasks in the publish column.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $targetTime = $now->copy()->addMinutes(5)->format('H:i:00');
        $today = $now->format('Y-m-d');

        $tasks = Task::with('assignees')
            ->whereHas('column', function ($query) {
                $query->where('is_publish_column', true);
            })
            ->where('publish_date', $today)
            ->where('publish_time', $targetTime)
            ->where('is_published', false)
            ->has('assignees')
            ->get();

        foreach ($tasks as $task) {
            foreach ($task->assignees as $user) {
                if ($user && !empty($user->notification_settings['publish_time_reminder_enabled'])) {
                    $user->notify(new PublishTimeReminderNotification($task));
                }
            }
        }

        $this->info("Sent reminders for {$tasks->count()} tasks.");
    }
}
