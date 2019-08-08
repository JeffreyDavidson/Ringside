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
        $tagteam = TagTeam::find($value);

        if (! $tagteam) {
            return false;
        }

        if (!data_get($tagteam, 'employment.started_at')) {
            return false;
        }

        if ($tagteam->employment->started_at->isFuture()) {
            return false;
        }

        if (!$tagteam->is_bookable) {
            return false;
        }

        if ($tagteam->stables()->bookable()->whereKeyNot($this->stable->id)->exists()) {
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
