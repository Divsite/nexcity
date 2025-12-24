<?php

namespace App\Http\Livewire\Profiles;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog as Log;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class AuthenticationLogTable extends DataTableComponent
{
    protected $model = Log::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('login_at', 'desc')
            ->setSearchDisabled()
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
            Column::make(__('messages.ip_address'), 'ip_address')
                ->sortable(),
            Column::make(__('messages.browser'), 'user_agent')
                ->sortable()
                ->format(function($value) {
                    $agent = tap(new Agent, fn($agent) => $agent->setUserAgent($value));
                    return $agent->platform() . ' - ' . $agent->browser();
                }),
            Column::make(__('messages.login_at'), 'login_at')
                ->sortable()
                ->format(fn($value) => $value ? $value->format('d/m/Y h:i A ') : '-'),
            Column::make(__('messages.login_successful'), 'login_successful')
                ->sortable()
                ->format(function ($value) {
                    $yesNo = __('messages.no');

                    if ($value === true) {
                        $yesNo = __('messages.yes');
                        return '<span class="badge bg-success-subtle text-success text-uppercase">'.$yesNo.'</span>';
                    }

                    return '<span class="badge bg-danger-subtle text-danger text-uppercase">'.$yesNo.'</span>';
                })
                ->html(),
            Column::make(__('messages.logout_at'), 'logout_at')
                ->sortable()
                ->format(fn($value) => $value ? $value->format('d/m/Y h:i A ') : '-'),
        ];
    }

    public function builder(): Builder
    {
        return Log::query()
            ->where('authenticatable_type', User::class)
            ->where('authenticatable_id', Auth::id());
    }

    public function filters(): array
    {
        return [
            TextFilter::make(__('messages.ip_address'), 'ip_address')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '25',
                ])
                ->filter(function(Builder $builder, string $value) {
                    $builder->where('ip_address', 'like', '%'.$value.'%');
                }),
            TextFilter::make(__('messages.browser'), 'user_agent')
                ->setWireLive()
                ->config([
                    'placeholder' => __('messages.search'),
                    'maxlength' => '50',
                ])
                ->filter(function(Builder $builder, string $value) {
                    $builder->where('user_agent', 'like', '%'.$value.'%');
                }),
            DateRangeFilter::make(__('messages.login_at'), 'login_at')
                ->setWireLive()
                ->config([
                    'allowInput' => false,   // Allow manual input of dates
                    'altFormat' => 'd/m/Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'd/m/Y', // An aria-friendly date format
                ])
                ->filter(function (Builder $builder, array $dateRange) { // Expects an array.
                    $builder
                        ->whereDate('login_at', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('login_at', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),
            SelectFilter::make(__('messages.login_successful'))
                ->setWireLive()
                ->options(
                    to_options([
                        'yes' => __('messages.yes'),
                        'no' => __('messages.no'),
                    ]))
                ->filter(function(Builder $builder, string $value) {
                    $builder->where('login_successful', $value[0] == 'yes');
                }),
            DateRangeFilter::make(__('messages.logout_at'), 'logout_at')
                ->setWireLive()
                ->config([
                    'allowInput' => false,   // Allow manual input of dates
                    'altFormat' => 'd/m/Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'd/m/Y', // An aria-friendly date format
                ])
                ->filter(function (Builder $builder, array $dateRange) { // Expects an array.
                    $builder
                        ->whereDate('logout_at', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('logout_at', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),
        ];
    }
}
