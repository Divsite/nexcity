<?php

namespace App\Console;

use App\Console\Commands\FormSubmissionFiles\DeleteTemporaryFiles;
use App\Console\Commands\ReleaseUnpaidQurbanHolds;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // Delete temporary files for form submission files
        $schedule->command(DeleteTemporaryFiles::class)->daily();

        // Patungan lines from the app with no deposit in time go back on the
        // board. Hourly: a line should free up the day its hold ends.
        $schedule->command(ReleaseUnpaidQurbanHolds::class)->hourly()->withoutOverlapping();
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
