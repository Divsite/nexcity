<?php

namespace App\Http\Livewire\Notifications;

use App\Models\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class NotificationTable extends DataTableComponent
{
    protected $model = Notification::class;

    protected int $index = 0;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setAdditionalSelects(['id'])
            ->setSearchDisabled()
            ->setDefaultSort('updated_at', 'desc')
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
            ->setTrAttributes(fn($row, $index) => ['default' => false, 'class' => 'align-middle'])
            ->setTableRowUrl(function($row) {
                return !empty($row->data['redirect_to']) ? $row->data['redirect_to'] : null;
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#')->label(
                fn($row, Column $column) => ++$this->index + ($this->paginators['page'] - 1) * $this->perPage
            ),
            Column::make(__('messages.description'), "data")
                ->searchable()
                ->sortable()
                ->format(function ($value) {
                    return $value['data'];
                }),
            Column::make(__('messages.has_read'), "read_at")
                ->sortable()
                ->searchable()
                ->format(function ($value) {
                    if ($value) {
                        return __('messages.yes');
                    }

                    return __('messages.no');
                }),
            Column::make(__('messages.created_at'), "created_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->format('d/m/Y h:i A ') : '-'),
            Column::make(__('messages.last_updated_at'), "updated_at")
                ->sortable()
                ->format(fn($value) => $value ? $value->diffForHumans() : '-'),
            Column::make(__('messages.actions'))
                ->label(
                    fn($row, Column $column) => view('notifications.columns.table-actions')->withRow($row)
                )
                ->unclickable()
                ->hideIf(
                    !auth()->user()->can('read-notifications') &&
                    !auth()->user()->can('edit-notifications') &&
                    !auth()->user()->can('delete-notifications')
                )
        ];
    }

    public function builder(): Builder
    {
        return Notification::where('notifiable_id', auth()->user()->id);
    }

    public function filters(): array
    {
        $read = [Notification::HAS_READ => __('messages.yes'), Notification::NOT_READ => __('messages.no')];
        $hasReadOptions = ['' => __('messages.all')] + $read;

        return [
            TextFilter::make(__('messages.description'), 'data')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '25',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('data', 'like', '%'.$value.'%');
                }),
            SelectFilter::make(__('messages.has_read'))
                ->setWireLive()
                ->options($hasReadOptions)
                ->filter(function (Builder $builder, int $value) {
                    if ($value == Notification::HAS_READ) {
                        $builder->whereNotNull('read_at');
                    }

                    if ($value == Notification::NOT_READ) {
                        $builder->whereNull('read_at');
                    }
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
                        ->whereDate('created_at', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('created_at', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),
        ];
    }

    public function destroy($id)
    {
        if (auth()->user()->cannot('delete-notifications')) {
            flash()->error(__('messages.user_does_not_have_the_right_permissions'));
            return to_route('dashboard');
        }

        $model = Notification::query()->find($id);

        if ($model) {
            if ($model->notifiable_id != auth()->id()) {
                return to_route('notifications.index');
            }

            $model->delete();

            flash()->success(__('messages.notification_successfully_deleted'));

            activity(__('messages.notifications'))
                ->causedBy(auth()->user())
                ->log(__('messages.notification_has_been_deleted', ['id' => $model->id]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        return to_route('notifications.index');
    }

    public function setAsHasRead($id)
    {
        $model = Notification::query()->find($id);

        if ($model) {
            if ($model->notifiable_id != auth()->id()) {
                return to_route('notifications.index');
            }

            if ($model->read_at) {
                flash()->info(__('messages.notification_already_read'));
            } else {
                $model->read_at = now();
                $model->update();

                flash()->success(__('messages.notification_successfully_mark_as_has_read'));

                activity(__('messages.notifications'))
                    ->causedBy(auth()->user())
                    ->log(__('messages.notification_mark_as_has_read', ['id' => $model->id]));
            }
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        return to_route('notifications.index');
    }

    public function customView(): string
    {
        return 'partials.livewire-confirmation';
    }
}
