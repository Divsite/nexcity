<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidRegex implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        set_error_handler(function () {}, E_WARNING); // Suppress warnings
        $isValid = @preg_match($value, null) !== false; // Check if regular expression is valid
        restore_error_handler(); // Restore error handler

        if (!$isValid) {
            $fail(__('messages.not_valid_regular_expression', ['attribute' => Str::lower(__('messages.number_pattern'))]));
        }
    }
}
