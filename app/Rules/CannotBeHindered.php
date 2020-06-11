<?php

namespace App\Rules;

use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class CannotBeHindered implements Rule
{
    protected $wrestler;

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

        if ($this->wrestler->isSuspended() || $this->wrestler->isRetired() || $this->wrestler->isInjured()) {
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
        return $this->wreslter->name.' is not allowed to join this tag team.';
    }
}
