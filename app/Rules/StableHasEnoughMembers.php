<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StableHasEnoughMembers implements Rule
{
    private ?string $startDate;
    private ?array $tagTeamIds = [];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(?string $startDate = null, ?array $tagTeamIds = [])
    {
        $this->startDate = $startDate;
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
        return ! ($this->startDate && ((! $this->tagTeamIds && count($value) < 3) || (! $value && count($this->tagTeamIds) === 1)));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'There is not enough members for this stable.';
    }
}
