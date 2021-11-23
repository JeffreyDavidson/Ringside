<?php

namespace App\Rules;

use App\Models\MatchType;
use Illuminate\Contracts\Validation\Rule;

class CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType implements Rule
{
    /**
     * @var int
     */
    protected $matchTypeId;

    public function __construct(int $matchTypeId)
    {
        $this->matchTypeId = $matchTypeId;
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
        return MatchType::find($this->matchTypeId)->number_of_sides === count($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This match does not have the required amount of sides of competitors.';
    }
}
