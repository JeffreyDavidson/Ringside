<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Managers\RemoveFromCurrentTagTeamsAction;
use App\Actions\Managers\RemoveFromCurrentWrestlersAction;
use App\Events\Managers\ManagerReleased;
use App\Events\Managers\ManagerRetired;
use Illuminate\Events\Dispatcher;

class ManagerSubscriber
{
    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            ManagerReleased::class,
            [$this::class, 'handleManagerReleased']
        );

        $events->listen(
            ManagerRetired::class,
            [$this::class, 'handleManagerRetired']
        );
    }

    /**
     * Handle manager clear injury event.
     */
    public function handleManagerReleased(ManagerReleased $event): void
    {
        $event->manager->currentTagTeams
            ->whenNotEmpty(fn () => app(RemoveFromCurrentTagTeamsAction::class)->handle($event->manager));

        $event->manager->currentWrestlers
            ->whenNotEmpty(fn () => app(RemoveFromCurrentWrestlersAction::class)->handle($event->manager));
    }

    /**
     * Handle the manager retired event.
     */
    public function handleManagerRetired(ManagerRetired $event): void
    {
        $event->manager->currentTagTeams
            ->whenNotEmpty(fn () => app(RemoveFromCurrentTagTeamsAction::class)->handle($event->manager));

        $event->manager->currentWrestlers
            ->whenNotEmpty(fn () => app(RemoveFromCurrentWrestlersAction::class)->handle($event->manager));
    }
}
