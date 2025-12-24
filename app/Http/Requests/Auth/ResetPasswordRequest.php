<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules['token'] = ['required'];
        $rules['email'] = ['required', 'email'];
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
            'token' => Str::lower(__('messages.token')),
            'email' => Str::lower(__('messages.email')),
            'password' => Str::lower(__('messages.password')),
        ];
    }
}
