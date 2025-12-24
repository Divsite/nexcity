<?php

namespace App\Http\Livewire\FormSubmissions;

use App\Models\Forms\Form;
use App\Models\FormSubmissions\FormSubmission;
use Carbon\Carbon;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class FormSubmissionTable extends DataTableComponent
{
    protected $model = FormSubmission::class;

    public string $formId;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects(['data'])
            ->setDefaultSort('updated_at', 'desc')
            ->setTableWrapperAttributes([
                'class' => 'table-card mt-2',
            ])
            ->setTheadAttributes([
                'class' => 'table-light text-nowrap',
            ])
            ->setTableAttributes([
                'default' => false,
                'class' => 'table table-striped'
            ])
            ->setTdAttributes(function () {
                return ['class' => 'align-middle text-truncate', 'style' => 'max-width:150px'];
            })
            ->setTrAttributes(fn($row, $index) => ['default' => false, 'class' => 'align-middle']);
    }

    public function builder(): Builder
    {
        return FormSubmission::query()
            ->where('form_id', $this->formId);
    }

    public function columns(): array
    {
        $formFields = form_fields();
        $dataSourceInput = data_source_input();

        $columns[] = Column::make("ID", "id")->sortable();

        $columns[] = Column::make(__('messages.actions'))
            ->label(
                fn($row, Column $column) => view('form-submissions.columns.table-actions')->withRow($row)
            )->hideIf(
                !auth()->user()->can('read-submissions') &&
                !auth()->user()->can('edit-submissions') &&
                !auth()->user()->can('delete-submissions')
            );

        $form = Form::findOrFail($this->formId);
        if ($form) {
            $properties = collect($form->prepareFields);

            foreach ($properties as $property) {

                if ($property['type'] == $formFields['text']) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property) {
                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    $inputGroupTextLeft = null;
                                    $inputGroupTextRight = null;

                                    if ($property['input_group'] && $property['display_input_group_text']) {
                                        $inputGroupTextLeft = $property['left_text_input_group'];
                                        $inputGroupTextRight = $property['right_text_input_group'];
                                    }

                                    return $inputGroupTextLeft.$value.$inputGroupTextRight;
                                }
                            }

                            return '';
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property) {
                            $search = Str::lower($searchTerm);
                            $column = "data->{$property['id']}";
                            return $query->orWhere($column, 'like', "%$search%");
                        });
                }

                if (
                    $property['type'] == $formFields['email'] ||
                    $property['type'] == $formFields['textarea'] ||
                    $property['type'] == $formFields['phone'] ||
                    $property['type'] == $formFields['number'] ||
                    $property['type'] == $formFields['url'] ||
                    $property['type'] == $formFields['time']
                ) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property) {
                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    return $value;
                                }
                            }

                            return '';
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property) {
                            $search = Str::lower($searchTerm);
                            $column = "data->{$property['id']}";
                            return $query->orWhere($column, 'like', "%$search%");
                        });
                }

                if ($property['type'] == $formFields['date']) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property) {
                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    return Carbon::createFromFormat('Y-m-d', $value)
                                        ->translatedFormat($property['date_format']);
                                }
                            }

                            return '';
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property) {
                            $search = Str::lower($searchTerm);
                            $column = "data->{$property['id']}";
                            return $query->orWhere($column, 'like', "%$search%");
                        });
                }

                if ($property['type'] == $formFields['select']) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property, $dataSourceInput) {
                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    if ($property['data_source'] == $dataSourceInput['list']) {
                                        $options = collect($property['options']);

                                        // If option value exist
                                        if ($options->contains('value', $value)) {
                                            return $options->firstWhere('value', $value)['label'];
                                        } else {
                                            return $value." " .__('messages.data_not_exist');
                                        }
                                    }

                                    if ($property['data_source'] == $dataSourceInput['url']) {
                                        return $value;
                                    }
                                }
                            }

                            return '';
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property, $dataSourceInput) {
                            if ($property['data_source'] == $dataSourceInput['list']) {
                                $options = collect($property['options']);

                                if ($options->contains('label', $searchTerm)) {
                                    $search = $options->firstWhere('label', $searchTerm)['value'];
                                    $column = "data->{$property['id']}";
                                    return $query->orWhere($column, 'like', "%$search%");
                                }
                            }

                            if ($property['data_source'] == $dataSourceInput['url']) {
                                $search = Str::lower($searchTerm);
                                $column = "data->{$property['id']}";
                                return $query->orWhere($column, 'like', "%$search%");
                            }

                            return '';
                        });
                }

                if ($property['type'] == $formFields['radio']) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property) {
                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    $options = collect($property['options']);

                                    // If option value exist
                                    if ($options->contains('value', $value)) {
                                        return $options->firstWhere('value', $value)['label'];
                                    } else {
                                        return $value." " .__('messages.data_not_exist');
                                    }
                                }
                            }

                            return '';
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property) {
                            $options = collect($property['options']);

                            if ($options->contains('label', $searchTerm)) {
                                $search = $options->firstWhere('label', $searchTerm)['value'];
                                $column = "data->{$property['id']}";
                                return $query->orWhere($column, 'like', "%$search%");
                            }

                            return '';
                        });
                }

                if ($property['type'] == $formFields['currency']) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property) {
                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    $currency = $property['currency'];
                                    return Money::$currency($value)->format(App::currentLocale());
                                }
                            }

                            return '';
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property) {
                            $search = Str::lower($searchTerm);
                            $column = "data->{$property['id']}";
                            return $query->orWhere($column, 'like', "%$search%");
                        });
                }

                if ($property['type'] == $formFields['time_range']) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property) {
                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    return "{$value['from']} - {$value['to']}";
                                }
                            }

                            return '';
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property) {
                            $search = Str::lower($searchTerm);
                            $column = "data->{$property['id']}";
                            return $query->orWhere($column, 'like', "%$search%");
                        });
                }

                if ($property['type'] == $formFields['checkbox_group']) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property, $dataSourceInput) {
                            $values = [];

                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    if ($property['data_source'] == $dataSourceInput['list']) {
                                        $options = collect($property['options']);

                                        foreach ($value as $item) {
                                            // If option value exist
                                            if ($options->contains('value', $item)) {
                                                $values[] = $options->firstWhere('value', $item)['label'];
                                            } else {
                                                $values[] = $item." " .__('messages.data_not_exist');
                                            }
                                        }
                                    }

                                    if ($property['data_source'] == $dataSourceInput['url']) {
                                        foreach ($value as $item) {
                                            $values[] = $item;
                                        }
                                    }
                                }
                            }

                            return view('form-submissions.columns.badges-array', [
                                'values' => $values,
                            ]);
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property, $dataSourceInput) {
                            if ($property['data_source'] == $dataSourceInput['list']) {
                                $options = collect($property['options']);

                                if ($options->contains('label', $searchTerm)) {
                                    $search = $options->firstWhere('label', $searchTerm)['value'];
                                    $column = "data->{$property['id']}";
                                    return $query->orWhere($column, 'like', "%$search%");
                                }
                            }

                            if ($property['data_source'] == $dataSourceInput['url']) {
                                $search = Str::lower($searchTerm);
                                $column = "data->{$property['id']}";
                                return $query->orWhere($column, 'like', "%$search%");
                            }

                            return '';
                        });
                }

                if ($property['type'] == $formFields['date_range']) {
                    $columns[] = Column::make($property['name'])
                        ->label(function ($row, Column $column) use ($property) {
                            foreach ($row->data as $fieldId => $value) {
                                if ($fieldId == $property['id']) {
                                    $dateRangeValue = explode(' - ', $value);

                                    $fromDate = Carbon::createFromFormat('Y-m-d', $dateRangeValue[0])->startOfDay();

                                    $toDate = null;
                                    if (!empty($dateRangeValue[1])) {
                                        $toDate = Carbon::createFromFormat('Y-m-d', $dateRangeValue[1])->startOfDay();
                                    }

                                    $dateRange = $fromDate->translatedFormat($property['date_format']);

                                    if ($toDate) {
                                        $dateRange .= ' - ' . $toDate->translatedFormat($property['date_format']);
                                    }

                                    return $dateRange;
                                }
                            }

                            return '';
                        })
                        ->searchable(function (Builder $query, $searchTerm) use ($property) {
                            $search = Str::lower($searchTerm);
                            $column = "data->{$property['id']}";
                            return $query->orWhere($column, 'like', "%$search%");
                        });
                }

            }
        }

        $columns[] = Column::make(__('messages.created_at'), "created_at")
            ->sortable()
            ->format(fn($value) => $value ? $value->format('d/m/Y h:i A ') : '-');

        $columns[] = Column::make(__('messages.updated_at'), "updated_at")
            ->sortable()
            ->format(fn($value) => $value ? $value->diffForHumans() : '-');

        return $columns;
    }

    public function destroy($id)
    {
        if (auth()->user()->cannot('delete-submissions')) {
            flash()->error(__('messages.user_does_not_have_the_right_permissions'));
            return to_route('dashboard');
        }

        $model = FormSubmission::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.submission_successfully_deleted'));

            activity(__('messages.form_submissions'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.form_submissions_has_been_deleted', ['id' => $model->id]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        return to_route('forms.submissions.index', $model->form->id);
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }
}
