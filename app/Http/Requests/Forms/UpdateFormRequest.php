<?php

namespace App\Http\Requests\Forms;

use App\Models\FormTypes\FormType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $formTypes = FormType::select(['id', 'name'])->pluck('id');

        $webhookUrlType = 'url';
        if ($this->use_current_url) {
            $webhookUrlType = 'string';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'type_id' => ['nullable', Rule::in($formTypes)],
            'properties' => ['nullable', 'array'],
            'use_current_url' => ['required', 'boolean'],
            'webhook_url' => ['nullable', $webhookUrlType, 'max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'name' => Str::lower(__('messages.form_name')),
            'type_id' => Str::lower(__('messages.form_type')),
            'webhook_url' => Str::lower(__('messages.webhook_url')),
        ];
    }
}
