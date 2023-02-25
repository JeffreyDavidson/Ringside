<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\MatchType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType implements ValidationRule
{
    public function __construct(protected int $matchTypeId)
    {
        $this->matchTypeId = $matchTypeId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (MatchType::find($this->matchTypeId)?->number_of_sides !== count($value)) {
            $fail('This match does not have the required amount of sides of competitors.');
        }
    }
}
