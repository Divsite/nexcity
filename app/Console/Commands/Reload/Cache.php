<?php

namespace App\Console\Commands\Reload;

use Illuminate\Console\Command;

class Cache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reload:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload all cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('event:clear');
        $this->call('optimize:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('config:clear');
        $this->call('cache:clear');

        $this->info('Successfully reload caches.');

        Command::SUCCESS;
    }
}
