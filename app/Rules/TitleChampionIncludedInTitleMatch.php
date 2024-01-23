<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class TitleChampionIncludedInTitleMatch implements DataAwareRule, ValidationRule
{
    /**
     * All the data under validation.
     *
     * @var array<string, array<string>|string>
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, string>  $data
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $competitors = collect((array) $value);

        $wrestlerIds = $competitors->groupBy('wrestlers')->flatten();
        $tagTeamIds = $competitors->groupBy('tag_teams')->flatten();

        $wrestlers = Wrestler::query()->whereIn('id', $wrestlerIds)->get();
        $tagTeams = TagTeam::query()->whereIn('id', $tagTeamIds)->get();

        /** @var Collection<int, Wrestler|TagTeam>  $competitors */
        $competitors = collect();
        $competitors->merge($wrestlers)->merge($tagTeams);

        /** @var array<string> $titles */
        $titles = $this->data['titles'];

        $champions = Title::with('currentChampionship.currentChampion')
            ->findMany($titles)
            ->reject(fn (Title $title) => $title->isVacant())
            ->every(fn (Title $title) => $competitors->contains($title->currentChampionship?->currentChampion));

        if (! $champions) {
            $fail('This match requires the champion to be involved.');
        }
    }
}
