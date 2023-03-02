<?php

namespace App\Listeners\Wrestlers;

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerRetired;
use App\Repositories\WrestlerRepository;
use Illuminate\Events\Dispatcher;

class RetiredWrestlerSubscriber
{
    /**
     * Handle tag team wrestler retired events.
     */
    public function handleTagTeamWrestlerRetired(WrestlerRetired $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam->update(['status' => TagTeamStatus::UNBOOKABLE]);
            app(WrestlerRepository::class)->removeFromCurrentTagTeam($event->wrestler, $event->retirementDate);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            WrestlerRetired::class,
            [RetiredWrestlerSubscriber::class, 'handleTagTeamWrestlerRetired']
        );
    }
}
