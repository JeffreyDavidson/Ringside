<?php

namespace App\Listeners\Wrestlers;

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerInjured;
use Illuminate\Events\Dispatcher;

class InjuredWrestlerSubscriber
{
    /**
     * Handle tag team wrestler injured events.
     */
    public function handleTagTeamWrestlerInjured(WrestlerInjured $event): void
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
            WrestlerInjured::class,
            [InjuredWrestlerSubscriber::class, 'handleTagTeamWrestlerInjured']
        );
    }
}
