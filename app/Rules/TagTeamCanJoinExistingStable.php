<?php

namespace App\Rules;

use App\Models\TagTeam;
use Illuminate\Contracts\Validation\Rule;

class TagTeamCanJoinExistingStable implements Rule
{
    protected $messages;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $tagTeam = TagTeam::with(['currentWrestlers', 'currentStable'])->whereKey($value)->sole();

        if ($tagTeam->currentStable !== null) {
            return false;
        }

        if ($tagTeam->isSuspended()) {
            $this->messages = "{$tagTeam->name} is supsended and cannot join the stable.";

            return false;
        }

        if ($tagTeam->isCurrentlyEmployed() && ! $tagTeam->employedBefore($this->date('started_at'))) {
            $this->messages = "{$tagTeam->name} cannot have an employment start date after stable's start date.";

            return false;
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
        return $this->messages;
    }
}
