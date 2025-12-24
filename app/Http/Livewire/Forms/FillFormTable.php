<?php

namespace App\Http\Livewire\Forms;

use App\Models\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class FillFormTable extends DataTableComponent
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
                ->sortable()
                ->format(fn($value) => $value ?? '-'),
            Column::make(__('messages.actions'))
                ->label(
                    fn($row, Column $column) => view('forms.columns.fill-form-btn-action')->withRow($row)
                )
                ->hideIf(!auth()->user()->can('add-submissions'))
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
        ];
    }
}
