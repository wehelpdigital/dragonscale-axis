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
        $schedule->command('outreach:process-queue')->everyMinute()->withoutOverlapping();
        $schedule->command('outreach:fetch-replies')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('outreach:scrape-grids --limit=3')->everyTwoMinutes()->withoutOverlapping();
        $schedule->command('outreach:enrich-leads --limit=5')->everyThreeMinutes()->withoutOverlapping();
        $schedule->command('outreach:categorize-leads --limit=100')->everyFiveMinutes()->withoutOverlapping();
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
