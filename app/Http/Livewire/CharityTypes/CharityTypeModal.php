<?php

namespace App\Http\Livewire\CharityTypes;

use App\Models\CharityTypes\CharityType;
use App\Models\CharityTypeSources\CharityTypeSource;
use Livewire\Component;
use Livewire\WithPagination;

class CharityTypeModal extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $organization_id = null;
    public ?int $charity_type_source_id = null;
    public int $year;
    public ?float $min_amount = null;
    public ?float $max_amount = null;
    public bool $is_rice = false;
    public ?float $total_rice = null;
    public ?string $description = null;
    public bool $is_active = true;
    public string $search = '';

    public bool $isPartner = false;

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->year = (int) now()->year;

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
            'charity_type_source_id' => ['required', 'exists:m_charity_type_sources,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'is_rice' => ['boolean'],
            'total_rice' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function createNew(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $model = CharityType::findOrFail($id);
        $this->editingId = $model->id;
        $this->organization_id = $model->organization_id;
        $this->charity_type_source_id = $model->charity_type_source_id;
        $this->year = (int) $model->year;
        $this->min_amount = $model->min_amount;
        $this->max_amount = $model->max_amount;
        $this->is_rice = (bool) $model->is_rice;
        $this->total_rice = $model->total_rice;
        $this->description = $model->description;
        $this->is_active = (bool) $model->is_active;

        $this->dispatchBrowser('currency-sync', [
            'id' => $this->getId(),
            'values' => [
                'min_amount' => $this->min_amount,
                'max_amount' => $this->max_amount,
            ],
        ]);
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->min_amount !== null && $this->max_amount !== null && $this->max_amount < $this->min_amount) {
            $this->addError('max_amount', __('messages.max_amount_must_be_greater_equal_min_amount'));
            return;
        }

        if ($this->is_rice && empty($this->total_rice)) {
            $this->addError('total_rice', __('validation.required', ['attribute' => __('messages.total_rice')]));
            return;
        }

        if ($this->isPartner && $this->organization_id) {
            $data['organization_id'] = $this->organization_id;
        }

        if (! $this->editingId) {
            $exists = CharityType::query()
                ->where('organization_id', $data['organization_id'])
                ->where('charity_type_source_id', $data['charity_type_source_id'])
                ->where('year', $data['year'])
                ->exists();

            if ($exists) {
                $this->addError('charity_type_source_id', __('messages.charity_type_year_exists'));
                return;
            }
        }

        CharityType::updateOrCreate(['id' => $this->editingId], $data);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->editingId ? __('messages.updated_successfully') : __('messages.created_successfully'),
        ]);

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $model = CharityType::find($id);
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
        $this->charity_type_source_id = null;
        $this->year = (int) now()->year;
        $this->min_amount = null;
        $this->max_amount = null;
        $this->is_rice = false;
        $this->total_rice = null;
        $this->description = null;
        $this->is_active = true;
        $this->resetValidation();

        $this->dispatchBrowser('currency-sync', [
            'id' => $this->getId(),
            'values' => [
                'min_amount' => $this->min_amount,
                'max_amount' => $this->max_amount,
            ],
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
        $query = CharityType::query()->with(['source', 'organization']);
        if ($this->isPartner && $this->organization_id) {
            $query->where('organization_id', $this->organization_id);
        }
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('year', 'like', '%' . $this->search . '%')
                    ->orWhereHas('source', function ($sourceQuery) {
                        $sourceQuery->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('organization', function ($organizationQuery) {
                        $organizationQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return view('charity-types.modal', [
            'items' => $query->orderByDesc('year')->paginate(10),
            'sources' => CharityTypeSource::query()->orderBy('name')->get(),
            'componentId' => $this->getId(),
        ]);
    }
}
