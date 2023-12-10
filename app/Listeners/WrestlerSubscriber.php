<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\TagTeamStatus;
use App\Events\Wrestlers\WrestlerClearedFromInjury;
use App\Events\Wrestlers\WrestlerInjured;
use App\Events\Wrestlers\WrestlerReinstated;
use App\Events\Wrestlers\WrestlerReleased;
use App\Events\Wrestlers\WrestlerRetired;
use App\Events\Wrestlers\WrestlerSuspended;
use App\Repositories\WrestlerRepository;
use Illuminate\Events\Dispatcher;

class WrestlerSubscriber
{
    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            WrestlerClearedFromInjury::class,
            [$this::class, 'handleTagTeamWrestlerClearedFromInjury']
        );

        $events->listen(
            WrestlerInjured::class,
            [$this::class, 'handleTagTeamWrestlerInjured']
        );

        $events->listen(
            WrestlerReinstated::class,
            [$this::class, 'handleTagTeamWrestlerReinstated']
        );

        $events->listen(
            WrestlerReleased::class,
            [$this::class, 'handleTagTeamWrestlerReleased']
        );

        $events->listen(
            WrestlerRetired::class,
            [$this::class, 'handleTagTeamWrestlerRetired']
        );

        $events->listen(
            WrestlerSuspended::class,
            [$this::class, 'handleTagTeamWrestlerSuspended']
        );
    }

    /**
     * Handle tag team wrestler clearedFromInjury events.
     */
    public function handleTagTeamWrestlerClearedFromInjury(WrestlerClearedFromInjury $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam?->update(['status' => TagTeamStatus::Bookable]);
        }
    }

    /**
     * Handle tag team wrestler injured events.
     */
    public function handleTagTeamWrestlerInjured(WrestlerInjured $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam?->update(['status' => TagTeamStatus::Unbookable]);
        }
    }

    /**
     * Handle tag team wrestler reinstated events.
     */
    public function handleTagTeamWrestlerReinstated(WrestlerReinstated $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam?->update(['status' => TagTeamStatus::Bookable]);
        }
    }

    /**
     * Handle tag team wrestler released events.
     */
    public function handleTagTeamWrestlerReleased(WrestlerReleased $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam?->update(['status' => TagTeamStatus::Unbookable]);

            app(WrestlerRepository::class)->removeFromCurrentTagTeam($event->wrestler, $event->releaseDate);
        }
    }

    /**
     * Handle tag team wrestler retired events.
     */
    public function handleTagTeamWrestlerRetired(WrestlerRetired $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam?->update(['status' => TagTeamStatus::Unbookable]);

            app(WrestlerRepository::class)->removeFromCurrentTagTeam($event->wrestler, $event->retirementDate);
        }
    }

    /**
     * Handle tag team wrestler suspended events.
     */
    public function handleTagTeamWrestlerSuspended(WrestlerSuspended $event): void
    {
        if ($event->wrestler->isAMemberOfCurrentTagTeam()) {
            $event->wrestler->currentTagTeam?->update(['status' => TagTeamStatus::Unbookable]);
        }
    }
}
