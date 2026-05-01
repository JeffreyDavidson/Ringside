<?php

declare(strict_types=1);

namespace App\Rules\Matches;

use App\Enums\MatchType;
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
        if (! isset($this->data['match_type'])) {
            return; // No match type to validate against
        }

        $matchType = $this->data['match_type'] instanceof MatchType
            ? $this->data['match_type']
            : MatchType::tryFrom($this->data['match_type']);

        if ($matchType && $matchType->numberOfSides() !== count((array) $value)) {
            $fail('This match does not have the required number of competitor sides.');
        }
    }
}
