<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Models\EventMatch;
use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTitlesToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Add titles to an event match.
     *
     * @param  \App\Models\EventMatch  $eventMatch
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Title>  $titles
     * @return void
     */
    public function handle(EventMatch $eventMatch, $titles): void
    {
        $titles->map(
            fn (Title $title) => $this->eventMatchRepository->addTitleToMatch($eventMatch, $title)
        );
    }
}
