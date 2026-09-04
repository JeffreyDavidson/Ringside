<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

class PreviousMatches extends BasePreviousMatchesTable
{
    /**
     * Tag Team to use for component.
     */
    #[Locked]
    public ?int $tagTeamId;

    /** @return EventMatchBuilder<EventMatch> */
    public function builder(): EventMatchBuilder
    {
        $tagTeamId = $this->requireContextId($this->tagTeamId ?? null, 'tag team');

        return EventMatch::query()
            ->forHistory()
            ->forTagTeamId($tagTeamId);
    }

    protected function configure(): void
    {
        $tagTeamId = $this->requireContextId($this->tagTeamId ?? null, 'tag team');

        Gate::authorize('view', TagTeam::query()->findOrFail($tagTeamId));
    }
}
