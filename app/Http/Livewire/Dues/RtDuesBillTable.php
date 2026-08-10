<?php

namespace App\Http\Livewire\Dues;

use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

/**
 * Who has paid, for one month.
 *
 * The list is bound to a period id passed in from the page, and the query is
 * scoped by it — so this component cannot be pointed at another RT's month by
 * changing a parameter in the browser.
 */
class RtDuesBillTable extends DataTableComponent
{
    protected $model = RtDuesBill::class;

    public int $periodId = 0;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setSearchDisabled()
            ->setColumnSelectStatus(false)
            ->setFilterLayoutSlideDown()
            ->setTableWrapperAttributes(['class' => 'table-card mt-2'])
            ->setTheadAttributes(['class' => 'table-light'])
            ->setTableAttributes(['default' => false, 'class' => 'table table-striped'])
            ->setTrAttributes(fn () => ['default' => false, 'class' => 'align-middle']);
    }

    public function builder(): Builder
    {
        return RtDuesBill::query()
            ->with('resident')
            ->where('rt_dues_period_id', $this->periodId)
            // Unpaid first: the treasurer opens this page to work through who
            // still owes, not to admire who already paid. A plain sort on the
            // status column would put "paid" first, alphabetically.
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'paid' THEN 1 ELSE 2 END");
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.resident'), 'resident.name')
                ->sortable()
                ->label(fn ($row) => view('rt.dues.columns.resident')->withRow($row)),
            Column::make(__('messages.amount'), 'amount')
                ->sortable()
                ->label(function ($row) {
                    $amount = 'Rp ' . number_format((float) $row->amount, 0, ',', '.');
                    $tier = $row->tierLabel();

                    // The golongan is shown next to the figure so a treasurer
                    // reading "15.000" next to a neighbour's "20.000" can see
                    // why without opening anything.
                    return $tier ? $amount . ' · ' . $tier : $amount;
                }),
            Column::make(__('messages.status'), 'status')
                ->sortable()
                ->label(fn ($row) => view('rt.dues.columns.status')->withRow($row)),
            Column::make(__('messages.actions'))
                ->label(fn ($row) => view('rt.dues.columns.actions')->withRow($row))
                // Level-aware, like the `capability:` middleware on the route.
                // `can()` reads the Spatie role, which no RT officer gets
                // dues permissions from.
                ->hideIf(! $this->mayRecord()),
        ];
    }

    /** Whether the signed-in officer may record a payment. */
    protected function mayRecord(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && app(CapabilityResolver::class)->holds($user, 'edit-rt-dues');
    }

    public function filters(): array
    {
        return [
            TextFilter::make(__('messages.name'), 'name')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereHas(
                        'resident',
                        fn ($query) => $query->where('name', 'like', '%' . $value . '%')
                    );
                }),
            SelectFilter::make(__('messages.status'), 'status')
                ->setWireLive()
                ->options([
                    '' => __('messages.all'),
                    RtDuesBill::STATUS_PENDING => __('messages.pending'),
                    RtDuesBill::STATUS_PAID => __('messages.paid'),
                    RtDuesBill::STATUS_WAIVED => __('messages.dues_waived'),
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('status', $value);
                    }
                }),
        ];
    }
}
