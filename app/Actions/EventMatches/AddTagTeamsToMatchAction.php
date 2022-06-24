<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Models\EventMatch;
use App\Models\TagTeam;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTagTeamsToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Add titles to an event match.
     *
     * @param \App\Models\EventMatch $eventMatch
     * @param \Illuminate\Support\Collection<TagTeam> $tagTeams
     * @param int $sideNumber
     * @return void
     */
    public function handle(EventMatch $eventMatch, Collection $tagTeams, int $sideNumber): void
    {
        $tagTeams->each(
            fn (TagTeam $tagTeam) => $this->eventMatchRepository->addTagTeamToMatch(
                $eventMatch,
                $tagTeam,
                $sideNumber
            )
        );
    }
}
