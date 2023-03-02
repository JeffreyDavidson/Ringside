<?php

namespace App\Listeners\Wrestlers;

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerReinstated;
use Illuminate\Events\Dispatcher;

class ReinstatedWrestlerSubscriber
{
    /**
     * Handle tag team wrestler reinstated events.
     */
    public function handleTagTeamWrestlerReinstated(WrestlerReinstated $event): void
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
            WrestlerReinstated::class,
            [ReinstatedWrestlerSubscriber::class, 'handleTagTeamWrestlerReinstated']
        );
    }
}
