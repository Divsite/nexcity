<?php

namespace App\Http\Livewire\DistributionTypes;

use App\Models\DistributionTypes\DistributionType;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class DistributionTypeModal extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public string $name = '';
    public ?string $description = null;
    public bool $is_active = true;
    public string $search = '';

    protected $paginationTheme = 'bootstrap';

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('m_distribution_types', 'name')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createNew(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $model = DistributionType::findOrFail($id);
        $this->editingId = $model->id;
        $this->name = $model->name;
        $this->description = $model->description;
        $this->is_active = (bool) $model->is_active;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['slug'] = $this->generateUniqueSlug($this->name, $this->editingId);

        DistributionType::updateOrCreate(['id' => $this->editingId], $data);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->editingId ? __('messages.updated_successfully') : __('messages.created_successfully'),
        ]);

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $model = DistributionType::find($id);
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
        $this->editingId = null;
        $this->name = '';
        $this->description = null;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $query = DistributionType::query();
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
        }

        return view('distribution-types.modal', [
            'items' => $query->orderBy('name')->paginate(10),
        ]);
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (DistributionType::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $counter++;
            $slug = $baseSlug . '-' . $counter;
        }

        return $slug;
    }
}
