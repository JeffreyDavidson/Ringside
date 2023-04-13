<?php

namespace App\Rules;

use App\Models\Referee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RefereeCanRefereeMatch implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $referee = Referee::find($value);

        if (! $referee->isBookable()) {
            $fail('Referee is not able to be booked for this match.');
        }
    }
}
