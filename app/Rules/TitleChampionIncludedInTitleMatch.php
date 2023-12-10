<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TitleChampionIncludedInTitleMatch implements DataAwareRule, ValidationRule
{
    /**
     * All the data under validation.
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     *
     * @return $this
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
        $competitors = collect((array) $value);

        $wrestlerIds = $competitors->groupBy('wrestlers')->flatten();
        $tagTeamIds = $competitors->groupBy('tag_teams')->flatten();

        $wrestlers = Wrestler::query()->whereIn('id', $wrestlerIds)->get();
        $tagTeams = TagTeam::query()->whereIn('id', $tagTeamIds)->get();

        $competitors = $wrestlers->merge($tagTeams);

        $champions = Title::with('currentChampionship.champion')
            ->findMany($this->data['titles'])
            ->reject(fn (Title $title) => $title->isVacant())
            ->every(fn (Title $title) => $competitors->contains($title->currentChampionship?->champion));

        if (! $champions) {
            $fail('This match requires the champion to be involved.');
        }
    }
}
