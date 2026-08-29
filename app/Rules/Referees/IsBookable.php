<?php

declare(strict_types=1);

namespace App\Rules\Referees;

use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Roster\Referees\Referee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsBookable implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $referee = Referee::query()->find($value);

        if (! $referee instanceof Referee || ! RosterBookingEligibility::allows($referee)) {
            $fail('This referee is not available to officiate matches.');
        }
    }
}
