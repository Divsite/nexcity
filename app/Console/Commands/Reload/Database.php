<?php

namespace App\Console\Commands\Reload;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class Database extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reload:db {--dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload database and clear all uploaded file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (app()->environment() == 'production') {
            $this->comment('Cannot run on production environment.');

            return Command::FAILURE;
        }

        $this->call('migrate:fresh', ['--quiet' => true]);
        File::cleanDirectory(storage_path('app/uploads/avatar'));
        File::cleanDirectory(storage_path('app/uploads/images'));
        File::cleanDirectory(storage_path('app/form-submission-files'));

        if ($this->option('dev')) {
            $this->call('db:seed');
        }

        $this->info('Successfully reload database.');

        return Command::SUCCESS;
    }
}
