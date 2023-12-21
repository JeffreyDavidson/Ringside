<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\MatchType;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType implements DataAwareRule, ValidationRule
{
    /**
     * All the data under validation.
     *
     * @var array<string, string>
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, string>  $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var MatchType $matchType */
        $matchType = MatchType::find($this->data['match_type_id']);

        if ($matchType->number_of_sides !== count((array) $value)) {
            $fail('This match does not have the required amount of sides of competitors.');
        }
    }
}
