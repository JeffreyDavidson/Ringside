<?php

declare(strict_types=1);

namespace App\Rules\Titles;

use App\Models\Titles\Title;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsActive implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $title = Title::find($value);

        if (! $title instanceof Title || ! $title->isCurrentlyActive()) {
            $fail('This title is not active and cannot be used in matches.');
        }
    }
}
