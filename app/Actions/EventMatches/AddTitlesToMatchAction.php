<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Models\EventMatch;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTitlesToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Add titles to an event match.
     *
     * @param  Collection<int, \App\Models\Title>  $titles
     */
    public function handle(EventMatch $eventMatch, Collection $titles): void
    {
        $titles->each(
            fn (Title $title) => $this->eventMatchRepository->addTitleToMatch($eventMatch, $title)
        );
    }
}
