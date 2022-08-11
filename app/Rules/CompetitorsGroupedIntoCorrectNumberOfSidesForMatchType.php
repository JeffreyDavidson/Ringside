<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\MatchType;
use Countable;
use Illuminate\Contracts\Validation\Rule;

class CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType implements Rule
{
    /**
     * The match type to check against.
     *
     * @var int
     */
    protected $matchTypeId;

    /**
     * Create a new rule instance.
     *
     * @param  int  $matchTypeId
     */
    public function __construct($matchTypeId)
    {
        $this->matchTypeId = $matchTypeId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  array|Countable  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return MatchType::find($this->matchTypeId)?->number_of_sides === count($value);
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
