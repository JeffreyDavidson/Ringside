<?php

namespace App\Rules;

use App\Models\Contracts\Employable;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;

class EmploymentStartDateCanBeChanged implements Rule
{
    private $rosterMember;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Employable $rosterMember)
    {
        $this->rosterMember = $rosterMember;
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
        $name = $this->rosterMember->name;

        if ($this->rosterMember->isReleased() && ! $this->rosterMember->employedOn(Carbon::parse($value))) {
            $this->message = "{$name} was released and the employment date cannot be changed.";

            return false;
        }

        if ($this->rosterMember->isCurrentlyEmployed() && ! $this->rosterMember->employedOn(Carbon::parse($value))) {
            $this->message = "{$name} is currently employed and the employment date cannot be changed.";

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
        return $this->message;
    }
}
