<?php

declare(strict_types=1);

namespace App\Rules\Referees;

use App\Models\Referees\Referee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CanRefereeMatch implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Referee|null $referee */
        $referee = Referee::find($value);

        if (! $referee instanceof Referee || ! $referee->isBookable()) {
            $fail('This referee is not available to officiate matches.');
        }
    }
}
