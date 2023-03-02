<?php

namespace App\Listeners\Wrestlers;

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerReleased;
use App\Repositories\WrestlerRepository;
use Illuminate\Events\Dispatcher;

class ReleasedWrestlerSubscriber
{
    /**
     * Handle tag team wrestler released events.
     */
    public function handleTagTeamWrestlerReleased(WrestlerReleased $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam->update(['status' => TagTeamStatus::UNBOOKABLE]);
            app(WrestlerRepository::class)->removeFromCurrentTagTeam($event->wrestler, $event->releaseDate);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            WrestlerReleased::class,
            [ReleasedWrestlerSubscriber::class, 'handleTagTeamWrestlerReleased']
        );
    }
}
