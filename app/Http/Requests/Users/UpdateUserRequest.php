<?php

namespace App\Http\Requests\Users;

use App\Models\Roles\Role;
use App\Models\Users\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $roles = Role::all()->pluck('name');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->route('user'))],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'phone' => ['nullable', 'phone:ID,mobile'],
            'status' => ['required', Rule::in([User::VERIFIED, User::UNVERIFIED])],
            'role' => ['required', Rule::in($roles)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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

    public function attributes()
    {
        return [
            'name' => Str::lower(__('messages.name')),
            'username' => Str::lower(__('messages.username')),
            'email' => Str::lower(__('messages.email')),
            'phone' => Str::lower(__('messages.phone_number')),
            'status' => Str::lower(__('messages.status')),
            'role' => Str::lower(__('messages.role')),
            'password' => Str::lower(__('messages.password')),
            'avatar' => Str::lower(__('messages.profile_picture')),
        ];
    }
}
