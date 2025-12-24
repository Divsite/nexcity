<?php

namespace App\Console\Commands\Reload;

use Illuminate\Console\Command;

class All extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reload:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload all cache, database and upload file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('reload:cache');
        $this->call('reload:db', ['--dev' => true]);

        $this->info('Successfully reload caches, database and upload file');

        return Command::SUCCESS;
    }
}
