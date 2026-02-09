<?php

namespace App\Http\Requests\Menus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserMenuRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['route_parameters', 'visibility_rules'] as $field) {
            if (is_string($this->{$field}) && $this->{$field} !== '') {
                $decoded = json_decode($this->{$field}, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'context' => ['required', Rule::in(['admin', 'rt', 'mosque', 'resident'])],
            'section' => ['nullable', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'route_parameters' => ['nullable', 'array'],
            'url' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'user_level_id' => ['nullable', 'exists:user_levels,id'],
            'visibility_rules' => ['nullable', 'array'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
