<?php

namespace App\Rules;

use App\Support\MediaUrlValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedMediaUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        $url = MediaUrlValidator::sanitize($value);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        if (! MediaUrlValidator::isAllowed($url)) {
            $fail('The :attribute is not allowed.');
        }
    }
}
