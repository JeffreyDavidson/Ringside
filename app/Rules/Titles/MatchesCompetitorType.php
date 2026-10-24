<?php

declare(strict_types=1);

namespace App\Rules\Titles;

use App\Models\Titles\Title;
use App\ValueObjects\CompetitorSelection;
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
            $fail(__('titles.validation.invalid'));

            return;
        }

        $title = Title::query()->find((int) $value);

        if (! $title instanceof Title) {
            $fail(__('titles.validation.invalid'));

            return;
        }

        $selection = CompetitorSelection::fromInput($this->data['competitors'] ?? null);

        // The selection only lists types that have a competitor, so the title's type must be the only one.
        if (array_keys($selection->idsByType()) === [$title->type->championMorphClass()]) {
            if ($this->hasSideWithMultipleCompetitors($selection)) {
                $fail(__('titles.validation.one_competitor_per_side', ['title' => $title->name]));
            }

            return;
        }

        $fail(__('titles.validation.competitor_type', ['title' => $title->name, 'competitors' => $title->type->competitorLabel()]));
    }

    private function hasSideWithMultipleCompetitors(CompetitorSelection $selection): bool
    {
        return array_any($selection->sides(), fn (int $side): bool => count($selection->onSide($side)->entries()) > 1);
    }
}
