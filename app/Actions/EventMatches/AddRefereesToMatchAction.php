<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Models\EventMatch;
use App\Models\Referee;
use Lorisleiva\Actions\Concerns\AsAction;

class AddRefereesToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Add referees to an event match.
     *
     * @param \App\Models\EventMatch $eventMatch
     * @param \Illuminate\Database\Eloquent\Collection<Referee> $referees
     * @return void
     */
    public function handle(EventMatch $eventMatch, $referees): void
    {
        $referees->map(
            fn (Referee $referee) => $this->eventMatchRepository->addRefereeToMatch($eventMatch, $referee)
        );
    }
}
