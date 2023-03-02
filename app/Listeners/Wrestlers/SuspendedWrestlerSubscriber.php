<?php

namespace App\Listeners\Wrestlers;

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerSuspended;
use Illuminate\Events\Dispatcher;

class SuspendedWrestlerSubscriber
{
    /**
     * Handle tag team wrestler suspended events.
     */
    public function handleTagTeamWrestlerSuspended(WrestlerSuspended $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam->update(['status' => TagTeamStatus::UNBOOKABLE]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            WrestlerSuspended::class,
            [SuspendedWrestlerSubscriber::class, 'handleTagTeamWrestlerSuspended']
        );
    }
}
