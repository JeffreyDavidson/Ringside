<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Models\EventMatch;
use App\Models\Wrestler;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class AddWrestlersToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Add titles to an event match.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Wrestler>  $wrestlers
     */
    public function handle(EventMatch $eventMatch, Collection $wrestlers, int $sideNumber): void
    {
        $wrestlers->each(
            fn (Wrestler $wrestler) => $this->eventMatchRepository->addWrestlerToMatch(
                $eventMatch,
                $wrestler,
                $sideNumber
            )
        );
    }
}
