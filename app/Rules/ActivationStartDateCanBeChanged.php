<?php

namespace App\Rules;

use App\Models\Title;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class ActivationStartDateCanBeChanged implements Rule
{
    private $title;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Title $title)
    {
        $this->title = $title;
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
        if ($this->title->isCurrentlyActivated() && ! $this->title->activatedOn(Carbon::parse($value))) {
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
        return "{$this->title->name} is currently activated and the activation date cannot be changed.";
    }
}
