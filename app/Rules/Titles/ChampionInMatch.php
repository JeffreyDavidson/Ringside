<?php

declare(strict_types=1);

namespace App\Rules\Titles;

use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class ChampionInMatch implements DataAwareRule, ValidationRule
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
        // Get competitors from form data
        $wrestlerIds = array_filter((array) ($this->data['wrestlers'] ?? []));
        $tagTeamIds = array_filter((array) ($this->data['tag_teams'] ?? []));

        $wrestlers = Wrestler::query()->whereIn('id', $wrestlerIds)->get();
        $tagTeams = TagTeam::query()->whereIn('id', $tagTeamIds)->get();

        /** @var Collection<int, Wrestler|TagTeam> $allCompetitors */
        $allCompetitors = $wrestlers->concat($tagTeams);

        // Get titles for this match
        $titleIds = array_filter((array) ($this->data['titles'] ?? []));
        $titles = Title::findMany($titleIds);

        foreach ($titles as $title) {
            if ($title->isVacant()) {
                continue;
            }

            $currentChampionship = TitleChampionship::query()
                ->where('title_id', $title->id)
                ->whereNull('lost_at')
                ->with('champion')
                ->first();

            if (! $currentChampionship) {
                continue;
            }

            $champion = $currentChampionship->champion;
            $championIncluded = $allCompetitors->contains(function (object $competitor) use ($champion): bool {
                return $competitor->is($champion);
            });

            if (! $championIncluded) {
                $fail('The current champion must be included in title matches.');

                return;
            }
        }
    }
}
