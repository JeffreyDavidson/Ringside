<?php

namespace App\Rules\Stables;

use App\Models\Stable;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HasMinimumAmountOfMembers implements Rule
{
    /**
     * Undocumented variable.
     *
     * @var \App\Models\Stable
     */
    protected $stable;

    /**
     * Undocumented variable.
     *
     * @var \Illuminate\Support\Carbon
     */
    protected $startDate;

    /**
     * Undocumented variable.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $wrestlers;

    /**
     * Undocumented variable.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $tagTeams;

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon  $startDate
     * @param  \Illuminate\Support\Collection  $wrestlers
     * @param  \Illuminate\Support\Collection  $tagTeams
     */
    public function __construct(Stable $stable, Carbon $startDate, Collection $wrestlers, Collection $tagTeams)
    {
        $this->stable = $stable;
        $this->startDate = $startDate;
        $this->wrestlers = $wrestlers;
        $this->tagTeams = $tagTeams;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes($attribute, $value)
    {
        if ($this->stable->isCurrentlyActivated()) {
            $tagTeamsCountFromRequest = $this->tagTeams->count();
            $wrestlersCountFromRequest = $this->wrestlers->count();

            $tagTeamMembersCount = $tagTeamsCountFromRequest * 2;

            if ($tagTeamMembersCount + $wrestlersCountFromRequest < 3) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "{$this->stable->name} is currently activated and the activation date cannot be changed.";
    }
}
