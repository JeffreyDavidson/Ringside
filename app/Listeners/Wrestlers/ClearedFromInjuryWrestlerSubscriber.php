<?php

namespace App\Listeners\Wrestlers;

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerClearedFromInjury;
use Illuminate\Events\Dispatcher;

class ClearedFromInjuryWrestlerSubscriber
{
    /**
     * Handle tag team wrestler clearedFromInjury events.
     */
    public function handleTagTeamWrestlerClearedFromInjury(WrestlerClearedFromInjury $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam->update(['status' => TagTeamStatus::BOOKABLE]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            WrestlerClearedFromInjury::class,
            [ClearedFromInjuryWrestlerSubscriber::class, 'handleTagTeamWrestlerClearedFromInjury']
        );
    }
}
