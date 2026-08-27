<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // OutreachEngine ("Lead Finder"). QUEUE_CONNECTION is sync, so cron is
        // the module's real execution path rather than a queue worker. Every
        // entry is withoutOverlapping() because a second copy of a run would
        // re-send the email the first one is still holding.
        /* AniSystem's mail.
         *
         * Every ten minutes rather than once a day, because the command does
         * two things: it checks whether any season's notification HOUR has
         * come round (a hundred seasons, a hundred different hours — one cron
         * cannot be set to all of them), and it drains whatever is due in the
         * book. A season is still only ever queued once a day; the guard is
         * notifyLastSentDate, written the moment the rows are made so a run
         * that dies mid-send cannot post everybody a second copy. */
        $schedule->command('anisystem:mail-run --limit=50')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('outreach:process-queue')->everyMinute()->withoutOverlapping();
        $schedule->command('outreach:fetch-replies')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('outreach:scrape-grids --limit=3')->everyTwoMinutes()->withoutOverlapping();
        $schedule->command('outreach:enrich-leads --limit=5')->everyThreeMinutes()->withoutOverlapping();
        $schedule->command('outreach:categorize-leads --limit=100')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('outreach:verify-emails --limit=50')->everyFiveMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
