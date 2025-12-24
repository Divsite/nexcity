<?php

namespace App\Http\Livewire\SubmissionLists;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\FormSubmissions\FormSubmission;

class SubmissionListTable extends DataTableComponent
{
    protected $model = FormSubmission::class;

    protected int $index = 0;

    public string $type;

    public function mount(): void
    {
        $this->type = task_type()['all'];
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects([
                'form_submissions.id', 'form_submissions.form_id', 'form_submissions.created_by', 'data'
            ])
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
            ->setTrAttributes(fn($row, $index) => ['default' => false, 'class' => 'align-middle']);
    }

    public function builder(): Builder
    {
        return FormSubmission::with(['form', 'currentStatus', 'processors'])
            ->whereHas('processors', function (Builder $query) {
                $query->where('user_id', '=', auth()->user()->id);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#')->label(
                fn($row, Column $column) => ++$this->index + ($this->paginators['page'] - 1) * $this->perPage
            ),
            Column::make(__('messages.form_name'), "form.name")
                ->searchable()
                ->sortable(),
            Column::make(__('messages.status'), "currentStatus.status.name")
                ->searchable()
                ->sortable()
                ->format(function ($value) {
                    return $value ?? __('messages.new_default_status');
                }),
            Column::make(__('messages.process', ['number' => '']), "currentStatus.process.name")
                ->searchable()
                ->sortable()
                ->format(function ($value) {
                    return $value ?? '-';
                }),
            Column::make(__('messages.created_by'), "author.name")
                ->sortable()
                ->searchable(),
            Column::make(__('messages.created_at'), "created_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->format('d/m/Y h:i A ') : '-'),
            Column::make(__('messages.updated_at'), "updated_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->diffForHumans() : '-'),
            Column::make(__('messages.actions'))
                ->label(
                    fn($row, Column $column) => view('submission-lists.columns.table-actions')->withRow($row)
                ),
        ];
    }
}
