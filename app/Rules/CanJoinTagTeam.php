<?php

namespace App\Rules;

use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class CanJoinTagTeam implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $wrestler = Wrestler::find($value);
        $startedAtDate = $wrestler->employment->started_at;

        if (is_null($startedAtDate) || $startedAtDate->isFuture()) {
            return false;
        }

        if ($wrestler->tagteam()->exists()) {
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
        return 'A wrestler is not allowed to be added to this tag team.';
    }
}
