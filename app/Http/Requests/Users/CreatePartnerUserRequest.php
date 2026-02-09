<?php

namespace App\Http\Requests\Users;

use App\Models\Users\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreatePartnerUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->resolveOrganizationId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'phone:ID,mobile'],
            'level_slug' => [
                'required',
                Rule::exists('user_levels', 'slug')->where('organization_id', $organizationId),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2000'],
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
