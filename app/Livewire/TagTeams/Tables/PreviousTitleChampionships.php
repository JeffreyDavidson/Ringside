<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousTitleChampionshipsTable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class PreviousTitleChampionships extends BasePreviousTitleChampionshipsTable
{
    /**
     * Tag Team to use for component.
     */
    public ?int $tagTeamId;

    /**
     * @return Builder<TitleChampionship>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new LogicException('A tag team was not provided.');
        }

        $tagTeam = TagTeam::query()->findOrFail($this->tagTeamId);

        return TitleChampionship::query()
            ->forChampion($tagTeam)
            ->previous()
            ->with('title');
    }
}
