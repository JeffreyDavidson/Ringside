<?php

namespace App\Rules;

use App\Models\TagTeam;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TagTeamCanJoinNewStable implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tagTeam = TagTeam::with(['currentWrestlers', 'currentStable'])->whereKey($value)->sole();

        if ($tagTeam->currentStable !== null) {
            $fail('This tag team is already a member of a stable.');
        }
    }
}
