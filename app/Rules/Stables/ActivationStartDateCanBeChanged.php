<?php

namespace App\Rules\Stables;

use App\Models\Stable;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class ActivationStartDateCanBeChanged implements Rule
{
    private $stable;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
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
        if ($this->stable->isCurrentlyActivated() && ! $this->stable->activatedOn(Carbon::parse($value))) {
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
        return "{$this->stable->name} is currently activated and the activation date cannot be changed.";
    }
}