<?php

namespace App\Http\Requests\FormProcesses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CreateFormProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => Str::lower(__('messages.name')),
            'status' => Str::lower(__('messages.status')),
        ];
    }
}
