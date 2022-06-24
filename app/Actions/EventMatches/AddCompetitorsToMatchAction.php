<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Models\EventMatch;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class AddCompetitorsToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Add competitors to an event match.
     *
     * @param \App\Models\EventMatch $eventMatch
     * @param \Illuminate\Support\Collection $competitors
     * @return void
     */
    public function handle(EventMatch $eventMatch, Collection $competitors): void
    {
        $competitors->each(function ($sideCompetitors, $sideNumber) use ($eventMatch) {
            if (Arr::exists($sideCompetitors, 'wrestlers')) {
                AddWrestlersToMatchAction::run($eventMatch, Arr::get($sideCompetitors, 'wrestlers'), $sideNumber);
            }

            if (Arr::exists($sideCompetitors, 'tag_teams')) {
                AddTagTeamsToMatchAction::run($eventMatch, Arr::get($sideCompetitors, 'tag_teams'), $sideNumber);
            }
        });
    }
}
