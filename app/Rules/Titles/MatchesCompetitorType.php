<?php

declare(strict_types=1);

namespace App\Rules\Titles;

use App\Models\Titles\Title;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class MatchesCompetitorType implements DataAwareRule, ValidationRule
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
        if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
            $fail('The selected title is invalid.');

            return;
        }

        $title = Title::query()->find((int) $value);

        if (! $title instanceof Title) {
            $fail('The selected title is invalid.');

            return;
        }

        $expectedCompetitorKey = $title->type->competitorInputKey();
        $unexpectedCompetitorKey = $title->type->opposingCompetitorInputKey();

        if ($this->hasCompetitors($expectedCompetitorKey) && ! $this->hasCompetitors($unexpectedCompetitorKey)) {
            return;
        }

        $fail("The {$title->name} may only be contested by {$title->type->competitorLabel()}.");
    }

    private function hasCompetitors(string $competitorKey): bool
    {
        $competitorSides = $this->data['competitors'] ?? null;

        if (! is_array($competitorSides)) {
            return false;
        }

        foreach ($competitorSides as $competitorSide) {
            if (! is_array($competitorSide)) {
                continue;
            }

            $competitors = $competitorSide[$competitorKey] ?? null;

            if (is_array($competitors) && $competitors !== []) {
                return true;
            }
        }

        return false;
    }
}
