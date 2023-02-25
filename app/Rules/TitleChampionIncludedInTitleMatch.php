<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class TitleChampionIncludedInTitleMatch implements ValidationRule
{
    public function __construct(protected Collection $titleIds)
    {
        $this->titleIds = $titleIds;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $competitors = collect($value)->flatten(1);

        $wrestlers = Wrestler::query()
            ->whereIn('id', $competitors->where('competitor_type', 'wrestler')->pluck('competitor_id'))
            ->get();

        $tagTeams = TagTeam::query()
            ->whereIn('id', $competitors->where('competitor_type', 'tag_team')->pluck('competitor_id'))
            ->get();

        $competitors = $wrestlers->merge($tagTeams);

        $champions = Title::with('currentChampionship.champion')
            ->findMany($this->titleIds)
            ->reject(fn ($title) => $title->isVacant())
            ->every(fn ($title) => $competitors->contains($title->currentChampionship->champion));

        if (! $champions) {
            $fail('This match requires the champion to be involved.');
        }
    }
}
