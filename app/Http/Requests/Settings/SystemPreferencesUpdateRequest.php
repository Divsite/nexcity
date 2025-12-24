<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SystemPreferencesUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'logo_sm' => ['nullable', 'image', 'mimes:jpg,png', 'max:3048'],
            'logo_lg' => ['nullable', 'image', 'mimes:jpg,png', 'max:3048'],
            'favicon' => ['nullable', 'image', 'mimes:jpg,png,ico', 'max:1048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'logo_sm' => Str::lower(__('messages.small_logo')),
            'logo_lg' => Str::lower(__('messages.large_logo')),
            'favicon' => Str::lower(__('messages.favicon')),
        ];
    }

    public function messages(): array
    {
        return [
            'logo_sm.max' => __('messages.logo_max_size_file'),
            'logo_lg.max' => __('messages.logo_max_size_file'),
            'favicon.max' => __('messages.favicon_max_size_file'),
        ];
    }
}
