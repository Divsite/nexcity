<?php

namespace App\Http\Livewire\CharityPayments;

use App\Models\CharityPayments\CharityPayment;
use App\Models\Masters\Bank;
use Livewire\Component;
use Livewire\WithPagination;

class CharityPaymentModal extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $organization_id = null;
    public string $type = 'transfer';
    public ?int $bank_id = null;
    public ?string $account_name = null;
    public ?string $account_number = null;
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
            'bank_id' => ['required', 'exists:m_banks,id'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
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
        $this->notes = $model->notes;
        $this->is_active = (bool) $model->is_active;

        $this->dispatch('select-sync', [
            'id' => $this->getId(),
            'field' => 'bank_id',
            'value' => $this->bank_id,
        ]);
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->isPartner && $this->organization_id) {
            $data['organization_id'] = $this->organization_id;
        }

        CharityPayment::updateOrCreate(['id' => $this->editingId], $data);

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
        $this->notes = null;
        $this->is_active = true;
        $this->resetValidation();

        $this->dispatch('select-sync', [
            'id' => $this->getId(),
            'field' => 'bank_id',
            'value' => $this->bank_id,
        ]);
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
