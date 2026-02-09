<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePartnerUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->resolveOrganizationId();
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'phone:ID,mobile'],
            'level_slug' => [
                'required',
                Rule::exists('user_levels', 'slug')->where('organization_id', $organizationId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2000'],
            'initial_name' => ['required', Rule::in([\App\Models\Users\User::AVATAR_INITIAL_NAME, \App\Models\Users\User::AVATAR_NOT_INITIAL_NAME])],
        ];
    }

    public function messages(): array
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
            'level_slug' => Str::lower(__('messages.user_level')),
            'password' => Str::lower(__('messages.password')),
            'avatar' => Str::lower(__('messages.profile_picture')),
        ];
    }

    private function resolveOrganizationId(): ?int
    {
        $user = $this->user();
        $membership = $user?->organizationMemberships()
            ->where('is_primary', true)
            ->first();

        return $membership?->organization_id;
    }
}
