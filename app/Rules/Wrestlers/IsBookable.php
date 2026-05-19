<?php

declare(strict_types=1);

namespace App\Rules\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsBookable implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Wrestler|null $wrestler */
        $wrestler = Wrestler::find($value);

        if (! $wrestler || ! $wrestler->isBookable()) {
            $fail('This wrestler is not available for booking.');
        }
    }
}
