<?php

namespace App\Jobs\FormSubmissions;

use App\Models\FormSubmissionFiles\FormSubmissionFile;
use App\Models\FormSubmissionFiles\TemporaryFile;
use App\Models\FormSubmissions\FormSubmission;
use App\Models\Users\User;
use App\Utilities\FormProcesses\ProcessUserCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateFormSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public FormSubmission $formSubmission;
    public array $formData;
    public User $user;
    public string $locale;
    public bool $revertSubmitter;

    /**
     * Create a new job instance.
     */
    public function __construct(
        FormSubmission $formSubmission,
        array $formData,
        User $user,
        string $locale,
        $revertSubmitter = false
    ) {
        $this->formSubmission = $formSubmission;
        $this->formData = $formData;
        $this->user = $user;
        $this->locale = $locale;
        $this->revertSubmitter = $revertSubmitter;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->formSubmission->update([
            'data' => $this->getFormData(),
            'updated_by' => $this->user->id,
        ]);

        // If file upload available on the form
        $this->processFiles($this->formSubmission);

        if ($this->revertSubmitter && !empty($this->formSubmission->form->defaultStatus)) {
            $this->formSubmission->currentStatus()->update([
                'status_id' => $this->formSubmission->form->defaultStatus->id,
                'process_id' => null,
                'comment' => null,
                'is_revert_submitter' => false,
                'is_end_process' => false,
            ]);

            $this->formSubmission->statuses()->create([
                'status_id' => $this->formSubmission->form->defaultStatus->id,
                'created_by' => $this->user->id,
                'updated_by' => $this->user->id,
            ]);

            $this->formSubmission->processes()->create([
                'status_id' => $this->formSubmission->form->defaultStatus->id,
                'created_by' => $this->user->id,
                'updated_by' => $this->user->id,
            ]);

            $this->generateCurrentTasks();
        }

        activity(__('messages.form_submissions', [], $this->locale))
            ->causedBy($this->user)
            ->performedOn($this->formSubmission)
            ->log(__('messages.form_submissions_has_been_updated', ['id' => $this->formSubmission->id], $this->locale));
    }

    public function getFormData()
    {
        $data = $this->formData;
        $properties = collect($this->formSubmission->form->prepareFields);
        $formFields = form_fields();

        $items = [];

        foreach ($data as $fieldId => $value) {
            $field = $properties->where('id', $fieldId)->first();

            if ($field) {
                if ($field['type'] != $formFields['file']) {
                    $items[$fieldId] = $value;
                }
            }
        }

        return $items;
    }

    public function processFiles($model)
    {
        $data = $this->formData;
        $properties = collect($this->formSubmission->form->prepareFields);
        $formFields = form_fields();

        foreach ($data as $fieldId => $value) {
            $field = $properties->where('id', $fieldId)->first();

            if ($field) {
                if ($field['type'] == $formFields['file']) {
                    // File value is array because file is uploaded first
                    if (!empty($value)) {

                        // Delete current file if exist
                        if ($this->formSubmission->files()->exists()) {
                            foreach ($this->formSubmission->files as $file) {
                                // Delete file on location
                                $path = FormSubmissionFile::FILE_PATH.$this->formSubmission->id.'/'.$file->name;
                                Storage::delete($path);

                                // Delete data
                                $file->delete();
                            }
                        }

                        foreach ($value as $item) {
                            // Get tmp file
                            $tmp = TemporaryFile::where('folder', $item)->first();

                            if ($tmp) {
                                $tmpFolder = $tmp->folder;
                                $tmpFolderFileName = $tmpFolder.'/'.$tmp->filename;
                                $tmpFileExtension = File::extension(storage_path(TemporaryFile::FILE_PATH.$tmpFolderFileName));
                                $tmpFileSize = Storage::size(TemporaryFile::FILE_PATH.$tmpFolderFileName);
                                $tmpFileMimeType = Storage::mimeType(TemporaryFile::FILE_PATH.$tmpFolderFileName);

                                // Unique name
                                $fileName = Str::uuid().'-'.now()->timestamp.'.'.$tmpFileExtension;
                                $toPath = $model->id.'/'.$fileName;

                                // Move tmp file to actual location
                                Storage::move(TemporaryFile::FILE_PATH.$tmpFolderFileName,
                                    FormSubmissionFile::FILE_PATH.$toPath);

                                // Create form submission files
                                FormSubmissionFile::create([
                                    'form_submission_id' => $model->id,
                                    'disk' => 'local',
                                    'field' => $field['id'],
                                    'label' => $field['label'],
                                    'name' => $fileName,
                                    'mime_type' => $tmpFileMimeType,
                                    'extension' => $tmpFileExtension,
                                    'size' => $tmpFileSize,
                                ]);

                                // Delete tmp data
                                $tmp->delete();

                                // Delete tmp folder
                                Storage::deleteDirectory(TemporaryFile::FILE_PATH.$tmpFolder);
                            }
                        }

                    }
                }
            }
        }
    }

    public function generateCurrentTasks(): void
    {
        // Delete all completed tasks on the submission if exists
        // Completed tasks exists cause of revert to submitter (processors decided to revert, then the process is completed)
        if ($this->formSubmission->completedTasks()->exists()) {
            $this->formSubmission->completedTasks()->delete();
        }

        // Generate current tasks for the first process after submitter update submission
        if ($this->formSubmission->currentStatus->status->is_default && empty($this->formSubmission->currentStatus->process)) {
            $process = $this->formSubmission->form->processes()->where('order', '=', 1)->first();

            if ($process) {
                $userIds = ProcessUserCollector::get($process);

                foreach ($userIds as $userId) {
                    $this->formSubmission->currentTasks()->create([
                        'user_id' => $userId,
                        'process_id' => $process->id,
                    ]);
                }
            }
        }
    }
}
