<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class PreviousMatches extends BasePreviousMatchesTable
{
    /**
     * Tag Team to use for component.
     */
    public ?int $tagTeamId;

    protected string $databaseTableName = 'event_matches';

    protected string $resourceName = 'matches';

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new LogicException('A tag team was not provided.');
        }

        $tagTeam = TagTeam::query()->findOrFail($this->tagTeamId);

        return EventMatch::query()
            ->forPastEvents()
            ->with(['titles', 'competitors.competitor', 'winningSide.competitors.competitor'])
            ->forCompetitor($tagTeam)
            ->latestEventFirst();
    }
}
