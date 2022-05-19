<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

class TitleChampionIncludedInTitleMatch implements Rule
{
    /**
     * @var array
     */
    protected $titleIds;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $titleIds)
    {
        $this->titleIds = $titleIds;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $titles = Title::with('currentChampionship.champion')
            ->findMany($this->titleIds)
            ->filter(fn ($title) => ! $title->isVacant());

        $competitors = collect($value)->flatten(1);

        $wrestlers = Wrestler::whereIn(
            'id',
            $competitors->where('competitor_type', 'wrestler')->pluck('competitor_id')
        )->get();

        $tagTeams = TagTeam::whereIn(
            'id',
            $competitors->where('competitor_type', 'tag_team')->pluck('competitor_id')
        )->get();

        $competitors = $wrestlers->merge($tagTeams);

        foreach ($titles as $title) {
            if (! $competitors->contains($title->currentChampionship->champion)) {
                return false;
            }
        }

        return true;
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
