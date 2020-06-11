<?php

namespace App\Rules;

use App\Models\Stable;
use App\Models\TagTeam;
use Illuminate\Contracts\Validation\Rule;

class TagTeamCanJoinStable implements Rule
{
    protected $stable;

    public function __construct(Stable $stable)
    {
        $this->stable = $stable;
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
        $tagTeam = TagTeam::find($value);

        if (! $tagTeam) {
            return false;
        }

        if (!data_get($tagTeam, 'currentEmployment.started_at')) {
            return false;
        }

        if ($tagTeam->currentEmployment->started_at->isFuture()) {
            return false;
        }

        if (!$tagTeam->is_bookable) {
            return false;
        }

        if ($tagTeam->stables()->bookable()->whereKeyNot($this->stable->id)->exists()) {
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
        return 'This tag team cannot join this stable.';
    }
}
