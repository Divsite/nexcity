<?php

namespace App\Jobs\FormSubmissions;

use App\Models\Forms\Form;
use App\Models\FormSubmissionFiles\FormSubmissionFile;
use App\Models\FormSubmissionFiles\TemporaryFile;
use App\Models\Users\User;
use App\Utilities\FormProcesses\ProcessUserCollector;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\WebhookServer\WebhookCall;

class StoreFormSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Form $form;
    public array $formData;
    public User $user;
    public string $locale;

    /**
     * Create a new job instance.
     */
    public function __construct(Form $form, array $formData, User $user, string $locale)
    {
        $this->form = $form;
        $this->formData = $formData;
        $this->user = $user;
        $this->locale = $locale;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $model = $this->form->submissions()->create([
            'data' => $this->getFormData(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // If file upload available on the form
        $this->processFiles($model);

        if (!empty($this->form->webhook_url)) {
            $payload = [
                'submission_id' => $model->id,
                'form_name' => $this->form->name,
                'data' => $this->webhookPayload(),
                'created_at' => $model->created_at ? Carbon::createFromFormat('Y-m-d H:i:s',
                    $model->created_at)->format('Y-m-d H:i:s') : null,
                'updated_at' => $model->updated_at ? Carbon::createFromFormat('Y-m-d H:i:s',
                    $model->updated_at)->format('Y-m-d H:i:s') : null,
            ];

            $url = $this->form->webhook_url;
            if ($this->form->use_current_url) {
                $url = config('app.url').$this->form->webhook_url;
            }

            WebhookCall::create()
                ->url($url)
                ->doNotSign()
                ->payload($payload)
                ->dispatchSync();
        }

        if ($this->form->defaultStatus) {
            $model->currentStatus()->create([
                'status_id' => $this->form->defaultStatus->id,
            ]);

            $model->statuses()->create([
                'status_id' => $this->form->defaultStatus->id,
                'created_by' => $this->user->id,
                'updated_by' => $this->user->id,
            ]);

            $model->processes()->create([
                'status_id' => $this->form->defaultStatus->id,
                'created_by' => $this->user->id,
                'updated_by' => $this->user->id,
            ]);
        }

        // Generate processors to authorize view submission
        $this->generateProcessors($model);

        // Generate current tasks
        $this->generateCurrentTask($model);

        activity(__('messages.form_submissions', [], $this->locale))
            ->causedBy($this->user)
            ->performedOn($model)
            ->log(__('messages.form_submissions_has_been_created', ['name' => $this->form->name], $this->locale));
    }

    public function getFormData(): array
    {
        $data = $this->formData;
        $properties = collect($this->form->prepareFields);
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

    public function webhookPayload(): array
    {
        $data = $this->formData;
        $properties = collect($this->form->prepareFields);
        $formFields = form_fields();

        $items = [];

        foreach ($data as $fieldId => $value) {
            $field = $properties->where('id', $fieldId)->first();

            if ($field) {

                if ($field['type'] == $formFields['text']) {
                    $inputGroupTextLeft = null;
                    $inputGroupTextRight = null;

                    if ($field['input_group'] && $field['display_input_group_text']) {
                        $inputGroupTextLeft = $field['left_text_input_group'];
                        $inputGroupTextRight = $field['right_text_input_group'];
                    }

                    if ($value) {
                        $value = $inputGroupTextLeft.$value.$inputGroupTextRight;
                    }
                }

                $items[] = [
                    'id' => $field['id'],
                    'name' => $field['name'],
                    'value' => $value,
                ];
            }
        }

        return $items;
    }

    public function processFiles($model)
    {
        $data = $this->formData;
        $properties = collect($this->form->prepareFields);
        $formFields = form_fields();

        foreach ($data as $fieldId => $value) {
            $field = $properties->where('id', $fieldId)->first();

            if ($field) {
                if ($field['type'] == $formFields['file']) {
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

    public function generateProcessors($model): void
    {
        $processes = $this->form->processes()->where('status', true)->get();

        if ($processes->isNotEmpty()) {
            $allUserIds = collect();

            foreach ($processes as $process) {
                $userIds = ProcessUserCollector::get($process);

                $allUserIds = $allUserIds->merge($userIds)->unique();
            }

            if ($allUserIds->isNotEmpty()) {
                foreach ($allUserIds as $userId) {
                    if (!$model->processors()->where('user_id', $userId)->exists()) {
                        $model->processors()->create(['user_id' => $userId]);
                    }
                }
            }
        }
    }

    public function generateCurrentTask($model): void
    {
        if (!empty($model->currentStatus)) {
            // Generate current tasks for first process
            if ($model->currentStatus->status->is_default && empty($model->currentStatus->process)) {
                $process = $this->form->processes()->where('order', '=', 1)->first();

                if ($process) {
                    $userIds = ProcessUserCollector::get($process);

                    foreach ($userIds as $userId) {
                        $model->currentTasks()->create([
                            'user_id' => $userId,
                            'process_id' => $process->id,
                        ]);
                    }
                }
            }
        }
    }
}
