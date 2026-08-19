<?php

namespace App\Console\Commands;

use App\Mail\DailyBriefingMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyBriefingEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:daily-briefing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a daily briefing email to users at 9 AM with their tasks for today.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');

        // Find users with daily_briefing_email_enabled
        $users = User::whereNotNull('notification_settings')
            ->where('notification_settings->daily_briefing_email_enabled', '1')
            ->get();

        $count = 0;

        foreach ($users as $user) {
            // Get tasks for today or overdue
            $tasks = $user->tasks()
                ->with(['project', 'client'])
                ->where('is_published', false)
                ->where(function($query) use ($today) {
                    $query->where('publish_date', '<=', $today)
                          ->orWhereNull('publish_date'); // maybe they want tasks without date?
                })
                ->whereNotNull('publish_date') // Only tasks with date
                ->orderBy('publish_time')
                ->get();

            if ($tasks->isNotEmpty()) {
                Mail::to($user->email)->send(new DailyBriefingMail($user, $tasks));
                $count++;
            }
        }

        $this->info("Daily briefing sent to {$count} users.");
    }
}
