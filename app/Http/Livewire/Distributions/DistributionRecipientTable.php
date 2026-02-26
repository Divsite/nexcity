<?php

namespace App\Http\Livewire\Distributions;

use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class DistributionRecipientTable extends DataTableComponent
{
    protected $model = DistributionRecipient::class;

    public int $distributionId;

    public ?int $statusRecipientId = null;

    public ?string $statusAction = null;

    public string $statusNote = '';

    public ?string $rescheduleAt = null;

    protected $listeners = [
        'refreshDistributionRecipients' => '$refresh',
    ];

    public function mount(int $distributionId): void
    {
        $this->distributionId = $distributionId;
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('distribution_recipients.created_at', 'desc')
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
        return DistributionRecipient::query()
            ->with(['resident', 'distributionClass'])
            ->where('distribution_recipients.distribution_id', $this->distributionId);
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.recipient'), 'resident.name')
                ->searchable()
                ->sortable()
                ->label(fn ($row) => $this->recipientLabel($row)),
            Column::make(__('messages.status'), 'status')
                ->format(fn ($value) => $value ? __('messages.' . $value) : '-'),
            Column::make(__('messages.status_note'), 'status_note')
                ->label(fn ($row) => $row->status_note ?? '-'),
            Column::make(__('messages.distributed_at'), 'distributed_at')
                ->label(function ($row) {
                    if ($row->distributed_at) {
                        return $row->distributed_at->format('d/m/Y H:i');
                    }

                    if ($row->reschedule_at) {
                        return $row->reschedule_at->format('d/m/Y');
                    }

                    return '-';
                }),
            Column::make(__('messages.actions'))
                ->label(fn ($row) => view('distributions.recipients.columns.actions')->withRow($row)),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make(__('messages.recipient'), 'recipient')
                ->setWireLive()
                ->config(['placeholder' => __('messages.search')])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(function (Builder $query) use ($value) {
                        $query->whereHas('resident', fn (Builder $resident) => $resident->where('name', 'like', '%' . $value . '%'))
                            ->orWhere('recipient_name', 'like', '%' . $value . '%');
                    });
                }),
            SelectFilter::make(__('messages.status'), 'status')
                ->setWireLive()
                ->options([
                    '' => __('messages.all'),
                    'pending' => __('messages.pending'),
                    'distributed' => __('messages.distributed'),
                    'failed' => __('messages.failed'),
                    'rescheduled' => __('messages.rescheduled'),
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('status', $value)),
        ];
    }

    public function markDistributed(int $recipientId): void
    {
        $recipient = $this->findRecipient($recipientId);
        $recipient->update([
            'status' => 'distributed',
            'status_note' => null,
            'distributed_at' => now(),
            'reschedule_at' => null,
        ]);

        $this->updateDistributionStatus();
        $this->dispatchBrowserEvent('toast', ['type' => 'success', 'message' => __('messages.updated_successfully')]);
    }

    public function openStatusModal(int $recipientId, string $action): void
    {
        $this->statusRecipientId = $recipientId;
        $this->statusAction = $action;
        $this->statusNote = '';
        $this->rescheduleAt = null;

        $this->dispatchBrowserEvent('distribution-status-modal:open');
    }

    public function saveStatus(): void
    {
        if (! $this->statusRecipientId || ! $this->statusAction) {
            throw ValidationException::withMessages([
                'status' => __('messages.something_went_wrong'),
            ]);
        }

        $recipient = $this->findRecipient($this->statusRecipientId);

        if (in_array($this->statusAction, ['failed', 'rescheduled'], true) && empty($this->statusNote)) {
            throw ValidationException::withMessages([
                'statusNote' => __('messages.status_note_required'),
            ]);
        }

        if ($this->statusAction === 'rescheduled' && empty($this->rescheduleAt)) {
            throw ValidationException::withMessages([
                'rescheduleAt' => __('messages.reschedule_date_required'),
            ]);
        }

        $payload = [
            'status' => $this->statusAction,
            'status_note' => $this->statusNote,
            'distributed_at' => null,
            'reschedule_at' => null,
        ];

        if ($this->statusAction === 'rescheduled') {
            $payload['reschedule_at'] = $this->rescheduleAt;
        }

        $recipient->update($payload);

        $this->updateDistributionStatus();
        $this->dispatchBrowserEvent('distribution-status-modal:close');
        $this->dispatchBrowserEvent('toast', ['type' => 'success', 'message' => __('messages.updated_successfully')]);
        $this->reset(['statusRecipientId', 'statusAction', 'statusNote', 'rescheduleAt']);
    }

    public function customView(): string
    {
        return 'distributions.recipients.modal';
    }

    protected function recipientLabel($row): string
    {
        if ($row->resident) {
            return $row->resident->name;
        }

        return $row->recipient_name ? $row->recipient_name . ' (' . __('messages.manual') . ')' : '-';
    }

    protected function findRecipient(int $recipientId): DistributionRecipient
    {
        $recipient = DistributionRecipient::query()
            ->where('distribution_id', $this->distributionId)
            ->findOrFail($recipientId);

        return $recipient;
    }

    protected function updateDistributionStatus(): void
    {
        $distribution = Distribution::query()->find($this->distributionId);
        if (! $distribution) {
            return;
        }

        $total = $distribution->recipients()->count();
        $distributed = $distribution->recipients()->where('status', 'distributed')->count();

        if ($total > 0 && $distributed === $total) {
            $distribution->update(['status' => 'completed']);
        } else {
            if ($distribution->status === 'completed') {
                $distribution->update(['status' => 'pending']);
            }
        }
    }
}
