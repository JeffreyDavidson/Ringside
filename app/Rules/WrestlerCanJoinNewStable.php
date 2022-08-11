<?php

namespace App\Rules;

use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class WrestlerCanJoinNewStable implements Rule
{
    /**
     * @var array
     */
    protected $tagTeamIds;

    /**
     * Create a new rule instance.
     *
     * @param  array $tagTeamIds
     * @return void
     */
    public function __construct($tagTeamIds)
    {
        $this->tagTeamIds = $tagTeamIds;
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
        $wrestler = Wrestler::with(['currentStable'])->find($value);

        if (! is_null($wrestler->currentStable) && $wrestler->currentStable->exists()) {
            return false;
        }

        if (is_array($this->tagTeamIds) && count($this->tagTeamIds) > 0) {
            if (! is_null($wrestler->currentTagTeam)
                && collect($this->tagTeamIds)->contains($wrestler->currentTagTeam->id)
            ) {
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
        return 'This wrestler is already a member of a stable.';
    }
}
