<?php

namespace App\Models\Forms;

use App\Models\FormProcesses\FormProcess;
use App\Models\FormProcesses\FormProcessStatus;
use App\Models\FormSubmissionFiles\FormSubmissionFile;
use App\Models\FormSubmissions\FormSubmission;
use App\Models\FormTypes\FormType;
use App\Service\Forms\FormCleaner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Form extends Model
{
    use HasFactory;

    protected $casts = [
        'properties' => 'array',
    ];

    protected $appends = ['prepare_fields', 'prepare_input'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Form $form) {
            // Delete submission files
            if ($form->submissions) {
                foreach ($form->submissions as $submission) {
                    if ($submission->files) {
                        // Delete files
                        Storage::deleteDirectory(FormSubmissionFile::FILE_PATH.$submission->id);

                        $submission->files()->delete();
                    }
                }
            }

            $form->submissions()->delete();
        });
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(FormType::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(FormProcessStatus::class)->where('is_default', '=', false);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(FormProcess::class);
    }

    public function defaultStatus(): HasOne
    {
        return $this->hasOne(FormProcessStatus::class)->where('is_default', '=', true);
    }

    protected function prepareFields(): Attribute
    {
        return new Attribute(
            get: function () {
                $items = [];
                if ($this->properties) {
                    foreach ($this->properties as $property) {
                        $formCleaner = new FormCleaner();

                        // General field
                        $data = [
                            'id' => $property['id'],
                            'name' => $property['name'],
                            'type' => $property['type'],
                        ];

                        $items[] = array_merge($data, $formCleaner->getData($property));
                    }
                }

                return $items;
            }
        );
    }

    protected function prepareInput(): Attribute
    {
        return new Attribute(
            get: function () {
                $formField = form_fields();

                $values = [];
                if ($this->prepareFields) {
                    foreach ($this->prepareFields as $property) {

                        if (
                            $property['type'] == $formField['text'] ||
                            $property['type'] == $formField['email'] ||
                            $property['type'] == $formField['textarea'] ||
                            $property['type'] == $formField['hidden'] ||
                            $property['type'] == $formField['phone']
                        ) {
                            $dataSourceInput = data_source_input();

                            $key = $property['id'];
                            $val = null;

                            if ($property['data_source'] == $dataSourceInput['text']) {
                                $val = $property['prefill'];
                            }

                            if ($property['data_source'] == $dataSourceInput['current_user']) {
                                $userInfo = user_info();
                                $val = $userInfo[$property['column_name']] ?? null;
                            }

                            $values[$key] = $val;
                        }

                        if (
                            $property['type'] == $formField['date'] ||
                            $property['type'] == $formField['number'] ||
                            $property['type'] == $formField['url'] ||
                            $property['type'] == $formField['radio'] ||
                            $property['type'] == $formField['checkbox'] ||
                            $property['type'] == $formField['select'] ||
                            $property['type'] == $formField['currency']
                        ) {
                            $key = $property['id'];
                            $val = $property['prefill'] ?? null;

                            $values[$key] = $val;
                        }

                        if (
                            $property['type'] == $formField['file'] ||
                            $property['type'] == $formField['checkbox_group']
                        ) {
                            $key = $property['id'];

                            $values[$key] = [];
                        }

                        if ($property['type'] == $formField['time']) {
                            $key = $property['id'];
                            $val = null;

                            if ($property['prefill']) {
                                if ($property['time_24hr']) {
                                    $val = Carbon::createFromFormat("g:i A", $property['prefill'])->format("H:i");

                                    if ($property['enable_seconds']) {
                                        $val = Carbon::createFromFormat("g:i A", $property['prefill'])->format("H:i:s");
                                    }
                                } else {
                                    $val = Carbon::createFromFormat("g:i A", $property['prefill'])->format("g:i A");

                                    if ($property['enable_seconds']) {
                                        $val = Carbon::createFromFormat("g:i A", $property['prefill'])->format("g:i:s A");
                                    }
                                }
                            }

                            $values[$key] = $val;
                        }

                        if ($property['type'] == $formField['time_range']) {
                            $key = $property['id'];

                            $valFrom = null;
                            $valTo = null;

                            if ($property['prefill_from']) {
                                if ($property['time_24hr']) {
                                    $valFrom = Carbon::createFromFormat("g:i A", $property['prefill_from'])->format("H:i");

                                    if ($property['enable_seconds']) {
                                        $valFrom = Carbon::createFromFormat("g:i A", $property['prefill_from'])->format("H:i:s");
                                    }
                                } else {
                                    $valFrom = Carbon::createFromFormat("g:i A", $property['prefill_from'])->format("g:i A");

                                    if ($property['enable_seconds']) {
                                        $valFrom = Carbon::createFromFormat("g:i A", $property['prefill_from'])->format("g:i:s A");
                                    }
                                }
                            }

                            if ($property['prefill_to']) {
                                if ($property['time_24hr']) {
                                    $valTo = Carbon::createFromFormat("g:i A", $property['prefill_to'])->format("H:i");

                                    if ($property['enable_seconds']) {
                                        $valTo = Carbon::createFromFormat("g:i A", $property['prefill_to'])->format("H:i:s");
                                    }
                                } else {
                                    $valTo = Carbon::createFromFormat("g:i A", $property['prefill_to'])->format("g:i A");

                                    if ($property['enable_seconds']) {
                                        $valTo = Carbon::createFromFormat("g:i A", $property['prefill_to'])->format("g:i:s A");
                                    }
                                }
                            }

                            $values[$key] = [
                                'from' => $valFrom,
                                'to' => $valTo,
                            ];
                        }

                        if ($property['type'] == $formField['date_range']) {
                            $key = $property['id'];

                            $val = null;
                            if (!empty($property['prefill'])) {
                                $val = implode(' - ', $property['prefill']);
                            }

                            $values[$key] = $val;
                        }
                    }
                }

                return $values;
            }
        );
    }
}
