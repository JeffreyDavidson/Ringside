<?php

namespace App\Rules;

use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class CannotBeEmployedAfterDate implements Rule
{
    protected $wrestler;
    protected ?string $startedAt;

    public function __construct(string $startedAt = null)
    {
        $this->startedAt = $startedAt;
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
        if ($this->startedAt === null || ! is_string($this->startedAt)) {
            return true;
        }

        $this->wrestler = Wrestler::find($value);

        if ($this->wrestler->isUnemployed()) {
            return true;
        }

        if ($this->wrestler->isCurrentlyEmployed()) {
            return $this->wrestler->currentEmployment->startedBefore($this->startedAt);
        }

        if ($this->wrestler->hasFutureEmployment()) {
            return $this->wrestler->futureEmployment->startedBefore($this->startedAt);
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->wrestler->name.' is not employed before '.$this->startedAt;
    }
}
