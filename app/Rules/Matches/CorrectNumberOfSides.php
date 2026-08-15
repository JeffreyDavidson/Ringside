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
        if (! is_array($value)) {
            return;
        }

        $matchTypeValue = $this->data['matchType'] ?? $this->data['match_type'] ?? null;
        $matchType = $matchTypeValue instanceof MatchType
            ? $matchTypeValue
            : (is_string($matchTypeValue) ? MatchType::tryFrom($matchTypeValue) : null);
        $requiredSides = $matchType?->numberOfSides();

        if ($requiredSides !== null && $requiredSides !== count($value)) {
            $fail('This match does not have the required number of competitor sides.');
        }
    }
}
