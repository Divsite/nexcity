<?php

namespace App\Console\Commands\FormSubmissionFiles;

use App\Models\FormSubmissionFiles\TemporaryFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteTemporaryFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'form-submission-files:delete-temporary-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete temporary files for form submission uploads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $items = TemporaryFile::all();

        foreach ($items as $item) {
            $createdAt = $item->created_at;

            if ($createdAt->addDay()->timestamp <= now()->timestamp) {
                // Delete folder
                $folder = $item->folder;
                Storage::deleteDirectory(TemporaryFile::FILE_PATH.$folder);

                // Delete data
                $item->delete();

                $this->info('Deleted folder: '.$folder);
            }
        }
    }
}
