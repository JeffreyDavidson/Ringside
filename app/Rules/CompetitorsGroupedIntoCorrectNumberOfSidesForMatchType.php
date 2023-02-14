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
     */
    public function __construct(int $matchTypeId)
    {
        $this->matchTypeId = $matchTypeId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  array|Countable  $value
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes(string $attribute, $value): bool
    {
        return MatchType::find($this->matchTypeId)?->number_of_sides === count($value);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'This match does not have the required amount of sides of competitors.';
    }
}
