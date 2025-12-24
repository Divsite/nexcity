<?php

namespace App\Models\FormSubmissions;

use App\Models\Forms\Form;
use App\Models\FormSubmissionFiles\FormSubmissionFile;
use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Cknow\Money\Money;

class FormSubmission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (FormSubmission $formSubmission) {
            if ($formSubmission->files()->exists()) {
                // Delete files
                Storage::deleteDirectory(FormSubmissionFile::FILE_PATH.$formSubmission->id);

                // Delete data
                $formSubmission->files()->delete();
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FormSubmissionFile::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(FormSubmissionProcess::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(FormSubmissionStatus::class);
    }

    public function currentStatus(): HasOne
    {
        return $this->hasOne(FormSubmissionCurrentStatus::class);
    }

    public function processors(): HasMany
    {
        return $this->hasMany(FormSubmissionProcessor::class);
    }

    public function currentTasks(): HasMany
    {
        return $this->hasMany(FormSubmissionCurrentTask::class);
    }

    public function completedTasks(): HasMany
    {
        return $this->hasMany(FormSubmissionCompletedTask::class);
    }

    /**
     * Use to render view/show submission (form-submissions/show.blade.php)
     *
     * @return array
     */
    public function formData(): array
    {
        $formFields = form_fields();
        $dataSourceInput = data_source_input();

        $properties = collect($this->form->prepareFields);
        $data = collect($this->data);

        $items = [];

        foreach ($properties as $property) {

            if ($property['type'] == $formFields['text']) {
                $value = null;

                $inputGroupTextLeft = null;
                $inputGroupTextRight = null;
                if ($property['input_group'] && $property['display_input_group_text']) {
                    $inputGroupTextLeft = $property['left_text_input_group'];
                    $inputGroupTextRight = $property['right_text_input_group'];
                }

                if ($data->has($property['id'])) {
                    $value = $inputGroupTextLeft.$data->get($property['id']).$inputGroupTextRight;
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }

            if (
                $property['type'] == $formFields['email'] ||
                $property['type'] == $formFields['textarea'] ||
                $property['type'] == $formFields['phone'] ||
                $property['type'] == $formFields['number'] ||
                $property['type'] == $formFields['url'] ||
                $property['type'] == $formFields['hidden'] ||
                $property['type'] == $formFields['time']
            ) {
                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $data->get($property['id']) ?? '',
                ];
            }

            if ($property['type'] == $formFields['date']) {
                $value = null;

                if ($data->get($property['id'])) {
                    $value = Carbon::createFromFormat('Y-m-d', $data->get($property['id']))
                        ->translatedFormat($property['date_format']);
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }

            if ($property['type'] == $formFields['select']) {
                $value = '';

                if ($property['data_source'] == $dataSourceInput['list']) {
                    $options = collect($property['options']);

                    // If option value exist
                    if ($data->get($property['id']) && $options->contains('value', $data->get($property['id']))) {
                        $value = $options->firstWhere('value', $data->get($property['id']))['label'];
                    } else {
                        $value = $data->get($property['id'])." " .__('messages.data_not_exist');
                    }
                }

                if ($property['data_source'] == $dataSourceInput['url']) {
                    $value = $data->get($property['id']);
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }

            if ($property['type'] == $formFields['radio']) {
                $options = collect($property['options']);

                // If option value exist
                if ($data->get($property['id']) && $options->contains('value', $data->get($property['id']))) {
                    $value = $options->firstWhere('value', $data->get($property['id']))['label'];
                } else {
                    $value = $data->get($property['id'])." " .__('messages.data_not_exist');
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }

            if ($property['type'] == $formFields['checkbox']) {
                $value = __('messages.not_checked');

                if ($data->get($property['id'])) {
                    $value = __('messages.checked');
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }

            if ($property['type'] == $formFields['currency']) {
                $value = null;
                if ($data->get($property['id'])) {
                    $currency = $property['currency'];
                    $value = Money::$currency($data->get($property['id']))->format(App::currentLocale());
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }

            if ($property['type'] == $formFields['time_range']) {
                $value = null;

                if ($data->has($property['id'])) {
                    $formData = $data->get($property['id']);
                    $value = "{$formData['from']} - {$formData['to']}";
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }

            if ($property['type'] == $formFields['checkbox_group']) {
                $value = [];

                if ($property['data_source'] == $dataSourceInput['list']) {
                    $options = collect($property['options']);

                    // If value exist
                    if ($data->has($property['id'])) {
                        foreach ($data->get($property['id']) as $item) {
                            if ($options->contains('value', $item)) {
                                $value[] = $options->firstWhere('value', $item)['label'];
                            }
                        }
                    }
                }

                if ($property['data_source'] == $dataSourceInput['url']) {
                    if ($data->has($property['id'])) {
                        $value = $data->get($property['id']);
                    }
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }

            if ($property['type'] == $formFields['date_range']) {
                $dateRangeValue = explode(' - ', $data->get($property['id']));

                $fromDate = Carbon::createFromFormat('Y-m-d', $dateRangeValue[0])->startOfDay();

                $toDate = null;
                if (!empty($dateRangeValue[1])) {
                    $toDate = Carbon::createFromFormat('Y-m-d', $dateRangeValue[1])->startOfDay();
                }

                $value = $fromDate->translatedFormat($property['date_format']);

                if ($toDate) {
                    $value .= ' - ' . $toDate->translatedFormat($property['date_format']);
                }

                $items[] = [
                    'id' => $property['id'],
                    'name' => $property['name'],
                    'value' => $value,
                ];
            }
        }

        return $items;
    }

    public function prepareEditInput(): array
    {
        $formFields = form_fields();
        $dataSourceInput = data_source_input();

        $properties = collect($this->form->properties);
        $data = collect($this->data);

        $items = [];

        foreach ($properties as $property) {

            if (
                $property['type'] == $formFields['text'] ||
                $property['type'] == $formFields['email'] ||
                $property['type'] == $formFields['textarea'] ||
                $property['type'] == $formFields['date'] ||
                $property['type'] == $formFields['phone'] ||
                $property['type'] == $formFields['number'] ||
                $property['type'] == $formFields['url'] ||
                $property['type'] == $formFields['hidden'] ||
                $property['type'] == $formFields['checkbox'] ||
                $property['type'] == $formFields['currency'] ||
                $property['type'] == $formFields['date_range']
            ) {
                $items[$property['id']] = $data->get($property['id']) ?? null;
            }

            if ($property['type'] == $formFields['select']) {
                if ($property['data_source'] == $dataSourceInput['list']) {
                    $options = collect($property['options']);

                    // If option value exist
                    if ($data->get($property['id']) && $options->contains('value', $data->get($property['id']))) {
                        $items[$property['id']] = $data->get($property['id']) ?? null;
                    } else {
                        $items[$property['id']] = null;
                    }
                }

                if ($property['data_source'] == $dataSourceInput['url']) {
                    $items[$property['id']] = $data->get($property['id']) ?? null;
                }
            }

            if ($property['type'] == $formFields['radio']) {
                $options = collect($property['options']);

                // If option value exist
                if ($data->get($property['id']) && $options->contains('value', $data->get($property['id']))) {
                    $items[$property['id']] = $data->get($property['id']) ?? null;
                } else {
                    $items[$property['id']] = null;
                }
            }

            if ($property['type'] == $formFields['file']) {
                $items[$property['id']] = [];
            }

            if ($property['type'] == $formFields['time']) {
                $value = null;

                if ($data->has($property['id'])) {
                    if ($property['time_24hr']) {
                        $value = Carbon::parse($data->get($property['id']))->format("H:i");

                        if ($property['enable_seconds']) {
                            $value = Carbon::parse($data->get($property['id']))->format("H:i:s");
                        }
                    } else {
                        $value = Carbon::parse($data->get($property['id']))->format("g:i A");

                        if ($property['enable_seconds']) {
                            $value = Carbon::parse($data->get($property['id']))->format("g:i:s A");
                        }
                    }
                }

                $items[$property['id']] = $value;
            }

            if ($property['type'] == $formFields['time_range']) {
                $fromValue = null;
                $toValue = null;

                if ($data->has($property['id'])) {
                    $value = $data->get($property['id']);

                    if ($value['from'] && $value['to']) {
                        if ($property['time_24hr']) {
                            $fromValue = Carbon::parse($value['from'])->format("H:i");
                            $toValue = Carbon::parse($value['to'])->format("H:i");

                            if ($property['enable_seconds']) {
                                $fromValue = Carbon::parse($value['from'])->format("H:i:s");
                                $toValue = Carbon::parse($value['to'])->format("H:i:s");
                            }
                        } else {
                            $fromValue = Carbon::parse($value['from'])->format("g:i A");
                            $toValue = Carbon::parse($value['to'])->format("g:i A");

                            if ($property['enable_seconds']) {
                                $fromValue = Carbon::parse($value['from'])->format("g:i:s A");
                                $toValue = Carbon::parse($value['to'])->format("g:i:s A");
                            }
                        }
                    }
                }

                $items[$property['id']] = [
                    'from' => $fromValue,
                    'to' => $toValue,
                ];
            }

            if ($property['type'] == $formFields['checkbox_group']) {
                if ($property['data_source'] == $dataSourceInput['list']) {
                    $options = collect($property['options']);

                    // If value exist
                    if ($data->has($property['id'])) {
                        foreach ($data->get($property['id']) as $value) {
                            if ($options->contains('value', $value)) {
                                $items[$property['id']][] = $value;
                            }
                        }
                    } else {
                        $items[$property['id']] = [];
                    }
                }

                if ($property['data_source'] == $dataSourceInput['url']) {
                    $items[$property['id']] = $data->get($property['id']) ?? [];
                }
            }
        }

        return $items;
    }
}
