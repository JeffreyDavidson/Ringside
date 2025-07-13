<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class AddRefereesToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Add referees to an event match.
     *
     * @param  Collection<int, Referee>  $referees
     */
    public function handle(EventMatch $eventMatch, Collection $referees): void
    {
        $referees->each(
            fn (Referee $referee) => $this->eventMatchRepository->addRefereeToMatch($eventMatch, $referee)
        );
    }
}
