<?php

namespace App\Http\Requests\Profiles;

use App\Models\Users\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'phone:MY,mobile'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2000'],
            'initial_name' => ['required', Rule::in([User::AVATAR_INITIAL_NAME, User::AVATAR_NOT_INITIAL_NAME])],
        ];
    }

    public function messages()
    {
        return [
            'avatar.max' => __('messages.profile_picture_must_not_be_greater_than_2mb'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => Str::lower(__('messages.name')),
            'email' => Str::lower(__('messages.email')),
            'phone' => Str::lower(__('messages.phone_number')),
            'avatar' => Str::lower(__('messages.profile_picture')),
        ];
    }
}
