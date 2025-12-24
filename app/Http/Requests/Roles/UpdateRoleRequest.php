<?php

namespace App\Http\Requests\Roles;

use App\Models\Permissions\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        $permissions = Permission::all()->pluck('id');

        return [
            'display_name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array', Rule::in($permissions)],
        ];
    }

    public function attributes(): array
    {
        return [
            'display_name' => Str::lower(__('messages.display_name')),
            'description' => Str::lower(__('messages.description')),
            'permissions' => Str::lower(__('messages.permissions')),
        ];
    }
}
