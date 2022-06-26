<?php

namespace App\Rules\Stables;

use App\Models\Stable;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class HasMinimumAmountOfMembers implements Rule
{
    protected $stable;

    protected $startedAt;

    protected $wrestlers;

    protected $tagTeams;

    public function __construct(Stable $stable, $startedAt, $wrestlers, $tagTeams)
    {
        $this->stable = $stable;
        $this->startedAt = $startedAt;
        $this->wrestlers = $wrestlers;
        $this->tagTeams = $tagTeams;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->stable->isCurrentlyActivated() || Carbon::parse($this->startedAt)) {
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
