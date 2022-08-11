<?php

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class WrestlerCanJoinExistingTagTeam implements Rule
{
    /**
     * Undocumented variable
     *
     * @var \App\Models\TagTeam
     */
    protected $tagTeam;

    /**
     * Undocumented function
     *
     * @param  \App\Models\TagTeam  $tagTeam
     */
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
        /** @var \App\Models\Wrestler $wrestler */
        $wrestler = Wrestler::query()->with(['currentEmployment', 'futureEmployment'])->whereKey($value)->sole();

        if ($wrestler->isSuspended() || $wrestler->isInjured()) {
            return false;
        }

        if ($wrestler->currentTagTeam !== null && $wrestler->currentTagTeam->exists() && ! $wrestler->currentTagTeam->isNot($this->tagTeam)) {
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
