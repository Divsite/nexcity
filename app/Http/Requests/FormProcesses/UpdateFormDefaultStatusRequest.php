<?php

namespace App\Http\Requests\FormProcesses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateFormDefaultStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_submission_status' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'default_submission_status' => Str::lower(__('messages.default_submission_status')),
        ];
    }
}
