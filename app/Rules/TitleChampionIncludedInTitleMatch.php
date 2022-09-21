<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

class TitleChampionIncludedInTitleMatch implements Rule
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $titleIds;

    /**
     * Create a new rule instance.
     *
     * @param  \Illuminate\Support\Collection  $titleIds
     * @return void
     */
    public function __construct(Collection $titleIds)
    {
        $this->titleIds = $titleIds;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function passes($attribute, $value)
    {
        if ($this->titleIds->isEmpty()) {
            return true;
        }

        $competitors = collect($value)->flatten(1);

        $wrestlers = Wrestler::query()
            ->whereIn('id', $competitors->where('competitor_type', 'wrestler')->pluck('competitor_id'))
            ->get();

        $tagTeams = TagTeam::query()
            ->whereIn('id', $competitors->where('competitor_type', 'tag_team')->pluck('competitor_id'))
            ->get();

        $competitors = $wrestlers->merge($tagTeams);

        return Title::with('currentChampionship.champion')
            ->findMany($this->titleIds)
            ->reject(fn ($title) => $title->isVacant())
            ->every(fn ($title) => $competitors->contains($title->currentChampionship->champion));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This match requires the champion to be involved.';
    }
}
