<?php

namespace App\Http\Livewire\CharityPayments;

use App\Models\CharityPayments\CharityPayment;
use App\Models\Masters\Bank;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CharityPaymentModal extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;
    public ?int $organization_id = null;
    public string $type = 'transfer';
    public ?int $bank_id = null;
    public ?string $account_name = null;
    public ?string $account_number = null;
    public ?string $qris_image_path = null;
    public $qris_image = null;
    public ?string $notes = null;
    public bool $is_active = true;
    public string $search = '';

    public bool $isPartner = false;

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $context = $this->partnerContext();
        if ($context) {
            $this->isPartner = true;
            $this->organization_id = $context['organization_id'];
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'type' => ['required', 'string', 'max:50'],
            'bank_id' => ['nullable', 'required_unless:type,qris', 'exists:m_banks,id'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'required_unless:type,qris', 'string', 'max:255'],
            'qris_image' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function createNew(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $model = CharityPayment::findOrFail($id);
        $this->editingId = $model->id;
        $this->organization_id = $model->organization_id;
        $this->type = $model->type;
        $this->bank_id = $model->bank_id;
        $this->account_name = $model->account_name;
        $this->account_number = $model->account_number;
        $this->qris_image_path = $model->qris_image_path;
        $this->qris_image = null;
        $this->notes = $model->notes;
        $this->is_active = (bool) $model->is_active;

        $this->dispatchBrowser('select-sync', [
            'id' => $this->getId(),
            'field' => 'bank_id',
            'value' => $this->bank_id,
        ]);
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->type === 'qris' && ! $this->qris_image && ! $this->qris_image_path) {
            $this->addError('qris_image', __('validation.required', ['attribute' => __('messages.qris_image')]));
            return;
        }

        if ($this->type !== 'qris') {
            $data['qris_image_path'] = null;
        } else {
            $data['account_number'] = null;
        }

        if ($this->isPartner && $this->organization_id) {
            $data['organization_id'] = $this->organization_id;
        }

        $existing = $this->editingId ? CharityPayment::find($this->editingId) : null;

        if ($this->qris_image) {
            $data['qris_image_path'] = $this->qris_image->store(CharityPayment::QRIS_PATH, 'uploads');
        }

        CharityPayment::updateOrCreate(['id' => $this->editingId], $data);

        if ($existing && $existing->qris_image_path) {
            if ($this->type !== 'qris' || $this->qris_image) {
                Storage::disk('uploads')->delete($existing->qris_image_path);
            }
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->editingId ? __('messages.updated_successfully') : __('messages.created_successfully'),
        ]);

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $model = CharityPayment::find($id);
        if ($model) {
            $model->delete();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('messages.deleted_successfully'),
            ]);
        }

        $this->resetPage();
    }

    private function resetForm(): void
    {
        $context = $this->partnerContext();
        $this->editingId = null;
        $this->organization_id = $context['organization_id'] ?? null;
        $this->type = 'transfer';
        $this->bank_id = null;
        $this->account_name = null;
        $this->account_number = null;
        $this->qris_image_path = null;
        $this->qris_image = null;
        $this->notes = null;
        $this->is_active = true;
        $this->resetValidation();

        $this->dispatchBrowser('select-sync', [
            'id' => $this->getId(),
            'field' => 'bank_id',
            'value' => $this->bank_id,
        ]);
    }

    protected function dispatchBrowser(string $name, array $payload = []): void
    {
        if (method_exists($this, 'dispatchBrowserEvent')) {
            $this->dispatchBrowserEvent($name, $payload);
            return;
        }

        $this->dispatch($name, ...$payload);
    }

    private function partnerContext(): ?array
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $membership = $user->organizationMemberships()
            ->where('is_primary', true)
            ->where('level_slug', 'like', 'mosque-%')
            ->first();

        if (! $membership) {
            return null;
        }

        return [
            'organization_id' => $membership->organization_id,
        ];
    }

    public function render()
    {
        $query = CharityPayment::query()->with(['organization', 'bank']);
        if ($this->isPartner && $this->organization_id) {
            $query->where('organization_id', $this->organization_id);
        }
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('type', 'like', '%' . $this->search . '%')
                    ->orWhere('account_name', 'like', '%' . $this->search . '%')
                    ->orWhere('account_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('bank', function ($bankQuery) {
                        $bankQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return view('charity-payments.modal', [
            'items' => $query->orderByDesc('id')->paginate(10),
            'banks' => Bank::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values(),
            'componentId' => $this->getId(),
        ]);
    }
}
