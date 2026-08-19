<?php

declare(strict_types=1);

namespace App\Rules\Titles;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\TitleChampionship;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class CurrentChampionIsCompeting implements DataAwareRule, ValidationRule
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
            return;
        }

        $currentChampionship = TitleChampionship::query()
            ->forTitleId((int) $value)
            ->current()
            ->first(['champion_type', 'champion_id']);

        if (! $currentChampionship instanceof TitleChampionship) {
            return;
        }

        $competitorKey = match ($currentChampionship->champion_type) {
            Wrestler::query()->getModel()->getMorphClass() => 'wrestlers',
            TagTeam::query()->getModel()->getMorphClass() => 'tag_teams',
            default => null,
        };

        if ($competitorKey !== null && $this->includesChampion($competitorKey, $currentChampionship->champion_id)) {
            return;
        }

        $fail('The current champion must be included in title matches.');
    }

    private function includesChampion(string $competitorKey, int $championId): bool
    {
        $competitorSides = $this->data['competitors'] ?? [];

        if (! is_array($competitorSides)) {
            return false;
        }

        foreach ($competitorSides as $competitorSide) {
            if (! is_array($competitorSide)) {
                continue;
            }

            $competitorIds = $competitorSide[$competitorKey] ?? [];

            if (! is_array($competitorIds)) {
                continue;
            }

            foreach ($competitorIds as $competitorId) {
                if ((is_int($competitorId) || is_string($competitorId)) && (int) $competitorId === $championId) {
                    return true;
                }
            }
        }

        return false;
    }
}
