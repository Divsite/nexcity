<?php

namespace App\Http\Requests\FormTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateFormTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => Str::lower(__('messages.name')),
            'description' => Str::lower(__('messages.description')),
        ];
    }
}
