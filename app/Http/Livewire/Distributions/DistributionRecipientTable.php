<?php

namespace App\Http\Livewire\Distributions;

use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Distributions\DistributionRecipientAttachment;
use App\Models\Profiles\UserResidentProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class DistributionRecipientTable extends DataTableComponent
{
    use WithFileUploads;

    protected $model = DistributionRecipient::class;

    public int $distributionId;

    public ?int $statusRecipientId = null;

    public ?string $statusAction = null;

    public string $statusNote = '';

    public ?string $rescheduleAt = null;

    public ?string $deliveryMethod = null;

    public $attachments = [];

    public ?int $viewRecipientId = null;

    public array $viewAttachments = [];

    public ?string $statusReason = null;

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
            ->select('distribution_recipients.*')
            ->with(['resident', 'createdBy', 'distributionClass', 'attachments'])
            ->where('distribution_recipients.distribution_id', $this->distributionId);
    }

    public function columns(): array
    {
        return [
            Column::make(__('messages.recipient'), 'resident.name')
                ->searchable()
                ->sortable()
                ->format(fn ($value, $row) => $this->recipientLabel($row, $value)),
            Column::make(__('messages.status'), 'status')
                ->label(fn ($row) => view('distributions.recipients.columns.status')->withRow($row)),
            Column::make(__('messages.created_by'), 'createdBy.name')
                ->format(fn ($value) => $value ?? '-'),
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
                        $query->where('residents.name', 'like', '%' . $value . '%')
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

    public function openStatusModal($recipientId, $action): void
    {
        if (! auth()->user()?->can('edit-mosque-charity-distribution-recipients')) {
            abort(403);
        }

        if (! $recipientId || ! $action) {
            return;
        }

        $this->statusRecipientId = $recipientId;
        $this->statusAction = $action;
        $this->statusNote = '';
        $this->statusReason = null;
        $this->rescheduleAt = null;
        $this->deliveryMethod = 'direct';
        $this->attachments = [];

        $this->dispatch('distribution-status-modal:open');
    }

    public function saveStatus(): void
    {
        if (! auth()->user()?->can('edit-mosque-charity-distribution-recipients')) {
            abort(403);
        }

        if (! $this->statusRecipientId || ! $this->statusAction) {
            throw ValidationException::withMessages([
                'status' => __('messages.something_went_wrong'),
            ]);
        }

        $recipient = $this->findRecipient($this->statusRecipientId);

        if (in_array($this->statusAction, ['failed', 'rescheduled'], true)) {
            if (empty($this->statusReason)) {
                throw ValidationException::withMessages([
                    'statusReason' => __('messages.status_reason_required'),
                ]);
            }

            if ($this->statusReason === 'other' && empty($this->statusNote)) {
                throw ValidationException::withMessages([
                    'statusNote' => __('messages.status_note_required'),
                ]);
            }
        }

        if ($this->statusAction === 'rescheduled' && empty($this->rescheduleAt)) {
            throw ValidationException::withMessages([
                'rescheduleAt' => __('messages.reschedule_date_required'),
            ]);
        }

        if (! empty($this->attachments)) {
            $this->validate([
                'attachments.*' => ['image', 'max:2048'],
            ], [], [
                'attachments.*' => __('messages.documentation_photos'),
            ]);
        }

        if ($this->statusAction === 'distributed' && empty($this->attachments)) {
            throw ValidationException::withMessages([
                'attachments' => __('messages.documentation_required'),
            ]);
        }

        $payload = [
            'status' => $this->statusAction,
            'status_note' => $this->buildStatusNote(),
            'distributed_at' => null,
            'reschedule_at' => null,
        ];

        if ($this->statusAction === 'rescheduled') {
            $payload['reschedule_at'] = $this->rescheduleAt;
        }

        if ($this->statusAction === 'distributed') {
            $payload['distributed_at'] = now();
            $payload['reschedule_at'] = null;
        }

        $recipient->update($payload);
        $this->storeAttachments($recipient);

        $this->updateDistributionStatus();
        $this->dispatch('distribution-status-modal:close');
        $this->dispatch('toast', ['type' => 'success', 'message' => __('messages.updated_successfully')]);
        $this->reset(['statusRecipientId', 'statusAction', 'statusNote', 'statusReason', 'rescheduleAt', 'deliveryMethod', 'attachments']);
    }

    public function openViewModal($recipientId): void
    {
        if (! auth()->user()?->can('read-mosque-charity-distribution-recipients')) {
            abort(403);
        }

        if (! $recipientId) {
            return;
        }

        $recipient = $this->findRecipient($recipientId);
        $this->viewRecipientId = $recipient->id;
        $this->viewAttachments = $recipient->attachments
            ->map(function ($item) {
                $disk = $item->disk ?? 'public';
                $path = $item->file_path;

                if ($disk === 'public' && str_starts_with($path, 'uploads/')) {
                    $disk = 'uploads';
                    $path = ltrim(str_replace('uploads/', '', $path), '/');
                }

                return [
                    'id' => $item->id,
                    'file_path' => $item->file_path,
                    'url' => Storage::disk($disk)->url($path),
                    'original_name' => $item->original_name,
                ];
            })
            ->values()
            ->toArray();

        $this->dispatch('distribution-view-modal:open');
    }

    public function customView(): string
    {
        return 'distributions.recipients.modal';
    }

    protected function recipientLabel($row, $value = null): string
    {
        if (! empty($value)) {
            return (string) $value;
        }

        return $row->recipient_name ? $row->recipient_name . ' (' . __('messages.manual') . ')' : '-';
    }

    protected function findRecipient($recipientId): DistributionRecipient
    {
        if (! $recipientId) {
            throw ValidationException::withMessages([
                'status' => __('messages.something_went_wrong'),
            ]);
        }

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

    protected function buildDeliveredNote(): ?string
    {
        $method = $this->deliveryMethod ?: 'direct';
        $labelMap = [
            'direct' => __('messages.delivered_direct'),
            'neighbor' => __('messages.delivered_neighbor'),
            'door' => __('messages.delivered_left'),
        ];
        $label = $labelMap[$method] ?? __('messages.distributed');

        if (! empty($this->statusNote)) {
            return trim($label . ' - ' . $this->statusNote);
        }

        return $label;
    }

    protected function buildStatusNote(): ?string
    {
        if ($this->statusAction === 'distributed') {
            return $this->buildDeliveredNote();
        }

        $reasonMap = [
            'not_home' => __('messages.recipient_not_home'),
            'moved' => __('messages.recipient_moved'),
            'other' => __('messages.other'),
        ];
        $reasonLabel = $reasonMap[$this->statusReason] ?? null;

        if ($reasonLabel && ! empty($this->statusNote)) {
            return trim($reasonLabel . ' - ' . $this->statusNote);
        }

        if ($reasonLabel) {
            return $reasonLabel;
        }

        return $this->statusNote;
    }

    protected function storeAttachments(DistributionRecipient $recipient): void
    {
        if (empty($this->attachments)) {
            return;
        }

        $recipient->attachments->each(function (DistributionRecipientAttachment $attachment) {
            $disk = $attachment->disk ?? 'uploads';
            $path = $attachment->file_path;
            if ($disk === 'public' && str_starts_with($path, 'uploads/')) {
                $disk = 'uploads';
                $path = ltrim(str_replace('uploads/', '', $path), '/');
            }
            Storage::disk($disk)->delete($path);
            $attachment->delete();
        });

        foreach ($this->attachments as $file) {
            $fileName = \Illuminate\Support\Str::random(40) . '.' . $file->getClientOriginalExtension();
            Storage::disk('uploads')->putFileAs(DistributionRecipientAttachment::UPLOAD_PATH, $file, $fileName);
            $path = DistributionRecipientAttachment::UPLOAD_PATH . '/' . $fileName;

            $attachment = DistributionRecipientAttachment::create([
                'distribution_recipient_id' => $recipient->id,
                'file_path' => $path,
                'file_name' => pathinfo($path, PATHINFO_BASENAME),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'disk' => 'uploads',
                'created_by' => auth()->id(),
            ]);

            if ($recipient->resident && $recipient->resident->residentProfile) {
                $profile = $recipient->resident->residentProfile;
                $houseFilename = pathinfo($path, PATHINFO_BASENAME);
                $housePath = UserResidentProfile::HOUSE_PHOTO_PATH . '/' . $houseFilename;

                if (! Storage::disk('uploads')->exists($housePath)) {
                    Storage::disk('uploads')->copy($path, $housePath);
                }

                $paths = $profile->house_photo_paths ?? [];
                $paths[] = $housePath;
                $profile->update(['house_photo_paths' => array_values(array_unique($paths))]);
            }
        }
    }
}
