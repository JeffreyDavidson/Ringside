<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Title;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TitleMustBeActive implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $title = Title::query()->whereKey($value)->sole();

        if (! $title->isCurrentlyActivated()) {
            $fail('This title is not active and cannot be added to the match.');
        }
    }
}
