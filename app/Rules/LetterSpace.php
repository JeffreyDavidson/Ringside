<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\InvokableRule;

class LetterSpace implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function __invoke(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[a-zA-Z\s,]+$/', $value)) {
            $fail('The :attribute only allows for letters and spaces.');
        }
    }
}
