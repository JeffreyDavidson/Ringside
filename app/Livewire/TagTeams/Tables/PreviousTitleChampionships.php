<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Livewire\Base\Tables\BasePreviousTitleChampionshipsTable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

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
        $tagTeamId = $this->requireContextId($this->tagTeamId ?? null, 'tag team');

        return TitleChampionship::query()
            ->forTagTeamId($tagTeamId)
            ->forPreviousHistory();
    }

    protected function configure(): void
    {
        parent::configure();

        $tagTeamId = $this->requireContextId($this->tagTeamId ?? null, 'tag team');

        Gate::authorize('view', TagTeam::query()->findOrFail($tagTeamId));
    }
}
