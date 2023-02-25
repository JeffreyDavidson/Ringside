<?php

namespace App\Rules\Stables;

use App\Models\Stable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class HasMinimumAmountOfMembers implements ValidationRule
{
    public function __construct(private Stable $stable, private Collection $wrestlers, private Collection $tagTeams)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->stable->isCurrentlyActivated()) {
            $tagTeamsCountFromRequest = $this->tagTeams->count();
            $wrestlersCountFromRequest = $this->wrestlers->count();

            $tagTeamMembersCount = $tagTeamsCountFromRequest * 2;

            if ($tagTeamMembersCount + $wrestlersCountFromRequest < 3) {
                $fail("{$this->stable->name} is currently activated and the activation date cannot be changed.");
            }
        }
    }
}
