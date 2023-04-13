<?php

namespace App\Rules;

use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WrestlerMustBeBookable implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $wrestler = Wrestler::find($value);

        if (! $wrestler->isBookable()) {
            $fail('This wrestler is not bookable and cannot be added to the match.');
        }
    }
}
