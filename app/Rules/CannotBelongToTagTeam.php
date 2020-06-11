<?php

namespace App\Rules;

use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class CannotBelongToTagTeam implements Rule
{
    private $wrestler;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->wrestler = Wrestler::find($value);

        return $this->wrestler->currentTagTeam()->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->wrestler->name . ' is already a part of a current tag team.';
    }
}
