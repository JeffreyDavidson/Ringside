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
        if (! is_array($this->tagTeamIds)) {
            return false;
        }

        $wrestler = Wrestler::with(['currentStable'])->whereKey($value)->sole();

        if (count($this->tagTeamIds) === 0 || is_null($wrestler->currentTagTeam)) {
            return true;
        }

        if (collect($this->tagTeamIds)->doesntContain($wrestler->currentTagTeam->id)) {
            return true;
        }

        if (! is_null($wrestler->currentStable) && $wrestler->currentStable->exists()) {
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
        return 'This wrestler is already a member of a stable.';
    }
}
