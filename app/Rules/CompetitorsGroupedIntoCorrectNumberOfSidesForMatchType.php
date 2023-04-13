<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\MatchType;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType implements ValidationRule, DataAwareRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
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
        if (MatchType::find($this->data['match_type_id'])?->number_of_sides !== count($value)) {
            $fail('This match does not have the required amount of sides of competitors.');
        }
    }
}
