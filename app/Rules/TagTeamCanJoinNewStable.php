<?php

namespace App\Rules;

use App\Models\TagTeam;
use Illuminate\Contracts\Validation\Rule;

class TagTeamCanJoinNewStable implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  mixed  $value
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes(string $attribute, $value): bool
    {
        $tagTeam = TagTeam::with(['currentWrestlers', 'currentStable'])->whereKey($value)->sole();

        if ($tagTeam->currentStable !== null) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'This tag team is already a member of a stable.';
    }
}
