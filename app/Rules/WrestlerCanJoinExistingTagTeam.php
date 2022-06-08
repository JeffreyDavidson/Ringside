<?php

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class WrestlerCanJoinExistingTagTeam implements Rule
{
    protected $tagTeam;

    public function __construct(TagTeam $tagTeam)
    {
        $this->tagTeam = $tagTeam;
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
        $wrestler = Wrestler::query()->with(['currentEmployment', 'futureEmployment'])->whereKey($value)->sole();

        if ($wrestler->isSuspended() || $wrestler->isInjured()) {
            return false;
        }

        if (($wrestler->currentTagTeam() !== null && $wrestler->currentTagTeam()->exists())
            || (! is_null($this->tagTeam))) {
            return false;
        }

        if ($wrestler->currentTagTeam() !== null
            && ! $wrestler->currentTagTeam()->isNot($this->tag_team)
        ) {
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
        return 'This wrestler cannot join the tag team.';
    }
}
