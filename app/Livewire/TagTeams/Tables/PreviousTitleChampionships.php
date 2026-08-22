<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Livewire\Base\Tables\BasePreviousTitleChampionshipsTable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\TitleChampionship;
use Livewire\Attributes\Locked;
use LogicException;

class PreviousTitleChampionships extends BasePreviousTitleChampionshipsTable
{
    /**
     * Tag Team to use for component.
     */
    #[Locked]
    public ?int $tagTeamId;

    /** @return TitleChampionshipBuilder<TitleChampionship> */
    public function builder(): TitleChampionshipBuilder
    {
        if (! isset($this->tagTeamId)) {
            throw new LogicException('A tag team was not provided.');
        }

        $tagTeam = TagTeam::query()->findOrFail($this->tagTeamId);

        return TitleChampionship::query()
            ->forChampion($tagTeam)
            ->forPreviousHistory();
    }
}
