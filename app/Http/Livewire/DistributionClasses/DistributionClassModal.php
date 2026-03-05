<?php

namespace App\Http\Livewire\DistributionClasses;

use App\Models\DistributionClasses\DistributionClass;
use App\Models\DistributionClassSources\DistributionClassSource;
use Livewire\Component;
use Livewire\WithPagination;

class DistributionClassModal extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $organization_id = null;
    public ?int $distribution_class_source_id = null;
    public int $year;
    public ?float $get_money = null;
    public ?float $get_rice = null;
    public bool $is_internal = false;
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
            'distribution_class_source_id' => ['required', 'exists:m_distribution_class_sources,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'get_money' => ['nullable', 'numeric', 'min:0'],
            'get_rice' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'is_internal' => ['boolean'],
        ];
    }

    public function createNew(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $model = DistributionClass::findOrFail($id);
        $this->editingId = $model->id;
        $this->organization_id = $model->organization_id;
        $this->distribution_class_source_id = $model->distribution_class_source_id;
        $this->year = (int) $model->year;
        $this->get_money = $model->get_money;
        $this->get_rice = $model->get_rice;
        $this->description = $model->description;
        $this->is_active = (bool) $model->is_active;
        $this->is_internal = (bool) $model->is_internal;

        $this->dispatchBrowser('currency-sync', [
            'id' => $this->getId(),
            'values' => [
                'get_money' => $this->get_money,
            ],
        ]);
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->isPartner && $this->organization_id) {
            $data['organization_id'] = $this->organization_id;
        }

        if (! $this->editingId) {
            $exists = DistributionClass::query()
                ->where('organization_id', $data['organization_id'])
                ->where('distribution_class_source_id', $data['distribution_class_source_id'])
                ->where('year', $data['year'])
                ->exists();

            if ($exists) {
                $this->addError('distribution_class_source_id', __('messages.distribution_class_year_exists'));
                return;
            }
        }

        DistributionClass::updateOrCreate(['id' => $this->editingId], $data);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->editingId ? __('messages.updated_successfully') : __('messages.created_successfully'),
        ]);

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $model = DistributionClass::find($id);
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
        $this->distribution_class_source_id = null;
        $this->year = (int) now()->year;
        $this->get_money = null;
        $this->get_rice = null;
        $this->description = null;
        $this->is_active = true;
        $this->is_internal = false;
        $this->resetValidation();

        $this->dispatchBrowser('currency-sync', [
            'id' => $this->getId(),
            'values' => [
                'get_money' => $this->get_money,
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
        $query = DistributionClass::query()->with(['source', 'organization']);
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

        return view('distribution-classes.modal', [
            'items' => $query->orderByDesc('year')->paginate(10),
            'sources' => DistributionClassSource::query()->orderBy('name')->get(),
            'componentId' => $this->getId(),
        ]);
    }
}
