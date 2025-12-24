<?php

namespace App\Http\Requests\Profiles;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->user()->password) {
            $rules['current_password'] = ['required', 'current_password:web'];
        }

        $rules['new_password'] = ['required'];

        $passwordRule = config('core.password_rules');

        if ($passwordRule['enabled']) {
            $password = Password::min($passwordRule['min']);

            if ($passwordRule['letter']) {
                $password->letters();
            }

            if ($passwordRule['mixed_case']) {
                $password->mixedCase();
            }

            if ($passwordRule['number']) {
                $password->numbers();
            }

            if ($passwordRule['symbol']) {
                $password->symbols();
            }

            if ($passwordRule['uncompromised']) {
                $password->uncompromised();
            }

            $rules['new_password'][] = $password;
        }

        $rules['new_password'][] = 'confirmed';
        $rules['new_password'][] = function (string $attribute, mixed $value, Closure $fail) {
            if (Hash::check($value, auth()->user()->password)) {
                $fail(__('messages.no_same_password_new_current'));
            }
        };

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'current_password' => Str::lower(__('messages.current_password')),
            'new_password' => Str::lower(__('messages.new_password')),
        ];
    }
}
