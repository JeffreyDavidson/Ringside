<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

class LetterSpace implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (! preg_match('/^[a-zA-Z\s,]+$/', $value)) {
            $fail('The :attribute only allows for letters and spaces.');
        }
    }
}
