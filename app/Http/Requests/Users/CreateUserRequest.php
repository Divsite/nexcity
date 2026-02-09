<?php

namespace App\Http\Requests\Users;

use App\Models\Roles\Role;
use App\Models\Users\User;
use App\Models\Organizations\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
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
        $role = $this->input('role');

        $roleTypeMap = [
            'rt_admin' => Organization::TYPE_RT,
            'mosque_admin' => Organization::TYPE_MOSQUE,
            'umkm_admin' => Organization::TYPE_UMKM,
            'institusi_admin' => Organization::TYPE_INSTITUTION,
        ];

        $organizationRules = ['nullable'];

        if ($role) {
            if (array_key_exists($role, $roleTypeMap)) {
                $organizationRules = [
                    'required',
                    Rule::exists('organizations', 'id')->where('type', $roleTypeMap[$role]),
                ];
            } elseif ($role === 'corporate_admin') {
                $organizationRules = ['required', Rule::exists('organizations', 'id')];
            }
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'phone:ID,mobile'],
            'status' => ['required', Rule::in([User::VERIFIED, User::UNVERIFIED])],
            'role' => ['required', Rule::in($roles)],
            'organization_id' => $organizationRules,
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2000'],
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
            'organization_id' => Str::lower(__('messages.organization')),
            'password' => Str::lower(__('messages.password')),
            'avatar' => Str::lower(__('messages.profile_picture')),
        ];
    }
}
