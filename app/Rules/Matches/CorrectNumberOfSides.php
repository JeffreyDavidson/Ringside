<?php

declare(strict_types=1);

namespace App\Rules\Matches;

use App\Models\Matches\MatchType;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class CorrectNumberOfSides implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @param array<string, mixed> $data */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!isset($this->data['match_type_id'])) {
            return; // No match type to validate against
        }

        $matchType = MatchType::find($this->data['match_type_id']);

        if ($matchType && $matchType->number_of_sides !== count((array) $value)) {
            $fail('This match does not have the required number of competitor sides.');
        }
    }
}
