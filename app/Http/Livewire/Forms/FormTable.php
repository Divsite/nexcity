<?php

namespace App\Http\Livewire\Forms;

use App\Models\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class FormTable extends DataTableComponent
{
    protected $model = Form::class;

    protected int $index = 0;

    public function configure(): void
    {
        $this->setPrimaryKey('forms.id')
            ->setAdditionalSelects(['forms.id'])
            ->setSearchDisabled()
            ->setDefaultSort('forms.updated_at', 'desc')
            ->setColumnSelectStatus(false)
            ->setFilterLayoutSlideDown()
            ->setTableWrapperAttributes([
                'class' => 'table-card mt-2',
            ])
            ->setTheadAttributes([
                'class' => 'table-light',
            ])
            ->setTableAttributes([
                'default' => false,
                'class' => 'table table-striped'
            ])
            ->setTrAttributes(fn($row, $index) => ['default' => false, 'class' => 'align-middle']);
    }

    public function columns(): array
    {
        return [
            Column::make('#')->label(
                fn($row, Column $column) => ++$this->index + ($this->paginators['page'] - 1) * $this->perPage
            ),
            Column::make(__('messages.form_name'), "name")
                ->searchable()
                ->sortable(),
            Column::make(__('messages.form_type'), "type.name")
                ->searchable()
                ->sortable(),
            Column::make(__('messages.created_at'), "created_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->format('d/m/Y h:i A ') : '-'),
            Column::make(__('messages.updated_at'), "updated_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->diffForHumans() : '-'),
            Column::make(__('messages.actions'))
                ->label(
                    fn($row, Column $column) => view('forms.columns.table-actions')->withRow($row)
                )
                ->hideIf(
                    !auth()->user()->can('read-forms') &&
                    !auth()->user()->can('edit-forms') &&
                    !auth()->user()->can('delete-forms') &&
                    !auth()->user()->can('add-submissions') &&
                    !auth()->user()->can('browse-submissions')
                )
        ];
    }

    public function builder(): Builder
    {
        return Form::query()->with('type');
    }

    public function filters(): array
    {
        return [
            TextFilter::make(__('messages.name'), 'name')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '25',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('forms.name', 'like', '%'.$value.'%');
                }),
            TextFilter::make(__('messages.form_type'), 'type_id')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '25',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereHas('type', function (Builder $query) use ($value) {
                        $query->where('name', 'like', '%'.$value.'%');
                    });
                }),
            DateRangeFilter::make(__('messages.created_at'), 'created_at')
                ->setWireLive()
                ->config([
                    'allowInput' => false,   // Allow manual input of dates
                    'altFormat' => 'd/m/Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'd/m/Y', // An aria-friendly date format
                ])
                ->filter(function (Builder $builder, array $dateRange) { // Expects an array.
                    $builder
                        ->whereDate('forms.created_at', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('forms.created_at', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),
        ];
    }

    public function destroy($id)
    {
        if (auth()->user()->cannot('delete-forms')) {
            flash()->error(__('messages.user_does_not_have_the_right_permissions'));
            return to_route('dashboard');
        }

        $model = Form::query()->find($id);

        if ($model) {
            $model->delete();
            flash()->success(__('messages.form_successfully_deleted'));

            activity(__('messages.forms'))
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log(__('messages.forms_has_been_deleted', ['name' => $model->name]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        return to_route('forms.index');
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }
}
