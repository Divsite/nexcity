<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules['name'] = ['required', 'string', 'max:255'];
        $rules['username'] = ['required', 'string', 'max:255', 'unique:users'];
        $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:users'];
        $rules['password'] = ['required'];

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

            $rules['password'][] = $password;
        }

        $rules['password'][] = 'confirmed';

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => Str::lower(__('messages.name')),
            'username' => Str::lower(__('messages.username')),
            'email' => Str::lower(__('messages.email')),
            'password' => Str::lower(__('messages.password')),
        ];
    }
}
