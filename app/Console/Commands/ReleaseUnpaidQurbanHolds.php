<?php

namespace App\Console\Commands;

use App\Actions\Qurban\ReleaseUnpaidHolds;
use Illuminate\Console\Command;

/**
 * Hourly: patungan lines taken from the app with no deposit in time go back
 * on the board. See ReleaseUnpaidHolds.
 */
class ReleaseUnpaidQurbanHolds extends Command
{
    protected $signature = 'qurban:release-unpaid-holds';

    protected $description = 'Release patungan lines taken from the app that never got a deposit';

    public function handle(ReleaseUnpaidHolds $release): int
    {
        $count = $release->handle();
        $this->info("Released {$count} unpaid patungan line(s).");

        return self::SUCCESS;
    }
}
